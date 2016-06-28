<?php
/**
 * Génère les formats détachés et le site statique basique sur Œuvres
 */
// cli usage
Oeuvres::deps();
set_time_limit(-1);


if (realpath($_SERVER['SCRIPT_FILENAME']) != realpath(__FILE__)) {
  // file is include do nothing
}
else if (php_sapi_name() == "cli") {
  Oeuvres::cli();
}
class Oeuvres
{
  static $sets = array(
    "dumas" => array(
      "glob" => '../dumas/*.xml',
      "publisher" => 'Œuvres',
      "source" => "http://oeuvres.github.io/dumas/%s.xml",
    ),
    "flaubert" => array(
      "glob" => '../flaubert/*.xml',
      "publisher" => 'Œuvres',
      "source" => "http://oeuvres.github.io/flaubert/%s.xml",
    ),
    "stendhal" => array(
      "glob" => '../stendhal/*.xml',
      "publisher" => 'Œuvres',
      "source" => "http://oeuvres.github.io/stendhal/%s.xml",
    ),
        /*
    "verne" => array(
      "glob" => '../verne/*.xml',
      "publisher" => 'Lille III',
      // "identifier" => "http://obvil.paris-sorbonne.fr/corpus/moliere/%s",
      "source" => "http://oeuvres.github.io/verne/%s.xml",
    ),
    */
    "textes" => array(
      "glob" => '../textes/*.xml',
      "publisher" => 'Œuvres',
      // "identifier" => "http://obvil.paris-sorbonne.fr/corpus/moliere/%s",
      "source" => "http://oeuvres.github.io/textes/%s.xml",
    ),
    "zola" => array(
      "glob" => '../zola/*.xml',
      "publisher" => 'Œuvres',
      "source" => "http://oeuvres.github.io/zola/%s.xml",
    ),
  );
  static $formats = array(
    'epub' => '.epub',
    'kindle' => '.mobi',
    'markdown' => '.txt',
    'iramuteq' => '.txt',
    'html' => '.html',
    'article' => '.html',
    // 'docx' => '.docx',
  );
  /** petite base sqlite pour conserver la mémoire des doublons etc */
  static $create = "
PRAGMA encoding = 'UTF-8';
PRAGMA page_size = 8192;

CREATE TABLE oeuvre (
  -- un texte
  id         INTEGER, -- rowid auto
  code       TEXT,    -- nom de fichier sans extension
  filemtime  INTEGER, -- date de dernière modification du fichier pour update
  publisher  TEXT,    -- nom de l’institution qui publie
  identifier TEXT,    -- uri chez le publisher
  source     TEXT,    -- XML TEI source URL
  author     TEXT,    -- auteur
  title      TEXT,    -- titre
  year       INTEGER, -- année de publication
  PRIMARY KEY(id ASC)
);
CREATE UNIQUE INDEX oeuvre_code ON oeuvre(code);
CREATE INDEX oeuvre_author_year ON oeuvre(author, year, title);
CREATE INDEX oeuvre_year_author ON oeuvre(year, author, title);

  ";
  /** Lien à une base SQLite */
  public $pdo;
  /** Requête d’insertion d’une pièce */
  private $_insert;
  /** Test de date d’une pièce */
  private $_sqlmtime;
  /** Pièce XML/TEI en cours de traitement */
  private $_dom;
  /** Processeur xpath */
  private $_xpath;
  /** Processeur xslt */
  private $_xslt;
  /** Vrai si dépendances vérifiées et chargées */
  private static $_deps;
  /** A logger, maybe a stream or a callable, used by self::log() */
  private static $_logger;
  /** Log level */
  public static $debug = true;
  /**
   * Constructeur de la base
   */
  public function __construct($sqlitefile, $logger="php://output") {
    if (is_string($logger)) $logger = fopen($logger, 'w');
    self::$_logger = $logger;
    $this->connect($sqlitefile);
    // create needed folders
    foreach (self::$formats as $format => $extension) {
      if (!file_exists($dir = dirname(__FILE__).'/'.$format)) {
        mkdir($dir, 0775, true);
        @chmod($dir, 0775);  // let @, if www-data is not owner but allowed to write
      }
    }
  }
  /**
   * Produire les exports depuis le fichier XML
   */
  public function add( $srcfile, $setcode=null, $force=false ) {
    $srcname = pathinfo($srcfile, PATHINFO_FILENAME);
    $srcmtime = filemtime($srcfile);
    $this->_sqlmtime->execute(array($srcname));
    list($basemtime) = $this->_sqlmtime->fetch();
    $teinte = null;
    if ($basemtime < $srcmtime) {
      $teinte = new Teinte_Doc($srcfile);
      $this->insert($teinte, $setcode);
    }
    $echo = "";
    foreach (self::$formats as $format => $extension) {
      $destfile = dirname(__FILE__).'/'.$format.'/'.$srcname.$extension;
      if (!$force && file_exists($destfile) && $srcmtime < filemtime($destfile)) continue;
      if (!$teinte) $teinte = new Teinte_Doc($srcfile);
      // delete destfile if exists ?
      if (file_exists($destfile)) unlink($destfile);
      $echo .= " ".$format;
      // TODO git $destfile
      if ($format == 'html') $teinte->html($destfile, 'http://oeuvres.github.io/Teinte/');
      if ($format == 'article') $teinte->article($destfile);
      else if ($format == 'markdown') $teinte->markdown($destfile);
      else if ($format == 'iramuteq') $teinte->iramuteq($destfile);
      else if ($format == 'epub') {
        $livre = new Livrable_Tei2epub($srcfile, self::$_logger);
        $livre->epub($destfile);
        // transformation auto en mobi, toujours après epub
        $mobifile = dirname(__FILE__).'/kindle/'.$srcname.".mobi";
        Livrable_Tei2epub::mobi($destfile, $mobifile);
      }
      else if ($format == 'docx') {
        Toff_Tei2docx::docx($srcfile, $destfile);
      }
    }
    if ($echo) self::log(E_USER_NOTICE, $srcfile.$echo);
  }
  /**
   * Insertion of books
   */
  private function insert( $teinte, $setcode ) {
    // métadonnées de pièces
    $meta = $teinte->meta();
    // supprimer la pièce, des triggers doivent normalement supprimer la cascade.
    $this->pdo->exec("DELETE FROM oeuvre WHERE code = ".$this->pdo->quote( $meta['filename'] ) );
    // les consignes
    if (isset(self::$sets[$setcode]['identifier']))
      $meta['identifier'] = sprintf (self::$sets[$setcode]['identifier'], $meta['filename'] );
    $this->_insert->execute(array(
      $meta['filename'],
      $meta['filemtime'],
      self::$sets[$setcode]['publisher'],
      $meta['identifier'],
      sprintf (self::$sets[$setcode]['source'], $meta['filename'] ),
      $meta['creator'],
      $meta['title'],
      $meta['date'],
    ));
  }

  /**
   * Sortir le catalogue en table html
   */
  public function table() {
    echo '<table class="sortable">
  <thead>
    <tr>
      <th>N°</th>
      <th>Code</th>
      <th>Auteur</th>
      <th>Date</th>
      <th>Titre</th>
      <th>Éditeur</th>
      <th>Téléchargements</th>
    </tr>
  </thead>
    ';
    $i = 1;
    foreach ($this->pdo->query("SELECT * FROM oeuvre ORDER BY author, year") as $oeuvre) {
      echo "\n    <tr>\n";
      echo "      <td>$i</td>\n";
      echo '      <td>'.$oeuvre['code']."</td>\n";
      echo '      <td>'.$oeuvre['author']."</td>\n";
      echo '      <td>'.$oeuvre['year']."</td>\n";
      if ($oeuvre['identifier']) echo '      <td><a href="'.$oeuvre['identifier'].'">'.$oeuvre['title']."</a></td>\n";
      else echo '      <td>'.$oeuvre['title']."</td>\n";

      if ($oeuvre['identifier']) echo '      <td><a href="'.$oeuvre['identifier'].'">'.$oeuvre['publisher']."</a></td>\n";
      else echo '      <td>'.$oeuvre['publisher']."</td>\n";
      echo "      <td>\n";
      if ($oeuvre['source']) echo '<a href="'.$oeuvre['source'].'">TEI</a>';
      $sep = ", ";
      foreach ( self::$formats as $label=>$extension) {
        if ($label == 'article') continue;
        echo $sep.'<a href="'.$label.'/'.$oeuvre['code'].$extension.'">'.$label.'</a>';
      }
      echo "      </td>\n";
      echo "    </tr>\n";
      $i++;
    }
    echo "\n</table>\n";
  }

  /**
   * Connexion à la base
   */
  private function connect($sqlite) {
    $dsn = "sqlite:" . $sqlite;
    // si la base n’existe pas, la créer
    if (!file_exists($sqlite)) {
      if (!file_exists($dir = dirname($sqlite))) {
        mkdir($dir, 0775, true);
        @chmod($dir, 0775);  // let @, if www-data is not owner but allowed to write
      }
      $this->pdo = new PDO($dsn);
      $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
      @chmod($sqlite, 0775);
      $this->pdo->exec(Oeuvres::$create);
    }
    else {
      $this->pdo = new PDO($dsn);
      $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    }
    // table temporaire en mémoire
    $this->pdo->exec("PRAGMA temp_store = 2;");
    $this->_insert = $this->pdo->prepare("
    INSERT INTO oeuvre (code, filemtime, publisher, identifier, source, author, title, year)
                VALUES (?,    ?,         ?,         ?,          ?,      ?,      ?,     ?);
    ");
    $this->_sqlmtime = $this->pdo->prepare("SELECT filemtime FROM oeuvre WHERE code = ?");
  }
  /**
   * Régularise les dépendances
   */
  static function deps() {
    if(self::$_deps) return;
    // Deps
    $inc = dirname(__FILE__).'/../Livrable/Tei2epub.php';
    if (!file_exists($inc)) {
      echo "Impossible de trouver ".realpath(dirname(__FILE__).'/../')."/Livrable/
    Vous pouvez le télécharger sur https://github.com/oeuvres/Livrable\n";
      exit();
    }
    else {
      include_once($inc);
    }

    $inc = dirname(__FILE__).'/../Teinte/Doc.php';
    if (!file_exists($inc)) {
      echo "Impossible de trouver ".realpath(dirname(__FILE__).'/../')."/Teinte/
    Vous pouvez le télécharger sur https://github.com/oeuvres/Teinte\n";
      exit();
    }
    else {
      include_once($inc);
    }
    /*
    $inc = dirname(__FILE__).'/../Toff/Tei2docx.php';
    if (!file_exists($inc)) {
      echo "Impossible de trouver ".realpath(dirname(__FILE__).'/../')."/Toff/
    Vous pouvez le télécharger sur https://github.com/oeuvres/Toff\n";
      exit();
    }
    else {
      include_once($inc);
    }
    */
    self::$_deps=true;
  }
  /**
   * Custom error handler
   * May be used for xsl:message coming from transform()
   * To avoid Apache time limit, php could output some bytes during long transformations
   */
  static function log( $errno, $errstr=null, $errfile=null, $errline=null, $errcontext=null) {
    $errstr=preg_replace("/XSLTProcessor::transform[^:]*:/", "", $errstr, -1, $count);
    if ($count) { // is an XSLT error or an XSLT message, reformat here
      if(strpos($errstr, 'error')!== false) return false;
      else if ($errno == E_WARNING) $errno = E_USER_WARNING;
    }
    // not a user message, let work default handler
    else if ($errno != E_USER_ERROR && $errno != E_USER_WARNING && $errno != E_USER_NOTICE ) return false;
    // a debug message in normal mode, do nothing
    if ($errno == E_USER_NOTICE && !self::$debug) return true;
    if (!self::$_logger);
    else if (is_resource(self::$_logger)) fwrite(self::$_logger, $errstr."\n");
    else if ( is_string(self::$_logger) && function_exists(self::$_logger)) call_user_func(self::$_logger, $errstr);
  }

  static function epubcheck($glob) {
    echo "epubcheck epub/*.epub\n";
    foreach(glob($glob) as $file) {
      echo $file;
      // validation
      $cmd = "java -jar ".dirname(__FILE__)."/epubcheck/epubcheck.jar ".$file;
      $last = exec ($cmd, $output, $status);
      echo ' '.$status."\n";
      if ($status) rename($file, dirname($file).'/_'.basename($file));
    }
  }
  /**
   * Command line API
   */
  static function cli() {
    $timeStart = microtime(true);
    $usage = "\n usage    : php -f ".basename(__FILE__)." base.sqlite set\n";
    array_shift($_SERVER['argv']); // shift first arg, the script filepath
    $sqlite = 'oeuvres.sqlite';
    // pas d’argument, on démarre sur les valeurs par défaut
    if (!count($_SERVER['argv'])) {
      $base = new Oeuvres($sqlite, STDERR);
      foreach(self::$sets as $setcode=>$setrow) {
        $glob = $setrow['glob'];
        foreach(glob($glob) as $file) {
          $base->add($file, $setcode);
        }
      }
      exit();
    }
    if ($_SERVER['argv'][0] == 'epubcheck') {
      Oeuvres::epubcheck('epub/*.epub');
      exit();
    }
    // des arguments, on joue plus fin
    $base = new Oeuvres($sqlite,  STDERR);
    if (!count($_SERVER['argv'])) exit("\n    Quel set insérer ?\n");
    $setcode = array_shift($_SERVER['argv']);
    foreach(glob(self::$sets[$setcode]['glob']) as $file) {
      $base->add($file, $setcode);
    }
  }
}
?>

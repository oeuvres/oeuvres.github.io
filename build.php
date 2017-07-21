<?php
/**
 * Génère les formats détachés et le site statique basique sur Œuvres
 */
 set_time_limit(-1);
include( dirname(dirname(__FILE__))."/Teinte/Build.php" );
$build = new Teinte_Build (
  array(
    "sets" => array(
      "dumas" => array(
        "glob" => '../dumas/*_*.xml',
        "publisher" => 'Œuvres',
        "source" => "http://oeuvres.github.io/dumas/%s.xml",
      ),
      "flaubert" => array(
        "glob" => '../flaubert/*_*.xml',
        "publisher" => 'Œuvres',
        "source" => "http://oeuvres.github.io/flaubert/%s.xml",
      ),
      "hugo" => array(
        "glob" => '../hugo/*_*.xml',
        "publisher" => 'Œuvres',
        "source" => "http://oeuvres.github.io/hugo/%s.xml",
      ),
      "maupassant" => array(
        "glob" => '../maupassant/*_*.xml',
        "publisher" => 'Œuvres',
        "source" => "http://oeuvres.github.io/maupassant/%s.xml",
      ),
      "poesie" => array(
        "glob" => '../poesie/*_*.xml',
        "publisher" => 'Œuvres',
        "source" => "http://oeuvres.github.io/poesie/%s.xml",
      ),
      "stendhal" => array(
        "glob" => '../stendhal/*_*.xml',
        "publisher" => 'Œuvres',
        "source" => "http://oeuvres.github.io/stendhal/%s.xml",
      ),
      "textes" => array(
        "glob" => '../textes/*_*.xml',
        "publisher" => 'Œuvres',
        // "identifier" => "http://obvil.paris-sorbonne.fr/corpus/moliere/%s",
        "source" => "http://oeuvres.github.io/textes/%s.xml",
      ),
      "verlaine" => array(
        "glob" => '../verlaine/*_*.xml',
        "publisher" => 'Œuvres',
        "source" => "http://oeuvres.github.io/verlaine/%s.xml",
      ),
      "verne" => array(
        "glob" => '../verne/*_*.xml',
        "publisher" => 'Lille III',
        "source" => "http://oeuvres.github.io/verne/%s.xml",
      ),
      "zola" => array(
        "glob" => '../zola/*_*.xml',
        "publisher" => 'Œuvres',
        "source" => "http://oeuvres.github.io/zola/%s.xml",
      ),
    ),
    "sqlite" => "oeuvres.sqlite",
    "formats" => "article, epub, kindle, html, iramuteq, markdown",
  )
);
$build->sets( );
?>

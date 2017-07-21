<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <title>Œuvres</title>
    <link rel="stylesheet" type="text/css" href="../Teinte/tei2html.css" />
  </head>
  <body>
      <article id="article">
        <h1><a href="http://dramacode.github.io/">Œuvres</a>, textes littéraires en français, sources libres accès sur GitHub</h1>
        <p>Œuvres est une collection de textes français pour la recherche, convertis en XML/TEI par différents contributeurs crédités dans les fichiers. Cette page est générée automatiquement pour accéder à toute la collection dans différents formats : lecture (epub, mobi), recherche (markdown, iramuteq), sources XML/TEI. Pour d’autres format d’export, demander à <a onclick="this.href='mailto'+'\x3A'+'frederic.glorieux'+'\x40'+'fictif.org'" href="#">Frédéric Glorieux</a>.</p>
        <?php
include( dirname(dirname(__FILE__))."/Teinte/Build.php" );
$base = new Teinte_Build (
  array(
    "sqlite" => "oeuvres.sqlite",
    "formats" => "epub, kindle, html, iramuteq, markdown",
  )
);
$base->table();
        ?>
      </article>
    <script src="../Teinte/Sortable.js">//</script>
  </body>
</html>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <title>Œuvres</title>
    <link rel="stylesheet" type="text/css" href="../Teinte/tei2html.css" />
  </head>
  <body>
      <article id="article">
        <h1><a href="http://dramacode.github.io/">Œuvres</a>, textes français en libre accès sur GitHub</h1>
        <p>Cette page est générée automatiquement pour fournir une liste de liens vers des formats d’export pour la lecture (epub, mobi), mais aussi la recherche (markdown, iramuteq), et surtout les sources XML/TEI.</p>
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

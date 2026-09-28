<?php
require_once __DIR__ . '/ez_options.php';
require_once __DIR__ . '/code/functions.php';
require_once __DIR__ . '/lang/' . $lang . '.php';
require_once __DIR__ . '/smilies/smilies.php';
?>
<html>

<head>
  <title><?= $forumtitle ?></title>
  <LINK href="./skin/style.css" type="text/css" rel="STYLESHEET">
</head>

<body>
  <table class=smiliestable>
    <?php
    $doc = format_html($_GET["doc"]);
    $numsmilies = count($sm_search);
    $spr = 6; //smilies per row
    $j = 1;
    for ($i = 0; $i < $numsmilies; ++$i) {
      if ($j == 1)
        echo '<tr>';
      echo '<td class=smiliecell><a href="javascript:window.opener.paste_string2(' . $doc . ',\'' . $sm_search[$i] . '\')">' . $sm_replace[$i] . '</a>';
      if ($j == $spr)
        echo '</tr>';
      ++$j;
      if ($j == $spr + 1)
        $j = 1;
    }
    while ($j != 1) {
      echo '<td class=smiliecell>&nbsp;</td>';
      ++$j;
      if ($j == $spr + 1)
        $j = 1;
    }
    ?>
  </table><br>
  <center><a style="font-family: arial, verdana; font-size: 12px; color: #36c;" href="#"
      onclick="window.close();"><b><?= $lang['close_window'] ?></b></a></center>
</body>

</html>
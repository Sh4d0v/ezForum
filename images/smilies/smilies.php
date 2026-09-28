<?php

$sm_search = array(
  ':)',
  ':(',
  ':o',
  ':D',
  ';)',
  ':p',
  '8)',
  '[rolleyes]',
  '[confused]',
  '[cool]',
  '[mad]',
  '[unsure]',
  '[up]',
  '[down]'
);
$sm_replace = array(
  '<img src="images/smilies/smile.gif" border="0" align="absmiddle">',
  '<img src="images/smilies/sad.gif" border="0" align="absmiddle">',
  '<img src="images/smilies/ohmy.gif" border="0" align="absmiddle">',
  '<img src="images/smilies/biggrin.gif" border="0" align="absmiddle">',
  '<img src="images/smilies/wink.gif" border="0" align="absmiddle">',
  '<img src="images/smilies/tongue.gif" border="0" align="absmiddle">',
  '<img src="images/smilies/blink.gif" border="0" align="absmiddle">',
  '<img src="images/smilies/rolleyes.gif" border="0" align="absmiddle">',
  '<img src="images/smilies/huh.gif" border="0" align="absmiddle">',
  '<img src="images/smilies/cool.gif" border="0" align="absmiddle">',
  '<img src="images/smilies/mad.gif" border="0" align="absmiddle">',
  '<img src="images/smilies/unsure.gif" border="0" align="absmiddle">',
  '<img src="images/smilies/thumb_up.gif" border="0" align="absmiddle">',
  '<img src="images/smilies/thumb_down.gif" border="0" align="absmiddle">'
);

$barsmilies = 14; //show how many smilies on the smilies bar?

function generate_smilies_bar($doc)
{
  global $sm_search, $sm_replace, $lang, $barsmilies;

  $sarr = [];

  for ($i = 0; $i < $barsmilies; ++$i) {
    array_push($sarr, '<a href="javascript:paste_smilie(', $doc, ',\'', $sm_search[$i], '\')">', $sm_replace[$i], '</a>');
  }

  if ($barsmilies < count($sm_search))
    array_push($sarr, '&nbsp;&nbsp;<a style="font-family: arial, tahoma, verdana; font-size: 11px;" href="javascript:spopup(\'', $doc, '\', 400, 400, 1)">', $lang['all_smilies'], '</a>');
  
  return implode('', $sarr);
}
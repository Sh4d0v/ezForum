<?php
/**
 *----------------------------------------------------------------------
 * ezForum
 *----------------------------------------------------------------------
 * 
 * 
 *----------------------------------------------------------------------
 * Author: Plague Studio <plagues.pl>
 * Contact: contact@plagues.pl
 *----------------------------------------------------------------------
 */
if(!defined('EZFORUM'))
  die($lang['fatal_error']);

require_once __DIR__ . '/smilies/smilies.php';

// validate member id as integer (prevent injection / unexpected values)
if (!isset($m) || !filter_var($m, FILTER_VALIDATE_INT)) {
  die($lang['fatal_error']);
}
$m = (int) $m;
if ($m <= 0) {
  die($lang['fatal_error']);
}

$user_id_to_query = intval($m);
$result = mysqli_query($mysqli,"SELECT user_name, user_regdate, user_bio, user_bio_status, user_email, user_email_public, user_allowviewonline, user_numposts, user_gold, user_lang, user_lasttimereadpost, user_avatar FROM {$dbpref}users WHERE user_id='{$user_id_to_query}'");
if (mysqli_num_rows($result) != 1)
{
  $title = $forumtitle.' &raquo; '.$lang['member_profile'];
  require_once('forumheader.php');
  show_error($lang['no_such_user']);
} else {
  list($member_name, $member_regdate, $member_bio, $member_bio_status, $member_email, $member_email_public, $member_allowviewonline, $member_numposts, $member_gold, $member_lang, $member_lasttimereadpost, $user_avatar) = mysqli_fetch_row($result);
  $title = $forumtitle.' &raquo; '.$lang['member_profile'].' &raquo; '.$member_name;
  require_once __DIR__ . '/forumheader.php';
  
  $user_avatar = htmlspecialchars($user_avatar, ENT_QUOTES, 'UTF-8');
  $user_avatar = '<img src="' . $user_avatar . '" alt="' . $member_name . '" border="0" />';

  if ($member_email_public == 0)
    $member_email = null;
    
  $member_bio = format_html($member_bio);
  
  if (($member_bio_status & 8) != 0)
    $member_bio = str_replace($sm_search, $sm_replace, $member_bio);

  if (($member_bio_status & 2) != 0)
    $member_bio = format_bbcodes($member_bio);
  
  $member_bio = fix_tabs_spaces($member_bio);
  

  $ppd = $member_numposts/(ceil((time()-$member_regdate)/86400));
  $member_numposts = sprintf($lang['posts_per_day'], $member_numposts, $ppd);
  
  $member_regdate = format_datetime($member_regdate, $user_timezone);
  // build safe link: rawurlencode for URL, htmlspecialchars for displayed text
  $safe_forumscript = htmlspecialchars($forumscript, ENT_QUOTES, 'UTF-8');
  $safe_member_name_text = htmlspecialchars($member_name, ENT_QUOTES, 'UTF-8');
  $find_member_topics = '<a href="'.$safe_forumscript.'?a=search&amp;s=&amp;postsby=&amp;postauthor='.rawurlencode($member_name).'">'.sprintf($lang['member_topics'], $safe_member_name_text).'</a>';
  print eval(get_template('memberprofile'));  
}
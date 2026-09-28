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

if (!isset($t) || !is_numeric($t))
  die($lang['fatal_error']);

$result = mysqli_query($mysqli,"SELECT forum_id, topic_title FROM {$dbpref}topics WHERE topic_id='$t'");
if (mysqli_num_rows($result) != 1)
  die($lang['no_such_topic']);

list($f, $topic_title) = mysqli_fetch_row($result);
$errormessage = null;

if (isset($to)) {
  if (!is_numeric($to))
    die($lang['fatal_error']);
  if (!isset($f_lookup_by_id[$to]))
    die($lang['fatal_error']);
  if (forum_visible($user_id, $to) == 0)
    die($lang['access_denied']);
  if ($f == $to)
    $errormessage = get_error($lang['move_same_forum']);
  if ($f_rows[$f_lookup_by_id[$to]][1] == 0)
    $errormessage = get_error($lang['cannot_move_to_cat']);
}

if (($user_id != 1) && (is_user_moderator($f, $user_id) == 0))
  die($lang['access_denied']);

$title = $forumtitle . ' &raquo; ' . $lang['move_topic'];
$forum_path = get_forum_path($f, $lang['move_topic']);
require_once('forumheader.php');

if (!isset($to) || !is_null($errormessage)) {
  $forums_select = '<select class=selectbox name=\'to\'>' . select_forums() . '</select>';
  print eval (get_template('usermovetopic'));
} else {
  mysqli_query($mysqli, "UPDATE {$dbpref}topics SET forum_id='$to', topic_moved=1 WHERE topic_id='$t'");
  fix_forum_stats($f);
  fix_forum_stats($to);
  show_message($lang['move_topic_success']);
}
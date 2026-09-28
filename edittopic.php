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
if (!defined('EZFORUM'))
  die($lang['fatal_error']);

if (!isset($t) || !is_numeric($t))
  die($lang['fatal_error']);

$result = mysqli_query($mysqli, "SELECT forum_id, topic_title FROM {$dbpref}topics WHERE topic_id='$t'");
if (mysqli_num_rows($result) != 1)
  die($lang['no_such_topic']);

list($f, $topic_title) = mysqli_fetch_row($result);
$errormessage = null;

if (($user_id != 1) && (is_user_moderator($f, $user_id) == 0))
  die($lang['access_denied']);

$title = $forumtitle . ' &raquo; ' . $lang['edit_topic'];
$forum_path = get_forum_path($f, $lang['edit_topic']);
require_once __DIR__ . '/forumheader.php';

if (isset($newtitle) && (strlen($newtitle) == 0))
  $errormessage = get_error($lang['title_empty']);

if (!isset($newtitle) || !is_null($errormessage)) {
  print eval (get_template('useredittopic'));
} else {
  mysqli_query($mysqli, "UPDATE {$dbpref}topics SET topic_title='$newtitle' WHERE topic_id='$t'");
  show_message($lang['edit_title_success']);
}
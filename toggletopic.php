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
if (!defined('EZFORUM')) {
  die($lang['fatal_error']);
}

if (!isset($t) || !is_numeric($t))
  die($lang['fatal_error']);

$t_safe = intval($t);
$query = "SELECT forum_id FROM {$dbpref}topics WHERE topic_id='$t_safe'";
$result = mysqli_query($mysqli, $query);

if (!$result || mysqli_num_rows($result) !== 1) {
  die($lang['no_such_topic']);
}

$row = mysqli_fetch_row($result);
$f = intval($row[0]);

if ($user_id != 1 && is_user_moderator($f, $user_id) === 0) {
  die($lang['access_denied']);
}

$title = $forumtitle . ' &raquo; ' . $lang['edit_topic_options'];
$forum_path = get_forum_path($f, $lang['edit_topic_options']);
require_once('forumheader.php');

if (!isset($sticky) && !isset($lock)) {
  die($lang['fatal_error']);
}

if (isset($sticky)) {
  $query = "UPDATE {$dbpref}topics SET topic_sticky = 1 - topic_sticky WHERE topic_id='$t_safe'";
  mysqli_query($mysqli, $query);
  show_message(($sticky == 0) ? $lang['topic_made_sticky'] : $lang['topic_made_unsticky']);
} else {
  $query = "UPDATE {$dbpref}topics SET topic_locked = 1 - topic_locked WHERE topic_id='$t_safe'";
  mysqli_query($mysqli, $query);
  show_message(($lock == 0) ? $lang['topic_locked'] : $lang['topic_unlocked']);
}
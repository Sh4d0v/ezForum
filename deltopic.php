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

if (($user_id != 1) && (is_user_moderator($f, $user_id) == 0))
  die($lang['access_denied']);

$title = $forumtitle . ' &raquo; ' . $lang['del_topic'];
$forum_path = get_forum_path($f, $lang['del_topic']);
require_once('forumheader.php');

if (!isset($confirm)) {
  $message = sprintf($lang['confirm_del_topic'], $topic_title);
  ;
  $confirmed_link = '<a href="' . $forumscript . '?a=deltopic&amp;t=' . $t . '&amp;confirm=1">' . $lang['confirm_action'] . '</a>';
  print eval (get_template('confirm'));
} else {
  //get posters' IDs
  $result = mysqli_query($mysqli, "SELECT DISTINCT post_author_id FROM {$dbpref}posts WHERE topic_id='$t'");
  $members = [];
  while ($row = mysqli_fetch_row($result)) {
    $members[] = $row[0];
  }

  // delete posts
  mysqli_query($mysqli, "DELETE FROM {$dbpref}posts WHERE topic_id='$t'");

  // delete topic
  mysqli_query($mysqli, "DELETE FROM {$dbpref}topics WHERE topic_id='$t'");

  //fix members' stats
  foreach ($members as $m_id) {
    $count_res = mysqli_query($mysqli, "SELECT COUNT(*) FROM {$dbpref}posts WHERE post_author_id='$m_id'");
    $count_row = mysqli_fetch_row($count_res);
    $user_numposts = intval($count_row[0]);

    mysqli_query($mysqli, "UPDATE {$dbpref}users 
                           SET user_numposts='$user_numposts' 
                           WHERE user_id='$m_id'")
      or die(mysqli_error($mysqli));
  }

  //fix forum stats
  fix_forum_stats($f);

  show_message($lang['del_topic_success']);
}
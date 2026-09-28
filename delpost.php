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

if (!isset($p) || !is_numeric($p))
  die($lang['fatal_error']);

$result = mysqli_query($mysqli, "SELECT topic_id, post_time, post_author_id FROM {$dbpref}posts WHERE post_id='$p'");
if (mysqli_num_rows($result) != 1)
  die($lang['no_such_post']);

list($t, $post_time, $post_author_id) = mysqli_fetch_row($result);

$result = mysqli_query($mysqli, "SELECT forum_id FROM {$dbpref}topics WHERE topic_id='$t'");
if (mysqli_num_rows($result) != 1)
  die($lang['fatal_error']);

$row = mysqli_fetch_row($result);
$f = intval($row[0]);

if (($user_id != 1) && (is_user_moderator($f, $user_id) == 0))
  die($lang['access_denied']);

$title = $forumtitle . ' &raquo; ' . $lang['del_post'];
$forum_path = get_forum_path($f, $lang['del_post']);
require_once('forumheader.php');

//trying to delete the first post in a topic?
$result = mysqli_query($mysqli, "SELECT COUNT(*) FROM {$dbpref}posts 
                                 WHERE topic_id='$t' AND post_time < '$post_time'");

$count_row = mysqli_fetch_row($result);
$post_count_before = intval($count_row[0]);

if ($post_count_before === 0)
  show_error_back($lang['del_first_post']);
else {
  if (!isset($confirm)) {
    $message = $lang['confirm_del_post'];
    $confirmed_link = '<a href="' . $forumscript . '?a=delpost&amp;p=' . $p . '&amp;confirm=1">' . $lang['confirm_action'] . '</a>';
    print eval (get_template('confirm'));
  } else {
    //del post
    mysqli_query($mysqli, "DELETE FROM {$dbpref}posts WHERE post_id = '$p'");
    //update topic stats
    $result = mysqli_query($mysqli, "SELECT post_author_id, post_author, post_time FROM {$dbpref}posts WHERE topic_id='$t' ORDER BY post_time DESC LIMIT 1");
    list($a_id, $a_name, $p_time) = mysqli_fetch_row($result);
    mysqli_query($mysqli, "UPDATE {$dbpref}topics SET topic_numreplies=topic_numreplies-1, topic_lastposter_id='$a_id', topic_lastposter_name='$a_name', topic_lastpost_time='$p_time' WHERE topic_id = '$t'");

    //update forum stats
    $result = mysqli_query($mysqli, "SELECT topic_lastpost_time, topic_lastposter_name FROM {$dbpref}topics WHERE forum_id='$f' ORDER BY topic_lastpost_time DESC LIMIT 1");
    list($p_time, $a_name) = mysqli_fetch_row($result);
    mysqli_query($mysqli, "UPDATE {$dbpref}forums SET forum_numreplies=forum_numreplies-1, forum_lastpost_time='$p_time', forum_lastposter='$a_name' WHERE forum_id = '$f'");

    //update author's user_numposts
    mysqli_query($mysqli, "UPDATE {$dbpref}users SET user_numposts=user_numposts-1 WHERE user_id='$post_author_id'");

    show_message($lang['del_post_success']);
  }
}
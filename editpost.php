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

require_once __DIR__ . '/smilies/smilies.php';

if (!isset($p) || !is_numeric($p))
  die($lang['fatal_error']);

$result = mysqli_query($mysqli, "SELECT p.post_text, p.post_text_status, p.post_time, p.topic_id, p.post_author, p.post_author_id, p.post_author_ip, u.user_allowviewonline, u.user_lasttimereadpost, u.user_numposts, u.user_regdate FROM {$dbpref}posts p LEFT JOIN {$dbpref}users u ON p.post_author_id=u.user_id WHERE post_id = '$p'");
if (mysqli_num_rows($result) != 1)
  die($lang['no_such_post']);

if (!isset($_POST["editpost"]))
  list($message, $message_status, $post_time, $t, $author_name, $author_id, $author_ip, $author_allowviewonline, $author_lasttimereadpost, $author_num_posts, $author_reg_date) = mysqli_fetch_row($result);
else
  list(, , $post_time, $t, $author_name, $author_id, $author_ip, $author_allowviewonline, $author_lasttimereadpost, $author_num_posts, $author_reg_date) = mysqli_fetch_row($result);

$result = mysqli_query($mysqli, "SELECT forum_id FROM {$dbpref}topics WHERE topic_id = '$t'");
if ($result === false || mysqli_num_rows($result) !== 1) {
  die($lang['fatal_error']);
}

$row = mysqli_fetch_row($result);
$f = (int) $row[0];


$now = time();

if (($user_id != 1) && (is_user_moderator($f, $user_id) == 0)) {
  if (($user_id == 0) || ($author_id != $user_id) || (($author_id == $user_id) && (($now - $post_time) > $usereditposttimeout)))
    die($lang['access_denied']);
}

$title = $forumtitle . ' &raquo; ' . $lang['edit_post'];
$forum_path = get_forum_path($f, $lang['edit_post']);
require_once('forumheader.php');

$errormessage = null;

if (!isset($_POST["editpost"])) {
  $smiliesbar = generate_smilies_bar('document.PForm.message');
  $bbcodes_check = (($message_status & 1) != 0) ? 'checked' : null;
  $emoticons_check = (($message_status & 4) != 0) ? 'checked' : null;
  $makeurls_check = 'checked';
  $message = format_html($message);

  print eval (get_template('usereditpost'));
} else {
  $message = stripslashes($message);

  if (strlen($message) == 0)
    $errormessage = get_error($lang['message_empty']);

  if (is_null($errormessage) && isset($makeurls) && isset($bbcodes))
    $message = makeURLs($message);

  if (is_null($errormessage))
    $errormessage = validate_text($message, isset($bbcodes));

  if (!is_null($errormessage)) {
    $message = format_html($message);
    $bbcodes_check = isset($bbcodes) ? 'checked' : null;
    $emoticons_check = isset($emoticons) ? 'checked' : null;
    $makeurls_check = isset($makeurls) ? 'checked' : null;
    $smiliesbar = generate_smilies_bar('document.PForm.message');
    print eval (get_template('usereditpost'));
  } else {
    if (isset($preview)) {
      $cell_iterator = 0;
      $pagination = null;

      if (!in_array($f, $allowhtmlforums))
        $message = format_html($message);

      $post_title = $lang['preview_post'];
      $post_message = $message;

      if (isset($emoticons))
        $post_message = str_replace($sm_search, $sm_replace, $post_message);

      if (isset($bbcodes))
        $post_message = format_bbcodes($post_message);
      $post_message = fix_tabs_spaces($post_message);

      if ($author_id == 0) {
        $posted_by = '<span class=memberbutton style="text-decoration: none; cursor: default;">' . $author_name . '&nbsp;</span>';
        $author_num_posts = null;
        $author_reg_date = null;
        $author_online = null;
      } else {
        $posted_by = get_member_link($author_id, $author_name);
        $author_num_posts = $lang['posts'] . ': &nbsp;' . $author_num_posts;
        $author_reg_date = $lang['joined'] . ': ' . format_shortdate($author_reg_date);
        if ($author_allowviewonline == 0)
          $author_online = null;
        else {
          if (($now - $author_lasttimereadpost) < $visittimeout)
            $author_online = '<br>' . $lang['user_online'];
          else
            $author_online = '<br>' . $lang['user_offline'];
        }
      }

      $user_type = get_user_type($author_id, $f);
      $posted_on = $lang['posted'] . ': &nbsp;' . format_datetime($post_time, $user_timezone);
      $user_ip = '&nbsp;&nbsp;' . $lang['ip'] . ': ' . $author_ip;
      $edited_by = '&nbsp;&nbsp;' . $lang['edited_by'] . ': ' . $user_name;
      $post_actions = '<br>';
      $topic_commands = null;

      $posts_html = eval (get_template('mainpostcell'));

      print eval (get_template('mainposttable'));

      $bbcodes_check = isset($bbcodes) ? 'checked' : null;
      $emoticons_check = isset($emoticons) ? 'checked' : null;
      $makeurls_check = isset($makeurls) ? 'checked' : null;
      $smiliesbar = generate_smilies_bar('document.PForm.message');

      print eval (get_template('usereditpost'));

    } else { //save edited post

      $now = time();

      $message_status = 0;

      if (isset($bbcodes)) {
        $message_status |= 1;
        //are there any bbcodes to format?
        if (format_bbcodes($message) != $message)
          $message_status |= 2;
      }
      if (isset($emoticons)) {
        $message_status |= 4;
        //are there any smilies to convert?
        if (str_replace($sm_search, $sm_replace, $message) != $message)
          $message_status |= 8;
      }

      $message = addslashes($message);

      mysqli_query($mysqli, "UPDATE {$dbpref}posts 
                             SET post_text='$message',
                                 post_text_status='$message_status',
                                 post_edited=1,
                                 post_edited_by='$user_name',
                                 post_edited_time='$now'
                             WHERE post_id='$p'");

      $result = mysqli_query($mysqli, "SELECT COUNT(*) FROM {$dbpref}posts WHERE topic_id='$t' AND post_time < '$post_time'");

      if ($result === false) {
        die($lang['fatal_error']);
      }

      $row = mysqli_fetch_row($result);
      $post_num = (int) $row[0] + 1;

      header('Location: ' . get_topic_url($t, ceil($post_num / $postsperpage)));
      exit;
    }
  }
}
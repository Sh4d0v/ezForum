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

require_once __DIR__ . '/images/smilies/smilies.php';

if (!isset($t) || !is_numeric($t))
  die($lang['fatal_error']);

if (!isset($p))
  $p = 1;

if (!is_numeric($p) || ($p < 1))
  die($lang['fatal_error']);

if ($p > 1)
  $enable_delete = 1;

$result = mysqli_query($mysqli, "SELECT topic_title, forum_id, topic_numreplies, topic_sticky, topic_locked, topic_moved FROM {$dbpref}topics WHERE topic_id='$t'");
if (mysqli_num_rows($result) != 1)
  die($lang['fatal_error']);

list($title, $f, $num_replies, $t_sticky, $t_locked, $t_moved) = mysqli_fetch_row($result);

if (!forum_visible($user_id, $f))
  die($lang['access_denied']);

//is the logged user a moderator
$user_mod = is_user_moderator($f, $user_id);

//generate forum_path & validate $f (forum_id)  
$forum_path = get_forum_path($f, $title);

require_once __DIR__ . '/forumheader.php';

$errormessage = null;

$startfrom = ($p - 1) * $postsperpage;
if (($p != 1) && ($num_replies < $startfrom))
  die($lang['fatal_error']);

if (!isset($preview))
  $pagination = get_topic_pages($t, $p, $num_replies + 1);
else
  $pagination = '<br />';

if (isset($_POST["postreply"])) {
  if (!can_user_post_reply($user_id, $f, $t_locked))
    die($lang['access_denied']);

  $message = stripslashes($message);

  //anti-spam protection
  $poster_ip = $_SERVER["REMOTE_ADDR"];
  $spam_time_limit = time() - $antispam;
  $sql = "SELECT COUNT(*) FROM {$dbpref}posts 
        WHERE post_author_ip='$poster_ip' 
        AND post_time > '$spam_time_limit'";

  $result = $mysqli->query($sql);
  $row = $result->fetch_row();
  $count = $row[0];

  if ($count > 0) {
    $errormessage = get_error($lang['anti_spam']);
  }

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
    print eval (get_template('userpostreply'));
  } else {
    if (isset($preview)) {
      $cell_iterator = 0;
      if (!in_array($f, $allowhtmlforums))
        $message = format_html($message);

      $post_title = $lang['preview_reply'] . ': ' . format_html($title);
      $post_message = $message;

      if (isset($emoticons))
        $post_message = str_replace($sm_search, $sm_replace, $post_message);

      if (isset($bbcodes))
        $post_message = format_bbcodes($post_message);
      $post_message = fix_tabs_spaces($post_message);

      if ($user_id == 0) {
        $posted_by = '<span class=memberbutton style="text-decoration: none; cursor: default;">' . $user_name . '</span>';
        $author_num_posts = null;
        $author_reg_date = null;
        $author_gold = null;
        $author_online = null;
      } else {
        $posted_by = get_member_link($user_id, $user_name);
        $author_num_posts = $lang['posts'] . ': ' . ($user_numposts + 1);
        $author_gold = $lang['gold'] . ': ' . $user_gold . '🪙';
        $author_reg_date = $lang['joined'] . ': ' . format_shortdate($user_regdate);
        if ($user_allowviewonline == 0)
          $author_online = null;
        else
          $author_online = '<span class="logged-in" title="' . $lang['user_online'] . '">●</span>';
      }

      $user_type = get_user_type($user_id, $f);
      $posted_on = $lang['posted'] . ': ' . format_datetime(time(), $user_timezone);
      $user_ip = null;
      $edited_by = null;
      $post_actions = null;
      if (isset($user_avatar[0]) && $signaturesandavatars && $user_view_avatars) {
        $author_avatar = '<img src="' . $user_avatar . '" border=0 />';
        $avatar_style = 'padding: 5px 0px 5px 5px;';
      } else {
        $author_avatar = null;
        $avatar_style = null;
      }

      $posts_html = eval (get_template('mainpostcell'));
      $topic_commands = null;

      print eval (get_template('mainposttable'));

      $bbcodes_check = isset($bbcodes) ? 'checked' : null;
      $emoticons_check = isset($emoticons) ? 'checked' : null;
      $makeurls_check = isset($makeurls) ? 'checked' : null;
      $smiliesbar = generate_smilies_bar('document.PForm.message');

      print eval (get_template('userpostreply'));

    } else {
      $now = time();

      //create reply
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

      mysqli_query($mysqli, "INSERT INTO {$dbpref}posts (topic_id, post_author, post_author_id, post_author_ip, post_text, post_text_status, post_time)
      VALUES ('$t','$user_name','$user_id', '$poster_ip', '$message', '$message_status', '$now')");

      //update topic stats
      mysqli_query($mysqli, "UPDATE {$dbpref}topics SET topic_lastposter_id='$user_id', topic_lastposter_name='$user_name', topic_lastpost_time='$now', topic_numreplies=topic_numreplies+1 WHERE topic_id='$t'");
      //update forum stats
      mysqli_query($mysqli, "UPDATE {$dbpref}forums SET forum_numreplies=forum_numreplies+1, forum_lastpost_time='$now', forum_lastposter='$user_name' WHERE forum_id='$f'");
      //update user stats
      if ($user_id != 0)
        mysqli_query($mysqli, "UPDATE {$dbpref}users SET user_numposts=user_numposts+1 WHERE user_id='$user_id'");

      header('Location: ' . get_topic_url($t, ceil(($num_replies + 2) / $postsperpage)));
    }
  }
}

if (!isset($_POST["postreply"]) || isset($preview)) {
  if (!isset($preview)) {
    $now = time();
    mysqli_query($mysqli, "UPDATE {$dbpref}users SET user_lasttimereadpost='$now' WHERE user_id='$user_id'");
    mysqli_query($mysqli, "UPDATE {$dbpref}topics SET topic_numviews=topic_numviews+1 WHERE topic_id='$t'");
  }

  $result = mysqli_query($mysqli, "SELECT p.post_id, p.post_author, p.post_author_id, p.post_author_ip, p.post_text, p.post_text_status, p.post_time, p.post_edited, p.post_edited_by, p.post_edited_time, u.user_numposts, u.user_gold, u.user_regdate, u.user_allowviewonline, u.user_lasttimereadpost, u.user_signature, u.user_signature_status, u.user_avatar FROM {$dbpref}posts p LEFT JOIN {$dbpref}users u ON p.post_author_id=u.user_id WHERE p.topic_id='$t' ORDER BY post_time LIMIT $startfrom,$postsperpage");

  $template_post = get_template('mainpostcell');
  $post_title = format_html($title);
  $posts_html = [];
  $cell_iterator = 0;
  $now = time();
  if ($signaturesandavatars)
    $signature_cache = [];

  while ($row = mysqli_fetch_row($result)) {
    list($p_id, $author_name, $author_id, $author_ip, $post_message, $post_status, $post_time, $post_edited, $post_edited_by, $post_edited_time, $author_num_posts, $author_gold, $author_reg_date, $author_allowviewonline, $author_lasttimereadpost, $author_signature, $author_signature_status, $author_avatar) = $row;

    if ($author_id == 0) {
      $posted_by = '<span class="memberbutton" style="text-decoration: none; cursor: default;"><a href=index.php?a=member&m=' . $author_id . '>' . $author_name . '</a></span>';
      $author_num_posts = null;
      $author_reg_date = null;
      $author_gold = null;
      $author_online = null;
    } else {
      $posted_by = get_member_link($author_id, $author_name);
      $author_num_posts = $lang['posts'] . ': ' . $author_num_posts;
      $author_gold = $lang['gold'] . ': ' . $author_gold . '🪙';
      $author_reg_date = $lang['joined'] . ': ' . format_shortdate($author_reg_date);
      if ($author_allowviewonline == 0)
        $author_online = null;
      else {
        if (($now - $author_lasttimereadpost) < $visittimeout)
          $author_online = '<span class="logged-in" title="' . $lang['user_online'] . '">●</span>';
        else
          $author_online = '<span class="logged-out" title="' . $lang['user_offline'] . '">●</span>';
      }
    }

    $user_type = get_user_type($author_id, $f);
    $posted_on = $lang['posted'] . ': ' . format_datetime($post_time, $user_timezone);

    if (($user_id == 1) || ($user_mod == 1)) {
      $user_ip = '(' . $lang['ip'] . ': ' . get_view_ip_link($author_ip) . ')';
      if (isset($enable_delete))
        $post_actions = '[ <a href="' . $forumscript . '?a=editpost&amp;p=' . $p_id . '">' . $lang['edit'] . '</a> ]' .
          '&nbsp;[ <a href="' . $forumscript . '?a=delpost&amp;p=' . $p_id . '">' . $lang['delete'] . '</a> ]';
      else
        $post_actions = '[ <a href="' . $forumscript . '?a=editpost&amp;p=' . $p_id . '">' . $lang['edit'] . '</a> ]';
    } else {
      $user_ip = null;
      if (($user_id != 0) && ($user_id == $author_id) && (($now - $post_time) < $usereditposttimeout))
        $post_actions = '[ <a href="' . $forumscript . '?a=editpost&amp;p=' . $p_id . '">' . $lang['edit'] . '</a> ]';
      else
        $post_actions = null;
    }

    if ($post_edited == 1)
      $edited_by = '&nbsp;&nbsp;' . $lang['edited_by'] . ': ' . $post_edited_by;
    else
      $edited_by = null;

    if (!in_array($f, $allowhtmlforums))
      $post_message = format_html($post_message);

    if (($post_status & 8) != 0)
      $post_message = str_replace($sm_search, $sm_replace, $post_message);

    //any bbcodes to format?
    if (($post_status & 2) != 0)
      $post_message = format_bbcodes($post_message);

    // take care of the signature
    if ($user_view_signatures && $signaturesandavatars && $author_id != 0 && isset($author_signature[0])) {
      if (!isset($signature_cache[$author_id])) {
        $author_signature = format_html($author_signature);

        if (($author_signature_status & 8) != 0)
          $author_signature = str_replace($sm_search, $sm_replace, $author_signature);

        if (($author_signature_status & 2) != 0)
          $author_signature = format_bbcodes($author_signature);

        $signature_cache[$author_id] = '<br />__________________<div style="padding-top: 5px;">' . $author_signature . '</div>';

      }
      $post_message .= $signature_cache[$author_id];
    }
    //end of signature stuff

    //avatar stuff
    if (isset($author_avatar[0]) && $signaturesandavatars && $user_view_avatars) {
      $author_avatar = '<img src="' . $author_avatar . '" border=0 />';
      $avatar_style = 'padding: 5px 0px 5px 5px;';
    } else {
      $author_avatar = null;
      $avatar_style = null;
    }
    //end of avatar stuff

    $post_message = fix_tabs_spaces($post_message);

    array_push($posts_html, eval ($template_post));

    $cell_iterator = 1 - $cell_iterator;
    $enable_delete = 1;
  }

  $posts_html = implode('', $posts_html);
  if (($user_id == 1) || ($user_mod == 1)) {
    $sticky_link_title = ($t_sticky == 0) ? $lang['make_sticky'] : $lang['make_unsticky'];
    $lock_link_title = ($t_locked == 0) ? $lang['lock'] : $lang['unlock'];
    $topic_commands = '<div class=topiccommands>' . $lang['topic_options'] . ' - [ <a href="' . $forumscript . '?a=edittopic&amp;t=' . $t . '">' . $lang['edit'] . '</a> | <a href="' . $forumscript . '?a=deltopic&t=' . $t . '">' . $lang['delete'] .
      '</a> | <a href="' . $forumscript . '?a=movetopic&amp;t=' . $t . '">' . $lang['move'] . '</a> | <a href="' . $forumscript . '?a=toggletopic&amp;lock=' . $t_locked . '&amp;t=' . $t . '">' . $lock_link_title . '</a> | <a href="' . $forumscript . '?a=toggletopic&amp;sticky=' . $t_sticky . '&amp;t=' . $t . '">' . $sticky_link_title . '</a> ]</div>';
  } else
    $topic_commands = null;
  print eval (get_template('mainposttable'));
}

if (can_user_post_reply($user_id, $f, $t_locked) && !isset($_POST["postreply"])) {
  $message = null;
  $bbcodes_check = 'checked';
  $emoticons_check = 'checked';
  $makeurls_check = 'checked';
  $smiliesbar = generate_smilies_bar('document.PForm.message');

  print eval (get_template('userpostreply'));
}
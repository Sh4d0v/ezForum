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

require_once __DIR__ . '/smilies/smilies.php';

if (!isset($f) || !is_numeric($f))
  die($lang['fatal_error']);

//generate forum_path & validate $f (forum_id)  
if (isset($_POST["createtopic"]))
  $forum_path = get_forum_path($f, $lang['preview_topic']);
else
  $forum_path = get_forum_path($f);

if (!isset($p))
  $p = 1;

if (!is_numeric($p) || ($p < 1))
  die($lang['fatal_error']);

if (!forum_visible($user_id, $f))
  die($lang['access_denied']);

$f_index = $f_lookup_by_id[$f];
$title = $forumtitle . ' &raquo; ' . $f_rows[$f_index][3];
require_once __DIR__ . '/forumheader.php';

$errormessage = null;

//if is forum category or has sub-forums then print them
if (isset($f_lookup_by_parent[$f]) && !isset($_POST["createtopic"])) {
  $forums_html = [];
  $cell_iterator = 0;

  $template_forum = get_template('forumcell');

  $shownforums = 0;

  $f_index = $f_lookup_by_parent[$f];
  while (isset($f_rows[$f_index]) && ($f_rows[$f_index][1] == $f)) {
    list($f_id, , , $f_name, $forum_desc, $num_topics, $num_replies, $lastpost_time, $lastposter) = $f_rows[$f_index];
    if (!forum_visible($user_id, $f_id)) {
      ++$f_index;
      continue;
    }
    $forum_link = get_forum_link($f_id, $f_name, 'forumlink');
    $subforums = '';
    $moderated_by = ($showmoderators == 0) ? null : get_forum_moderators($f_id);
    if ($num_topics == 0)
      $lastpost = $lang['no_posts_yet'];
    else
      $lastpost = $lastposter . '<br /><span class="text-muted">' . format_datetime($lastpost_time, $user_timezone) . '</span>';
    $num_subforums = 0;

    if (isset($f_lookup_by_parent[$f_id])) {
      $subf_index = $f_lookup_by_parent[$f_id];
      $subforums = "<div class=subforums>📂{$lang['sub_forums']}: ";
      while (true) {
        $subf_id = $f_rows[$subf_index][0];
        if (forum_visible($user_id, $subf_id)) {
          $subforums .= get_forum_link($subf_id, $f_rows[$subf_index][3], 'forumlink');
          ++$num_subforums;
        }
        if (isset($f_rows[++$subf_index]) && ($f_rows[$subf_index][1] == $f_id)) {
          if ($num_subforums > 0)
            $subforums .= ',&nbsp;&nbsp';
        } else
          break;
      }
    }

    if ($num_subforums == 0)
      $subforums = null;
    else
      $subforums .= '</div>';
    array_push($forums_html, eval ($template_forum));
    ++$shownforums;
    ++$f_index;
    $cell_iterator = 1 - $cell_iterator;
  }
  $forums_html = implode('', $forums_html);
  if ($shownforums != 0)
    print eval (get_template('mainforumtable'));
}

// show topics
if (($f_rows[$f_lookup_by_id[$f]][1] != 0) && !isset($_POST["createtopic"])) {
  $startfrom = ($p - 1) * $topicsperpage;
  if (($p != 1) && ($f_rows[$f_lookup_by_id[$f]][5] <= $startfrom))
    die($lang['fatal_error']);

  $pagination = get_forum_pages($f, $p, $f_rows[$f_lookup_by_id[$f]][5]);

  if (!in_array($f, $articleforums))
    $result = mysqli_query($mysqli, "SELECT topic_id, topic_title, topic_poster_name, topic_poster_id, topic_lastposter_name, topic_lastposter_id, topic_created_time, topic_lastpost_time, topic_numreplies, topic_numviews, topic_sticky, topic_locked, topic_moved FROM {$dbpref}topics WHERE forum_id='$f' ORDER BY topic_sticky DESC, topic_lastpost_time DESC LIMIT $startfrom,$topicsperpage");
  else
    $result = mysqli_query($mysqli, "SELECT topic_id, topic_title, topic_poster_name, topic_poster_id, topic_lastposter_name, topic_lastposter_id, topic_created_time, topic_lastpost_time, topic_numreplies, topic_numviews, topic_sticky, topic_locked, topic_moved FROM {$dbpref}topics WHERE forum_id='$f' ORDER BY topic_sticky DESC, topic_created_time DESC LIMIT $startfrom,$topicsperpage");
  if (mysqli_num_rows($result) == 0) {
    show_message($lang['no_topics_yet']);
  } else {
    $cell_iterator = 0;
    $topics_html = [];
    $template_topic = get_template('maintopiccell');
    while ($row = mysqli_fetch_row($result)) {
      list($id, $title, $author, $author_id, $lastposter, $lastposter_id, $createdtime, $lastposttime, $num_replies, $num_views, $sticky, $locked, $moved) = $row;
      $topic_link = get_topic_link($id, $title, $num_replies + 1);

      if (($lastposttime > $user_lastsession) && ($lastposter_id != $user_id) && ($user_id != 0))
        if ($createdtime < $user_lastsession) //created before lastsession
          $topic_link = $lang['new'] . ': ' . $topic_link;
        else //created after lastsession
          if ($author_id != $user_id)
            $topic_link = $lang['new'] . ': ' . $topic_link;

      $topic_ind = [];
      if ($sticky == 1)
        array_push($topic_ind, $lang['sticky']);
      if ($moved == 1)
        array_push($topic_ind, $lang['moved']);
      if ($locked == 1)
        array_push($topic_ind, $lang['locked']);
      if (!empty($topic_ind)) {
        $topic_link = implode(', ', $topic_ind) . ': ' . $topic_link;
      }

      $started_by = $lang['started_by'] . ' <a href=index.php?a=member&m=' . $author_id . '>' . $author . '</a>';
      $lastpost = '<a href=index.php?a=member&m=' . $lastposter_id . '>' . $lastposter . '</a><br /><span class="text-muted">' . format_datetime($lastposttime, $user_timezone) . '</span>';
      array_push($topics_html, eval ($template_topic));
      $cell_iterator = 1 - $cell_iterator;
    }
    $topics_html = implode('', $topics_html);
    print eval (get_template('maintopicstable'));
    unset($template_topic);
  }
}

if (isset($_POST["createtopic"])) {

  if (!can_user_create_topic($user_id, $f))
    die($lang['access_denied']);

  $message = stripslashes($message);

  $poster_ip = $_SERVER["REMOTE_ADDR"];
  $spam_time_limit = time() - $antispam;

  $query = "SELECT COUNT(*) FROM {$dbpref}posts 
            WHERE post_author_ip='" . mysqli_real_escape_string($mysqli, $poster_ip) . "' 
            AND post_time > " . intval($spam_time_limit);

  $result = mysqli_query($mysqli, $query);

  if ($result) {
    $row = mysqli_fetch_row($result);
    $post_count = (int) $row[0];

    if ($post_count > 0) {
      $errormessage = get_error($lang['anti_spam']);
    }
  } else {
    show_error(mysqli_error($mysqli));
  }

  if (is_null($errormessage) && (strlen($topictitle) == 0))
    $errormessage = get_error($lang['title_empty']);

  if (is_null($errormessage) && (strlen($message) == 0))
    $errormessage = get_error($lang['message_empty']);

  if (is_null($errormessage) && isset($makeurls) && isset($bbcodes))
    $message = makeURLs($message);

  if (is_null($errormessage))
    $errormessage = validate_text($message, isset($bbcodes));

  if (!is_null($errormessage)) {
    $message = format_html($message);
    $topictitle = format_html(stripslashes($topictitle));
    $bbcodes_check = isset($bbcodes) ? 'checked' : null;
    $emoticons_check = isset($emoticons) ? 'checked' : null;
    $makeurls_check = isset($makeurls) ? 'checked' : null;
    $smiliesbar = generate_smilies_bar('document.PForm.message');
    print eval (get_template('usercreatetopic'));
  } else {
    if (isset($preview)) {
      $cell_iterator = 0;
      $pagination = '<br />';

      if (!in_array($f, $allowhtmlforums))
        $message = format_html($message);
      $topictitle = format_html(stripslashes($topictitle));

      $post_title = $lang['preview_topic'] . ': ' . $topictitle;
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
        $author_online = null;
      } else {
        $posted_by = get_member_link($user_id, $user_name);
        list($author_num_posts, $author_reg_date, $author_allowviewonline, $author_signature, $author_signature_status, $author_avatar) = mysqli_fetch_row(mysqli_query($mysqli, "SELECT user_numposts, user_regdate, user_allowviewonline, user_signature, user_signature_status, user_avatar FROM {$dbpref}users WHERE user_id='$user_id'"));
        $author_num_posts = $lang['posts'] . ': &nbsp;' . ($user_numposts + 1);
        $author_reg_date = $lang['joined'] . ': ' . format_shortdate($user_regdate);
        if ($user_allowviewonline == 0)
          $author_online = null;
        else
          $author_online = '<br />' . $lang['user_online'];
      }

      $user_type = get_user_type($user_id, $f);
      $posted_on = $lang['posted'] . ': &nbsp;' . format_datetime(time(), $user_timezone);
      $user_ip = null;
      $edited_by = null;
      $post_actions = '<br />';

      if ($user_view_signatures && $signaturesandavatars && $user_id != 0 && isset($author_signature[0])) {
        $author_signature = format_html($author_signature);

        if (($author_signature_status & 8) != 0)
          $author_signature = str_replace($sm_search, $sm_replace, $author_signature);

        if (($author_signature_status & 2) != 0)
          $author_signature = format_bbcodes($author_signature);

        $post_message .= '<br />__________________<div style="padding-top: 5px;">' . $author_signature . '</div>';
      }

      if (isset($author_avatar[0]) && $signaturesandavatars && $user_view_avatars) {
        $author_avatar = '<img src="' . $author_avatar . '" border=0 />';
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

      print eval (get_template('usercreatetopic'));

    } else {
      $now = time();
      //create topic
      mysqli_query($mysqli, "INSERT INTO {$dbpref}topics (topic_title, topic_poster_id, topic_poster_name, topic_created_time, topic_lastposter_id, topic_lastposter_name, topic_lastpost_time, forum_id)
      VALUES ('$topictitle', '$user_id', '$user_name', '$now', '$user_id', '$user_name', '$now', '$f')");

      //create post
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
      $topic_id = mysqli_insert_id($mysqli);

      mysqli_query($mysqli, "INSERT INTO {$dbpref}posts (topic_id, post_author, post_author_id, post_author_ip, post_text, post_text_status, post_time)
      VALUES ('$topic_id','$user_name','$user_id', '$poster_ip', '$message', '$message_status', '$now')");

      //update forum stats
      mysqli_query($mysqli, "UPDATE {$dbpref}forums SET forum_numtopics=forum_numtopics+1, forum_lastpost_time='$now', forum_lastposter='$user_name' WHERE forum_id='$f'");
      //update user stats
      if ($user_id != 0)
        mysqli_query($mysqli, "UPDATE {$dbpref}users SET user_numposts=user_numposts+1 WHERE user_id='$user_id'");

      header('Location: ' . get_topic_url($topic_id, 1));
    }
  }
}

if (can_user_create_topic($user_id, $f) && !isset($_POST["createtopic"])) {
  $message = null;
  $bbcodes_check = 'checked';
  $emoticons_check = 'checked';
  $makeurls_check = 'checked';
  $topictitle = null;
  $smiliesbar = generate_smilies_bar('document.PForm.message');

  print eval (get_template('usercreatetopic'));
}
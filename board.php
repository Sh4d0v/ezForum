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

$title = $forumtitle;
require_once('forumheader.php');
if (count($f_rows) == 0) {
  show_message($lang['forums_empty']);
  exit;
}

$forums_html = [];

$c_index = $f_lookup_by_parent[0];
$cell_iterator = 0;

$template_cat = get_template('forumcat');
$template_forum = get_template('forumcell');

while (isset($f_rows[$c_index]) && ($f_rows[$c_index][1] == 0)) {
  $c_id = $f_rows[$c_index][0];
  if (!forum_visible($user_id, $c_id)) {
    ++$c_index;
    continue;
  }
  $forum_link = get_forum_link($c_id, $f_rows[$c_index][3]);
  $forum_desc = (strlen($f_rows[$c_index][4]) != 0) ? ' - ' . $f_rows[$c_index][4] : null;

  array_push($forums_html, eval ($template_cat));
  if (isset($f_lookup_by_parent[$c_id])) {
    $f_index = $f_lookup_by_parent[$c_id];
    while (isset($f_rows[$f_index]) && ($f_rows[$f_index][1] == $c_id)) {
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
        $lastpost = '<a href=index.php?a=member&m=' . $user_id . '>' . $lastposter . '</a><br />' . format_datetime($lastpost_time, $user_timezone);
      $num_subforums = 0;
      if (isset($f_lookup_by_parent[$f_id])) {
        $subf_index = $f_lookup_by_parent[$f_id];
        $subforums = "<div class=subforums>📂{$lang['sub_forums']}: ";
        while (true) {
          $subf_id = $f_rows[$subf_index][0];
          if ($f_visible = forum_visible($user_id, $subf_id)) {
            $subforums .= get_forum_link($subf_id, $f_rows[$subf_index][3], 'forumlink');
            ++$num_subforums;
          }
          if (isset($f_rows[++$subf_index]) && ($f_rows[$subf_index][1] == $f_id)) {
            if ($num_subforums > 0 && $f_visible)
              $subforums .= ', &nbsp';
          } else
            break;
        }
      }
      if ($num_subforums == 0)
        $subforums = null;
      else
        $subforums .= '</div>';
      array_push($forums_html, eval ($template_forum));
      ++$f_index;
      $cell_iterator = 1 - $cell_iterator;
    }
  }
  ++$c_index;
}
$forums_html = implode('', $forums_html);
print eval (get_template('mainforumtable'));
unset($template_cat);
unset($template_forum);
unset($forums_html);

if ($showlastposts == 1) {
  $cell_iterator = 0;
  //exclude private to the current user forums + the ones in $lastpostsexclude
  $userprivateforums = get_invisible_forums($user_id);
  if (!empty($lastpostsexclude))
    $userprivateforums = array_unique(array_merge($userprivateforums, $lastpostsexclude));
  if (count($userprivateforums) == 0)
    $result = mysqli_query($mysqli, "SELECT topic_id, topic_title, topic_poster_name, topic_poster_id, topic_lastposter_name, topic_lastposter_id, topic_created_time, topic_lastpost_time, topic_numreplies, topic_numviews, topic_sticky, topic_locked, topic_moved FROM {$dbpref}topics ORDER BY topic_lastpost_time DESC LIMIT $shownumlastposts");
  else {
    $where = 'WHERE forum_id<>' . implode(' AND forum_id<>', $userprivateforums);
    $result = mysqli_query($mysqli, "SELECT topic_id, topic_title, topic_poster_name, topic_poster_id, topic_lastposter_name, topic_lastposter_id, topic_created_time, topic_lastpost_time, topic_numreplies, topic_numviews, topic_sticky, topic_locked, topic_moved FROM {$dbpref}topics $where ORDER BY topic_lastpost_time DESC LIMIT $shownumlastposts");
  }
  if (mysqli_num_rows($result) != 0) {
    $topics_html = [];
    $template_topic = get_template('mainlastpostscell');
    while ($row = mysqli_fetch_row($result)) {
      list($id, $title, $author, $author_id, $lastposter, $lastposter_id, $createdtime, $lastposttime, $num_replies, $num_views, $sticky, $locked, $moved) = $row;
      $topic_link = get_topic_link($id, $title, $num_replies + 1);

      if (($lastposttime > $user_lastsession) && ($lastposter_id != $user_id) && ($user_id != 0))
        if ($createdtime < $user_lastsession)
          $topic_link = $lang['new'] . ': ' . $topic_link;
        else
          if ($author_id != $user_id)
            $topic_link = $lang['new'] . ': ' . $topic_link;

      $started_by = $lang['started_by'] . ' <a href=index.php?a=member&m=' . $author_id . '>' . $author . '</a>';
      $lastpost = $title . '<br /><a href=index.php?a=member&m=' . $lastposter_id . '>' . $lastposter . '</a><br /><span class="text-muted">' . format_datetime($lastposttime, $user_timezone) . '</span>';
      array_push($topics_html, eval ($template_topic));
      $cell_iterator = 1 - $cell_iterator;
    }
    $topics_html = implode('', $topics_html);
    print eval (get_template('mainlastposts'));
    unset($template_topic);
  }
}

if ($showforumstats == 1) {
  $result = mysqli_query($mysqli, "SELECT COUNT(*) FROM {$dbpref}users");
  $row = mysqli_fetch_row($result);
  $totalusers = intval($row[0]);

  $post_stats = sprintf($lang['post_stats'], $totaltopics, $totalreplies, $totaltopics + $totalreplies);
  $member_stats = sprintf($lang['member_stats'], $totalusers);

  $notonline = time() - intval($visittimeout);
  $result = mysqli_query($mysqli, "SELECT user_id, user_name
                                   FROM {$dbpref}users
                                   WHERE user_lasttimereadpost > '$notonline'
                                   AND user_allowviewonline = 1");

  if (!$result || mysqli_num_rows($result) == 0) {
    $members_online = null;
  } else { 
    $members_online = $lang['members_online'] . ': ';
    $online_users = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $uid  = intval($row['user_id']);
        $uname = htmlspecialchars($row['user_name'], ENT_QUOTES);

        $online_users[] = "<a class=\"badge badge-info\" href=\"index.php?a=member&m={$uid}\">{$uname}</a>";
    }
    $members_online .= implode(', ', $online_users);
  }

  print eval (get_template('forumstats'));
}
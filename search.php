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

$title = $forumtitle . ' &raquo; ' . $lang['search'];
if (isset($s) || isset($searchid))
  $forum_path = get_forum_path(-1, $lang['search_results']);
require_once('forumheader.php');

if (!isset($p))
  $p = 1;

if (!is_numeric($p) || ($p < 1))
  die($lang['fatal_error']);

$errormessage = null;

if ($user_id == 0)
  show_error($lang['guests_cant_search']);
else
  if (isset($s)) {
    if (isset($postsage) && !is_numeric($sdays))
      show_message($lang['no_results']);
    else {
      if (isset($forums)) {
        foreach ($forums as $forum_id)
          if (forum_visible($user_id, $forum_id) == 0)
            die($lang['fatal_error']);
      } else {
        $fc = count($f_rows);
        $forums = [];
        for ($i = 0; $i < $fc; ++$i)
          if (forum_visible($user_id, $f_rows[$i][0]) == 1)
            array_push($forums, $f_rows[$i][0]);
      }

      $where = ' (t.forum_id=' . implode(' OR t.forum_id=', $forums) . ')';
      if (isset($postsby))
        $where .= " AND (p.post_author='$postauthor')";
      if (isset($postsage)) {
        $limit = time() - $sdays * 86400;
        if ($timeselect == 0)
          $where .= ' AND (p.post_time > ' . $limit . ')';
        else
          $where .= ' AND (p.post_time < ' . $limit . ')';
      }

      $result = mysqli_query($mysqli, "SELECT MAX(topic_numviews) FROM {$dbpref}topics");

      if ($result && ($row = mysqli_fetch_row($result))) {
        $max_numviews = (int) $row[0];
      } else {
        $max_numviews = 0;
      }

      if ($max_numviews === 0) {
        $max_numviews = 1;
      }

      if (strlen($s) == 0)
        $result = mysqli_query($mysqli, "SELECT t.topic_id FROM {$dbpref}posts p, {$dbpref}topics t WHERE t.topic_id=p.topic_id AND $where GROUP BY t.topic_id ORDER BY t.topic_lastpost_time DESC LIMIT 1000");
      else
        $result = mysqli_query($mysqli, "SELECT t.topic_id, (t.topic_numviews/$max_numviews*SUM(MATCH(p.post_text) AGAINST ('$s'))) as score FROM {$dbpref}posts p, {$dbpref}topics t WHERE t.topic_id=p.topic_id AND $where AND MATCH(p.post_text) AGAINST ('$s') GROUP BY t.topic_id ORDER BY score DESC LIMIT 1000");
      if (mysqli_num_rows($result) == 0) {
        show_message($lang['no_results']);
      } else {
        $search_results = array();
        while ($row = mysqli_fetch_row($result)) {
          array_push($search_results, $row[0]);
        }
        $search_results = implode(':', $search_results);
        $now = time();
        mysqli_query($mysqli, "INSERT INTO {$dbpref}search (search_user_id, search_time, search_results) VALUES ('$user_id', '$now','$search_results')");
        $searchid = mysqli_insert_id($mysqli);
        header('Location: ' . $forumscript . '?a=search&searchid=' . $searchid);
      }
    }
  } elseif (isset($searchid)) {
    if (!is_numeric($searchid))
      die($lang['fatal_error']);

    $result = mysqli_query($mysqli, "SELECT search_user_id, search_results FROM {$dbpref}search WHERE search_id = '$searchid'");
    if (mysqli_num_rows($result) == 1) {
      $now = time();
      mysqli_query($mysqli, "UPDATE {$dbpref}search SET search_time='$now' WHERE search_id='$searchid'");

      list($search_user_id, $search_results) = mysqli_fetch_row($result);
      if ($search_user_id != $user_id)
        die($lang['access_denied']);

      $search_results = explode(':', $search_results);

      $num_results = count($search_results);

      $startfrom = ($p - 1) * $searchresultsperpage;
      if (($p != 1) && ($num_results <= $startfrom))
        die($lang['fatal_error']);

      $search_results = array_slice($search_results, $startfrom, $searchresultsperpage);

      $results_title = sprintf($lang['search_results_long'], $startfrom + 1, $startfrom + count($search_results), $num_results);

      $where = '(topic_id=' . implode(' OR topic_id=', $search_results) . ')';
      $result = mysqli_query($mysqli, "SELECT topic_id, topic_title, topic_poster_name, topic_lastposter_name, topic_lastpost_time, topic_numreplies, topic_numviews, topic_sticky, topic_locked, topic_moved FROM {$dbpref}topics WHERE $where");

      $pagination = get_search_results_pages($forumscript . '?a=search&searchid=' . $searchid, $p, $num_results);

      $cell_iterator = 0;
      $topics_html = [];
      $template_topic = get_template('searchresultscell');

      while ($row = mysqli_fetch_row($result)) {
        list($id, $title, $author, $lastposter, $lastposttime, $num_replies, $num_views, $sticky, $locked, $moved) = $row;
        $topic_link = get_topic_link($id, $title, $num_replies + 1);

        $started_by = $lang['started_by'] . ' ' . $author;
        $lastpost = $lastposter . '<br />' . format_datetime($lastposttime, $user_timezone);
        $topics_html[array_search($id, $search_results)] = eval ($template_topic);
        $cell_iterator = 1 - $cell_iterator;
      }
      ksort($topics_html);
      $topics_html = implode('', $topics_html);
      print eval (get_template('searchresultstable'));
      unset($template_topic);

    } else
      show_message($lang['search_expired']);
  } else {
    //DELETE ALL EXPIRED SEARCHES
    $timelimit = time() - $searchexpiretime;
    mysqli_query($mysqli, "DELETE FROM {$dbpref}search WHERE search_time < '$timelimit'");
    $errormessage = null;
    $searchforums = '<select multiple=multiple class=selectbox style="width: 50%;" name=\'forums[]\'>' . select_forums(-1) . '</select>';
    print eval (get_template('search'));
  }
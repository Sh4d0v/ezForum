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

/**
 * Build and return the forum data structures used by the application.
 *
 * @return array Associative array containing forum-related structures.
 */
function gen_forum_arrays(): void
{
  global $dbpref, $f_rows, $f_lookup_by_id, $f_lookup_by_parent, $totaltopics, $totalreplies, $mysqli;

  $f_rows = [];
  $f_lookup_by_id = [];
  $f_lookup_by_parent = [];
  $totaltopics = 0;
  $totalreplies = 0;

  $result = mysqli_query(
    $mysqli,
    "SELECT f.*, lt.topic_id, lt.topic_title, lt.topic_numreplies
     FROM {$dbpref}forums f
     LEFT JOIN {$dbpref}topics lt ON lt.topic_id = (
       SELECT t.topic_id
       FROM {$dbpref}topics t
       WHERE t.forum_id = f.forum_id
       ORDER BY t.topic_lastpost_time DESC, t.topic_id DESC
       LIMIT 1
     )
     ORDER BY f.forum_parent, f.forum_order"
  );
  if (!$result) {
    return;
  }

  $num_rows = 0;

  while ($row = mysqli_fetch_row($result)) {
    $f_rows[] = $row;
    $f_lookup_by_id[(int) $row[0]] = $num_rows;

    if (!isset($f_lookup_by_parent[(int) $row[1]])) {
      $f_lookup_by_parent[(int) $row[1]] = $num_rows;
    }

    ++$num_rows;

    $totaltopics += (int) $row[5];
    $totalreplies += (int) $row[6];
  }
}

function array_add_slashes(array &$array): void
{
  foreach ($array as $key => $value) {
    if (is_array($value)) {
      array_add_slashes($array[$key]);
    } else {
      $array[$key] = addslashes((string) $value);
    }
  }
}

/**
 * Convert plain text into HTML-safe formatted output.
 *
 * @param string $text Raw input text to format.
 * @return string HTML-formatted, escaped string safe for output.
 */
function format_html(string $text): string
{
  global $lang;

  $charset = $lang['charset'] ?? 'UTF-8';

  $escaped = htmlspecialchars($text, ENT_QUOTES, $charset);
  $escaped = str_replace('&amp;#', '&#', $escaped);

  return $escaped;
}

function trunc_url_title($url, $maxchars = 70)
{
  if (strlen($url) > $maxchars)
    return substr($url, 0, $maxchars - 5) . ' ... ';
  else
    return $url;
}

function trunc_url($url, $url_title)
{
  global $nofollow;

  if ($nofollow)
    return '<a href="' . $url . '" target="_new" rel="nofollow">' . trunc_url_title($url_title) . '</a>';
  else
    return '<a href="' . $url . '" target="_new">' . trunc_url_title($url_title) . '</a>';
}

/**
 * Convert BBCode-formatted text to safe HTML for display in forum posts.
 *
 * @param string $text Raw user-supplied text containing BBCode.
 * @return string HTML-safe string with BBCode converted to markup, ready for output.
 */
function format_bbcodes(string $text): string
{
  global $lang;

  // [b], [i], [u]
  $search = ['[b]', '[/b]', '[i]', '[/i]', '[u]', '[/u]'];
  $replace = ['<strong>', '</strong>', '<em>', '</em>', '<u>', '</u>'];
  $text = str_ireplace($search, $replace, $text);

  // [img]...[/img]
  $text = preg_replace_callback(
    '#\[img\](https?://[^\s\'"]+)\[/img\]#i',
    function ($m) {
      $url = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
      return '<img src="' . $url . '" alt="' . $url . '">';
    },
    $text
  );

  // [email]...[/email]
  $text = preg_replace_callback(
    '#\[email\](.*?)\[/email\]#i',
    function ($m) {
      $email = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
      return '<a href="mailto:' . $email . '">' . $email . '</a>';
    },
    $text
  );

  // [email=...]...[/email]
  $text = preg_replace_callback(
    '#\[email=(.*?)\](.*?)\[/email\]#i',
    function ($m) {
      $email = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
      $label = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
      return '<a href="mailto:' . $email . '">' . $label . '</a>';
    },
    $text
  );

  // [url=...]...[/url]
  $text = preg_replace_callback(
    '#\[url=(https?://[^\s\'"]+)\](.*?)\[/url\]#i',
    function ($m) {
      return trunc_url($m[1], $m[2]);
    },
    $text
  );

  // [url]...[/url]
  $text = preg_replace_callback(
    '#\[url\](https?://[^\s\'"]+)\[/url\]#i',
    function ($m) {
      return trunc_url($m[1], $m[1]);
    },
    $text
  );

  // [CODE]...[/CODE]
  $text = preg_replace_callback(
    '#\[code\](.*?)\[/code\]#is',
    function ($m) use ($lang) {
      $raw = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
      $code = htmlspecialchars($raw, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
      return '<div class="code"><strong>' . $lang['code'] . ':</strong><br /><br />' . $code . '</div>';
    },
    $text
  );

  // [QUOTE]...[/QUOTE] and [QUOTE=author]...[/QUOTE]
  $text = preg_replace_callback(
    '#\[quote(?:=(.*?))?\](.*?)\[/quote\]#is',
    function ($m) use ($lang) {
      $author = isset($m[1]) ? sprintf($lang['quoting'], htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8')) : $lang['quote'];
      return '<div class="quote"><strong>' . $author . ':</strong><br />' . $m[2] . '</div>';
    },
    $text
  );

  // [KBD]...[/KBD]
  $text = preg_replace_callback(
    '#\[kbd\](.*?)\[/kbd\]#is',
    function ($m) {
      $kbd = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
      return '<kbd>' . $kbd . '</kbd>';
    },
    $text
  );

  // [S]...[/S]
  $text = preg_replace_callback(
    '#\[s\](.*?)\[/s\]#is',
    function ($m) {
      $s = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
      return '<s>' . $s . '</s>';
    },
    $text
  );

  // [SPOILER]...[/SPOILER] and [SPOILER=summary]...[/SPOILER]
  $text = preg_replace_callback(
    '#\[spoiler(?:=(.*?))?\](.*?)\[/spoiler\]#is',
    function ($m) {
      $summary = isset($m[1]) && $m[1] !== '' ? htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') : 'Spoiler';
      return '<details><summary>' . $summary . '</summary>' . $m[2] . '</details>';
    },
    $text
  );

  // [INFO]...[/INFO]
  $text = preg_replace_callback(
    '#\[info\](.*?)\[/info\]#is',
    function ($m) {
      return '<div class="success">' . $m[1] . '</div>';
    },
    $text
  );

  // [WARNING]...[/WARNING]
  $text = preg_replace_callback(
    '#\[warning\](.*?)\[/warning\]#is',
    function ($m) {
      return '<div class="warning">' . $m[1] . '</div>';
    },
    $text
  );

  return $text;
}

/**
 * Convert plain-text URLs in a string to clickable HTML links.
 *
 * @param string $text The input text that may contain plain-text URLs.
 * @return string The input text with detected URLs converted to HTML anchors.
 */
function makeURLs(string $text): string
{
  $text = preg_replace_callback(
    '#(^|\s)(https?|ftp)(://[^\s\[]+)#i',
    function ($matches) {
      return $matches[1] . '[url]' . $matches[2] . $matches[3] . '[/url]';
    },
    $text
  );

  $text = preg_replace_callback(
    '#(^|\s)([a-z0-9._%-]+@[a-z0-9.-]+\.[a-z]{2,})#i',
    function ($matches) {
      return $matches[1] . '[email]' . $matches[2] . '[/email]';
    },
    $text
  );

  return $text;
}

/**
 * Normalize tabs and spaces in a text string.
 *
 * @param string $text The input text to process.
 * @return string The text with tabs and spaces normalized.
 */
function fix_tabs_spaces(string $text): string
{
  $search = ["\n", "\t", '  '];
  $replace = ['<br />', '&nbsp; &nbsp; ', '&nbsp; '];

  return str_replace($search, $replace, $text);
}

/**
 * Determine whether a forum is visible to a given user.
 *
 * @param int|string $user_id  Identifier of the user to check (user ID or guest token).
 * @param int|string $forum_id Identifier of the forum to check.
 * @return bool True if the forum is visible/accessible to the user, false otherwise.
 */
function forum_visible(int $user_id, int $forum_id): int
{
  global $privateforums;

  if (isset($privateforums[$forum_id]) && !in_array($user_id, $privateforums[$forum_id], true) && $user_id !== 1) {
    return 0;
  }

  return 1;
}

/**
 * Get the list of forums that are invisible to a given user.
 *
 * @param int $user_id ID of the user to evaluate permissions for.
 * @return int[] Array of forum IDs that are invisible to the user. Returns an empty array if none.
 */
function get_invisible_forums($user_id)
{
  global $privateforums;
  $result = [];
  if (($user_id == 1) || (count($privateforums) == 0))
    return $result;
  foreach ($privateforums as $forum_id => $allowed_users) {
    if (!in_array($user_id, $allowed_users))
      array_push($result, $forum_id);
  }
  return $result;
}

/**
 * Determine whether a user may create a new topic in a given forum.
 *
 * @param int|null $user_id  ID of the user attempting to create the topic; null for guest.
 * @param int      $forum_id ID of the forum where the topic would be created.
 * @return bool True if the user is permitted to create a topic in the forum; false otherwise.
 */
function can_user_create_topic($user_id, $forum_id)
{
  global $privateforums, $readonlyforums, $postonlyforums, $guestscanpostforums, $f_rows, $f_lookup_by_id;
  if (!isset($f_lookup_by_id[$forum_id]))
    return 0;
  if ($f_rows[$f_lookup_by_id[$forum_id]][1] == 0)
    return 0;
  if ($user_id == 1)
    return 1;
  if (in_array($forum_id, $postonlyforums))
    return 0;
  if (in_array($forum_id, $readonlyforums))
    return 0;
  if (isset($privateforums[$forum_id]) && !in_array($user_id, $privateforums[$forum_id]))
    return 0;
  if ($user_id == 0)
    return in_array($forum_id, $guestscanpostforums) ? 1 : 0;
  return 1;
}

/**
 * Determine whether a user is permitted to post a reply in a forum topic.
 *
 * @param int|null $user_id      ID of the user attempting to post, or null for guests.
 * @param int      $forum_id     ID of the forum containing the topic.
 * @param bool     $is_topic_locked True if the topic is locked.
 * @return bool True if the user may post a reply; false otherwise.
 */
function can_user_post_reply($user_id, $forum_id, $is_topic_locked)
{
  global $privateforums, $readonlyforums, $postonlyforums, $guestscanpostforums, $f_rows, $f_lookup_by_id, $moderators;
  if (!isset($f_lookup_by_id[$forum_id]))
    return 0;
  if ($f_rows[$f_lookup_by_id[$forum_id]][1] == 0)
    return 0;
  if ($user_id == 1)
    return 1;
  if (in_array($forum_id, $readonlyforums))
    return 0;
  if (isset($privateforums[$forum_id]) && !in_array($user_id, $privateforums[$forum_id]))
    return 0;
  if ($is_topic_locked == 1)
    return (($user_id == 1) || (isset($moderators[$forum_id][$user_id]))) ? 1 : 0;
  if ($user_id == 0)
    return in_array($forum_id, $guestscanpostforums) ? 1 : 0;
  return 1;
}

/**
 * Determine whether a user has moderator privileges for a specific forum.
 *
 * @param int|string $forum_id ID of the forum to check.
 * @param int|string $user_id  ID of the user to check.
 * @return bool True if the user is a moderator for the given forum (or a global moderator), false otherwise.
 */
function is_user_moderator($forum_id, $user_id)
{
  global $moderators;
  if ($forum_id == -1) {
    foreach ($moderators as $forum_id => $mods)
      if (isset($mods[$user_id]))
        return 1;
    return 0;
  } else {
    if (isset($moderators[$forum_id][$user_id]))
      return 1;
    else
      return 0;
  }
}

/**
 * Validate and sanitize user-provided text for safe output and optional BBCode processing.
 *
 * @param string $text         The raw text to validate and sanitize.
 * @param bool   $checkbbcodes If true, validate/process BBCode markup; if false, treat BBCode as plain text.
 * @return string The sanitized text safe for output (e.g., HTML-escaped and with line breaks handled).
 */
function validate_text($text, $checkbbcodes = true)
{
  global $lang, $maxwordlength, $maxpostlength;

  if (strlen($text) > $maxpostlength)
    return get_error($lang['text_too_long']);

  // Check for a word that is too long. reg expr could be improved
  if (preg_match('#\b[0-9A-Za-z_]{' . $maxwordlength . ',}\b#s', $text) != 0)
    return get_error($lang['word_too_long']);

  if ($checkbbcodes == false)
    return null;

  $stack = [];
  $tags1 = array('b', '/b', 'i', '/i', 'u', '/u', 'url', '/url', 'email', '/email', 'img', '/img', 'code', '/code', 'quote', '/quote');
  $tags2 = array('url', '/url', 'email', '/email', 'quote', '/quote');
  $ex = explode('[', $text);
  $excount = count($ex);
  if ($excount == 1)
    return null;
  for ($i = 0; $i < $excount; ++$i) {
    $temp_arr = explode(']', $ex[$i]);
    if (count($temp_arr) == 1)
      continue;
    $temp_arr2 = explode('=', $temp_arr[0]);
    $tag = strtolower($temp_arr2[0]);
    // Validate tags without '='
    if ((count($temp_arr2) == 1) && (!in_array($tag, $tags1)))
      continue;
    // Validate tags with '=' (example: [url=http://www.seobb.com]..)
    if ((count($temp_arr2) > 1) && (!in_array($tag, $tags2)))
      continue;
    if ($tag[0] != '/') {
      array_push($stack, $tag);
      continue;
    }
    if (count($stack) == 0)
      return get_error(sprintf($lang['bbcode_error1'], '[' . $tag . ']')); //unmatched closing tag
    if (end($stack) != substr($tag, 1))
      return get_error(sprintf($lang['bbcode_error2'], '[/' . end($stack) . ']', '[' . $tag . ']')); //closing the wrong tag example: [b][i][/b]
    array_pop($stack);
  }
  if (count($stack) != 0)
    return get_error($lang['bbcode_error3']);

  return null;
}

/**
 * Recalculate and update statistics for a forum.
 * 
 * @param int $forum_id The ID of the forum to fix.
 * @return void
 */
function fix_forum_stats($forum_id)
{
  global $dbpref, $mysqli;

  $forum_id = intval($forum_id);

  $result = mysqli_query($mysqli, "SELECT COUNT(*) FROM {$dbpref}topics WHERE forum_id='$forum_id'");
  $row = mysqli_fetch_row($result);
  $topics = intval($row[0]);

  if ($topics != 0) {
    $result = mysqli_query($mysqli, "SELECT SUM(topic_numreplies) FROM {$dbpref}topics WHERE forum_id='$forum_id'");
    $row = mysqli_fetch_row($result);
    $replies = intval($row[0]);
  } else {
    $replies = 0;
  }

  $result = mysqli_query($mysqli, "SELECT topic_lastpost_time, topic_lastposter_name 
                                     FROM {$dbpref}topics 
                                     WHERE forum_id='$forum_id' 
                                     ORDER BY topic_lastpost_time DESC LIMIT 1");
  if ($row = mysqli_fetch_row($result)) {
    list($last_post_time, $last_poster_name) = $row;
  } else {
    $last_post_time = 0;
    $last_poster_name = '';
  }

  mysqli_query($mysqli, "UPDATE {$dbpref}forums 
                           SET forum_numtopics='$topics',
                               forum_numreplies='$replies', 
                               forum_lastpost_time='$last_post_time', 
                               forum_lastposter='$last_poster_name' 
                           WHERE forum_id='$forum_id'");
}

/**
 * Recalculate and repair member statistics.
 *
 * @return int Number of member records that were updated.
 */
function fix_member_stats(): void
{
  global $dbpref, $mysqli;

  $result = mysqli_query($mysqli, "SELECT user_id FROM {$dbpref}users");
  if (!$result) {
    return;
  }

  while ($row = mysqli_fetch_row($result)) {
    $user_id = (int) $row[0];

    $count_result = mysqli_query($mysqli, "SELECT COUNT(*) FROM {$dbpref}posts WHERE post_author_id='$user_id'");

    if ($count_result) {
      $count_row = mysqli_fetch_row($count_result);
      $user_numposts = (int) $count_row[0];

      mysqli_query($mysqli, "UPDATE {$dbpref}users SET user_numposts='$user_numposts' WHERE user_id='$user_id'");
    }
  }
}
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
require_once __DIR__ . '/ez_options.php';
require_once __DIR__ . '/code/functions.php';
require_once __DIR__ . "/lang/{$lang}.php";
require_once __DIR__ . '/code/skinning.php';

$mysqli = @mysqli_connect($dbhost, $dbuser, $dbpass) or die($lang['db_error']);
@mysqli_select_db($mysqli, $dbname) or die($lang['db_error']);

gen_forum_arrays();

//exclude private to the guest user forums + the forums in $feedexcludeforums
$userprivateforums = get_invisible_forums(0);
if (!empty($feedexcludeforums))
  $userprivateforums = array_unique(array_merge($userprivateforums, $feedexcludeforums));


//topic_title, topic_id, topic_lastposter_name,
if (count($userprivateforums) == 0)
  $result = mysqli_query($mysqli, "SELECT forum_id, topic_id, topic_title, topic_poster_name, topic_lastposter_name, topic_created_time, topic_lastpost_time FROM {$dbpref}topics ORDER BY topic_lastpost_time DESC LIMIT $feednumtopics");
else {
  $where = 'WHERE forum_id<>' . implode(' AND forum_id<>', $userprivateforums);
  $result = mysqli_query($mysqli,"SELECT forum_id, topic_id, topic_title, topic_poster_name, topic_lastposter_name, topic_created_time, topic_lastpost_time FROM {$dbpref}topics $where ORDER BY topic_lastpost_time DESC LIMIT $feednumtopics");
}

header('Content-Type: text/xml; charset=' . $lang['charset']);
print '<' . '?xml version="1.0" encoding="' . $lang['charset'] . '"?' . '>';

$updated = false;

?>
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
  <id><?= format_html($forumdir . 'index.atom.php') ?></id>
  <title><?= format_html($forumtitle) ?></title>
  <link rel="self" type="application/atom+xml" href="<?= $forumdir . 'index.atom.php' ?>" />
  <link rel="alternate" type="application/rss+xml" href="<?= $forumdir . 'index.rss.php' ?>" />
  <link rel="alternate" type="text/html" href="<? $forumhome ?>" />
  <subtitle type="xhtml">
    <div xmlns="http://www.w3.org/1999/xhtml"><?= format_html($forumtitle) ?></div>
  </subtitle>
  <?php

  if (mysqli_num_rows($result) != 0) {
    while ($row = mysqli_fetch_row($result)) {
      list($f_id, $id, $title, $author, $lastposter, $createdtime, $lastposttime) = $row;
      list($post_text) = mysqli_fetch_row(mysqli_query($mysqli, "SELECT post_text FROM {$dbpref}posts WHERE topic_id='$id' ORDER BY post_time DESC LIMIT 1"));
      $f_name = $f_rows[$f_lookup_by_id[$f_id]][3];
      if (!$updated) {
        ?>
        <updated>
          <?= date("Y-m-d", $lastposttime) . "T" . preg_replace("/([0-9]{2})([0-9]{2})$/", "$1:$2", gmdate("h:i:s", $lastposttime)) ?>Z
        </updated>
        <?php
        $updated = true;
      }
      ?>
      <entry>
        <id><?= format_html(get_topic_url($id, 1)) ?></id>
        <title type="html"><?= format_html($title) ?></title>
        <link rel="alternate" type="text/html" href="<?= format_html(get_topic_url($id, 1)) ?>" />
        <author>
          <name><?= format_html($author) ?></name>
        </author>
        <updated>
          <?= date("Y-m-d", $lastposttime) . "T" . preg_replace("/([0-9]{2})([0-9]{2})$/", "$1:$2", gmdate("h:i:s", $lastposttime)) ?>Z
        </updated>
        <published>
          <?= date("Y-m-d", $createdtime) . "T" . preg_replace("/([0-9]{2})([0-9]{2})$/", "$1:$2", gmdate("h:i:s", $createdtime)) ?>Z
        </published>
        <category term="<?= format_html($f_name) ?>" />
        <summary><?= format_html(strip_tags($post_text)) ?></summary>
      </entry>
    <?php
    }
  }
  ?>
</feed>
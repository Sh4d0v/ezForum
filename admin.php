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

// Informations about software and hardware
$version = '1.0';
$php_version = phpversion();
$php_extensions = get_loaded_extensions();
$php_extensions = implode(', ', $php_extensions);
$server_os = php_uname();
$server_software = ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown');
$announcement_text = '';

// Show all errors, warnings, and notices
error_reporting(E_ALL);
ini_set('magic_quotes_runtime', 0);

// Include core functions
require_once __DIR__ . '/code/functions.php';

// Escape superglobals to add slashes to all input values
isset($_GET) && array_add_slashes($_GET);
isset($_POST) && array_add_slashes($_POST);
isset($_COOKIE) && array_add_slashes($_COOKIE);

foreach ($_GET as $var => $val) {
  if (is_array($val)) {
    $$var = $val;
  } else {
    $$var = trim($val);
  }
}

foreach ($_POST as $var => $val) {
  if (is_array($val)) {
    $$var = $val;
  } else {
    $$var = trim($val);
  }
}

foreach ($_COOKIE as $var => $val) {
  if (is_array($val)) {
    $$var = $val;
  } else {
    $$var = trim($val);
  }
}

require_once __DIR__ . '/ez_options.php';
require_once __DIR__ . '/code/skinning.php';

$available_languages = array_map(static fn($file) => basename($file, '.php'), glob(__DIR__ . '/lang/*.php') ?: []);
$cookie_language = $_COOKIE['language'] ?? null;
if (is_string($cookie_language) && in_array($cookie_language, $available_languages, true)) {
  $lang = $cookie_language;
}
if (!in_array($lang, $available_languages, true))
  $lang = in_array('eng', $available_languages, true) ? 'eng' : ($available_languages[0] ?? 'eng');
require_once __DIR__ . '/lang/' . $lang . '.php';

header('Content-Type: text/html; charset=' . $lang['charset']);

$mysqli = mysqli_connect($dbhost, $dbuser, $dbpass) or die($lang['db_error']);
@mysqli_select_db($mysqli, $dbname) or die($lang['db_error']);

$mysql_version = mysqli_get_server_info($mysqli);

$user_id = 0; //guest

if (!isset($_COOKIE[$cookiename]))
  die('You must be logged as admin to access the admin panel');

list($user_id, $user_pass_sha1) = @unserialize(stripslashes($_COOKIE[$cookiename]));
$user_id = addslashes($user_id);
$user_pass_sha1 = addslashes($user_pass_sha1);

if ($user_id != 1) {
  die('You must be logged in as admin to access the admin panel');
}

if (!is_numeric($user_id)) {
  die($lang['fatal_error']);
}

$user_id_safe = intval($user_id);
$user_pass_safe = mysqli_real_escape_string($mysqli, $user_pass_sha1);

$query = "SELECT user_name FROM {$dbpref}users WHERE user_id='$user_id_safe' AND user_pass='$user_pass_safe'";
$result = mysqli_query($mysqli, $query);

if (!$result || mysqli_num_rows($result) != 1) {
  die($lang['fatal_error']);
} else {
  $row = mysqli_fetch_assoc($result);
  $user_name = $row['user_name'];
}

$admin_panel_link_template = eval (get_template('adminpanellink'));
$admin_panel_link = str_replace('{username}', htmlspecialchars($user_name), $admin_panel_link_template);

//end of login stuff

$forum_path = null;

if (
  !isset($_GET['a']) ||
  !in_array($_GET['a'], array(
    'addforum',
    'editforum',
    'announcement',
    'gzip',
    'optimize',
    'phpinfo',
    'check_version',
    'delforum',
    'changeorder',
    'recountforums',
    'recountusers',
    'banuser',
    'unbanuser',
    'deluser'
  ))
)
  $action = 'admin';
else
  $action = $_GET['a'];

ob_start();


gen_forum_arrays();
$jumptoforum = '<select class=selectbox name=\'f\'>' . select_forums() . '</select>';

$title = $forumtitle . ' Admin Panel';
$navigation = eval (get_template('mainmembernavigation'));
print eval (get_template('mainheader'));

switch ($action) {
  case 'admin':
    print eval (get_template('adminpanel'));
    break;
  case 'addforum':
    if (!isset($_POST['addforum'])) {
      if (!isset($parent))
        $parent = 0;
      if (!isset($forumdesc))
        $forumdesc = '';
      if (!isset($forumname))
        $forumname = '';
      $parentselect = '<select name=\'parent\'><option value=\'0\'>*' . $lang['create_cat'] . '*</option>' . select_forums() . '</select>';
      print eval (get_template('adminaddforum'));
      break;
    } else {
      if (!isset($parent) || !isset($forumdesc) || !isset($forumname))
        die($lang['fatal_error']);
      if ($forumname == '') {
        show_error($lang['forum_empty']);
        $forumdesc = stripslashes($forumdesc);
        $forumname = stripslashes($forumname);
        $parentselect = '<select name=\'parent\'><option value=\'0\'>*' . $lang['create_cat'] . '*</option>' . select_forums($parent) . '</select>';
        print eval (get_template('adminaddforum'));
        break;
      }

      $position = 0;

      $result = mysqli_query($mysqli, "SELECT MAX(forum_order) FROM {$dbpref}forums WHERE forum_parent = '$parent'");
      if ($result) {
        $row = mysqli_fetch_row($result);
        if ($row && $row[0] !== null) {
          $position = (int) $row[0] + 1;
        }
      }

      // add forum to the database
      mysqli_query($mysqli, "INSERT INTO {$dbpref}forums (forum_parent, forum_order, forum_name, forum_desc) VALUES ('$parent','$position','$forumname','$forumdesc')");

      show_message($lang['forum_added']);
    }
    break;
  case 'editforum':
    if (!isset($_POST['editforum'])) {
      if (!isset($id)) {
        $forum_tree = get_forums_tree("$adminfile?a=editforum&amp;id=");
        if ($forum_tree == null)
          show_message($lang['noforums_indb']);
        else
          print eval (get_template('adminselecteditforum'));
      } else {
        if (!is_numeric($id))
          die($lang['fatal_error']);
        $row = mysqli_fetch_row(mysqli_query($mysqli, "SELECT forum_parent, forum_name, forum_desc FROM {$dbpref}forums WHERE forum_id='$id'"));
        if (!$row)
          show_error($lang['nosuch_forum']);
        else {
          $parentselect = '<select name=\'parent\'><option value=\'0\'>*' . $lang['create_cat'] . '*</option>' . select_forums($row[0]) . '</select>';
          $forumname = format_html($row[1]);
          $forumdesc = format_html($row[2]);
          print eval (get_template('admineditforum'));
        }
      }
    } else {
      if (!isset($id) || !is_numeric($id) || !isset($parent) || !is_numeric($parent) || !isset($forumdesc) || !isset($forumname))
        die($lang['fatal_error']);
      if (strlen($forumname) == 0) {
        show_message($lang['forum_empty']);
        $parentselect = '<select name=\'parent\'><option value=\'0\'>*' . $lang['create_cat'] . '*</option>' . select_forums($parent) . '</select>';
        print eval (get_template('admineditforum'));
      } else if ($parent == $id) {
        show_message($lang['own_parent']);
        $parentselect = '<select name=\'parent\'><option value=\'0\'>*' . $lang['create_cat'] . '*</option>' . select_forums($parent) . '</select>';
        print eval (get_template('admineditforum'));
      } else {
        mysqli_query($mysqli, "UPDATE {$dbpref}forums SET forum_name='$forumname', forum_desc='$forumdesc', forum_parent='$parent' WHERE forum_id='$id'");
        show_message($lang['forum_edited']);
      }
    }
    break;
  case 'optimize':
    $tables = [
      "{$dbpref}forums",
      "{$dbpref}users",
      "{$dbpref}topics",
      "{$dbpref}posts",
      "{$dbpref}search"
    ];

    $query = "OPTIMIZE TABLE " . implode(', ', $tables);

    if (!mysqli_query($mysqli, $query)) {
      show_error("Błąd optymalizacji tabel: " . mysqli_error($mysqli));
    } else {
      show_message($lang['tables_optimized']);
    }
    break;
  case 'announcement':
    if (!isset($forumannouncement))
      $forumannouncement = '';

    if (isset($_POST['announcement'])) {
      $new_announcement = mysqli_real_escape_string($mysqli, $forumannouncement);
      mysqli_query($mysqli, "UPDATE {$dbpref}config SET announcement='$new_announcement'");

      show_message($lang['announcement_updated']);
    } else {
      $announcement_text = null;
      $result = mysqli_query($mysqli, "SELECT announcement FROM {$dbpref}config LIMIT 1");

      if ($result && mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        $raw_announcement = trim($row['announcement']);

        if ($raw_announcement !== '') {
          $announcement_text = $raw_announcement;
        }
      }

      print eval (get_template('admineditannouncement'));
    }
    break;
  case 'gzip':
    if (isset($_POST['save_gzip'])) {
      $gzip_choice = $_POST['gzip_enabled'] ?? null;
      $gzip_enabled = (is_string($gzip_choice) && $gzip_choice === '1') ? 1 : 0;
      $options_file = __DIR__ . '/ez_options.php';
      $options_content = @file_get_contents($options_file);

      if ($options_content === false || !is_writable($options_file)) {
        show_error($lang['gzip_save_error']);
      } else {
        $replacement_count = 0;
        $updated_options = preg_replace_callback(
          '/^\$enablegzip\s*=\s*[01]\s*;[^\r\n]*/m',
          static fn() => '$enablegzip = ' . $gzip_enabled . '; // 0 - no; 1 - yes',
          $options_content,
          1,
          $replacement_count
        );

        if ($updated_options === null || $replacement_count !== 1) {
          show_error($lang['gzip_save_error']);
        } elseif (@file_put_contents($options_file, $updated_options, LOCK_EX) === false) {
          show_error($lang['gzip_save_error']);
        } else {
          if (function_exists('opcache_invalidate'))
            @opcache_invalidate($options_file, true);
          $enablegzip = $gzip_enabled;
          show_message($lang['gzip_saved']);
        }
      }
    } else {
      $gzip_enabled = ((int) $enablegzip === 1) ? '1' : '0';
      $gzip_on_selected = ($gzip_enabled === '1') ? 'selected' : '';
      $gzip_off_selected = ($gzip_enabled === '0') ? 'selected' : '';
      print eval (get_template('admingzip'));
    }
    break;
  case 'phpinfo':
    show_message(phpinfo());
    break;
  case 'check_version':
    $current_version = $version;
    $version_url = 'https://ezforum.plagues.pl/version.php?current_version=' . urlencode($current_version);

    if (function_exists('curl_version')) {
      $ch = curl_init($version_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, 5);
      $json_data = curl_exec($ch);
      curl_close($ch);
    } else {
      $json_data = @file_get_contents($version_url);
    }

    if (!$json_data) {
      show_message('Could not check for updates. Please try again later.');
      break;
    }

    $version_info = json_decode($json_data, true);
    if (!$version_info || !isset($version_info['up_to_date'])) {
      show_message($lang['invalid_response']);
      break;
    }

    if ($version_info['up_to_date']) {
      show_message("{$lang['up_to_date']} ({$lang['admin_forum_version']}: {$version_info['current_version']}).");
    } else {
      $link = $version_info['update_link'] ?? '#';
      show_message(
        "{$lang['new_verson']} {$version_info['latest_version']} {$lang['is_available']} " .
        "<a href='{$link}' target='_blank'>{$lang['download_now']}</a>."
      );
    }
    break;
  case 'delforum':
    if (!isset($id)) {
      $forum_tree = get_forums_tree("$adminfile?a=delforum&amp;id=");
      if ($forum_tree == null)
        show_message($lang['noforums_indb']);
      else
        print eval (get_template('adminselectdelforum'));
    } else {
      if (!is_numeric($id) || !isset($f_lookup_by_id[$id])) {
        show_error($lang['nosuch_forum']);
      } else
        if (isset($f_lookup_by_parent[$id])) {
          show_error($lang['cant_del_forum']);
          $forum_tree = get_forums_tree("$adminfile?a=delforum&amp;id=");
          print eval (get_template('adminselectdelforum'));
        } else {
          if (!isset($confirm)) {
            $forum_title = $f_rows[$f_lookup_by_id[$id]][3];
            $message = sprintf($lang['confirm_del_forum'], $forum_title);
            ;
            $confirmed_link = '<a href="' . $adminfile . '?a=delforum&amp;id=' . $id . '&amp;confirm=1">' . $lang['confirm_action'] . '</a>';
            print eval (get_template('confirm'));
          } else {
            $result = mysqli_query($mysqli, "SELECT topic_id FROM {$dbpref}topics WHERE forum_id='$id'");
            while ($row = mysqli_fetch_row($result)) {
              mysqli_query($mysqli, "DELETE FROM {$dbpref}posts WHERE topic_id='{$row[0]}'");
              mysqli_query($mysqli, "DELETE FROM {$dbpref}topics WHERE topic_id='{$row[0]}'");
            }
            mysqli_query($mysqli, "DELETE FROM {$dbpref}forums WHERE forum_id='$id'");
            fix_member_stats();
            show_message($lang['forum_deleted']);
          }
        }
    }
    break;
  case 'changeorder':
    if (!isset($_POST['changeorder'])) {
      $forums_order = get_forums_order(0);

      print eval (get_template('adminforumorder'));
    } else {
      $fc = count($f_rows);

      for ($i = 0; $i < $fc; ++$i) {
        $forum_id = (int) $f_rows[$i][0];
        $order_value = $_POST['o' . $forum_id] ?? null;

        if ($order_value !== null && is_numeric($order_value)) {
          $order_value_safe = (int) $order_value;
          $query = "UPDATE {$dbpref}forums SET forum_order='$order_value_safe' WHERE forum_id='$forum_id'";
          if (!mysqli_query($mysqli, $query)) {
            show_error(mysqli_error($mysqli));
          }
        }
      }

      show_message($lang['forum_orders_changed']);
    }
    break;
  case 'recountforums':
    $fc = count($f_rows);
    for ($i = 0; $i < $fc; ++$i)
      fix_forum_stats($f_rows[$i][0]);
    show_message($lang['forum_stats_recounted']);
    break;
  case 'recountusers':
    fix_member_stats();
    show_message($lang['user_stats_recounted']);
    break;
  case 'banuser':
    if (!isset($_POST['banuser']) || (isset($uie) && (strlen($uie) == 0))) {
      $ban_title = $lang['ban_user'];
      $ban_operation = 'banuser';
      $uie = null;
      $ban_help = '<br>' . $lang['ban_help'];
      print eval (get_template('adminbans'));

      $result = mysqli_query($mysqli, "SELECT ban_data FROM {$dbpref}bans");
      if (mysqli_num_rows($result) != 0) {
        $ban_title = $lang['banned_list'];
        $banned_list = [];
        while ($row = mysqli_fetch_row($result))
          array_push($banned_list, $row[0]);
        $result = mysqli_query($mysqli, "SELECT user_name FROM {$dbpref}users WHERE user_banned=1");
        while ($row = mysqli_fetch_row($result))
          array_push($banned_list, $row[0]);
        $banned_list = implode('<br>', $banned_list);
        print eval (get_template('adminbanlist'));
      }
    } else {
      $result = mysqli_query($mysqli, "UPDATE {$dbpref}users SET user_banned=1 WHERE user_name='$uie'");
      if (mysqli_affected_rows($mysqli) > 0)
        show_message(sprintf($lang['user_banned'], $uie));
      else {
        if (strpos($uie, '@') === FALSE) {
          if (!preg_match("/^[0-9.+]+$/", $uie))
            show_message($lang['invalid_IP']);
          else {
            mysqli_query($mysqli, "REPLACE INTO {$dbpref}bans (ban_data) VALUES ('$uie')");
            show_message(sprintf($lang['IP_banned'], $uie));
          }
        } else {
          if (!preg_match('#^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$#', $uie))
            show_message($lang['invalid_email']);
          else {
            mysqli_query($mysqli, "REPLACE INTO {$dbpref}bans (ban_data) VALUES ('$uie')");
            show_message(sprintf($lang['email_banned'], $uie));
          }
        }
      }
    }
    break;
  case 'unbanuser':
    if (!isset($_POST['unbanuser']) || (isset($uie) && (strlen($uie) == 0))) {
      $ban_title = $lang['unban_user'];
      $ban_operation = 'unbanuser';
      $uie = null;
      $ban_help = null;
      print eval (get_template('adminbans'));

      $result = mysqli_query($mysqli, "SELECT ban_data FROM {$dbpref}bans");
      if (mysqli_num_rows($result) != 0) {
        $ban_title = $lang['banned_list'];
        $banned_list = [];
        while ($row = mysqli_fetch_row($result))
          array_push($banned_list, $row[0]);
        $result = mysqli_query($mysqli, "SELECT user_name FROM {$dbpref}users WHERE user_banned=1");
        while ($row = mysqli_fetch_row($result))
          array_push($banned_list, $row[0]);
        $banned_list = implode('<br>', $banned_list);
        print eval (get_template('adminbanlist'));
      }
    } else {
      $result = mysqli_query($mysqli, "UPDATE {$dbpref}users SET user_banned=0 WHERE user_name='$uie'");
      if (mysqli_affected_rows($mysqli) > 0)
        show_message(sprintf($lang['user_unbanned'], $uie));
      else {
        mysqli_query($mysqli, "DELETE FROM {$dbpref}bans WHERE ban_data='$uie'");
        if (mysqli_affected_rows($mysqli) == 0)
          show_message($lang['unban_none']);
        else
          show_message($lang['unban_success']);
      }
    }
    break;
  case 'deluser':
    if (!isset($_POST['deluser']) && !isset($confirm)) {
      $delusername = null;
      print eval (get_template('admindeluser'));
    } else {
      if (isset($confirm) && isset($uid)) {
        $uid = intval($uid);
        mysqli_query($mysqli, "DELETE FROM {$dbpref}users WHERE user_id='$uid'") or die(mysqli_error($mysqli));
        mysqli_query($mysqli, "UPDATE {$dbpref}topics SET topic_poster_id=0 WHERE topic_poster_id='$uid'") or die(mysqli_error($mysqli));
        mysqli_query($mysqli, "UPDATE {$dbpref}topics SET topic_lastposter_id=0 WHERE topic_lastposter_id='$uid'") or die(mysqli_error($mysqli));
        mysqli_query($mysqli, "UPDATE {$dbpref}posts SET post_author_id=0 WHERE post_author_id='$uid'") or die(mysqli_error($mysqli));
        show_message($lang['user_deleted']);
      } else {
        $delusername_safe = mysqli_real_escape_string($mysqli, $delusername);
        $result = mysqli_query($mysqli, "SELECT user_id FROM {$dbpref}users WHERE user_name='$delusername_safe'");

        if (mysqli_num_rows($result) == 0) {
          show_error_back($lang['no_such_user']);
        } else {
          $row = mysqli_fetch_row($result);
          $uid = intval($row[0]);
          $forum_title = sprintf($lang['confirm_del_user'], $delusername);
          $message = $forum_title;
          $confirmed_link = '<a href="' . $adminfile . '?a=deluser&amp;uid=' . $uid . '&amp;confirm=1">' . $lang['confirm_action'] . '</a>';
          print eval (get_template('confirm'));
        }
      }
    }
    break;
}

print eval (get_template('mainfooter'));

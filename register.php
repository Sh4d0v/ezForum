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

if ($user_id != 0)
  die($lang['fatal_error']);

$title = $forumtitle . ' &raquo; ' . $lang['new_user_reg'];
require_once __DIR__ . '/forumheader.php';

$errormessage = null;

if (isset($_POST['register'])) {
  if ($registermode == 0) {
    $pass = substr(md5(rand()), 0, 6);
    $passagain = $pass;
  }
  $result = mysqli_query($mysqli, "SELECT user_id FROM {$dbpref}users WHERE user_name='$username'");
  if (mysqli_num_rows($result) != 0) {
    $errormessage = get_error($lang['user_exists']);
  } else if (strlen($username) < 3)
    $errormessage = get_error($lang['user_short']);
  else if (strlen($username) > 20)
    $errormessage = get_error($lang['user_long']);
  else if (strlen($pass) < 4)
    $errormessage = get_error($lang['pass_short']);
  else if (strlen($pass) > 30)
    $errormessage = get_error($lang['pass_long']);
  else if ($pass != $passagain)
    $errormessage = get_error($lang['pass_diff']);
  else if (in_array($username, $invalidusernames))
    $errormessage = get_error($lang['disallowed_user']);
  else if (!preg_match('#^[_a-zA-Z0-9 ]{3,30}$#', $username))
    $errormessage = get_error($lang['invalid_username']);
  else if (!preg_match('#^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$#i', $useremail))
    $errormessage = get_error($lang['invalid_email']);
  else {  //check for already registered user with the same email
    $result = mysqli_query($mysqli, "SELECT user_name FROM {$dbpref}users WHERE user_email='$useremail'");
    if ($result && mysqli_num_rows($result) > 0) {
      $row = mysqli_fetch_row($result);
      $existing_name = $row[0]; // user_name

      $errormessage = get_error(sprintf($lang['user_exists_byemail'], $existing_name));
    } else {
      $banResult = mysqli_query($mysqli, "SELECT ban_id FROM {$dbpref}bans WHERE ban_data='$useremail'");
      if ($banResult && mysqli_num_rows($banResult) > 0) {
        $errormessage = get_error($lang['banned']);
      }
    }
  }
  if ($errormessage === null) {
    $now = time();
    mysqli_query($mysqli, "INSERT INTO {$dbpref}users (user_name, user_pass, user_email, user_regdate, user_lasttimereadpost, user_lastsession, user_timezone) 
                          VALUES ('$username', '" . sha1($shaprefix . $pass) . "','$useremail','$now','$now','$now', '$forumtimezone')");

    if ($registermode == 0) {
      require('./code/emails/welcome_email.php');
      require('./code/mailer.php');
      send_email($email_subject, $email_body, $useremail, $adminemail, $adminemail);
      $message = $lang['user_added_pass_sent'];
    } else {
      $message = $lang['user_added'];
    }
    print eval (get_template('message'));
  } else {
    $username = stripslashes($username);
    $useremail = stripslashes($useremail);
    if ($registermode == 0)
      print eval (get_template('mainregisterusernopass'));
    else
      print eval (get_template('mainregisteruser'));
  }
} else {
  $username = '';
  $useremail = '';
  if ($registermode == 0) {
    print eval (get_template('mainregisterusernopass'));
  } else {
    print eval (get_template('mainregisteruser'));
  }
}
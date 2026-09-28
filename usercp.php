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
require('smilies/smilies.php');

if (!defined('EZFORUM')) {
  die($lang['fatal_error']);
}

if ($user_id == 0)
  die($lang['fatal_error']);

$v_signatures_and_avatars = $signaturesandavatars ? 'visible' : 'collapse';

$errormessage = null;
$username = $user_name;
if (isset($_POST['usercp'])) {
  $userbio = stripslashes($userbio);
  $usersignature = stripslashes($usersignature);

  if (!preg_match('#^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$#i', $useremail))
    $errormessage = get_error($lang['invalid_email']);

  if (is_null($errormessage) && (strlen($newpass) != 0)) {
    if (strlen($newpass) < 4)
      $errormessage = get_error($lang['pass_short']);
    else
      if (strlen($newpass) > 30)
        $errormessage = get_error($lang['pass_long']);
      else
        if ($newpass != $newpassconfirm)
          $errormessage = get_error($lang['pass_diff']);
  }

  if (is_null($errormessage) && isset($makeurls) && isset($bbcodes))
    $userbio = makeURLs($userbio);

  if (is_null($errormessage))
    $errormessage = validate_text($userbio, isset($bbcodes));

  if (is_null($errormessage) && isset($sig_makeurls) && isset($sig_bbcodes))
    $usersignature = makeURLs($usersignature);

  if (is_null($errormessage))
    $errormessage = validate_text($usersignature, isset($sig_bbcodes));

  if (is_null($errormessage) && strlen($usersignature) > $maxsignaturesize)
    $errormessage = get_error($lang['signature_too_long']);

  if (is_null($errormessage) && strpos(strtoupper($usersignature), '[IMG]') !== false)
    $errormessage = get_error($lang['no_images_in_signatures']);

  if (is_null($errormessage) && isset($eraseavatar))
    $_FILES['useravatarfile']['error'] = UPLOAD_ERR_NO_FILE;

  if (is_null($errormessage) && ($_FILES['useravatarfile']['error'] != UPLOAD_ERR_OK) && ($_FILES['useravatarfile']['error'] != UPLOAD_ERR_NO_FILE)) {
    $upload_error = $_FILES['useravatarfile']['error'];

    if ($upload_error == UPLOAD_ERR_INI_SIZE || $upload_error == UPLOAD_ERR_FORM_SIZE)
      $errormessage = get_error($lang['avatar_too_big']);
    else
      $errormessage = get_error($lang['cantupload_avatar']);
  }

  if (is_null($errormessage) && ($_FILES['useravatarfile']['error'] == UPLOAD_ERR_OK)) {
    if (!is_uploaded_file($_FILES['useravatarfile']['tmp_name']))
      $errormessage = get_error($lang['fatal_error']);
    else {
      $avatar_extensions = array('gif', 'jpeg', 'jpg', 'png');

      if (!in_array(array_pop(explode('.', $_FILES['useravatarfile']['name'])), $avatar_extensions))
        $errormessage = get_error($lang['avatar_bad_image']);

      if ($avatardirectory[strlen($avatardirectory) - 1] != '/')
        $avatardirectory .= '/';

      if (is_null($errormessage) && !@move_uploaded_file($_FILES['useravatarfile']['tmp_name'], $avatardirectory . $user_id . '.tmp'))
        $errormessage = get_error($lang['cantupload_avatar']);

      if (is_null($errormessage)) {
        $imagearr = @getimagesize($avatardirectory . $user_id . '.tmp');
        if ($imagearr === FALSE || !is_array($imagearr)) {
          @unlink($avatardirectory . $user_id . '.tmp');
          $errormessage = get_error($lang['avatar_bad_image']);
        } else {
          list($avwidth, $avheight, ) = $imagearr;
          if ($avwidth > $maxavatarwidth || $avheight > $maxavatarheight) {
            @unlink($avatardirectory . $user_id . '.tmp');
            $errormessage = get_error($lang['avatar_too_big']);
          } else {
            //delete previous avatar (if there's one)
            foreach ($avatar_extensions as $avext)
              @unlink($avatardirectory . $user_id . '.' . $avext);

            $uploaded_avatar = $avatardirectory . $user_id . '.' . array_pop(explode('.', $_FILES['useravatarfile']['name']));
            @rename($avatardirectory . $user_id . '.tmp', $uploaded_avatar);
            $uploaded_avatar = addslashes($uploaded_avatar);
            mysqli_query($mysqli, "UPDATE {$dbpref}users SET user_avatar='$uploaded_avatar' WHERE user_id='$user_id'");
          }
        }
      }
    }
  }

  if (!is_null($errormessage)) //show error
  {
    $title = $forumtitle . ' &raquo; ' . $lang['user_options'];
    require_once __DIR__ . '/forumheader.php';

    $htmlzones = get_timezone_select($usertimezone);
    $emailpublic_check = isset($emailpublic) ? 'checked' : null;
    $viewonline_check = isset($viewonline) ? 'checked' : null;
    $bbcodes_check = isset($bbcodes) ? 'checked' : null;
    $emoticons_check = isset($emoticons) ? 'checked' : null;
    $makeurls_check = isset($makeurls) ? 'checked' : null;
    $smiliesbar = generate_smilies_bar('document.PForm.userbio');
    $sig_smiliesbar = generate_smilies_bar('document.PForm.usersignature');
    $viewsignatures_check = isset($viewsignatures) ? 'checked' : null;
    $viewavatars_check = isset($viewavatars) ? 'checked' : null;
    $sig_bbcodes_check = isset($sig_bbcodes) ? 'checked' : null;
    $sig_emoticons_check = isset($sig_emoticons) ? 'checked' : null;
    $sig_makeurls_check = isset($sig_makeurls) ? 'checked' : null;

    $user_id_safe = intval($user_id);
    $query = "SELECT user_avatar FROM {$dbpref}users WHERE user_id='$user_id_safe'";
    $result = mysqli_query($mysqli, $query);

    if (!$result || mysqli_num_rows($result) !== 1) {
      die($lang['fatal_error']);
    }

    $row = mysqli_fetch_row($result);
    $user_avatar = $row[0];

    if (strlen($user_avatar) === 0) {
      $current_avatar = null;
    } else {
      $current_avatar = '<img src="' . htmlspecialchars($user_avatar, ENT_QUOTES, 'UTF-8') . '" border="0"><br>'
        . '<input type="checkbox" name="eraseavatar" value="1"> ' . $lang['erase_avatar'] . '.<br>';
    }

    print eval (get_template('userpanel'));
  } else {
    if (!isset($emailpublic))
      $emailpublic = 0;

    if (!isset($viewonline))
      $viewonline = 0;

    if (!isset($viewsignatures))
      $viewsignatures = 0;

    if (!isset($viewavatars))
      $viewavatars = 0;

    $user_bio_status = 0;
    $user_sig_status = 0;

    if (isset($bbcodes)) {
      $user_bio_status |= 1;
      //are there any bbcodes to format?
      if (format_bbcodes($userbio) != $userbio)
        $user_bio_status |= 2;
    }
    if (isset($emoticons)) {
      $user_bio_status |= 4;
      //are there any smilies to convert?
      if (str_replace($sm_search, $sm_replace, $userbio) != $userbio)
        $user_bio_status |= 8;
    }

    if (isset($sig_bbcodes)) {
      $user_sig_status |= 1;
      //are there any bbcodes to format?
      if (format_bbcodes($usersignature) != $usersignature)
        $user_sig_status |= 2;
    }
    if (isset($sig_emoticons)) {
      $user_sig_status |= 4;
      //are there any smilies to convert?
      if (str_replace($sm_search, $sm_replace, $usersignature) != $usersignature)
        $user_sig_status |= 8;
    }

    $userbio = addslashes($userbio);
    $usersignature = addslashes($usersignature);

    if (isset($eraseavatar))
      mysqli_query($mysqli, "UPDATE {$dbpref}users SET user_avatar='' WHERE user_id='$user_id'");

    if (strlen($newpass) != 0) {
      $newpass = sha1($shaprefix . $newpass);
      mysqli_query($mysqli, "UPDATE {$dbpref}users SET user_email='$useremail', user_timezone='$usertimezone', user_email_public='$emailpublic', user_allowviewonline='$viewonline', user_bio='$userbio', user_bio_status='$user_bio_status', user_signature='$usersignature', user_signature_status='$user_sig_status', user_view_signatures='$viewsignatures', user_view_avatars='$viewavatars', user_pass='$newpass' WHERE user_id='$user_id'");
      //set new cookie
      setcookie($cookiename, serialize(array($user_id, $newpass)), 0, $cookiepath, $cookiedomain, $cookiesecure);
    } else
      mysqli_query($mysqli, "UPDATE {$dbpref}users SET user_email='$useremail', user_timezone='$usertimezone', user_email_public='$emailpublic', user_allowviewonline='$viewonline', user_bio='$userbio', user_bio_status='$user_bio_status', user_signature='$usersignature', user_signature_status='$user_sig_status', user_view_signatures='$viewsignatures', user_view_avatars='$viewavatars' WHERE user_id='$user_id'");
    header("Location: {$forumscript}?a=member&m={$user_id}");
  }
} else {
  $title = $forumtitle . ' &raquo; ' . $lang['user_options'];
  require_once('forumheader.php');

  $smiliesbar = generate_smilies_bar('document.PForm.userbio');
  $sig_smiliesbar = generate_smilies_bar('document.PForm.usersignature');

  $result = mysqli_query($mysqli, "SELECT user_email, user_timezone, user_email_public, user_allowviewonline, user_bio, user_bio_status, user_signature, user_signature_status, user_view_signatures, user_avatar, user_view_avatars FROM {$dbpref}users WHERE user_id = '$user_id'");
  list($useremail, $tz, $emailpublic, $viewonline, $userbio, $userbio_status, $usersignature, $user_signature_status, $user_view_signatures, $user_avatar, $user_view_avatars) = mysqli_fetch_row($result);

  $userbio = format_html($userbio);
  $htmlzones = get_timezone_select($tz);
  $emailpublic_check = ($emailpublic == 1) ? 'checked' : null;
  $viewonline_check = ($viewonline == 1) ? 'checked' : null;
  $bbcodes_check = (($userbio_status & 1) != 0) ? 'checked' : null;
  $emoticons_check = (($userbio_status & 4) != 0) ? 'checked' : null;
  $makeurls_check = 'checked';
  $sig_bbcodes_check = (($user_signature_status & 1) != 0) ? 'checked' : null;
  $sig_emoticons_check = (($user_signature_status & 4) != 0) ? 'checked' : null;
  $sig_makeurls_check = 'checked';
  $viewsignatures_check = ($user_view_signatures == 1) ? 'checked' : null;
  $viewavatars_check = ($user_view_avatars == 1) ? 'checked' : null;

  if (strlen($user_avatar) == 0)
    $current_avatar = null;
  else
    $current_avatar = '<img src="' . $user_avatar . '" border=0><br><input type=checkbox name=eraseavatar value=1>' . $lang['erase_avatar'] . '.<br>';

  $htmllanguages = '';
  $lang_files = glob(__DIR__ . '/lang/*.php'); // find all PHP files in lang folder
  $current_lang = (isset($_COOKIE['language']) && $_COOKIE['language'] !== '') ? $_COOKIE['language'] : 'eng';
  $htmllanguages .= '<select name="user_lang">';
  foreach ($lang_files as $file) {
    $lang_code = basename($file, '.php'); // get file name without extension
    $selected = ($lang_code == $current_lang) ? ' selected' : '';
    $htmllanguages .= '<option value="' . htmlspecialchars($lang_code) . '"' . $selected . '>' . htmlspecialchars(strtoupper($lang_code)) . '</option>';
  }
  $htmllanguages .= '</select>';


  print eval (get_template('userpanel'));
}
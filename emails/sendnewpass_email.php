<?php

if (!defined('EZFORUM'))
  die($lang['fatal_error']);

$email_subject = "$forumtitle - Activating Your New Password";
$email_body = <<<BOD
Hello,
you have requested a new password for your account at $forumtitle, because you forgot your old one.

We cannot retrieve your old password, because it is kept encrypted in the database. Only you knew it.

We can only change your current password. 

Here is the new password we generated for you: $newpass

Please click on the URL below, or copy it into your browser, to change your old password, to $newpass.

$changepasslink

Later you can log in and change your password again.

BOD;
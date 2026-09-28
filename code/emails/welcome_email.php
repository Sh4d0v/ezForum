<?php

if (!defined('EZFORUM'))
  die($lang['fatal_error']);

$email_subject = "Welcome to $forumtitle";
$email_body = <<<BOD
Hello,

thank you for registering at $forumtitle.

Here is your account information:
Username: {$username}
Pass: {$pass}

BOD;
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

if ($user_id == 0) {
  $navigation = eval (get_template('mainguestnavigation'));
  print eval (get_template('mainheader'));

  if ($loginerror == 1) {
    show_error_back($lang['invalid_up']);
  }
} else {
  $navigation = eval (get_template('mainmembernavigation'));
  print eval (get_template('mainheader'));
}
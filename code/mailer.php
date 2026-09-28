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
function send_email(
  string $email_subject,
  string $email_body,
  string $send_to,
  string $send_from,
  string $errors_to
): void {
  $headers = "From: $send_from\r\n";
  $headers .= "Reply-To: $send_from\r\n";
  $headers .= "Errors-To: $errors_to\r\n";
  $headers .= "X-Mailer: PHP/" . phpversion();

  $body = str_replace("\r\n", "\n", $email_body);

  mail($send_to, $email_subject, $body, $headers);
}
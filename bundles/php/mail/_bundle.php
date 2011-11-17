<?php

namespace Bundles\Mail;
use Exception;
use e;

/**
 * PHP Mail Bundle
 */
class Bundle {

	public function _on_email_send($email) {
		
		$to      = implode(',', $email->to);
		$subject = $email->subject;
		$type    = $email->type;
		$message = $email->body;
		$from    = $email->from->name . ' <' . $email->from->email . '>';
		$php     = 'Evolution SDK on PHP ' . phpversion();
		$headers = "From: $from\r\n
Reply-To: $from\r\n
X-Mailer: $php";

		if($type == 'html')
			$headers  = "MIME-Version: 1.0\r\nContent-type: text/html; charset=iso-8859-1\r\n$headers";
		
		return mail($to, $subject, $message, $headers);
	}
	
}
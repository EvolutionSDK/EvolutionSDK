<?php

namespace Bundles\Email;
use Exception;
use e;

/**
 * Email Bundle
 */
class Bundle {

	public function __bundle_response() {
		return new Email();
	}
	
}

/**
 * Setup email class
 */
class Email {
	
	private $data = array();
	
	public function from($name, $email) {
		$this->data['from'] = (object) array('name' => $name, 'email' => $email);
		return $this;
	}
	
	public function to($emails) {
		return $this->addEmails('to', $emails);
	}
	
	public function cc($emails) {
		return $this->addEmails('cc', $emails);
	}
	
	public function bcc($emails) {
		return $this->addEmails('bcc', $emails);
	}
	
	public function text($text) {
		if(isset($this->data['type']))
			throw new Exception("Cannot add plain text to email when `$this->data[type]` is already present");
		$this->data['type'] = 'text';
		$this->data['body'] = $text;
		return $this;
	}
	
	public function html($html) {
		if(isset($this->data['type']))
			throw new Exception("Cannot add html to email when `$this->data[type]` is already present");
		$this->data['type'] = 'html';
		$this->data['body'] = $html;
		return $this;
	}
	
	public function subject($subject) {
		$this->data['subject'] = $subject;
		return $this;
	}
	
	public function validate($what) {
		foreach(explode(' ', 'subject body type from') as $var) {
			
			if(empty($this->$var))
				throw new Exception("Cannot $what an email before populating the `$var` field");
		}
	}
	
	public function send() {
		$this->validate('send');
		
		e::events()->email_send($this);
	}
	
	public function preview() {
		$this->validate('preview');
		
		?><div style="padding: 18px; background: #eee; font-family: sans-serif; border: 1px solid #888; margin-bottom: 8px;"><h1 style='margin-top: 0; font-size: 18px'>Email Preview</h1>
		
			<table style="font-size: 11px; font-family: sans-serif;"><?php
		
		foreach($this->data as $var => $val)
			echo "<tr><td style='padding: 2px 0;'>$var</td><td style='padding: 2px 4px;'>" . print_r($val, 1) . "</td></tr>";
		
		?></table></div><?php
		
		echo $this->body;
		
		e\complete();
	}
	
	public function __get($var) {
		if(!isset($this->data[$var]))
			return null;
		return $this->data[$var];
	}
	
	public function __isset($var) {
		return isset($this->data[$var]);
	}
	
	private function addEmails($field, $emails) {
		if(!is_array($emails))
			$emails = array($emails);
		if(!isset($this->data[$field]))
			$this->data[$field] = array();
		foreach($emails as $email)
			$this->data[$field][] = $email;
		return $this;
	}
}
<?php

namespace Bundles\Members;
use Bundles\SQL\SQLBundle;
use e;

class Bundle extends SQLBundle {
	
	public function __initBundle() {
		e::$events->lhtml_add_hook(':members', $this);
	}
	
	public function currentMember() {
		try { return e::$session->getMember(); }
		catch(\Bundles\SQL\NoMatchException $e) { return false; }
	}
	
	public function login($email, $password) {
		$return = e::$sql->query("SELECT * FROM `members.account` WHERE `email` = '$email' AND `password` = md5('$password');")->row();
		if($return) return $this->getMember($return)->linkSession(e::$session->_id);
		else return array('error', 'Email or Password was incorrect.');
	}
	
	public function getByEmail($email) {
		$return = e::$sql->query("SELECT * FROM `members.account` WHERE `email` = '$email';")->row();
		if($return) return $this->getMember($return);
		else return false;
	}
	
	public function logout() {
		return $this->currentMember()->unlinkSession(e::$session->_id);
	}
	
}
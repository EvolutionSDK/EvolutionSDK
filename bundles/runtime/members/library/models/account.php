<?php

namespace Bundles\Members\Models;
use Bundles\SQL\Model;
use Exception;
use e;

class Account extends Model {
	
	/**
	 * Get HTML Link
	 */
	public function __getHTMLLink() {
		return '<a href="/test/nate/member/'.$this->id.'">'.$this->first_name . ' ' . $this->last_name . '</a>';
	}
	
	/**
	 * Login this Member
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function login() {
		return $this->linkSession(e::$session->_id);
	}
	
	public function name() {
		return $this->first_name.' '.$this->last_name;
	}
	
}
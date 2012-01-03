<?php

namespace Evolution\Members\Lists;

class Account extends \Evolution\SQL\ListObj {
	
	public function has_permission($level) {
		return $this->condition('`permission` <=', $level);
	}
	
}
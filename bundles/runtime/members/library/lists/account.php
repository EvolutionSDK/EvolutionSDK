<?php

namespace Bundles\Members\Lists;

class Account extends \Bundles\SQL\ListObj {
	
	public function has_permission($level) {
		return $this->condition('`permission` <=', $level);
	}
	
}
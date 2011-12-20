<?php

namespace Bundles\Messages;
use Bundles\SQL\SQLBundle;
use Exception;
use e;

/**
 * Messages Bundle
 */
class Bundle extends SQLBundle {
	
	public function _on_message($data) {
		$message = $this->newMessage();
		$message->save($data);
	}
	
	public function activeMessages() {
		
	}
	
}

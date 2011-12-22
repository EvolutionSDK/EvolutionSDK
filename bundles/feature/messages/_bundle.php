<?php

namespace Bundles\Messages;
use Bundles\SQL\SQLBundle;
use Exception;
use e;

/**
 * Messages Bundle
 */
class Bundle extends SQLBundle {
	
	public function _on_message($data, $namespace = 'global') {
		$message = $this->newMessage();
		if(!isset($data['namespace']))
			$data['namespace'] = $namespace;
		$message->save($data);
		$message->linkSession(e::$session->_session());
	}
	
	public function currentMessages($namespace = 'none') {
		
	}
	
	public function printMessages($namespace = 'none') {
		
		if(!defined('BUNDLE_MESSAGES_PRINTED_STYLE')) {
			echo <<<_
<style>
ul.validator-messages {
	margin: 0;
	padding: 0;
	list-style-type: none;
}
ul.validator-messages li {
	margin: 0 0 1em 0;
	padding: 0.5em;
	
	border: 1px solid #888;
	background: #eee;
	color: #666;
}
ul.validator-messages li.message-error {
	border: 1px solid #600;
	background: #fcc;
	color: #600;
}
ul.validator-messages li span.field {
	font-weight: bold;
}
</style>
_;
			define('BUNDLE_MESSAGES_PRINTED_STYLE', 1);
		}
		echo '<ul class="validator-messages">';
		foreach($this->getMessages()->manual_condition('`namespace` IN ("global", "'.$namespace.'")') as $message) {
			echo '<li class="message-' . $message->type . '">' . $message->message . '</li>';
		}
		echo '</ul>';
	}
	
}

<?php

namespace Bundles\Exceptions;
use Bundles\SQL\SQLBundle;
use Exception;
use e;

/**
 * Serialize-safe Exception class
 * @author Nate Ferrero
 */
class SerializeableException {
	protected $trace;
	protected $className;
	protected $message;
	protected $previous;
	protected $code;
	protected $line;
	protected $file;
	public function getClass() {
		return $this->className;
	}
	public function getMessage() {
		return $this->message;
	}
	public function getTrace() {
		return $this->trace;
	}
	public function getCode() {
		return $this->code;
	}
	public function getLine() {
		return $this->line;
	}
	public function getFile() {
		return $this->file;
	}
	public function setPrevious($prev) {
		$this->previous = $prev;
	}
	public function getPrevious() {
		return $this->previous;
	}
	public function getTime() {
		return $this->time;
	}
	public function setTime($time) {
		$this->time = $time;
	}
	public function import($ex) {
		$this->className = get_class($ex);
		$this->message = $ex->getMessage();
		$this->code = $ex->getCode();
		$this->line = $ex->getLine();
		$this->file = $ex->getFile();
		$this->trace = $ex->getTrace();
		foreach($this->trace as $key => $step)
			$this->trace[$key]['args'] = null;//e\ToArray($this->trace[$key]['args']);
	}
}

/**
 * Exceptions Bundle
 * @author Nate Ferrero
 */
class Bundle extends SQLBundle {

	/**
	 * Show the exception
	 * @author Nate Ferrero
	 */
	public function route($path) {

		if($path === array('test'))
			throw new Exception("Test Exception");

		if($path === array('last'))
			return $this->last();

		if(!is_array($path) || count($path) !== 1 || !is_numeric($id = $path[0])) {
			if($path[0] == 'clear')
				return $this->clearAll();

			echo '<style>._e_dump a.exception-link { text-decoration: none; display: block; margin-bottom: 12px;}</style>';

			$links = array();
			$all = $this->getExceptions()->all();
			foreach($all as $item) {
				$links[] = '<a class="exception-link" href="/@exceptions/'.$item->id.'"><span class="key">' .
					$item->class . '</span> '. e\code_blocks($item->message) .' on <span class="line">' . 
					htmlspecialchars($item->url) . '</span> &mdash; about '.e\time_since($item->created_timestamp).'.</a>';
			}
			$count = count($all);
			$title = "Found " . e\plural($count, "Exception", "Exceptions");
			$body = implode(' ', $links);
			$body .= ' <a href="/@exceptions">Reload</a> &bull; <a href="/@exceptions/clear">Clear All</a>';
			require(e\root . '/' . e\bundles . '/debug/message.php');
			exit;
		}

		try {
			$ex = $this->getException($id);
			$exception = unserialize(base64_decode($ex->serialized));
			if(!($exception instanceof SerializeableException))
				$exception = new Exception("No exception found with that id");

			/**
			 * Show relevant information
			 * @author Nate Ferrero
			 */
			$get = unserialize(base64_decode($ex->get));
			$post = unserialize(base64_decode($ex->post));
			$url = $ex->url;
			$overrideUrl = $url;
			$exception->setTime($ex->created_timestamp);

			$additional = "<div class='section'><h4>Page Information</h4><div class='trace'><div class='step'>";
			$additional .= '<table class="dump"><tbody>';
			foreach(array('URL' => 'url', '$_GET' => 'get', '$_POST' => 'post') as $name => $var) {
				$additional .= '<tr><td align="right" width="1" class="dump-key"><span class="key">' . $name . 
					'</span></td><td class="dump-value">';
				if(is_string($$var))
					$additional .= '<span class="string">\''.$$var.'\'</span>';
				else
					$additional .= '<span class="array">'.implode(', ', e\stylize_array($$var, 2)).'</span>';
				$additional .= '</td></tr>';
			}
			$additional .= '</tbody></table></div></div>';

			require(e\root . '/' . bundles . '/debug/message.php');
			exit;
		} catch(Exception $e) {
			dump($e);
			exit;
		}
	}

	/**
	 * Clear all
	 * @author Nate Ferrero
	 */
	public function clearAll() {
		e::$sql->query("DELETE FROM `exceptions.exception`");
		e\redirect('/@exceptions');
	}

	/**
	 * Save exception
	 * @author Nate Ferrero
	 */
	public function _on_exception($exception) {

		/**
		 * Create a serializeable exception, removing object references
		 * @author Nate Ferrero
		 */
		$ex = new SerializeableException();
		$ex->import($exception);

		try {
			/**
			 * Save to the database
			 * @author Nate Ferrero
			 */
			$model = $this->newException();
			$model->message = addslashes($ex->getMessage());
			$model->file = addslashes($ex->getFile());
			$model->line = $ex->getLine();
			$model->class = addslashes($ex->getClass());
			$model->serialized = base64_encode(serialize($ex));
			$model->url = $_SERVER['REDIRECT_URL'];
			$model->get = base64_encode(serialize(e\ToArray($_GET)));
			$model->post = base64_encode(serialize(e\ToArray($_POST)));
			if(!method_exists($model, 'save'))
				throw new Exception("Cannot save exception");
			$model->save();

			e::$events->exceptionSaved($model);
		} catch(Exception $e) {
			/**
			 * If there was an error saving to the database, store in cache files
			 * @author Nate Ferrero
			 */
			$ex2 = new SerializeableException();
			$ex2->import($e);

			e::$cache->store('exceptions', 'reason', $ex2);
			e::$cache->store('exceptions', 'last', $ex);

			if(strpos($_SERVER['REDIRECT_URL'], '/@exceptions') !== false) {
				echo '<h3>Exception:</h3><pre>';
				var_dump($ex2);
				echo '</pre><h3>Original:</h3><pre>';
				var_dump($ex);
				die('</pre>');
			}
			e\redirect('/@exceptions/last');
		}
	}

	/**
	 * Show the latest exception
	 * @author Nate Ferrero
	 */
	private function last() {
		try {
			$reason = e::$cache->get('exceptions', 'reason');
			$exception = e::$cache->get('exceptions', 'last');
			$exception->setPrevious($reason);
		} catch(Exception $exception) {}
		try {
			require(e\root . '/' . bundles . '/debug/message.php');
			exit;
		} catch(Exception $e) {
			var_dump($exception);
			die("<hr/>Error displaying last exception");
		}
	}
}
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
	public function __class() {
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
			$exception = new Exception("Invalid exception URL");
			require(e\root . '/' . bundles . '/debug/message.php');
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

			$additional = "<div class='section'><h4>Page Information</h4><div class='trace' style=\"margin-bottom: 0\"><div class='step'>";
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
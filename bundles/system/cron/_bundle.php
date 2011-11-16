<?php

namespace bundles\Cron;
use bundles\sql\SQLBundle;
use e;

class Bundle extends SQLBundle {
	
	public $run;
	
	public function run($return = 0) {
		$jobs = $this->getCronjobs();
		
		$i = 0;
		foreach($jobs as $job) {
			$lastrun = strtotime($job->lastrun);
			
			if($job->interval  == '1min') $interval = 60;
			if($job->interval == '15min') $interval = 60 * 15;
			if($job->interval == '30min') $interval = 60 * 30;
			if($job->interval == '1hr') $interval = 60 * 60;
			if($job->interval == '12hr') $interval = 60 * 60 * 12;
			if($job->interval == '24hr') $interval = 60 * 60 * 24;
			else if($job->interval == 'custom') $interval = $job->interval_custom * 60;
			
			if($lastrun > (strtotime(date("Y-m-d h:i:s")) - $interval)) continue;
			
			$log = $this->newCronlog();
			$log->start = date("Y-m-d h:i:s");
			$log->save();
						
			$log->linkCronjob($job->id);
			$log->command = $job->command;
			
			if($job->command_type == 'function') {
				try { eval("\$log->return = json_encode($job->command);"); }
				catch(\Exception $e) { $log->message = $e->getMessage(); $log->message_type = 'error'; }
			}
			
			else if($job->command_type == 'system') {
				try {
					exec($job->command, $passthru_out);
					$log->return = json_encode($passthru_out);
				}
				catch(Exception $e) { $log->message = $e->getMessage(); $log->message_type = 'error'; }
			}
			
			else if($job->command_type == 'call_url') {
				try { $log->return = json_encode(e::curl()->get($job->command, true)); }
				catch(Exception $e) { $log->message = $e->getMessage(); $log->message_type = 'error'; }
			}
			
			$log->end = date("Y-m-d h:i:s");
			$log->save();
			$job->lastrun = $log->start;
			$job->save();
			$i++;
		}
	}
	
	public function route() {
		$this->run = true;
		return true;
	}
	
	public function _on_after_framework_loaded() {
		if(!is_null($this->run)) $this->run();
	}
	
}
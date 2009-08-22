<?php
if (!defined('Shimmer')) header('Location:/');
class DataWorker {
	var $Shimmer;
	
	function DataWorker() {
		global $Shimmer;
		$this->Shimmer =& $Shimmer;
	}
	
	function getAppList() {
		$allApps = array();
		foreach ($this->Shimmer->apps->list as $app) {
			array_push($allApps, array(
				'name'	=>	$app['name'],
				'id'	=>	$app['id'],
				'users'	=>	$this->Shimmer->stats->appUsers($app)
			));
		}
		return $allApps;
	}

}
?>
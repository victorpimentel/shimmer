<?php
if (!defined('Shimmer')) header('Location:/');

class PrefManager {
	var $Shimmer;
	
	function PrefManager() {
		global $Shimmer;
		$this->Shimmer =& $Shimmer;
	}
	
	function exists($prefKey) {
		$sql = "SELECT COUNT(*) AS 'match_count' FROM `settings` WHERE `id`='" . sql_safe($prefKey) . "'";
		$result = $this->Shimmer->query($sql);
		if ($result) {
			$row = mysql_fetch_array($result);
			if (intval($row['match_count'])>0) return true;
		}
		return false;
	}
	
	
	function save($prefKey,$newValue,$serialize=false) {
		$insertValue = ( !$serialize ? $newValue : serialize($newValue) );
		$insertValue = str_replace("'","\'",$insertValue);

		if ( $this->exists($prefKey) ) {
			$sql = "UPDATE `settings` SET `value`='" . sql_safe($insertValue) . "' WHERE `id`='" . sql_safe($prefKey) . "'";
			return $this->Shimmer->query($sql);
		} else {
			$sql = "INSERT INTO `settings` (`id`, `value`) VALUES ('" . sql_safe($prefKey) . "', '" . sql_safe($insertValue) . "')";
			return $this->Shimmer->query($sql);
		}
	}
	
	function read($prefKey,$serialized=false) {
		$sql = "SELECT `value` FROM `settings` WHERE `id`='" . sql_safe($prefKey) . "'";
		$result = $this->Shimmer->query($sql);
		if ($result) {
			$row = mysql_fetch_array($result);
			$returnValue = $row['value'];
			if (isset($returnValue)) {
				return ( !$serialized ? $returnValue : str_replace("\'","'",unserialize($returnValue)) );
			}
		}
		if ($serialized) return array();
		return false;
	}
	
}


?>
<?php
if (!defined('Shimmer')) header('Location:/');

class VersionManager {
	var $Shimmer;
	var $knownVersions = array();
	
	function VersionManager() {
		global $Shimmer;
		$this->Shimmer =& $Shimmer;
	}
	
	// OPTIONS: $includeUserCount=false, $onlyLive=false, $flipDirection=false
	function versions($app,$options=array()) {
		if (isset($options['fields']) && count($options['fields'])>0) {
			$fieldSql = "";
			foreach ($options['fields'] as $i => $field) {
				$fieldSql .= "`" . sql_safe($field) . "`";
				if ($i+1<count($options['fields'])) $fieldSql .= ", ";
			}
		} else {
			$fieldSql .= "*";
		}
		$versionSQL = "SELECT " . $fieldSql . " FROM `" . sql_safe($app['versions_table']) . "` ";
		$where = array();
		if ($options['onlyLive'])     array_push($where, "`live`=1");
		if ($options['timestamp'])    array_push($where, "`published`='"                     . sql_safe($options['timestamp'])    . "'");
		if ($options['minTimestamp']) array_push($where, "`published`>'"                     . sql_safe($options['minTimestamp']) . "'");
		if ($options['maxTimestamp']) array_push($where, "`published`<='"                    . sql_safe($options['maxTimestamp']) . "'");
		if ($options['version'])      array_push($where, "`version`='"                       . sql_safe($options['version'])      . "'");
		if ($options['build'])        array_push($where, "`build`='"                         . sql_safe($options['build'])        . "'");
		if ($options['increment'])    array_push($where, "`" . $app['incrementType'] . "`='" . sql_safe($options['increment'])    . "'");
		if (count($where)>0) $versionSQL .= "WHERE " . implode(" AND ", $where) . " ";
		$versionSQL .= "ORDER BY `published` ";
		$versionSQL .= $options['flipDirection'] ? "ASC" : "DESC";
		if ($options['limit']) $versionSQL .= " LIMIT " . sql_safe(intval($options['limit']));
		$result = $this->Shimmer->query($versionSQL);
		if ($result) {
			$versions = array();
			while ($version = mysql_fetch_array($result)) {
				$versionInfo = array(
					'version'			=>	$version['version'],
					'build'				=>	$version['build'],
					'download'			=>	$version['download'],
					'size'				=>	$version['bytes'],
					'download_count'	=>	$version['download_count'],
					'signature'			=>	$version['signature'],
					'notes'				=>	$version['notes'],
					'published'			=>	$version['published'],
					'modified'			=>	$version['modified'],
					'live'				=>	$version['live'],
					'download_rate'		=>	$version['download_rate'],
					'user_rate'			=>	$version['user_rate']
				);
				if ($options['includeUserCount']) $versionInfo['user_count'] = $this->versionUsers($app,$versionInfo);
				array_push($versions, $versionInfo);
			}
			return $versions;
		}
		return false;
	}
	
	function flatVersions($app, $onlyLive=false) {
		$options['fields'] = array('version');
		$options['onlyLive'] = $onlyLive;
		$results = $this->versions($app, $options);
		if ($results) {
			$versions = array();
			foreach ($results as $result) {
				array_push($versions, $result['version']);
			}
			return $versions;
		}
		return false;
	}
	
	function version($app,$options=array()) {
		$options['limit'] = 1;
		$versionsArray = $this->versions($app,$options);
		if ($versionsArray && count($versionsArray)>0) return $versionsArray[0];
		return false;
	}

	// Finds the number of users for a particular version //
	function versionUsers($app,$version) {
		if (!array_key_exists($app['name'],$this->knownVersions)) { // reload the version number cache for the supplied app if needed
			$cutoff = date('Y-m-d', time()-(86400*30));
			$versionsSql = "SELECT `last_version` as 'version', COUNT(*) AS 'users_count' FROM `" . sql_safe($app['users_table']) . "` WHERE `last_seen`>='" . sql_safe($cutoff) . "' GROUP BY `last_version`";
			$versionsResult = $this->Shimmer->query($versionsSql);
			if ($versionsResult) {
				$versionList = array();
				while ($versionRow = mysql_fetch_array($versionsResult)) {
					$versionNumber = $versionRow['version'];
					if (strlen($versionNumber)>0) {
						array_push($versionList, array(
							'version'	=>	$versionNumber,
							'users'		=>	$versionRow['users_count']
						));
					}
				}
				$this->knownVersions[$app['name']] = $versionList;
			}
		}

		foreach ($this->knownVersions[$app['name']] as $versionPair) {
			if ($versionPair['version']==$version['version']) return intval($versionPair['users']);
		}
		return 0;
	}
	
	// Finds the number of versions for a particular app
	function versionCountForApp($app, $onlyLive=false) {
		$sql = "SELECT count(*) as 'version_count' FROM `" . sql_safe($app['users_table']) . "`";
		if ($onlyLive) $sql .= " WHERE `live`=1";
		$result = $this->Shimmer->query($sql);
		if ($result) {
			$row = mysql_fetch_array($result);
			if ($row) return intval($row['version_count']);
		}
		return 0;
	}
	
	function versionCountForAllApps($onlyLive=false) {
		$tally = 0;
		foreach ($this->Shimmer->apps->list as $app) {
			$tally += $this->versionCountForApp($app, $onlyLive);
		}
		return $tally;
	}
	
	function exists($app, $versionAttributes=array()) {
		if (count($versionAttributes)>0) {
			$sql = "SELECT COUNT(*) AS 'version_count' FROM `" . sql_safe($app['versions_table']) . "` WHERE ";
			$where = array();
			if ($versionAttributes['version'])	 array_push($where," `version`='"	. sql_safe($versionAttributes['version']) .		"'");
			if ($versionAttributes['build'])	 array_push($where," `build`='"		. sql_safe($versionAttributes['build']) . 		"'");
			if ($versionAttributes['timestamp']) array_push($where," `published`='"	. sql_safe($versionAttributes['timestamp']) .	"'");
			if (count($where)>0) {
				$sql .= implode(" AND ", $where);
				$result = $this->Shimmer->query($sql);
				if ($result) {
					$row = mysql_fetch_array($result);
					if (intval($row['version_count'])>0) return true;
				}
			}
		}
		return false;
	}
	
	function incrementExists($app, $incrementNumber, $excludeTimestamp=false) {
		if (strlen($incrementNumber)>0) {
			$sql = "SELECT COUNT(*) AS 'version_count' FROM `" . sql_safe($app['versions_table']) . "` WHERE `" . $app['incrementType'] . "`='" . sql_safe($incrementNumber) . "'";
			if ($excludeTimestamp) $sql .= " AND NOT `published`='" . $excludeTimestamp . "'";
			if ($row = $this->Shimmer->querySelect($sql, true)) {
				if (intval($row['version_count'])>0) return true;
			}
		}
		return false;
	}
	
	function add($app, $newInfo = array()) {
		$versionExists = $newInfo['DID_CHECK_EXISTS'] ? false : $this->exists($app, array( 'version' => $newInfo['version'] ));
		if (!$versionExists) {
			$insertValues = array(
				'version'			=>	"",
				'build'				=>	"",
				'download'			=>	"http://",
				'bytes'				=>	0,
				'signature'			=>	"",
				'notes'				=>	"",
				'published'			=>	time(),
				'modified'			=>	time(),
				'live'				=>	0,
				'download_count'	=>	0,
				'download_rate'		=>	"",
				'user_rate'			=>	"",
			);
			if (isset($newInfo['version']))			$insertValues['version']			=	$newInfo['version'];
			if (isset($newInfo['build']))			$insertValues['build']				=	$newInfo['build'];
			if (isset($newInfo['download']))		$insertValues['download']			=	$newInfo['download'];
			if (isset($newInfo['size']))			$insertValues['bytes']				=	$newInfo['size'];
			if (isset($newInfo['signature']))		$insertValues['signature']			=	$newInfo['signature'];
			if (isset($newInfo['notes']))			$insertValues['notes']				=	$newInfo['notes'];
			if (isset($newInfo['published']))		$insertValues['published']			=	$newInfo['published'];
			if (isset($newInfo['modified']))		$insertValues['modified']			=	$newInfo['modified'];
			if (isset($newInfo['live']))			$insertValues['live']				=	$newInfo['live'];
			if (isset($newInfo['download_count']))	$insertValues['download_count']		=	$newInfo['download_count'];
			
			for ($rateType=0; $rateType < 2; $rateType++) { 
				$rateName  = ($rateType==0 ? 'download_rate' : 'user_rate');
				$rateInput = $newInfo[$rateName];
				if (strlen($rateInput)>0) {
					$allRates = explode(',', $rateInput);
					if ($allRates) {
						$ratesPairs = array();
						foreach ($allRates as $i => $theRate) {
							array_push($ratesPairs, $this->Shimmer->rates->ratesDateArray($i,intval($theRate)));
						}
						$saveData = str_replace("'","\'",serialize($ratesPairs));
						$insertValues[$rateName] = $saveData;
					}
				}
			}
			
			$sql = "INSERT INTO `" . sql_safe($app['versions_table']) . "`";
			$sql .= " (`version`, `build`, `download`, `bytes`, `signature`, `notes`, `published`, `modified`, `live`, `download_count`, `download_rate`, `user_rate`) VALUES (";
			$sql .= "'" . sql_safe($insertValues['version'])				. "', ";
			$sql .= "'" . sql_safe($insertValues['build'])					. "', ";
			$sql .= "'" . sql_safe($insertValues['download'])				. "', ";
			$sql .= "'" . sql_safe($insertValues['bytes'])					. "', ";
			$sql .= "'" . sql_safe($insertValues['signature'])				. "', ";
			$sql .= "'" . sql_safe($insertValues['notes'])					. "', ";
			$sql .= "'" . sql_safe($insertValues['published'])				. "', ";
			$sql .= "'" . sql_safe($insertValues['modified'])				. "', ";
			$sql .= 	  sql_safe(intval($insertValues['live']))			. ",  ";
			$sql .= 	  sql_safe(intval($insertValues['download_count'])) . ",  ";
			$sql .= "'" . sql_safe($insertValues['download_rate'])			. "', ";
			$sql .= "'" . sql_safe($insertValues['user_rate'])				. "'";
			$sql .= ")";
			$result = $this->Shimmer->query($sql);
			if ($result) return true;
		}
		return false;
	}
	
	function update($app, $newInfo = array()) {
		$reference = $newInfo['REFERENCE_TIMESTAMP'];
		$versionExists = $newInfo['DID_CHECK_EXISTS'] ? true : $this->exists($app, array( 'timestamp' => $reference ));
		if ($versionExists) {
			$updateValues = array();
			if (isset($newInfo['version']))			array_push($updateValues, "`version`='" .		sql_safe($newInfo['version']) . "'");
			if (isset($newInfo['build']))			array_push($updateValues, "`build`='" .			sql_safe($newInfo['build']) . "'");
			if (isset($newInfo['download']))		array_push($updateValues, "`download`='" .		sql_safe($newInfo['download']) . "'");
			if (isset($newInfo['bytes']))			array_push($updateValues, "`bytes`='" .			sql_safe($newInfo['bytes']) . "'");
			if (isset($newInfo['signature']))		array_push($updateValues, "`signature`='" .		sql_safe($newInfo['signature']) . "'");
			if (isset($newInfo['notes']))			array_push($updateValues, "`notes`='" .			sql_safe($newInfo['notes']) . "'");
			if (isset($newInfo['modified']))		array_push($updateValues, "`modified`='" .		sql_safe($newInfo['modified']) . "'");
			if (isset($newInfo['live']))			array_push($updateValues, "`live`='" .			sql_safe($newInfo['live']) . "'");
			if (isset($newInfo['download_count']))	array_push($updateValues, "`download_count`='" .sql_safe($newInfo['download_count']) . "'");
			if (isset($newInfo['published']))		array_push($updateValues, "`published`='" .		sql_safe($newInfo['published']) . "'");

			if (count($updateValues)>0) {
				$sql = "UPDATE `" . sql_safe($app['versions_table']) . "` SET " . implode(', ', $updateValues) . " WHERE `published`='" . sql_safe($reference) . "'";
				$result = $this->Shimmer->query($sql);
				if ($result) return true;
			}
		}
		return false;
	}
	
	function delete($app,$timestamp,$checkedVersionExists=false) {
		$versionExists = $checkedVersionExists ? true : $this->exists($app, array( 'timestamp' => $timestamp ));
		if ($versionExists) {
			$sql = "DELETE FROM `" . sql_safe($app['versions_table']) . "` WHERE `published`='" . sql_safe($timestamp) . "'";
			$result = $this->Shimmer->query($sql);
			if ($result) return true;
		}
		return false;
	}
	
}


?>
<?php
if (!defined('Shimmer')) header('Location:/');

class AppManager {
	var $Shimmer;
	var $list = array();
	
	function AppManager() {
		global $Shimmer;
		$this->Shimmer =& $Shimmer;
		// Load list of known apps for quick retrieval in later functions
		$this->loadAppsList();
	}
	
	function loadAppsList() {
		$sql = "SELECT * FROM `applications` ORDER BY `app_order` ASC";
		$apps = $this->Shimmer->query($sql);
		if ($apps) {
			$newApps = array();
			while ($app = mysql_fetch_array($apps)) {
				
				$notesTheme   = $app['notes_theme'] ?
								$app['notes_theme'] :
								'default.shimmer.xml';
				$notesMask 	  = $this->Shimmer->isURL($app['notes_mask']) ?
								$app['notes_mask'] :
								$this->Shimmer->baseURL . '?notes&app=_APP_&version=_VER_&build=_BUILD_';
				$downloadMask = $this->Shimmer->isURL($app['download_mask']) ?
								$app['download_mask'] :
								$this->Shimmer->baseURL . '?download&app=_APP_&version=_VER_&build=_BUILD_';
								
				array_push($newApps, array(
					'name'				=>	$app['name'],
					'id'				=>	$app['id'],
					'order'				=>	intval($app['app_order']),
					'versions_table'	=>	$app['table'],
					'users_table'		=>	$app['users'],
					'notesTheme'		=>	$notesTheme,
					'notesMask'			=>	$notesMask,
					'downloadMask'		=>	$downloadMask,
					'usesSparkle'		=>	($app['uses_sparkle']=='1' ? true : false),
					'identifier'		=>	$this->getIdentifier(array(
																	'users_table'	=> $app['users'],
																	'identifier'	=> $app['identifier']
																))
				));
			}
			$this->list = $newApps;
		}
	}
	
	function setNotesThemeForApp($app, $notesTheme) {
		if (!preg_match('/\.shimmer\.xml$/', $notesTheme)) $notesTheme = "default.shimmer.xml";
		$updateSql = "UPDATE `applications` SET `notes_theme`='" . sql_safe($notesTheme) . "' WHERE (`name`='" . sql_safe($app['name']) . "')";
		$this->Shimmer->query($updateSql);
	}
	
	function setMasksForApp($app, $masks) {
		$sqlUpdates = array();
		if ($this->Shimmer->isURL($masks['notesMask']))		array_push($sqlUpdates, "`notes_mask`='"	. sql_safe($masks['notesMask'])		. "'");
		if ($this->Shimmer->isURL($masks['downloadMask']))	array_push($sqlUpdates, "`download_mask`='"	. sql_safe($masks['downloadMask'])	. "'");
		if (sizeof($sqlUpdates)>0) {
			$sql = "UPDATE `applications` SET " . implode(", ", $sqlUpdates) . " WHERE (`name`='" . sql_safe($app['name']) . "')";
			$this->Shimmer->query($sql);
		}
	}
	
	function boxesForApp($app) {
		$sql = "SELECT `boxes` FROM `applications` WHERE (`name`='" . sql_safe($app['name']) . "')";
		$result = $this->Shimmer->query($sql);
		if ($result) {
			$resultsRow = mysql_fetch_array($result);
			if ($resultsRow) {
				$serializedBoxes = $resultsRow['boxes'];
				if (strlen($serializedBoxes)) {
					$boxes = str_replace("\'","'",unserialize($serializedBoxes));
					return $boxes;
				}
			}
		}
		
		// If we get to this point, then the boxes haven't been returned, so return an empty box list
		return array(
			'r1b1'	=>	false,
			'r2b1'	=>	false,
			'r2b2'	=>	false,
			'r2b3'	=>	false
		);
	}
	
	function app($name) {
		$lowerName = strtolower($name);
		foreach ($this->list as $app) {
			if (strtolower($app['name'])==$lowerName) return $app;
		}
		return false;
	}
	
	function defaultApp() {
		if (sizeof($this->list) > 0) {
			foreach ($this->list as $app) {
				if ($app['order']===0) return $app;
			}
			return $this->$list[0];
		}
		return false;
	}
	
	// How many apps uses Sparkle
	function sparkleAppCount() {
		$tally = 0;
		foreach ($this->list as $app) {
			if ($app['usesSparkle']) $tally++;
		}
		return $tally;
	}
	
	// How many apps do not use Sparkle
	function nonSparkleAppCount() {
		$tally = 0;
		foreach ($this->list as $app) {
			if ($app['usesSparkle']===false) $tally++;
		}
		return $tally;
	}
	
	function create($name, $usesSparkle=true) {
		if (strlen($name)>0) {
			$existingApp = $this->app($name);
			if ( !$existingApp && count($this->list)<=15) {
				$lowercaseAppName = $this->convertTableName($name);
				$newVersionsTableName = "versions_" . $lowercaseAppName;
				$newUsersTableName    = "users_"    . $lowercaseAppName;
				
				if ( $this->Shimmer->table->createVersionTable($newVersionsTableName) && $this->Shimmer->table->createUserTable($newUsersTableName, $usesSparkle) ) {
					$sql = "INSERT INTO `applications` (`name`, `table`, `users`, `creation_date`, `uses_sparkle`) VALUES (";
					$sql .= "'" . sql_safe($name) . "'";
					$sql .= ", '" . sql_safe($newVersionsTableName)	. "'";
					$sql .= ", '" . sql_safe($newUsersTableName)	. "'";
					$sql .= ", '" . sql_safe(date('Y-m-d'))		. "'";
					$sql .= ", "  . ($usesSparkle ? '1' : '0')		. ")";
					$result = $this->Shimmer->query($sql);
					if ($result) {
						$this->loadAppsList();
						if ($usesSparkle) $this->Shimmer->table->addSparkleColumnsToTable($app['users_table']);
						return $this->app($name);
					}
				}
				return false;
			}
		}
	}
	
	function delete($app) {
		$deleteVersionsSql = "DROP TABLE `" . sql_safe($app['versions_table']) . "`";
		$this->Shimmer->query($deleteVersionsSql);
		$deleteUsersSql = "DROP TABLE `" . sql_safe($app['users_table']) . "`";
		$this->Shimmer->query($deleteUsersSql);

		$deleteRowSql = "DELETE FROM `applications` WHERE `table`='" . sql_safe($app['versions_table']) . "'";
		$result = $this->Shimmer->query($deleteRowSql);
		if ($result) return true;
		return false;
	}
	
	function rename($app,$newName) {
		$lowercaseAppName		=	$this->convertTableName($newName);
		$oldVersionsTableName	=	$app['versions_table'];
		$newVersionsTableName	=	"versions_" . $lowercaseAppName;
		$oldUsersTableName		=	$app['users_table'];
		$newUsersTableName		=	"users_" . $lowercaseAppName;

		if ( strcasecmp($app['name'],$newName)!=0 ) {
			$renameWorked = true;
			$renameSql = "RENAME TABLE `" . sql_safe($oldVersionsTableName) . "` TO `" . sql_safe($newVersionsTableName) . "`";
			if (!$this->Shimmer->query($renameSql)) $renameWorked = false;
			$renameSql = "RENAME TABLE `" . sql_safe($oldUsersTableName) . "` TO `" . sql_safe($newUsersTableName) . "`";
			if (!$this->Shimmer->query($renameSql)) $renameWorked = false;

			if ($renameWorked) {
				/* Update table links within main 'applications' table */
				$updateSql = "UPDATE `applications` SET `name`='" . sql_safe($newName) . "', `table`='" . sql_safe($newVersionsTableName) . "', `users`='" . sql_safe($newUsersTableName) . "' WHERE `name`='" . sql_safe($app['name']) . "'";
				$this->Shimmer->query($updateSql);
				$this->loadAppsList();
			} else return false;
		}
		return $this->app($newName);
	}
	
	function setAppUsesSparkle($app, $usesSparkle) {
		// Update the boolean 'uses_sparkle' flag in the Applications table.
		// This determines if additional Graphs are available in the dashboard.
		$sql = "UPDATE `applications` SET `uses_sparkle`='" . ($usesSparkle ? 1 : 0) . "' WHERE `name`='" . sql_safe($app['name']) . "'";
		if ($this->Shimmer->query($sql)) {
			// Add the Sparkle columns if they aren't already there
			$this->Shimmer->table->addSparkleColumnsToTable($app['users_table']);
			
			// Also update the in-memory app list, in case any functions check the usesSparkle flag too
			foreach ($this->list as $i => &$currentApp) {
				if ($currentApp['name']==$app['name']) {
					$currentApp['usesSparkle'] = ($usesSparkle?true:false);
					break;
				}
			}
			return true;
		}
		return false;
	}

	function updateStatIndexesForApp($app) {
		$table      = $app['users_table'];
		$identifier = $app['identifier'];
		
		// Drop the old index first
		$dropSql = 'DROP INDEX `identifier_index` ON `' . sql_safe($table) . '`';
		$this->Shimmer->query($dropSql);
		
		// Now add the index for the new Identifier
		$addSql = 'CREATE INDEX `identifier_index` ON `' . sql_safe($table) . '` (`' . $identifier . '`)';
		$worked = $this->Shimmer->query($addSql);
		
		return ($worked?true:false);
	}
	
	// Returns the Parameter name for the App's Identifier. Ensures that stored Identifier actually exists.
	// Falls back to 'ip' if an error is found.
	function getIdentifier($app) {
		$identifier = $app['identifier'];
		if (sizeof($identifier)>0 && $this->Shimmer->stats->parameterExists($app, $identifier)) return $identifier;
		return 'ip';
	}
	
	function setAppIdentifier($app, $identifier) {
		$sql = "UPDATE `applications` SET `identifier`='" . sql_safe($identifier) . "' WHERE `name`='" . sql_safe($app['name']) . "'";
		if ($this->Shimmer->query($sql)) {
			$app['identifier'] = $identifier;
			$this->updateStatIndexesForApp($app);
			return true;
		}
		return false;
	}
	
	function dsaKeysForApp($app) {
		$keys = array(
			'public'  => false,
			'private' => false
		);
		$sql = "SELECT `public_key`, `private_key` FROM `applications` WHERE `name`='" . $app['name'] . "'";
		if ($result = $this->Shimmer->query($sql)) {
			if ($row = mysql_fetch_array($result)) {
				if ($row['public_key'])  $keys['public']  = $row['public_key'];
				if ($row['private_key']) $keys['private'] = $row['private_key'];
			}
		}
		return $keys;
	}
	
	function loadSignatureKeysForApp($app, $sessionKeys) {
		if ($sessionKeys['public'] || $sessionKeys['private']) {
			$search = array();
			if ($sessionKeys['public'])  {
				if ($uploadInfo = $this->Shimmer->pref->read('public-key-processing', true)) {
					if ($uploadInfo['key_ok'] && $uploadInfo['session'] == $sessionKeys['public']) {
						if ($keyContents = file_get_contents($uploadInfo['file'])) {
							$sql = "UPDATE `applications` SET `public_key`='" . sql_safe($keyContents) . "' WHERE `name`='" . sql_safe($app['name']) . "'";
							$this->Shimmer->query($sql);
						}
					}
				}
			}

			if ($sessionKeys['private'])  {
				if ($uploadInfo = $this->Shimmer->pref->read('private-key-processing', true)) {
					if ($uploadInfo['key_ok'] && $uploadInfo['session'] == $sessionKeys['private']) {
						if ($keyContents = file_get_contents($uploadInfo['file'])) {
							$sql = "UPDATE `applications` SET `private_key`='" . sql_safe($keyContents) . "' WHERE `name`='" . sql_safe($app['name']) . "'";
							$this->Shimmer->query($sql);
						}
					}
				}
			}
		}
	}
	
	/* This function replaces spaces with underscores. Use for table names */
	function convertTableName($appName) {
		return preg_replace("/^-|-$/","",preg_replace("/[^a-zA-Z0-9]+/","-",strtolower($appName)));
	}
	
}


?>
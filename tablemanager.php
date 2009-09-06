<?php
if (!defined('Shimmer')) header('Location:/');

class TableManager {
	var $Shimmer;
	
	function TableManager() {
		global $Shimmer;
		$this->Shimmer =& $Shimmer;
	}
	
	function exists($name) {
		$result = $this->Shimmer->query("CHECK TABLE `" . sql_safe($name) . "`");
		if (!$result || mysql_num_rows($result) <= 0) {
			return false;
		} else {
			$checkRow = mysql_fetch_array($result);
			return ( strtolower($checkRow['Msg_type']) != "error" );
		}
	}
	
	function createVersionTable($tableName) {
		$sql = "CREATE TABLE IF NOT EXISTS `" . sql_safe($tableName) . "` (";
		$sql .= " `version`			VARCHAR(20)		NOT NULL";
		$sql .= ", `build`			VARCHAR(20)		NOT NULL";
		$sql .= ", `download`		VARCHAR(255)	NOT NULL";
		$sql .= ", `bytes`			INT				NOT NULL";
		$sql .= ", `download_count`	INT				NOT NULL";
		$sql .= ", `signature`		VARCHAR(128)	NOT NULL";
		$sql .= ", `notes`			TEXT			NOT NULL";
		$sql .= ", `published`		VARCHAR(14)		NOT NULL";
		$sql .= ", `modified`		VARCHAR(14)		NOT NULL";
		$sql .= ", `live`			BOOL			NOT NULL";
		$sql .= ", `download_rate`	TEXT			NOT NULL";
		$sql .= ", `user_rate`		TEXT			NOT NULL";
		$sql .= ", PRIMARY KEY (`version`, `build`)";
		$sql .= ") CHARACTER SET utf8 COLLATE utf8_unicode_ci";

		$result = $this->Shimmer->query($sql);
		if ($result) return true;
		return false;
	}

	// Creates the base user stats table. Sparkle columns can be added later using the StatManager.
	function createUserTable($tableName, $usesSparkle) {
		$sql = "CREATE TABLE IF NOT EXISTS `" . $tableName . "` (";
		
		// Add the mandatory Parameter columns
		$sql .= "  `ip`				VARCHAR(15)		NOT NULL"; // PRIMARY KEY
		$sql .= ", `last_version`	VARCHAR(10)		NOT NULL";
		$sql .= ", `first_version`	VARCHAR(10)		NOT NULL";
		$sql .= ", `last_seen`		DATE			NOT NULL";
		$sql .= ", `first_seen`		DATE			NOT NULL";

		// Finish off the table, and create it
		$sql .= ") CHARACTER SET utf8 COLLATE utf8_unicode_ci";
		$result = $this->Shimmer->query($sql);
		if ($result) {
			if ($usesSparkle) $this->addSparkleColumnsToTable($tableName);
			return true;
		}
		return false;
	}
	
	// Todo check if ADD COLUMN IF NOT EXISTS to reduce errors
	function addSparkleColumnsToTable($tableName) {
		$sparkleParameters = $this->Shimmer->stats->sparkleParameterDefinitions();
		foreach ($sparkleParameters as $parameter) {
			$sql = "ALTER TABLE `" . sql_safe($tableName) . "` ADD COLUMN `" . sql_safe($parameter) . "` VARCHAR(128) NOT NULL";
			$this->Shimmer->query($sql);
		}
	}

	///////////////////// TEST TABLE MANIPULATION ///////////////////////////

	function testTables() {
		$tableName = "installer_test";
		if ($this->createTestTable($tableName)) {
			if ($this->insertTestContent($tableName)) {
				if ($this->dropTestTable($tableName)) {
					return true;
				}
			}
		}
		return false;
	}
	
	function createTestTable($tableName) {
		$sql = "CREATE TABLE IF NOT EXISTS `" . sql_safe($tableName) . "` (";
		$sql .= " `text`	VARCHAR(5)	NOT NULL";
		$sql .= ", `int`	INT			NOT NULL";
		$sql .= ", `bool`	BOOL		NOT NULL";
		$sql .= ") CHARACTER SET utf8 COLLATE utf8_unicode_ci";
		$result = $this->Shimmer->query($sql);
		if ($result) return true;
		return false;
	}

	function insertTestContent($tableName) {
		$sql = "INSERT INTO `" . sql_safe($tableName) . "` (`text`, `int`, `bool`) VALUES ('text', 5, 1)";
		$result = $this->Shimmer->query($sql);
		if ($result) return true;
		return false;
	}

	function dropTestTable($tableName) {
		$sql = "DROP TABLE `" . sql_safe($tableName) . "`";
		$result = $this->Shimmer->query($sql);
		if ($result) return true;
		return false;
	}
	
	///////////////////// CREATE CORE TABLES ///////////////////////////
	function createCoreTables() {
		return ( $this->createAppsTable() && $this->createLockoutTable() && $this->createSettingsTable() );
	}

	function createAppsTable() {
		$sql = "CREATE TABLE IF NOT EXISTS `applications` (";
		$sql .= " `name`			VARCHAR(255)	NOT NULL";
		$sql .= ", `variant`		VARCHAR(255)	NOT NULL";
		$sql .= ", `id`				INT(11)			NOT NULL	AUTO_INCREMENT PRIMARY KEY";
		$sql .= ", `table`			VARCHAR(255)	NOT NULL";
		$sql .= ", `users`			VARCHAR(255)	NOT NULL";
		$sql .= ", `graphs`			TEXT			NOT NULL";
		$sql .= ", `params`			TEXT			NOT NULL";
		$sql .= ", `identifier`		VARCHAR(255)	NOT NULL";
		$sql .= ", `app_order`		INT(11)			NOT NULL";
		$sql .= ", `notes_theme`	VARCHAR(255)	NOT NULL";
		$sql .= ", `download_mask`	VARCHAR(255)	NOT NULL";
		$sql .= ", `notes_mask`		VARCHAR(255)	NOT NULL";
		$sql .= ", `creation_date`	DATE			NOT NULL";
		$sql .= ", `boxes`			TEXT			NOT NULL";
		$sql .= ", `public_key`		VARCHAR(3000)	NOT NULL";
		$sql .= ", `private_key`	VARCHAR(3000)	NOT NULL";
		$sql .= ", `uses_sparkle`	TINYINT(1)		NOT NULL	default '0'";
		$sql .= ") CHARACTER SET utf8 COLLATE utf8_unicode_ci";
		$result = $this->Shimmer->query($sql);
		if ($result) return true;
		return false;
	}

	function createLockoutTable() {
		$sql = "CREATE TABLE IF NOT EXISTS `lockout` (";
		$sql .= " `ip`		VARCHAR(255)	NOT NULL";
		$sql .= ", `expire`	DATETIME		NOT NULL";
		$sql .= ") CHARACTER SET utf8 COLLATE utf8_unicode_ci";
		$result = $this->Shimmer->query($sql);
		if ($result) return true;
		return false;
	}

	function createSettingsTable() {
		$sql = "CREATE TABLE IF NOT EXISTS `settings` (";
		$sql .= " `id`		VARCHAR(96)		NOT NULL PRIMARY KEY";
		$sql .= ", `value`	TEXT			NOT NULL";
		$sql .= ") CHARACTER SET utf8 COLLATE utf8_unicode_ci";
		$result = $this->Shimmer->query($sql);
		if ($result) return true;
		return false;
	}
	
	function dropAllTables() {
		foreach ($this->Shimmer->apps->list as $app) $this->Shimmer->apps->delete($app);
		$this->Shimmer->query("DROP TABLE `applications`");
		$this->Shimmer->query("DROP TABLE `lockout`");
		$this->Shimmer->query("DROP TABLE `settings`");		
		return true;
	}
	
	function optimizeTables() {
		$this->Shimmer->stats->deleteOldUsers();
		$this->Shimmer->auth->clearOldLoginAttempts();
	}
	
}

?>
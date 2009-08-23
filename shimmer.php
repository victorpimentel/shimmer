<?php
if (!defined('Shimmer')) header('Location:/');
include('authmanager.php');
include('prefmanager.php');
include('appmanager.php');
include('versionmanager.php');
include('ratemanager.php');
include('statmanager.php');
include('tablemanager.php');

class Shimmer {
	// The currently installer version. Divide by 100 to get the actual value.
	var $version = '0.1';
	var $build   = '0';

	// Database connection details
	var $database		= array(
		'server'	=> 'localhost',
		'username'	=> '',
		'password'	=> '',
		'database'	=> '',
		'connected'	=> 0
	);
	
	// References to functionality managers
	var $auth;
	var $pref;
	var $apps;
	var $versions;
	var $rates;
	var $stats;
	var $table;
	
	// Holds all SQL queries executed in this flow
	var $allQueries = array();
	
	// The current domain name running the Shimmer install, such as yourapp.com
	var $domain;
	// The full URL for the current Shimmer install, such as http://yourapp.com/shimmer/
	var $baseURL;
	
	// The base internal path to the base Shimmer install, such as /Users/ben/Sites/shimmer/
	var $base = '';
	// The path, relative to $this->base, to the temporary storage directory
	var $tempFolder = 'tmp/';
	
	// Constructor. Parameter contains an associative array of database connection details.
	function Shimmer($options = array()) {
		$this->base = preg_replace('/\/$/', '', dirname(__FILE__));
		$this->database['server']		= $options['server'];
		$this->database['username'] 	= $options['username'];
		$this->database['password'] 	= $options['password'];
		$this->database['database'] 	= $options['database'];
		if (isset($options['connected'])) {
			$this->database['connected'] = true;
		} else {
			$this->database['connected'] = $this->connectToDatabase();
		}
	}
	
	// Initialization method called at startup
	function setup() {
		if ($this->database['connected']) {
			$this->pref		= new PrefManager();

			$this->domain	= $this->currentDomain();
			
			$storedBase = $this->pref->read('baseURL');
			if ($storedBase && strlen($storedBase)>0) {
				$this->baseURL	= $storedBase;
			} else {
				$this->baseURL	= "http" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? "s" : "") . "://" . $this->domain . preg_replace("/\?(&?[^=]+=?[^=]*)*$/", "", $_SERVER['REQUEST_URI']);
			}

			$this->auth		= new AuthManager();
			$this->stats	= new StatManager();
			$this->apps		= new AppManager();
			$this->versions	= new VersionManager();
			$this->rates	= new RateManager();
			$this->table	= new TableManager();
		}
	}
	
	// Convenience method for matching URLs
	function isURL($url) {
		if ($url) return preg_match('/^http[s]?:\/\/(\.?[^\.^\/]+)+(\/[^\/]+)*/', $url) ? true : false;
		return false;
	}
	
	// Returns the current domain, such as 'yourapp.com'. Will probably be made more robust in future versions.
	function currentDomain() {
		return $_SERVER['HTTP_HOST'];
	}
	
	// Is the temporary folder accessible. Tries to create it (if possible).
	function tempFolder() {
		$tempLoc = $this->tempFolder;
		if (!file_exists($tempLoc)) @mkdir($tempLoc);
		if (file_exists($tempLoc) && is_writeable($tempLoc)) return true;
		return false;
	}
	
	// Did the user just install Shimmer? Returns false after first run.
	function didInstall() {
		$lastVersion = $this->pref->read('lastVersion');
		if ($lastVersion!==false) {
			if ($lastVersion === '0') {
				// Hit the appcast at shimmerpp.com, so we don't have to wait 24hrs to provide usage stats
				$this->checkForUpdates();
				$this->pref->save('lastVersion', $this->version);
				return true;
			}
		}
		return false;
	}
	
	// Is the last known version less than the current version?
	function didUpdate() {
		$lastVersion = $this->pref->read('lastVersion');
		if ($lastVersion===false) {
			$this->pref->save('lastVersion', $this->version);
			return false;
		} else if ($lastVersion < $this->version) {
			// Hit the appcast at shimmerpp.com, so we don't have to wait 24hrs to provide usage stats
			$this->checkForUpdates();
			$this->pref->save('lastVersion', $this->version);
			return true;
		}
		return false;		
	}
	
	// Returns true if the current version is outdated. Cached for 1 day.
	function updatesAvailable() {
		$currentTime = time();
		$lastChecked = $this->pref->read('lastUpdateCheck');
		if ($lastChecked==false) $lastChecked = $currentTime - 86400;
		if ($currentTime - $lastChecked >= 86400 || isset($_GET['checkForUpdates'])) {
			$updateAvailable = $this->checkForUpdates();
			$this->pref->save('updateAvailable', $updateAvailable);
			$this->pref->save('lastUpdateCheck', $currentTime);
		}
		if (!isset($updateAvailable)) $updateAvailable = (bool)$this->pref->read('updateAvailable');
		return $updateAvailable;
	}
	
	// Ask shimmerapp.com for the appcast to see if a new version is available. Not cached.
	function checkForUpdates() {
		$appcastURL = "http://shimmerapp.com/appcast.xml";

		// If we have a UUID, include some stats with the appcast request
		$uuid = $this->uuid();
		if ($uuid) {
			$appcastURL .= "?uuid="               . $uuid;
			$appcastURL .= "&appVersion="         . $this->version;
			$appcastURL .= "&appBuild="           . $this->build;
			$appcastURL .= "&phpversion="         . phpversion();
			$appcastURL .= "&mq_gpc="             . (get_magic_quotes_gpc()?'1':'0');
			$appcastURL .= "&mq_sybase="          . (ini_get('magic_quotes_sybase')?'1':'0');
			$appcastURL .= "&mem_limit="          . ini_get('memory_limit');
			$appcastURL .= "&post_max="           . ini_get('post_max_size');
			$appcastURL .= "&upload_max="         . ini_get('upload_max_filesize');
			$appcastURL .= "&time_limit="         . ini_get('max_execution_time');
			$appcastURL .= "&mysqlVersion="       . $this->mysqlVersion();
			$appcastURL .= "&appCount="           . sizeof($this->apps->list);
			$appcastURL .= "&userCount="          . $this->stats->allAppUsers();
			$appcastURL .= "&dsaGenCount="        . $this->stats->totalDsaCount();
			$appcastURL .= "&versionCount="       . $this->versions->versionCountForAllApps();
			$appcastURL .= "&avgActivityAge="     . $this->stats->averageTimeSinceUserSeenForAllApps();
			$appcastURL .= "&sparkleAppCount="    . $this->apps->sparkleAppCount();
			$appcastURL .= "&nonSparkleAppCount=" . $this->apps->nonSparkleAppCount();
		}
		
		$appcast = $this->readURL($appcastURL);
		if ($appcast) {
			include_once('parseappcast.php');
			$document = parse_appcast($appcast);
			$latest      = "0.0";
			$build = 0;
			if (sizeof($document)>0) {
				$allVersions = array_keys($document);
				$latest = $allVersions[0];
				$build  = $document[$latest]['build'];
			}
			
			$latestVersion = (float)$latest;
			$latestBuild   = intval($build);
			if ($latestVersion > $this->version) {
				return true;
			} else if ($latestVersion == $this->version && $latestBuild > $this->build) {
				return true;
			}
		}
		return false;
	}
	
	// Return the current MySQL version. If not found, returns '0'.
	function mysqlVersion() {
		$result = $this->query("SELECT VERSION() as 'version'");
		if ($result) {
			$row = mysql_fetch_array($result);
			if ($row) {
				return $row['version'];
			}
		}
		return '0';
	}
	
	// Returns the stored serial number, or false if not set. Not guaranteed to be valid.
	function storedSerial() {
		$code = $this->pref->read('code');
		if ($code != false) return $code;
		return false;
	}
	
	// Stores the supplied serial number. Pass false if we want to clear the stored value.
	function setStoredSerial($serial) {
		if ($serial==false) $serial = '';
		$this->pref->save('code', trim($serial));
		return true;
	}
	
	// Generates and stores a unique serial number
	function generateNewSerial() {
		$salt = "5h1mm3r.v3r510n.r3l3453.d0n3.r19ht";
		$hash = strtoupper(sha1(time().rand().$salt));

		$charactersPerGroup = 6;
		$numberOfGroups     = 6;
		$totalCharacters    = $charactersPerGroup*$numberOfGroups;

		$serial = '';
		for ($i=0; $i < $totalCharacters; $i++) { 
			if ($i>0 && $i%$charactersPerGroup==0) $serial .= '-';
			$serial .= $hash[$i];
		}
		
		$this->setStoredSerial($serial);
		
		return $serial;
	}
	
	// Checks with shimmerapp.com to see if the supplied serial is valid
	function checkSerial($serial) {
		$appcastURL = "http://shimmerapp.com/appcast.xml?uuid=" . $serial;
		$result = $this->readURL($appcastURL);
		if ($result && $result=="NOWAI") return false;
		return true;
	}
	
	// Returns a unique user ID. Currently returns the user's serial number.
	function uuid() {
		$code = $this->pref->read('code');
		if ($code !== false) return $code;
		return false;
	}
	
	// Returns the IP Address of the current request.
	function requestIP() {
		return gethostbyname($_SERVER['REMOTE_ADDR']);
	}
	
	// Try to connect to the database using the stored database settings. Returns boolean success value.
	function connectToDatabase() {
		@ $conn = mysql_pconnect($this->database['server'],$this->database['username'],$this->database['password']);
		if ($conn) {
			@ $selected = mysql_select_db($this->database['database']);
			if ($selected) return true;
		}
		return false;
	}
	
	// Run an SQL query. Assumes data has already been sanitized. Performs optional logging.
	function query($sql) {
		$result = mysql_query($sql);
		
		if (isset($_GET['debug']) && $this->auth->authenticated) {
			$trace=debug_backtrace();
			$traces = array();
			foreach ($trace as $caller) {
				$classTrace = $caller['function'];
				$classTrace .= " (";
				if (isset($caller['class'])) $classTrace .= $caller['class'];
				if (isset($caller['line'])) $classTrace .= ":" . $caller['line'];
				$classTrace .= ")";
				array_push($traces, $classTrace);
			}

			array_push($this->allQueries, array(
				'worked'	=>	($result ? true : false),
				'query'		=>	$sql,
				'traces'	=>	$traces
			));
		}
		if ($result) return $result;
		// echo "\n\n---" . $sql . "---\n" . mysql_error();
		return false;
	}
	
	// When in debug mode, prints out all previous SQL queries
	function dumpQueries() {
		if (isset($_GET['debug']) && $this->auth->authenticated) {
			echo '<table border="0" style="font-size:12px;margin-top:0.5em;">';
			foreach ($this->allQueries as $query) {
				echo "<tr>";
				echo "<td valign=top><img src=\"img/" . ($query['worked'] ? 'plus_small.png' : 'minus_small.png') . "\" /></td>";
				echo "<td>" . $query['query'] . "<div style=\"font-size:10px;padding-left:10px;\">" . implode("<br>", $query['traces']) . "</div></td></tr>";
			}
			echo '</table>';
		}
	}
	
	// Sets a cookie for the current domain. Cookies apply to both xyz.com and www.xyz.com
	function setCookie($name,$value,$expireDays) {
		if ($_SERVER['HTTP_HOST'] != "localhost") $domain = str_replace("www.",".",$_SERVER['HTTP_HOST']);	
		setcookie($name,$value, time()+(86400*$expireDays), "/", $domain);
	}
	
	// Write data to a temporary file name. Returns the file path is successful.
	function writeToTempFile($filename, $data) {
		global $Shimmer;
		$path = $Shimmer->base . '/tmp/' . $filename;
		$fh = @fopen($path, 'w');
		if ($fh) {
			fwrite($fh, $data);
			fclose($fh);
			return $path;
		}
		return false;
	}	
	
	function readURL($url,$trim=false) {
		if (ini_get('allow_url_fopen') == '1') {
	   		$content = @file_get_contents($url);
		} else {
			if (function_exists('curl_init')) {
				$ch = curl_init(); 
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0'); 
			   	$content = curl_exec($ch); 
			   	curl_close($ch);
			} else {
				$parsedUrl = parse_url($url);
				$host = $parsedUrl['host'];
				if (isset($parsedUrl['path'])) {
					$path = $parsedUrl['path'];
				} else {
					$path = '/';
				}

				if (isset($parsedUrl['query'])) $path .= '?' . $parsedUrl['query'];

				if (isset($parsedUrl['port'])) {
					$port = $parsedUrl['port'];
				} else {
					$port = '80';
				}

				$timeout = 10;
				$response = '';
				$fp = @fsockopen($host, '80', $errno, $errstr, $timeout );

				if( !$fp ) { 
					echo "Cannot retrieve $url";
				} else {
					// send the necessary headers to get the file 
					fputs($fp, "GET $path HTTP/1.0\r\n" .
						"Host: $host\r\n" .
						"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.0.3) Gecko/20060426 Firefox/1.5.0.3\r\n" .
						"Accept: */*\r\n" .
						"Accept-Language: en-us,en;q=0.5\r\n" .
						"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n" .
						"Keep-Alive: 300\r\n" .
						"Connection: keep-alive\r\n" .
						"Referer: http://$host\r\n\r\n");

					while ( $line = fread( $fp, 4096 ) ) {
						$response .= $line;
					}

					fclose( $fp );

					$pos      = strpos($response, "\r\n\r\n");
					$response = substr($response, $pos + 4);
				}

				$content = $response;
			}
		}
		if ($trim) return trim($content);
		return $content;
	}
	
}


?>
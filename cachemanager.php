<?php
if (!defined('Shimmer')) header('Location:/');

class CacheManager {
	
	// Generates an MD5 hash using the provided URL parameters. Returns false if POST parameters are detected.
	function requestHash() {
		if (empty($_POST)){
			if (!empty($_GET)) {
				$hashSubject = '';
				foreach ($_GET as $key => $value) $hashSubject .= '&' . $key . '=' . $value;
			
				return md5($hashSubject);
			}
		}
		
		return false;
	}
	
	// Checks for a file named $hash in the /caches directory
	function cacheExistsForHash($hash) {
		$fileLocation = 'caches/' . $hash;
		return (file_exists($fileLocation) && time()-filemtime($fileLocation) < 60*60);
	}
	
	// Convenience method
	function cacheExistsForCurrentHash() {
		$currentHash = CacheManager::requestHash();
		return ($currentHash && CacheManager::cacheExistsForHash($currentHash));
	}
	
	function deleteCacheForHash($hash) {
		if (CacheManager::cacheExistsForHash($hash)) {
			@unlink('caches/' . $hash);
		}
	}
	
	// Prints the cache if it exists, otherwise returns false
	function printCacheForCurrentHash($contentType = false) {
		$currentHash = CacheManager::requestHash();
		if ($currentHash && CacheManager::cacheExistsForHash($currentHash)) {
			if ($contentType) header('Content-type: ' . $contentType);
			readfile('caches/' . $currentHash);
			return true;
		}
		return false;
	}
	
	// Stores the supplied data in a cache file. Overwrites if necessary.
	function storeCacheForCurrentHash($data) {
		$currentHash = CacheManager::requestHash();
		if (!$currentHash) return false;
		
		// Open the file in overwrite mode
		$fh = @fopen('caches/' . $currentHash, 'w');
		if ($fh) {
			fwrite($fh, $data);
			fclose($fh);
			return true;
		}
		
		return false;
	}

}
?>
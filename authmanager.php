<?php
if (!defined('Shimmer')) header('Location:/');

class AuthManager {
	var $Shimmer;
	var $authenticated = false;
	var $allowedAttempts = 5;
	
	function AuthManager() {
		global $Shimmer;
		$this->Shimmer =& $Shimmer;
		$this->authenticated = $this->authenticate();
	}

	///////////////////////////
	// GENERAL LOGIN METHODS //
	///////////////////////////
	
	function suppliedSessionCookie() {
		if (isset($_COOKIE['shimmer_session'])) return $_COOKIE['shimmer_session'];
		return false;
	}
	
	function authenticate() {
		$suppliedSession = $this->suppliedSessionCookie();
		if ($suppliedSession) {
			$pass = $this->Shimmer->pref->read('pass');
			if ($pass) return md5($pass) == $suppliedSession;
		}
		return false;
	}
	
	function accountExists($email) {
		$storedEmail = $this->Shimmer->pref->read('email');
		if ($storedEmail) return $email == $storedEmail;
		return false;
	}
	
	function storedEmail() {
		$storedEmail = $this->Shimmer->pref->read('email');
		if ($storedEmail) return $storedEmail;
		return false;
	}
	
	function loginExists($email,$pass) {
		$storedEmail	= $this->Shimmer->pref->read('email');
		$storedPass		= $this->Shimmer->pref->read('pass');
		if ($storedEmail && $storedPass) return ($storedEmail==$email && $storedPass==$pass);
		return false;
	}
	
	function login($email,$password) {
		if ( !$this->loginAttemptsExceeded() ) {
			$userIP = $this->userIP();
			if ( isset($email) && isset($password) ) {
				if ($this->loginExists($email,$password)) {
					$this->clearLoginAttemptsForIP($userIP);
					if ($this->setLogin($email, $password)) return true;
				} else {
					$this->insertAttemptForIP($userIP);
					return false;
				}
			}
		}
		return false;
	}
	
	function updateSessionCookie($newKey) {
		$this->Shimmer->setCookie("shimmer_session",$newKey,7*52);
	}
	
	function setLogin($email, $pass) {
		if (isset($email) && isset($pass)) {
			if ($this->Shimmer->pref->save('email', $email) && $this->Shimmer->pref->save('pass', $pass)) {
				$this->updateSessionCookie(md5($pass));	
				return true;
			}
		}
		return false;
	}
	
	/////////////////////
	// LOCKOUT METHODS //
	/////////////////////
	
	// TODO switch to the $Shimmer->requestIP method for centralisation purposes
	// Gets the public user IP. Probably needs to be tweaked to use other headers if available.
	function userIP() {
		return gethostbyname($_SERVER['REMOTE_ADDR']);
	}
	
	// Add a lockout entry for the IP, only if a session cookie was actually supplied.
	function addAttemptIfCookieSupplied() {
		if ($this->suppliedSessionCookie()) $this->insertAttemptForIP($this->userIP());
	}
	
	// Has the IP exceeded their allowed login attempts?
	function loginAttemptsExceeded() {
		$userIP = $this->userIP();
		return $this->allowedAttempts <= $this->loginAttemptsForIP($userIP);
	}
	
	// Add a lockout entry for the IP
	function insertAttemptForIP($ip) {
		$sql = "INSERT INTO `lockout` (`ip`,`expire`) VALUES ('" . sql_safe($ip) . "', TIMESTAMPADD(MINUTE,5,NOW()))";
		$this->Shimmer->query($sql);
	}

	// Remove any existing lockout entries for the IP
	function clearLoginAttemptsForIP($ip) {
		$sql = "DELETE FROM `lockout` WHERE `ip`='" . sql_safe($ip) . "'";
		$this->Shimmer->query($sql);
	}

	// Remove any existing but expired lockout entries for all IPs
	function clearOldLoginAttempts() {
		$sql = "DELETE FROM `lockout` WHERE `expire` <= NOW()";
		$this->Shimmer->query($sql);
	}

	// Returns the number of incorrect login attempts the IP has made
	function loginAttemptsForIP($ip) {
		$this->clearOldLoginAttempts();
		if ($ip) {
			$sql = "SELECT COUNT(*) AS 'login_attempts' FROM `lockout` WHERE `ip`='" . $ip . "'";
			$result = $this->Shimmer->query($sql);
			if ( $result ) {
				$theRow = mysql_fetch_array($result);
				return intval($theRow['login_attempts']);
			}
		}
		return 0;
	}
	
	///////////////////
	// RESET METHODS //
	///////////////////
	
	/////////////////// NEEDS TO BE UPDATED TO FLAT USERS ARCHITECTURE!!!!!!! <-----
	
	function sendReset($email) {
		if ($this->accountExists($email)) {
			$domain = $_SERVER['HTTP_HOST'];
			$resetCode = md5($email . time());
			$resetLink = $this->generateResetLink($resetCode);
			preg_match("/([^\.]+\.[^\.]+)$/",$domain,$matches);
			if (sizeof($matches)>0) $domain = $matches[0];
			$subject = "Shimmer Password Reset";
			$message = "Hi there,\nYou can reset your Shimmer password (for $domain) at:\n$resetLink\n\nHave a great day,\nShimmer";
			if (mail($email, $subject, $message, "From: Shimmer <shimmer@$domain>")) {
				$this->Shimmer->pref->save('reset_code', $resetCode);
				return true;
			}
		}
		return false;
	}
	
	function generateResetLink($resetCode) {
		return $this->Shimmer->baseURL . "?reset&code=$resetCode";
	}
	
	function useResetCode($code) {
		$validResetCode = $this->Shimmer->pref->read('reset_code');
		if ($validResetCode && strlen($validResetCode)>0) {
			if ($validResetCode == $code) {
				$oldPass = $this->Shimmer->pref->read('pass');
				$this->updateSessionCookie(md5($oldPass));
				$this->Shimmer->pref->save('reset_code', '');
				return true;
			}
		}
		return false;
	}
	
}


?>
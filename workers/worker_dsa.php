<?php
if (!defined('Shimmer')) header('Location:/');
class SignatureWorker {
	var $Shimmer;
	
	function SignatureWorker() {
		global $Shimmer;
		$this->Shimmer =& $Shimmer;
	}
	
	// Todo: Use regex to test if keys are valid
	function keyIsValid($key, $type) {
		return true;
	}
	
	function testKeyPair() {
		
	}
	
	function handleUpload($mode, $accessKey, $inputName) {
		$wasOK = false;
		// deleteOldFiles($mode);
		$sessionKey = $_POST['key-timestamp-input'];
		if ( $_FILES[$inputName] ) {
			if ($_FILES[$inputName]['error']>0) {
				$this->Shimmer->pref->save($accessKey, array(
					'session' => $sessionKey,
					'failed'  => true,
					'reason'  => 'Failed'
				), true);
			} else {
				if ($this->Shimmer->tempFolder()) {
					$uploaddir  = $this->Shimmer->tempFolder;
					$uploadFile = $uploaddir . md5(time() . "_" . basename($_FILES[$inputName]['name'])) . ".pem";
					$worked     = false;
					if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $uploadFile)) {
						$wasOK = true;
						$worked = true;
						
						$keyOK = $this->keyIsValid(file_get_contents($uploadFile), $mode);
						
						$this->Shimmer->pref->save($accessKey, array(
							'session' => $sessionKey,
							'file'    => $uploadFile,
							'key_ok'  => $keyOK
						), true);
					}

					if (!$worked) {
						$this->Shimmer->pref->save($accessKey, array(
							'session' => $sessionKey,
							'failed'  => true,
							'reason'  => $reason),
						true);
					} else {

					}
				} else {
					$this->Shimmer->pref->save($accessKey, array(
						'session' => $sessionKey,
						'failed'  => true,
						'reason'  => 'Could not save file. Please check permissions on /tmp'),
					true);
				}
			}
		}
		return $wasOK;
	}
	
}
?>
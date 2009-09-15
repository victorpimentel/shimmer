<?php
if (!defined('Shimmer')) header('Location:/');

class RateManager {
	var $Shimmer;
	
	function RateManager() {
		global $Shimmer;
		$this->Shimmer =& $Shimmer;
	}
	
	// Returns an array of 7 values, sourced from the longest possible time period
	function rateNumbers($rateData) {
		if ($rateData) {
			$dataArray = str_replace("\'","'",unserialize($rateData));
			
			// Work out how many days should be grouped into each of the final 7 data points
			foreach ($dataArray as $index => $value) {
				if ($value['count']==0) {
					$daysPerGroup = floor($index/7);
					break;
				}
			}
			if (!isset($daysPerGroup) || $daysPerGroup==0) $daysPerGroup = floor(sizeof($dataArray)/7);

			// Now we create 7 data points, grouping multiple days into each point
			if ($daysPerGroup>0) {
				$sevenGroups = array();
				$daysUsed = $daysPerGroup*7;
				$internalIndex = 0;
				$internalCount = 0;
				for ($currentDay=0; $currentDay < $daysUsed; $currentDay++) { 
					$internalCount += $dataArray[$currentDay]['count'];
					$internalIndex += 1;
					if ($internalIndex==$daysPerGroup) {
						array_unshift($sevenGroups, $internalCount);
						$internalCount = 0;
						$internalIndex = 0;
					}
				}
				return $sevenGroups;
			}
		}
		return array(0,0,0,0,0,0,0);
	}
	
	function ratesDateArray($daysAgo=0,$count=0) {
		return array('date'=> date('Y-m-d', time()-($daysAgo*60*60*24) ), 'count'=>$count );
	}
	
	function processVersionRates($appID) {
		$app = $this->Shimmer->apps->appFromID($appID);
		if ($app) {
			$wantedDayCount = 50;
			$versions = $this->Shimmer->versions->versions($app);
			foreach ($versions as $version) {
				for ($processType=1; $processType<=2; $processType++) {
					if ($processType==1 || $processType==2) {
						$ratePrefsKey = $processType==1 ? 'download_rate' : 'user_rate';

						// Get the rates from the DB
						$rates = str_replace("\'","'",unserialize($version[$ratePrefsKey]));
						if (!$rates) {
							// Create a new array populated with the max number of days
							$rates = array();
							for ($i=$wantedDayCount; $i>0; $i--) array_unshift($rates,$this->ratesDateArray($i-1));
						} else {
							// Check if there is a gap between today and the most recent day.
							// If so, just put a new item in the start (recent end) of the array.
							$lastKnownCount = intval($rates[0]['count']);
							$daysMissing = $this->dateDifferenceFromToday(strval($rates[0]['date']));
							for ($i=$daysMissing; $i>0; $i--) {
								if ( count($rates)>=$wantedDayCount ) {
									array_splice($rates,$wantedDayCount-1);	
								}
								array_unshift($rates,$this->ratesDateArray($i-1,$lastKnownCount));
							}
						}

						// If somehow we ended up with too many days, cut a few off the end
						if ( count($rates) > $wantedDayCount ) array_splice($rates,$wantedDayCount);

						// Update today with the latest value
						$currentAmount = $processType==1 ? intval($version['download_count']) : $this->Shimmer->versions->versionUsers($app,$version);
						$rates[0]['count'] = $currentAmount;

						// Save the new rate data back into the DB
						$saveData = str_replace("'","\'",serialize($rates));
						
						$updateSql = "UPDATE `" . sql_safe($app['versions_table']) . "` SET `" . sql_safe($ratePrefsKey) . "`='" . sql_safe($saveData) . "' WHERE `version`='" . sql_safe($version['version']) . "'";
						$this->Shimmer->query($updateSql);
					}
				}
			}
		}
	}

	function dateDifferenceFromToday($mysqlDate) {
		return $this->dateDifference(date('Y-m-d'),$mysqlDate);
	}

	function dateDifference($higherDate,$lesserDate) {
		return (strtotime($higherDate)-strtotime($lesserDate))/(60*60*24);
	}
	
}


?>
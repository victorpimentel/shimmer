<?php
if (!defined('Shimmer')) header('Location:/');
class StatWorker {
	var $Shimmer;
	
	function StatWorker() {
		global $Shimmer;
		$this->Shimmer =& $Shimmer;
	}
	
	function performStatLookup($appID, $locations) {
		$cutoff = date('Y-m-d', time()-(86400*30));

		$statsHolder = array();
		$app = $this->Shimmer->apps->appFromID($appID);
		if ($app) {
			$userCount	= $this->Shimmer->stats->appUsers($app);
			$graphs		= $this->Shimmer->stats->graphsForApp($app, true);
			$boxes		= $this->Shimmer->apps->boxesForApp($app);
			$usedGraphIndexes = array();
			foreach ($boxes as $location => &$box) {
				$graphId = $box;
				if (!$this->Shimmer->stats->graphExistsForApp($app, $graphId)) {
					if ($location=="r1b1") {
						$box = "downloads";
					} else if ($location=="r2b1") {
						$box = "users";
					} else {
						if ($app['usesSparkle']) {
							if ($location=="r2b2") {
								$box = "sparkle-language";
							} else if ($location=="r2b3") {
								$box = "sparkle-os";
							}
						} else {
							$x = 0;
							while((!$this->Shimmer->stats->graphExistsForApp($app, $graphs[$x]['id']) || in_array($x, $usedGraphIndexes)) && $x<sizeof($graphs)) $x++;
							$box = $graphs[$x]['id'];
							array_push($usedGraphIndexes, $x);
						}
					}
					if ($box==false) $box="downloads";
				}
			}
			foreach ($locations as $key => $location) {
				$currentStat = array();
				if ( isset($graphs) && isset($boxes) && $location) {
					$graphId = $boxes[$location];
					
					
					if ($graphId == "users") { // USERS STATS
						$currentStat['hover'] = '<<COUNT>> user<<PLURAL>>';
						$currentStat['axis'] = array('vertical'=>'Users', 'horizontal'=>'Version');

						$versions = $this->Shimmer->versions->versions($app,array(
							'includeUserCount'	=>	true,
							'onlyLive'			=>	true,
							'flipDirection'		=>	true
						));

						if ($versions) {
							$versionPairs = array();
							foreach ($versions as $i => $version) {
								$label = $version['version'];
								if ($version['build'] && sizeof($version['build'])>0) {
									$label = '<span class="version-number-text">' . $label . '</span> <span class="build-number-tiny" title="Build ' . $version['build'] . '"> ' . $version['build'] . '</span>';
								}
								array_push($versionPairs, array(
									"value" => $version['user_count'],
									"label" => $label
								));
							}
						}

						$currentStat['name'] = 'Users';
						$currentStat['id'] = $graphId;
						$currentStat['location'] = $location;

						if (!$versionPairs) $versionPairs = array();
						$currentStat['values'] = $versionPairs;

					} else if ($graphId == "downloads") { // DOWNLOAD STATS
						$versions = $this->Shimmer->versions->versions($app,array(
							'onlyLive'			=>	true,
							'flipDirection'		=>	true
						));
						$processedVersions = array();
						foreach ($versions as $version) {
							$label = $version['version'];
							if ($version['build'] && sizeof($version['build'])>0) {
								$label = '<span class="version-number-text">' . $label . '</span> <span class="build-number-tiny" title="Build ' . $version['build'] . '"> ' . $version['build'] . '</span>';
							}
							array_push($processedVersions,array(
								"value" => $version['download_count'],
								"label" => $label
							));	
						}
						$returnArray['wasOK'] = true;
						$currentStat['name'] = 'Downloads';
						$currentStat['id'] = $graphId;
						$currentStat['location'] = $location;
						$currentStat['hover'] = '<<COUNT>> download<<PLURAL>>';
						$currentStat['axis'] = array('vertical'=>'Downloads', 'horizontal'=>'Version');
						$currentStat['values'] = $processedVersions;
					} else { // NORMAL DISTINCT STATS
						// Get Parameter based on Graph id
						foreach ($graphs as $i => $graph) {
							if ($graph['id'] == $graphId) {
								$chosenParameter			= $graph['key'];
								$currentStat['name']		= $graph['name'];
								$currentStat['id']			= $graphId;
								$currentStat['location']	= $location;
								$currentStat['axis']		= array(
									'vertical'		=> 'Users',
									'horizontal'	=> $graph['name']
								);
							
								// Tooltip
								$hoverString				= "<<COUNT>> user<<PLURAL>>";
								if ($graphId=='sparkle-language') $hoverString = '<<COUNT>> <<LABEL>> user<<PLURAL>>';
								$currentStat['hover']		= $hoverString;
								break;
							}
						}
					
						// Gather distinct data using stat key, grouping using data linked to stat id
						if ( strlen($chosenParameter)>0 ) {
							$worked = true;
							$returnArray['wasOK'] = true;
							$distinctData = $this->newGather($app,$chosenParameter,$graph,$userCount);
							if (!$distinctData) $distinctData = array();
							$currentStat['values'] = $distinctData;
						}
					}
				}
				array_push($statsHolder,$currentStat);
			}
		}

		return $statsHolder;

	}

	function definitionForCountryCode($array, $code) {
	    foreach($array as $expression) {
	        $theExpression = "/(" . implode('|',$expression['searches']) . ")/i";
	        if ( preg_match($theExpression, $code) ) {
				$continueNumberProcessing = true;

				// CHECK THAT ORIGINAL NUMBER MATCHES ALL GRAPH CONDITIONS
				if ( isset($expression['conditions']) ) {
					foreach ($expression['conditions'] as $conditionSet) {
						$condOperator	= $conditionSet['operator'];
						$condValue		= $conditionSet['value'];
						if ($condOperator=="greater" && $code <= $condValue) {
							$continueNumberProcessing = false;
							break;
						} else if ($condOperator=="less" && $code >= $condValue) {
							$continueNumberProcessing = false;
							break;
						} else if ($condOperator=="equal" && $code != $condValue) {
							$continueNumberProcessing = false;
							break;
						} else if ($condOperator=="greater_or_equal" && $code < $condValue) {
							$continueNumberProcessing = false;
							break;
						} else if ($condOperator=="less_or_equal" && $code > $condValue) {
							$continueNumberProcessing = false;
							break;
						}
					}
				}

				// we only return a set if it matches the regex, and it matches the numerical conditions
				if ($continueNumberProcessing) return $expression;
			}
	    }
	    return null;
	}

	function countryCodesForProperName($array, $name) {
	    foreach($array as $common) {
	        if ( $common['label']== $name ) return $common['searches'];
	    }
	    return array($name);
	}

	function newGather($app,$fieldName,$graphDefinition,$userCount=0) {
		$sql = "SELECT DISTINCT `" . sql_safe($fieldName) . "` as value, count(*) as count FROM `" . sql_safe($app['users_table']) . "` WHERE `last_seen`>='" . sql_safe( date('Y-m-d', time()-(86400*30)) ) . "' GROUP BY `" . sql_safe($fieldName) . "` ORDER BY count(*) DESC";
		$rawValues = $this->Shimmer->query($sql);
		$returnArray = array();
		if ($rawValues) {
			if (sizeof($graphDefinition)>0) {
				$replacementDefinitions = $graphDefinition['values'];
				$runningTotal = 0;

				while ($row = mysql_fetch_array($rawValues) ) {
				    if (strlen($row['value']) > 0) {
						$targetSet = $this->definitionForCountryCode($replacementDefinitions,$row['value']);

						if ( isset($targetSet) ) { // if the raw value matched a regex
							if ($graphDefinition['type']==1) { // text grouping
								$uniqueLabel = $targetSet['label'];
							} else if ($graphDefinition['type']==2) { // number grouping
								$uniqueLabel = intval($row['value']);
								if ($uniqueLabel > 0) {
									$continueNumberProcessing = true;

									// PERFORM ALL NUMBER OPERATIONS
									foreach ($targetSet['modifications'] as $modificationSet) {
										$modOperator	= $modificationSet['operator'];
										$modValue		= $modificationSet['value'];
										if ($modOperator=="divide") {
											$uniqueLabel /= $modValue;
										} else if ($modOperator=="multiply") {
											$uniqueLabel *= $modValue;
										} else if ($modOperator=="plus") {
											$uniqueLabel += $modValue;
										} else if ($modOperator=="minus") {
											$uniqueLabel -= $modValue;
										}
									}

									// ROUND THE NUMBER TO x DECIMAL PLACES
									$roundAccuracy = $targetSet['round'];
									if ( isset($roundAccuracy) ) {
										$uniqueLabel = number_format($uniqueLabel, $roundAccuracy);
									}

									// APPEND A UNIT STRING
									$unitString = $targetSet['unit'];
									if ( isset($unitString) ) {
										$uniqueLabel .= " $unitString";
									}
								}
							}
						} else { // if no regex matched the raw value
							$uniqueLabel = $row['value'];
						}

						if ($graphDefinition['type']!=2 || ($graphDefinition['type']==2 && $uniqueLabel > 0)) {
							$count = intval($row['count']);
							$this->insertDataPair($returnArray, array( "label" => $uniqueLabel, "value" => $count ) );
							$runningTotal += $count;
						}
				    }
				}

				usort($returnArray, array($this, 'compareDistinctValueSet'));
				if ($userCount>0) {
					$unassignedValues = $userCount - $runningTotal;
					if ($runningTotal>0 && $unassignedValues>0) array_unshift($returnArray, array( "label" => "Other", "value" => $unassignedValues ));
				}
			}
		}
		return $returnArray;
	}

	function compareDistinctValueSet($a, $b) {
		return $a['value'] > $b['value'];
	}

	function insertDataPair(&$theArray, $dataPair) {
		$theValue = $dataPair['value'];
		$theLabel = $dataPair['label'];

		// We see if the array already contains the same label. This saves us from having to look for a new insertion index.
		foreach ($theArray as $i => $currentPair) {
			if ($currentPair['label']==$theLabel) {
				$found = true;
				$theArray[$i]['value'] += $theValue;
			}
		}

		// If the label is new, insert a new item into the sorted array
		if (!$found) {
			$currentIndex = 0;
			$insertIndex = -1;
			$found = false;

			while($currentIndex < sizeof($theArray) && $found==false) {
				$currentValue = $theArray[$currentIndex]['value'];
				if ($currentValue >= $theValue) {
					$insertIndex=$currentIndex;
					$found = true;
				}
				$currentIndex++;
			}

			if ($insertIndex<0) $insertIndex = sizeof($theArray);

			$start = array_slice($theArray, 0, $insertIndex); 
			$end = array_slice($theArray, $insertIndex);
			array_push($start,$dataPair);
			$theArray = array_merge($start, $end);
		}
	}
}
?>
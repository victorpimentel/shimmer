versions = {
	// Handy variables
	isReloadingVersions: false,
	isAnimatingNewVersionForm: false,
	isLoadingVersionDetails: false,
	selected_version: 0,
	reloadVersionsOnCancel: false,
	isFlippingLive: false,
	
	toggleVersionRates: function() {
		$('versions_content').toggleClassName('verbose');
	},
	
	openSelectedVersion: function() {
		if ( $('row1').visible() ) {
			var theCommand = $$("#versions_row_" + this.selected_version + ' div')[0].readAttribute("onclick");
			Shimmer.util.runInlineCommand(theCommand);
		}
	},

	flipSelectedVersionLive: function() {
		if ( $('row1').visible() && !$('versions_edit').visible() ) {
			var theTimestamp = $$("#versions_row_" + this.selected_version)[0].readAttribute("alt");
			var isLive = $$("#versions_row_" + this.selected_version + " a.switchlive").length==0;
			if (theTimestamp) versions.setVersionLive(apps.appsHub.currentAppID,theTimestamp,isLive?0:1);
		}
	},
	
	deleteSelectedVersion: function() {
		if ( $('row1').visible() ) {
			var theCommand = $("delete_version_button_" + this.selected_version).readAttribute("onclick");
			Shimmer.util.runInlineCommand(theCommand);
		}
	},

	alterSelectedVersion: function(direction) {
		if ( $('row1').visible() ) {
			this.selected_version = parseInt(this.selected_version) + parseInt(direction);
			if ($('versions_edit').visible()) this.openSelectedVersion();
			this.updateSelectedVersionClasses();
		}
	},

	updateSelectedVersionClasses: function() {
		var versionsCount = parseInt($$('div#versions-table div.versions-row').length);
		if (this.selected_version<0) {
			this.selected_version = versionsCount-1;
		} else if (this.selected_version > versionsCount-1 ) {
			this.selected_version = 0;
		}

		var allRows = $$("div#versions-table div.versions-row");
		allRows.each(function(versions_row, index) {
			if (index==versions.selected_version) {
				versions_row.addClassName("selected_version");
			} else {
				versions_row.removeClassName("selected_version");
			}
		});
		
		versionsUI.table.scroll.content.ensureRowIsShown(versions.selected_version);
	},
	
	createNewVersion: function(appID,newVersion,newBuild,downloadURL,bytes,signature,notes) {
		clearTimeout(apps.refreshVersionsTimer);
		new Ajax.Request('?ajax', {
			method:'post',
			parameters: {
							action:         'app.add.version',
							appID:          appID,
							version_number: newVersion,
							build_number:   newBuild,
							download_url:   downloadURL,
							bytes:          bytes,
							signature:      signature,
							release_notes:  notes},
			onSuccess: function(transport) {
				versions.reloadVersionsForApp(apps.appsHub.currentAppID, true);
				versionsUI.hideNewVersionForm();
				notify.update('New version added successfully', 10);
			}
	    });
	},
	
	reloadVersionsForApp: function(appID,force) {
		if (typeof force == "undefined") force=false;
		if (this.isReloadingVersions == false && (!($('versions_edit').visible()) || force) ) {
			clearTimeout(apps.refreshVersionsTimer);
			this.isReloadingVersions = true;
			new Ajax.Request('?ajax', {
				method:'get',
				parameters: {action:'get.data.many', appID:appID, versions:true, graphdata:true},
				onSuccess: function(transport) {
					var response = transport.responseText;
					var theResponse = JSON.parse(response);
					if (theResponse.wasOK) {
						if (theResponse.apps && theResponse.apps.length>0) {
							var chosenApp = theResponse.apps[0];
							versions.reloadVersionsForApp_handleResponse(chosenApp.allVersions);
							if (chosenApp.stats) processGraphData(chosenApp.stats);
						}
					}
				},
				onFailure: function() {
					versions.isReloadingVersions = false;
					apps.refreshReloadTimer(2);
				}
			});
		} else apps.refreshReloadTimer();
	},
	
	reloadVersionsForApp_handleResponse: function(allVersions) {
		var newCode = '';

		if ($('welcome').visible()) {
			Shimmer.hideWelcomeArea(function() { reloadVersionsForApp_handleResponse(allVersions) });
			return false;
		}

		if (allVersions.length > 0) {
			newCode += '<div id="versions_table_container"' + (!Shimmer.loaded ? ' style="display:none;"' : '') + '>';
			newCode += '<div id="versions-table">';
			for (var i=0;i<allVersions.length;i++) {
				var currentJsonVersion = allVersions[i];
				var theVersion = currentJsonVersion.version;
				var theBuild   = (currentJsonVersion.build && currentJsonVersion.build.length > 0) ? currentJsonVersion.build : false;

				var now = new Date();
				var isLive        = currentJsonVersion.live==true;
				var dateString    = null;
				var serverSeconds = parseInt(currentJsonVersion.date);
				if (isLive) {
					var theDate      = new Date(serverSeconds*1000);
					var extraSeconds = theDate.getTimezoneOffset()*60;
					dateString       = doRelativeDate(theDate.getFullYear(),theDate.getMonth()+1,theDate.getDate(),theDate.getHours(),theDate.getMinutes(),theDate.getSeconds()+extraSeconds);
				} else {
					dateString       = '<a href="#OnAir" class="switchlive publishNow" onclick="javascript:versions.setVersionLive(\'' + apps.appsHub.currentAppID + '\',\'' + serverSeconds + '\',1);return false;">Publish Now <img src="img/light_bulb.png" border="0"></a>';
				}

				var modifiedDate                    = new Date(parseInt(currentJsonVersion.modified)*1000);
				var versionRecency                  = now.getTime() - modifiedDate.getTime();
				var newVersionClassAttribute        = ( versionRecency < 10000 ? ' newlyCreatedVersion' : '' );
				var mostRecentVersionClassAttribute = ( i==0 ? ' most-recent' : '' );
				
				newCode += '<div class="versions-row' + newVersionClassAttribute + mostRecentVersionClassAttribute + '" id="versions_row_' + i + '" alt="' + serverSeconds + '">';

				var userCount     = currentJsonVersion.users;
				var userRate      = currentJsonVersion.userRate;
				var downloadCount = currentJsonVersion.downloads;
				var downloadRate  = currentJsonVersion.downloadRate;
				var openCode      = 'onclick="javascript:void(versionsUI.openEditPanel(\'' + apps.appsHub.currentAppID + '\',\'' + serverSeconds + '\',\'' + i + '\'));return false;"';
				
				newCode += '<div class="version-cell version-version" alt="' + theVersion + '" ' + openCode + '>' + theVersion + (theBuild ? ('<small class="build-number-small">' + theBuild + '</small>') : '') + '</div>';
				newCode += '<div class="version-cell version-published" ' + (isLive?openCode:'') + '>' + dateString + '</div>';
				newCode += '<div class="version-cell version-download-count" ' + openCode + '>' + downloadCount + '</div>';
				newCode += '<div class="version-cell version-download-graph" ' + openCode + '> <canvas height="16" width="50" id="download_spark_' + i + '" class="sparkline"></canvas></div>';
				newCode += '<div class="version-cell version-user-count" ' + openCode + '>' + userCount + '</div>';
				newCode += '<div class="version-cell version-user-graph" ' + openCode + '> <canvas height="16" width="50" id="user_spark_' + i + '" class="sparkline"></canvas></div>';
				newCode += '<div class="version-cell version-delete-cell"><a href="#DeleteVersion" onclick="javascript:void(versionsUI.deleteVersion(\'' + apps.appsHub.currentAppID + '\',\'' + serverSeconds + '\',\'' + theVersion + '\'));return false;" class="version_button delete_version_button" id="delete_version_button_' + i + '" title="Delete ' + apps.currentApp + ' ' + theVersion + '">&nbsp;</a></div>';
				newCode += '</div>';
			}
			newCode += '</div></div>';
			versionsUI.table.setContent(newCode);
			var maxSparkPoints = 7;
			for (var i=0;i<(allVersions.length<10?allVersions.length:10);i++) {
				var currentJsonVersion = allVersions[i];
				for (var sparkType=0; sparkType < 2; sparkType++) {
					var rateNumbers = (sparkType==0 ? currentJsonVersion.downloadRateNumbers : currentJsonVersion.userRateNumbers);
					if (rateNumbers) {
						if (rateNumbers.length>maxSparkPoints) {
							// Cut out some items to show only desired amount in Sparkline
							rateNumbers.splice(0,rateNumbers.length-maxSparkPoints);
						}
						var sparkIdentifier = (sparkType==0 ? 'download' : 'user') + '_spark_' + i;
						drawSparkline($(sparkIdentifier), 50, 16, rateNumbers);
					}
				};
			}
			$('no-versions-holder').hide();
			$('version-headers').show();
			$('scroll-cutoff').show();
		} else {
			$('version-headers').hide();
			$('scroll-cutoff').hide();
			$('no-versions-holder').show();
		}

		if (!Shimmer.loaded) {
			var theTable = $$('#box_versions div#versions_table_container')[0];
			if (theTable) theTable.show();
		}

		var newRow = document.getElementsByClassName('newlyCreatedVersion')[0];
		if (newRow) {
			// the afterFinish method is needed to clear the scriptaculous style attribute, so that :hover effects still work.
			new Effect.Highlight(newRow, { duration:2, afterFinish: function(theNotification) {
				var theElement = theNotification.element;
				Shimmer.util.removeStyleTag(theElement);
			} });
		}
		versions.updateSelectedVersionClasses();
			
		versions.isReloadingVersions = false;
		apps.refreshReloadTimer();

		setTimeout('versionsUI.table.scroll.refresh()',1);
		
		if (!Shimmer.loaded) {
			setTimeout('versionsUI.table.scroll.constrainer.setWantedRows(10);versionsUI.table.scroll.constrainer.go();',100);
		}
		
		Shimmer.loaded = true;
	},
	
	saveEditedVersionInfo: function(appID,referenceTimestamp,newTimestamp,newVersionNumber,newBuildNumber,downloadURL,bytes,signature,notes) {
		new Ajax.Request('?ajax', {
			method:'post',
			parameters: {
							action:              'app.update.version',
							appID:               appID,
							reference_timestamp: referenceTimestamp,
							new_timestamp:       newTimestamp,
							version_number:      newVersionNumber,
							build_number:        newBuildNumber,
							download_url:        downloadURL,
							bytes:               bytes,
							signature:           signature,
							release_notes:       notes},
			onSuccess: function(transport) {
				versionsUI.hideNewVersionForm();
				versions.reloadVersionsForApp(apps.currentApp,true);
				notify.update('Version updated successfully',10);
			}
		});
	},
	
	saveVersionButtonClicked: function() {
		if ($('versions_edit').visible()) {
			var referenceTimestamp = $('field_hidden_ref_timestamp').value;
			var       newTimestamp = $('field_hidden_updated_timestamp').value;
			var              appID = apps.appsHub.currentAppID;
			var   newVersionNumber = $('field_version').value;
			var     newBuildNumber = $('field_build').value;
			var        downloadURL = $('field_url').value;
			var              bytes = $('field_size').value;
			var          signature = $('field_signature').value;
			var              notes = $('field_notes').value;

			if ( $('field_build').readAttribute('placeholder').indexOf(newBuildNumber) > -1 ) newBuildNumber = "";

			if (referenceTimestamp.length>0) { //edit
				if ($('versions_edit').visible()) {
					versions.saveEditedVersionInfo(appID,referenceTimestamp,newTimestamp,newVersionNumber,newBuildNumber,downloadURL,bytes,signature,notes);
				}
			} else { //new
				versions.createNewVersion(appID,newVersionNumber,newBuildNumber,downloadURL,bytes,signature,notes);
			}
		}
	},
	
	mostRecentKnownVersion: function() {
		var allCells = $$('td.version_td');
		if (allCells.length>0) {
			var altValue = allCells[0].readAttribute('alt');
			if (altValue && altValue.length>0) return altValue;
		}
		return null;
	},
	
	setVersionLive: function(appID,timestamp,isLive) {
		if (this.isReloadingVersions==false && this.isFlippingLive==false) {
			this.isFlippingLive = true;
			new Ajax.Request('?ajax', {
				method: 'post',
				parameters: { action: "version.live.set", appID:appID, ref_timestamp:timestamp, is_live:isLive },
				onSuccess: function(transport) {
					var response = transport.responseText;
					var theResponse = JSON.parse(response);
					if (theResponse.wasOK) {
						if ( !$('versions_edit').visible() ) {
							versions.reloadVersionsForApp(apps.appsHub.currentAppID);
						}
					}
					versions.isFlippingLive = false;
				}
			});
		}
	},
	
	nextPredictedVersionNumber: function() {
		var lastVersion	= this.mostRecentKnownVersion();
		if (lastVersion) {
			var lastParts	= lastVersion.split(".");
			var lastPart	= lastParts[lastParts.length-1];
			if ( lastPart.match(/^[0-9]+$/) ) {
				delete lastParts[lastParts.length-1];
				return lastParts.join('.') + (parseInt(lastPart)+1);
			}
		} else lastVersion = "1.0";
		return lastVersion;
	}
	
	
	
}
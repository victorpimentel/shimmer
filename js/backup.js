backup = {
	isUploading: false,
	session: false,
	
	uploadFile: function() {
		if ($('backup_import_file').value.length>0) {
			notify.update('Uploading backup... <a href="#CancelUpload" onclick="backup.cancelUpload();return false;">cancel</a>', 0, 'uploading-backup');
			backup.versionPicker.close(function() {
				backup.session = Shimmer.util.timestamp();
				$('backup_session').value = backup.session;
				$('backup_import_form').submit();

				backup.stallTimer.start();
				backup.setIsUploading(true);
			});
		}
	},
	
	cancelUpload: function() {
		backup.setIsUploading(false);
		notify.update('Upload cancelled', 5);
	},
	
	setIsUploading: function(aBool) {
		if (backup.isUploading != aBool) {
			backup.isUploading = aBool;
			aBool ? Ajax.activeRequestCount++ : Ajax.activeRequestCount--;
			Shimmer.progress.updateLoadingCountDisplay();
			aBool ? Form.Element.disable($('backup_import_file')) : Form.Element.enable($('backup_import_file'));
			if (!aBool) {
				backup.stallTimer.stop();
				notify.hide('uploading-backup');
			}
		}
	},
	
	restartFileUpload: function() {
		backup.setIsUploading(false);
		$('backup_iframe').src = "about:none";
		backup.uploadFile();
	},
	
	// VERSION PICKER
	versionPicker: {
		isAnimating: false,
		
		selectedApps: function() {
			var apps = new Array();
			var allCheckboxes = $$('#select-backup-apps input');
			if (allCheckboxes.length>0) {
				allCheckboxes.each( function(checkbox) {
					if (checkbox.checked) apps.push(checkbox.readAttribute('value'));
				});
			}
			return apps;
		},
		
		setApps: function(apps) {
			var code = '<table id="select-backup-apps-table" cellpadding="0" cellspacing="0">';
			for (var i=0; i<apps.length; i++) {
				var currentApp		= apps[i];
				var backupAppName	= currentApp.name;
				var disabled		= !currentApp.valid;
				
				code += '<tr class="' + (i%2!=0 ? 'alternate' : '') + (i==0 ? ' first' : '') + '">';
				code += '  <td width="1%" class="checkbox"><input type="checkbox" name="backup_' + backupAppName + '" id="backup_' + backupAppName + '" class="select_app_checkbox" value="' + backupAppName + '" ' + (disabled ? 'DISABLED' : 'CHECKED') + '></td>';
				code += '  <td class="import-label' + (disabled ? ' disabled' : '') + '"><label for="backup_' + backupAppName + '">' + backupAppName + '</label></td>';
				code += '</tr>';
			}
			code += '</table>';
			
			backup.versionPicker.show(code, function() {
				var allCheckboxes = $$('#select-backup-apps input');
				if (allCheckboxes.length>0) {
					allCheckboxes.each( function(checkbox) {
						checkbox.observe('change', function() {
							backup.versionPicker.tally.update();
						});
					});
					allCheckboxes[0].focus();
				}
				backup.versionPicker.tally.update();
			});
		},
		
		show: function(content, callback) {
			if (typeof content	== "undefined")	content		= false;
			if (typeof callback	== "undefined")	callback	= false;
			
			var showFunction = function() {
				if (content) $('select-backup-apps-list').innerHTML = content;
				new Effect.BlindDown($('select-backup-apps'), {
					duration:0.5,
					afterFinish: function() {
						if (callback) callback();
					}
				})
			}
			
			if ($('select-backup-apps').visible()) {
				backup.versionPicker.close(showFunction);
			} else {
				showFunction();
			}
		},
		
		close: function(callback) {
			if (typeof callback	== "undefined")	callback = false;
			if ($('select-backup-apps').visible()) {
				new Effect.BlindUp($('select-backup-apps'), {
					duration:0.5,
					afterFinish: function() {
						if (callback) callback();
					}
				});
			} else {
				if (callback) callback();
			}
		},
		
		tally: {
			update: function(fade, callback) {
				backup.versionPicker.tally.showSentence(function() {
					if (typeof fade	== "undefined")	fade = true;
					if (typeof callback	== "undefined")	callback = false;
					var allCheckboxes = $$('#select-backup-apps input');
					var tally = 0;
					if (allCheckboxes.length>0) {
						allCheckboxes.each( function(checkbox) {
							if (checkbox.checked) tally++;
						});
					}
				
					if (fade) {
						backup.versionPicker.tally.fade(function() {
							backup.versionPicker.tally.setTally(tally);
							backup.versionPicker.tally.appear(function() {
								backup.versionPicker.tally.update(false);
								if (callback) callback();
							});
						});
					} else {
						backup.versionPicker.tally.setTally(tally);
						if (callback) "Calling callback " + callback;
						if (callback) callback();
					}
				});
			},
			
			setTally: function(tally) {
				$('backup-app-count-number').innerHTML = (tally==0 ? 'No' : tally) + ' app' + (tally==1 ? '' : 's');
			},
			
			fade: function(callback) {
				$('backup-app-count-number').setStyle({opacity:'0.4'});
				if (callback) callback();
			},
				
			appear: function(callback) {
				if (backup.versionPicker.isAnimating == false) {
					backup.versionPicker.isAnimating = true;
					if (typeof callback	== "undefined")	callback = false;
					fadeParams = {duration: 0.5, to:1.0};
					fadeParams.afterFinish = function() {
						backup.versionPicker.isAnimating = false;
						if (callback) callback();
					}

					new Effect.Appear('backup-app-count-number', fadeParams);
				}
			},
			
			showSentence: function(callback) {
				if (typeof callback	== "undefined")	callback = false;
				if (!parseInt($('backup-app-count').getStyle('opacity'))==1) {
					slideParams = {duration: 0.5};
					if (callback) slideParams.afterFinish = callback;
					new Effect.Appear($('backup-app-count'), slideParams);
				} else {
					if (callback) callback();
				}
			},
			
			hideSentence: function(callback) {
				if (typeof callback	== "undefined")	callback = false;
				if (!parseInt($('backup-app-count').getStyle('opacity'))==0) {
					slideParams = {duration: 0.2, to:0.5};
					if (callback) slideParams.afterFinish = callback;
					new Effect.Fade($('backup-app-count'), slideParams);
				} else {
					if (callback) callback();
				}
			}
		}
	},
	
	// POLL CHECKER
	// After the upload has completed, poll the server until the app list is ready
	poll: {
		timer: false,
		start: function() { //checkForBackupUpdates
			backup.poll.stopTimer();
			backup.poll.setTimeout('backup.poll.go();',4000);
		},
		stopTimer: function() {
			clearTimeout(backup.poll.timer);
			backup.poll.timer = false;
		},
		
		go: function() { //pollForBackupUpdates
			if (backup.isUploading && backup.session) {
				backup.poll.stopTimer();
				new Ajax.Request("?ajax&type=backup", {
					method: 'get',
					parameters: { action:"backup.upload.check", backup_session:backup.session },
					onSuccess: function(transport) {
						var response = transport.responseText;
						var theResponse = JSON.parse(response);
						if (theResponse.wasOK) {
							if (theResponse.updated) {
								notify.hide();
								if (!theResponse.failed) {
									var allApps = theResponse.apps;
									if (allApps) {
										backup.stallTimer.stop();
										notify.update('Found ' + allApps.length + ' app' + (allApps.length==1 ? '' : 's') + ' to be imported',5);
										backup.versionPicker.setApps(allApps);
										backup.setIsUploading(false);
										preferences.slideToPane(4);
									} else backup.poll.start();
								} else {
									// FAILED
									backup.stallTimer.stop();
									notify.update(theResponse.reason,30);
									backup.setIsUploading(false);
								}
							} else backup.poll.start();
						}
					}
				});
			}
		}
		
	},
	
	
	
	// STALL TIMER
	// Set of functions to display a restart message if the file upload takes a while
	stallTimer: {
		timer: false,
		
		start: function() {
			backup.stallTimer.stop();
			backup.stallTimer.timer = setTimeout('backup.stallTimer.showStalledMessage();',10*1000);
		},
		
		stop: function() {
			clearTimeout(backup.stallTimer.timer);
			backup.stallTimer.timer = false;
		},
		
		showStalledMessage: function() {
			notify.update('Upload taking a while? Maybe <a href="javascript:backup.restartFileUpload();">restart</a> or <a href="#CancelUpload" onclick="backup.cancelUpload();return false;">cancel</a>',0);
		}
	}
	
}
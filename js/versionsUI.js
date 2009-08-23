versionsUI = {
	toggleNewVersionForm: function() {
		if (versions.isAnimatingNewVersionForm == false) {
			if ( !$('versions_edit').visible() ) {
				this.showEmptyNewVersionForm();
			} else {
				this.hideNewVersionForm();
			}
		}
	},
	
	clearNewVersionForm: function() {
		Shimmer.util.setElementValue( $('addOrEditTitle'), apps.currentApp + " " + this.versionFieldCode("1.0",""));
		Shimmer.util.setElementValue( $('field_hidden_ref_timestamp') ,'');
		Shimmer.util.setElementValue( $('field_hidden_updated_timestamp') ,'');
		Shimmer.util.setElementValue( $('field_version') ,'x.x');
		Shimmer.util.setElementValue( $('field_url') ,'');
		Shimmer.util.setElementValue( $('field_size') ,'');
		Shimmer.util.setElementValue( $('field_signature') ,'');
		Shimmer.util.setElementValue( $('field_notes') ,'');

		textSizer.add( $("field_version") );
		textSizer.add( $("field_build")   );
	},

	versionFieldCode: function(defaultVersion,defaultBuild) {
		return '<input type="text" style="display:inline;width:100px;" value="' + defaultVersion + '" id="field_version" class="number" name="version" placeholder="x.x" onclick="javascript:this.select();"> <input type="text" style="display:inline;width:100px;font-size:10px;" value="' + defaultBuild + '" id="field_build" class="number" name="build" placeholder="build number" onclick="javascript:this.select();">';
	},
	
	showEmptyNewVersionForm: function() {
		$('editDateContainer').hide();
		$('toggle_live_icon').hide();
		this.clearNewVersionForm();
		$('field_version').value = versions.nextPredictedVersionNumber();
		this.showNewVersionForm();
	},

	showNewVersionForm: function() {
		if (!versions.isAnimatingNewVersionForm) {
			versions.isAnimatingNewVersionForm = true;
			$('preview-area').hide();
			$('field_notes').show();
			new Effect.Parallel([
				new Effect.Fade		( $('new-version-button'), { sync:true }),
				new Effect.Morph	( $('versions_footer'),	   { sync:true, style:'background-color:#FFFFCC;'}),
				new Effect.BlindUp  ( $('versions_container'), { sync:true } ),
				new Effect.BlindDown( $('versions_edit'),      { sync:true } )
			], {duration: 1.0, afterFinish:versionsUI.toggleNewVersionFormAnimationFinished});
		}
	},

	hideNewVersionForm: function() {
		if (!versions.isAnimatingNewVersionForm && $('versions_edit').visible() ) {
			versions.isAnimatingNewVersionForm = true;
			$('field_hidden_ref_timestamp').value = "";
			new Effect.Parallel([
				new Effect.Appear	( $('new-version-button'), { sync:true }),
				new Effect.Morph	( $('versions_footer'),	   { sync:true, style:'background-color:#FFFFFF;'}),
				new Effect.BlindDown( $('versions_container'), { sync: true } ),
				new Effect.BlindUp( $('versions_edit'), { sync:true } )
			], {
				duration: 0.8,
				afterFinish: function() {
					$('preview-area').innerHTML = "";
					$('field_notes').innerHTML = "";
					versionsUI.toggleNewVersionFormAnimationFinished();
				}
			});
		}
		versionsUI.autoload.setActive(false);
	},
	
	toggleNewVersionFormAnimationFinished: function() {
		versions.isAnimatingNewVersionForm = false;
		if ( $('versions_edit').visible() ) {
			$('field_version').focus();
			$('field_version').select();
			$('field_version').fire('textSizer:resize');
		} else {
			$('versions_edit').hide();
			Shimmer.blurAll();
			if (versions.reloadVersionsOnCancel) versions.reloadVersionsForApp(apps.currentApp);
		}
	},
	
	togglePreviewArea: function() {
		if ( $('field_notes').visible() ) {
			$('preview-area').innerHTML = $('field_notes').value;
			$('field_notes').hide();
			$('preview-area').show();
			$('preview-switch').innerHTML = 'Edit Release Notes';
			$('notes_status').innerHTML = 'You are viewing a preview of the release notes.';
		} else {
			$('preview-area').hide();
			$('field_notes').show();
			$('preview-switch').innerHTML = 'Preview Release Notes';
			$('notes_status').innerHTML = 'You are editing the release notes.';
			$('field_notes').focus();
			$('notes_container').addClassName('editing');
		}
	},
	
	openEditPanel: function(appName,timestamp, versionIndex) {
		if (versions.isLoadingVersionDetails==false && versions.isAnimatingNewVersionForm==false && $('field_hidden_ref_timestamp').value != timestamp) {
			versions.isLoadingVersionDetails = true;
			versions.selected_version = versionIndex;
			versions.updateSelectedVersionClasses();

			new Ajax.Request('?ajax', {
				method:'get',
				parameters: {
					action: 'app.version.get.values',
					app_name: appName,
					timestamp: timestamp
				},
				onSuccess: versionsUI.openEditPanel_handleResponse,
				onFailure: function() { versions.isLoadingVersionDetails = false; }
			});
		}
	},
	
	openEditPanel_handleResponse: function(transport) {
		var response = transport.responseText;
		var theResponse = JSON.parse(response);

		var versionInfo = theResponse.versionInfo;

		var isLive = versionInfo.live=="1";
		versionsUI.setToggleSwitchState(isLive,versionInfo.date);
		$('toggle_live_icon').show();
		versions.reloadVersionsOnCancel = false;

		var theDate = new Date(parseInt(versionInfo.date) * 1000);
		var dateString = versionsUI.formatVersionDateValue(theDate.getDate(),theDate.getMonth(),theDate.getFullYear());

		calendar.setChosenDate(theDate);
		calendar.newDraw();

		$('editDateLabel').innerHTML = dateString;
		$('addOrEditTitle').innerHTML = '<span class="app">' + apps.currentApp + '</span> ' + versionsUI.versionFieldCode("0.0","");

		$('field_version').value              	  = versionInfo.version;
		$('field_build').value                	  = versionInfo.build;
		$('field_hidden_ref_timestamp').value 	  = versionInfo.date;
		$('field_hidden_updated_timestamp').value = versionInfo.date;
		$('field_notes').value                	  = versionInfo.notes;
		$('field_url').value                  	  = versionInfo.download;
		$('field_size').value                 	  = versionInfo.bytes;
		$('field_signature').value            	  = versionInfo.signature;

		textSizer.add( $("field_version") , "App Name" );
		textSizer.add( $("field_build")   , "Build Number" );

		$('editDateContainer').show();

		versions.isLoadingVersionDetails = false;
		versionsUI.showNewVersionForm();
	},
	
	setToggleSwitchState: function(stateBool,date) {
		var toggleLive = $('toggle_live_icon');
		var toggleLiveCode = 'javascript:versions.setVersionLive(\'' + apps.currentApp + '\',\'' + date + '\',' + (stateBool?0:1) + ');versionsUI.setToggleSwitchState(' + !stateBool + ',' + date + ');return false;';
		toggleLive.writeAttribute('class','switchlive ' + (stateBool ? 'switchlive_On' : 'switchlive_Off') );
		toggleLive.writeAttribute('onclick',toggleLiveCode);
		toggleLive.writeAttribute('title',stateBool ? 'This version is live' : 'This version is not live');
		versions.reloadVersionsOnCancel = true;
	},
	
	parseNewVersionDateValue: function(day,date,month,year) {
		$('editDateLabel').innerHTML = versionsUI.formatVersionDateValue(day,date,month,year);
		$('field_hidden_updated_timestamp').value = Shimmer.util.timestamp();
		$('editDateCalendar').setStyle({top:'-10000px'});
		setTimeout("$('editDateCalendar').setStyle({top:'0'});",100)
	},

	formatVersionDateValue: function(date, month, year) {
		return date + " " + monthFromNumber(month) + " " + year;
	},
	
	deleteVersion: function(appName,timestamp,version) {
		if ( confirm("Are you sure you want to delete " + appName + " " + version + "?") ) {
			clearTimeout(apps.refreshVersionsTimer);
			new Ajax.Request('?ajax', {
				method:'get',
				parameters: {
								action:           'app.delete.version',
								app_name:         appName,
								delete_timestamp: timestamp,
								delete_version:   version},
				onSuccess: versionsUI.deleteVersion_handleResponse
			});
			if ( $('field_hidden_ref_timestamp').value == timestamp ) versionsUI.hideNewVersionForm();
		}
	},

	deleteVersion_handleResponse: function(transport) {
		var response = transport.responseText;
		var theResponse = JSON.parse(response);

		if (theResponse.wasOK) {
			notify.update('Version deleted',10);
			var deletedVersion = theResponse.deletedVersion;
			var allRows = $$('#versions-table div.versions-row');
			for (var i=0; i<allRows.length; i++) {
				var currentRow = allRows[i];
				var currentRowCells = currentRow.select("div.version-version");
				if (currentRowCells.length > 0) {
					var currentRowVersion = currentRowCells[0].readAttribute('alt');
					if (currentRowVersion == deletedVersion) {
						currentRow.style.background = "#FFCCCC";
						new Effect.Fade(currentRow, {
							// afterFinish: function() { versions.reloadVersionsForApp(apps.currentApp); }
						});
					}
				}
			}
		}
	}
	
		
}
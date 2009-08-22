// PREFERENCES: Control/modify global Shimmer settings

preferences = {
	show: function() {
		// possibly load some required data, then show the form
		new Ajax.Request('?ajax&type=pref', {
			method:'get',
			parameters: {
				action:	'preferences.get.values'
			},
			onSuccess: function(transport) {
				var response = transport.responseText;
				var theResponse = JSON.parse(response);
				if (theResponse.wasOK) {
					preferences.showForm(theResponse.email, theResponse.pass, theResponse.baseURL);
				}
			}
		});
	},
	
	slideToPane: function(paneNumber) {
		$$('div.pref-pane').each( function(pane) {
			pane.setStyle({visibility:'visible'});
		});
		var leftValue = (paneNumber-1)*-335;
		new Effect.Move($('pref-pane-holder'), {
			x:			leftValue,
			y:			0,
			mode:		'absolute',
			duration:	0.5,
			afterFinish:function() {
				// we hide all other panes so they can't be tabbed to using the keyboard
				var targetPane = 'pref-pane-' + paneNumber;
				$$('div.pref-pane').each( function(pane) {
					pane.setStyle({visibility:(pane.readAttribute('id')==targetPane ? 'visible' : 'hidden')});
				});
			}
		});
		$$('#focus_content ul#pref-tabs li.pref-tab').each( function(tab, index) {
			if (index+1 != paneNumber) {
				tab.removeClassName('active');
			} else {
				tab.addClassName('active');
			}
		});
	},
	
	showForm: function(email, password, baseURL) {
		// var code = '<form class="stat_edit_form" action="?ajax" target="_new">';
		var code = '';
		code += '  <ul id="pref-tabs">';
		code += '    <li class="pref-tab active" id="pref-tabs-account"><a href="#Account" onclick="preferences.slideToPane(1);return false;"><img src="img/tab_account.png" /> Account</a></li>';
		code += '    <li class="pref-tab" id="pref-tabs-config"><a href="#Configuration" onclick="preferences.slideToPane(2);return false;"><img src="img/gear.png" /> Configuration</a></li>';
		code += '    <li class="pref-tab" id="pref-tabs-plugins"><a href="#Plugins" onclick="preferences.slideToPane(3);return false;"><img src="img/tab_plugin.png" /> Plugins</a></li>';
		code += '    <li class="pref-tab last" id="pref-tabs-backup"><a href="#Backup" onclick="preferences.slideToPane(4);return false;"><img src="img/tab_backup.png" /> Backup</a></li>';
		code += '  </ul>';
		code += '  <div id="stat_edit">';
		code += '  <div id="pref-pane-cutoff"><div id="pref-pane-holder">';

		// PANE #1: ACCOUNT

		code += '    <div class="pref-pane" id="pref-pane-1">';
		code += '      <h6 class="first">Account</h6>';
		code += '      <table>';
		code += '        <tr><th>Email</th><td><input type="text" id="account-email" value="' + email + '" /></td></tr>';
		code += '        <tr><th>Password</th><td><input type="password" id="account-pass" value="' + password + '" /></td></tr>';
		code += '      </table>';
		code += '    </div>';

		// PANE #2: CONFIGURATION

		code += '    <div class="pref-pane" id="pref-pane-2">';
		code += '      <h6 class="first">Configuration</h6>';
		code += '      <table>';
		code += '        <tr>';
		code += '          <th>Location</th>';
		code += '          <td><input type="text" id="base-url" value="' + baseURL + '" /></td>';
		code += '          <td><a href="#" class="tiny-question-mark">?</a></td>';
		code += '        </tr>';
		code += '      </table>';
		code += '    </div>';

		// PANE #3: PLUGINS

		code += '    <div class="pref-pane" id="pref-pane-3">';
		code += '      <h6 class="first">Plugins</h6>';
		code += '      <div class="prefs-msg">';
		code += '        Soon, grasshopper. Imagine auto-tweeting about a new version.';
		code += '      </div>';
		code += '    </div>';
		
		// PANE #4: BACKUP

		code += '    <div class="pref-pane" id="pref-pane-4">';
		code += '      <h6 class="first">Export a new Backup</h6>';
		code += '      <div class="prefs-msg">';
		code += '        You can export a backup of all Shimmer data.';
		code += '        <br><a href="?export&format=xml&download=true" id="export-link">Export</a>';
		code += '      </div>';
		code += '      <h6>Import an existing Backup</h6>';
		code += '      <div class="prefs-msg">';
		code += '        Import data from a Backup on your computer.';
		code += '        <form id="backup_import_form" action="?ajax&type=backup&action=backup.upload" method="post" enctype="multipart/form-data" target="backup_iframe">';
		code += '          <input type="hidden" name="backup_session" id="backup_session" value="" />';
		code += '          <table><tr><td><input type="file" id="backup_import_file" name="backup_import_file" style="margin-bottom:5px;" onchange="backup.uploadFile();"></td></tr></table>';
		code += '        </form>';
		code += '        <form>';
		code += '        <div id="select-backup-apps" style="display:none">';
		code += '          <div id="select-backup-apps-list"></div>';
		code += '        </div>';
		code += '        <div id="backup-app-count" style="opacity:0.0;"><span id="backup-app-count-number">0 apps</span> will be imported when saving.</div>';
		code += '        </form>';
		code += '        <iframe name="backup_iframe" id="backup_iframe" onload="javascript:backup.poll.go();" src="" style="border:0;height:0;width:0;padding:0;position:absolute;visibility:hidden;"></iframe>';
		code += '      </div>';		
		code += '    </div>';
		
		// SAVE ROW
		
		code += '    </div>';
		code += '    <div id="save_focus">';

		code += focusBox.standardSaveCode('preferences.save();return false;');
		code += '    </div>';
		code += '  </div>';
		// code += '</form>';

		focusBox.present({titleString:"Preferences", titleImage:'title_preferences.png', content:code, width:332});		
	},
	
	validate: function(email, pass, baseURL) {
		if (!preferences.emailIsValid(email))
			return {panel: 1, id:'account-email', message:'Please enter a valid email address'};
		if (!pass.length>0)
			return {panel: 1, id:'account-pass', message:'Please enter a password'};

		// If everything passes, return true
		return true;
	},
	
	save: function() {
		var email			= $('account-email').value;
		var pass			= $('account-pass').value;
		var baseURL			= $('base-url').value;
		var backupChoices	= backup.versionPicker.selectedApps();
		var validationResult = preferences.validate(email, pass, baseURL);
		if (validationResult==true) {
			notify.update('Saving preferences...', 0);
			new Ajax.Request('?ajax&type=pref', {
				method:'post',
				parameters: {
					action:			'preferences.save',
					email:			email,
					password:		pass,
					baseURL:		$('base-url').value,
					backupSession:	(backup.session ? backup.session : ''),
					backupChoices:	JSON.stringify(backupChoices)
				},
				onSuccess: function(transport) {
					var response = transport.responseText;
					var theResponse = JSON.parse(response);
					if (theResponse.wasOK) {
						notify.update('Preferences saved', 5);
						focusBox.hide();
					} else {
						notify.update('Preferences could not be saved', 5);
					}
				}
			});
		} else {
			preferences.slideToPane(validationResult.panel);
			$(validationResult.id).focus();
			if (validationResult.message) notify.update(validationResult.message, 0);
		}
	},
	
	emailIsValid: function(email) {
		return (email.match(/^[^\s@]+@[^\s\.]+(\.[^\s\.]+)+/) ? true : false);
	}
}
<?php
if (!defined('Shimmer')) header('Location:/');

if ($Shimmer->storedSerial()==false) $Shimmer->generateNewSerial();

if ($Shimmer->didInstall()) {
	if (sizeof($Shimmer->apps->list)<=0) {
		$notify = "Welcome to Shimmer. Time to add your first app.";
	}
} else if ($Shimmer->didUpdate()) {
	$notify = "Nice work! Shimmer has been upgraded successfully.";
} else  if ($Shimmer->updatesAvailable()) {
	$notify = 'A new version of Shimmer is available. <a href="http://shimmerapp.com">Grab it now</a>';
} else if (isset($_GET['checkForUpdates'])) {
	$notify = 'You\'re up to date! Shimmer will let you know when an update is available.';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">

<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<link rel="shortcut icon" type="image/ico" href="img/favicon.ico" />	
	<script type="text/javascript" src="js/scriptaculous/prototype-1.6.0.3.js"></script>
	<script type="text/javascript" src="js/scriptaculous/scriptaculous.js"></script>
	<script src="?combine&amp;type=javascript&amp;files=Shimmer.js,json2.js,apps.js,apps_ui.js,apps_ui_form.js,preferences.js,boxes.js,versions.js,versionsUI.js,versionsUI_table.js,focusbox.js,shortcut.js,textsizer.js,editinplace.js,relativedate.js,stats.js,graph.js,calendar.js,versionsUI_autoprocess.js,backup.js,ajaxtimeout.js,linkcenter.js,notify.js,dsa.js" type="text/javascript"></script>
	<link href="?combine&amp;type=css&amp;files=homepage.css,stats.css,prefs.css,preferences.css,calendar.css,blob.css,titleimages.css,linkcenter.css" type="text/css" rel="stylesheet" />
	<title>Shimmer &mdash; release easy</title>
</head>

<body>

<div id="focusBackground" style="display:none;"></div>
<div id="focusWrapper">
	<div id="focusBox" style="display:none;">
		<div id="focusBoxTwo">
			<div class="shadowRight"></div>
			<div class="shadowLeft"></div>
			<div class="shadowBottomRight"></div>
			<div class="shadowBottom"></div>
			<div class="shadowBottomLeft"></div>
			<div id="focusHeader">Focus Title</div>
			<div id="focus_content">Focus Content</div>
			
		</div>
	</div>
</div>

<div id="blob" style="display:none;top:0px;left:0px;">
	<div id="leftcap"></div>
	<div id="rightcap"></div>
	<div id="middle">
		<span id="blobtext"></span>
		<div id="arrow"></div>
	</div>
</div>

<div id="wrapper">
	<div id="title">
		<img src="img/dashboard/header_logo.png" id="logo" onclick="javascript:apps.reloadAppList();" />
		<div id="app_chooser">
			<span id="current_app_title"></span>
			<div id="applist_container">
				<div id="applist_shadow_container">
					<ul id="applist"></ul>
					<div id="applistShadowRight" class="shadowRight"></div>
					<div id="applistShadowLeft" class="shadowLeft"></div>
					<div class="shadowBottomRight"></div>
					<div class="shadowBottom"></div>
					<div class="shadowBottomLeft"></div>
				</div>
			</div>
		</div>
		<img src="img/dashboard/ajax_loader.gif" id="loading_icon">
		<span id="loginstatus">
			<div id="actions_container">
				<div id="actions_shadow_container">
					<ul id="account_actions">
						<li><a href="#Preferences" onclick="javascript:preferences.show();return false;">Preferences</a></li>
						<li><a href="#LinkCenter" onclick="javascript:linkcenter.show();return false;">Link Center</a></li>
						<li><a href="#Logout" onclick="javascript:Shimmer.account.logout();return false;">Logout</a></li>
					</ul>
					<div id="actionsShadowRight" class="shadowRight"></div>
					<div id="actionsShadowLeft" class="shadowLeft"></div>
					<div class="shadowBottomRight"></div>
					<div class="shadowBottom"></div>
					<div class="shadowBottomLeft"></div>					
				</div>
			</div>
			<span id="email_header">
			<?php
				$realName = "Account Actions";
				$email = $Shimmer->auth->storedEmail();
				if ($email) $realName = $email;
				echo $realName;
			?>
			</span>
		</span>
		<div id="notify" style="display:none;"><?php if(isset($notify)) echo $notify; ?></div>
		<?php if(isset($notify)) { ?>
			<script>setTimeout("notify.show(10)", 1000);</script>
		<?php } ?>
	</div>

	<div class="floatclear"></div>

	<div id="welcome" style="display:none;"></div>

	<div class="floatclear"></div>

	<div id="row1" class="rowclear">

		<div class="dashed_box" id="dashed_r1b0">
			<div class="dashed_corner dashed_top_left"></div>
			<div class="dashed_corner dashed_top_right"></div>
			<div class="dashed_corner dashed_bottom_left"></div>
			<div class="dashed_corner dashed_bottom_right"></div>
		
			<div class="dashed_horizontal dashed_top"></div>
			<div class="dashed_horizontal dashed_bottom"></div>
			<div class="dashed_vertical dashed_left"></div>
			<div class="dashed_vertical dashed_right"></div>			
		</div>
		
		<div class="dashed_box dashed_box_last" id="dashed_r1b1">
			<div class="dashed_corner dashed_top_left"></div>
			<div class="dashed_corner dashed_top_right"></div>
			<div class="dashed_corner dashed_bottom_left"></div>
			<div class="dashed_corner dashed_bottom_right"></div>
			
			<div class="dashed_horizontal dashed_top"></div>
			<div class="dashed_horizontal dashed_bottom"></div>
			<div class="dashed_vertical dashed_left"></div>
			<div class="dashed_vertical dashed_right"></div>			
		</div>

		<div class="box" id="box_versions" style="float:left;display:none;">

			<div class="box_title">
				<div id="box_corner_top_left" class="box_corner_top"></div>
				<div id="box_corner_top_right" class="box_corner_top"></div>
				<div class="title_container" style="display:inline-block;">
					<span style="padding-left:15px;">Versions</span>
				</div>
				<small></small>
				<img src="img/new_version_button.png" id="new-version-button" border="0" onclick="javascript:versionsUI.toggleNewVersionForm();return false;" title="Add a new version" alt="Add a new version">
			</div>
			<div id="versions_container">
				<div class="box_data" id="versions_content">
					<ul id="version-headers">
						<li id="version-header-version">Version</li>
						<li id="version-header-published">Published</li>
						<li id="version-header-downloads">Downloads</li>
						<li id="version-header-users">Users</li>
					</ul>
					<div id="scroll-cutoff">
						<div id="scroll-bar">
							<div id="scroll-knob"></div>
						</div>						
						<div id="version-table-holder"></div>
					</div>
					<div id="no-versions-holder" style="display:none">
						<div class="needmorestatdata">Looks like you haven&apos;t added any versions yet. Want to <a href="#" onclick="javascript:void(versionsUI.showEmptyNewVersionForm());return false;">add a new version</a>?</div>
					</div>
				</div>
			</div>
			<div class="box_entry" id="versions_edit" style="display:none;"><div>

				<span id="editDateContainer">
					<span id="editDateLabel"></span>
					<div id="editDateCalendar"></div>
				</span>
				<span class="switchlive" id="toggle_live_icon"></span>
				<h3 id="addOrEditTitle">ScrobblePod</h3>
				
				<input type="hidden" id="field_hidden_ref_timestamp" value="">
				<input type="hidden" id="field_hidden_updated_timestamp" value="">
				
				<table border="0" cellpadding="0" cellspacing="0" class="edit_version_table alternate" style="margin-top:10px;">
					<tr>
						<th>Release Notes</th>
					</tr>
					<tr>
						<td>
							<div id="notes_container">
								<div id="notes_pad">
									<textarea name="notes" id="field_notes" style="width:100%;" onfocus="javascript:this.parentNode.parentNode.addClassName('editing');" onblur="javascript:this.parentNode.parentNode.removeClassName('editing');">Bug Fixes:<br>Test</textarea>
								</div>
								<div id="preview-area" style="display:none;"></div>
								<span id="notes_bar">
									<span id="notes_status">You are editing the release notes.</span> <a href="#TogglePreview" onclick="javascript:versionsUI.togglePreviewArea();return false;" id="preview-switch">Preview Release Notes</a>.
								</span>
							</div>
						</td>
					</tr>
				</table>

				<table border="0" cellpadding="0" cellspacing="0" class="edit_version_table" style="float:left;">
					<tr>
						<th>Download URL</th>
					</tr>
					<tr>
						<td width="100%">
							<div id="url_container">
								<div id="autoload_progress"></div>
								<input type="text" name="url" id="field_url" value="" placeholder="http://www.scrobblepod.com/downloads/..." onfocus="javascript:this.parentNode.addClassName('editing');" onblur="javascript:this.parentNode.removeClassName('editing');" spellcheck="false" />
								<span onclick="javascript:versionsUI.autoload.go();return false;" id="auto_load"></span>
							</div>
						</td>
					</tr>
				</table>

				<table border="0" cellpadding="0" cellspacing="0" class="edit_version_table alternate" style="float:left;">
					<tr>
						<th valign="bottom">File Size</th>
						<th valign="bottom">DSA Signature</th>
					</tr>
					<tr>
						<td width="20%">
							<input type="text" name="size" id="field_size" placeholder="0" />
						</td>
						<td valign=top width="80%">
							<input type="text" name="signature" id="field_signature" placeholder="MC0CFEalcI5PwPO8P744HqtVQPf+rPaiAhUAja2fiPIffnfouATcsD0aesPkc+g=" spellcheck="false" />
						</td>
					</tr>
				</table>

				<div id="save_version_row">
					<input type="submit" value="Save Version" id="save-version-button" onclick="javascript:versions.saveVersionButtonClicked();return false;">
				 	or <a href="#Cancel" onclick="javascript:versionsUI.hideNewVersionForm();return false;" class="cancel_edit_link">cancel</a>
				</div>

			</div></div>
			
			<div class="box_footer" id="versions_footer">
				<div id="box_corner_bottom_left" class="box_corner_bottom"></div>
				<div id="box_corner_bottom_right" class="box_corner_bottom"></div>
			</div>
		</div>

		<div class="box" id="r1b1" style="display:none;">		
			<div class="box_title">
				<div id="box_corner_top_left" class="box_corner_top"></div>
				<div id="box_corner_top_right" class="box_corner_top"></div>
				<div class="title_container">
					<span class="menu_title">
						<span class="menu_title_text">Downloads</span>
						<ul class="box_data_selector r1b1" alt="r1b1"><li>Loading...</li></ul>
					</span>
				</div>
				<div onclick="javascript:void(toggleStatsType('#r1b1 .box_data'));return false;" class="switchstats"></div>
			</div>
			<div class="box_data"></div>
			<div class="box_footer">
				<div id="box_corner_bottom_left" class="box_corner_bottom"></div>
				<div id="box_corner_bottom_right" class="box_corner_bottom"></div>
			</div>
		</div>

	</div>

	<div id="row2" class="rowclear">
		<div id="box_triptych">
			<div class="dashed_box_trip" id="dashed_r2b1">
				<div class="dashed_corner dashed_top_left"></div>
				<div class="dashed_corner dashed_top_right"></div>
				<div class="dashed_corner dashed_bottom_left"></div>
				<div class="dashed_corner dashed_bottom_right"></div>
			
				<div class="dashed_horizontal dashed_top"></div>
				<div class="dashed_horizontal dashed_bottom"></div>
				<div class="dashed_vertical dashed_left"></div>
				<div class="dashed_vertical dashed_right"></div>			
			</div>
			
			<div class="dashed_box_trip" id="dashed_r2b2">
				<div class="dashed_corner dashed_top_left"></div>
				<div class="dashed_corner dashed_top_right"></div>
				<div class="dashed_corner dashed_bottom_left"></div>
				<div class="dashed_corner dashed_bottom_right"></div>
			
				<div class="dashed_horizontal dashed_top"></div>
				<div class="dashed_horizontal dashed_bottom"></div>
				<div class="dashed_vertical dashed_left"></div>
				<div class="dashed_vertical dashed_right"></div>			
			</div>
			
			<div class="dashed_box_trip dashed_box_last" id="dashed_r2b3">
				<div class="dashed_corner dashed_top_left"></div>
				<div class="dashed_corner dashed_top_right"></div>
				<div class="dashed_corner dashed_bottom_left"></div>
				<div class="dashed_corner dashed_bottom_right"></div>
			
				<div class="dashed_horizontal dashed_top"></div>
				<div class="dashed_horizontal dashed_bottom"></div>
				<div class="dashed_vertical dashed_left"></div>
				<div class="dashed_vertical dashed_right"></div>			
			</div>

			<div class="box_trip" id="r2b1" style="display:none;">
				<div class="box_title" id="box_trip1">
					<div id="box_corner_top_left" class="box_corner_top"></div>
					<div id="box_corner_top_right" class="box_corner_top"></div>
					<div class="title_container">
						<span class="menu_title">
							<span class="menu_title_text">Users</span>
							<ul class="box_data_selector r2b1" alt="r2b1"><li>Loading...</li></ul>
						</span>
					</div>
					<div onclick="javascript:void(toggleStatsType('#r2b1 .box_data'));return false;" class="switchstats"></div>
				</div>
				<div class="box_data">Graph Goes Here</div>
				<div class="box_footer">
					<div id="box_corner_bottom_left" class="box_corner_bottom"></div>
					<div id="box_corner_bottom_right" class="box_corner_bottom"></div>
				</div>
			</div>

			<div class="box_trip" style="margin:0;display:none;" id="r2b2">
				<div class="box_title" id="box_trip2">
					<div id="box_corner_top_left" class="box_corner_top"></div>
					<div id="box_corner_top_right" class="box_corner_top"></div>
					<div class="title_container">
						<span class="menu_title">
							<span class="menu_title_text">OS Version</span>
							<ul class="box_data_selector r2b2" alt="r2b2"><li>Loading...</li></ul>
						</span>
					</div>
					<div onclick="javascript:void(toggleStatsType('#r2b2 .box_data'));return false;" class="switchstats"></div>
				</div>
				<div class="box_data">Graph Goes Here</div>
				<div class="box_footer">
					<div id="box_corner_bottom_left" class="box_corner_bottom"></div>
					<div id="box_corner_bottom_right" class="box_corner_bottom"></div>
				</div>
			</div>

			<div class="box_trip" style="float:right;margin:0;display:none;" id="r2b3">
				<div class="box_title" id="box_trip3">
					<div id="box_corner_top_left" class="box_corner_top"></div>
					<div id="box_corner_top_right" class="box_corner_top"></div>
					<div class="title_container">
						<span class="menu_title">
							<span class="menu_title_text">CPU Count</span>
							<ul class="box_data_selector r2b3" alt="r2b3"><li>Loading...</li></ul>
						</span>
					</div>
					<div onclick="javascript:void(toggleStatsType('#r2b3 .box_data'));return false;" class="switchstats"></div>
				</div>
				<div class="box_data">Graph Goes Here</div>
				<div class="box_footer">
					<div id="box_corner_bottom_left" class="box_corner_bottom"></div>
					<div id="box_corner_bottom_right" class="box_corner_bottom"></div>
				</div>
			</div>
		</div>
	</div>

	<div id="copyright">
		<a href="http://shimmerapp.com/support?code=<?php echo $Shimmer->storedSerial(); ?>" class="right-link" id="report-bug" target="_new">Report a Bug</a>
		<a href="?info" class="right-link" target="_new">Usage Data</a>
		releases by <u>Shimmer v<?php echo $Shimmer->version . ($Shimmer->build>0 ? ' build ' . $Shimmer->build : ''); ?> private beta</u>. &copy; 2009 Ben Gummer.
	</div>

</div>

<div id="canvas"></div>

</body>

</html>
// AppsUI: Controls the display of app data

appsUI = {

	'regenerateAppList': function(applist, reloadVersionsToo) {
		if (typeof reloadVersionsToo == "undefined") reloadVersionsToo=true;
		apps.appsHub.clearAppList();
		var newCode = "";
		if (applist.length>0) {
			for (var i=0; i<applist.length; i++) {
				var theApp  = applist[i];
				var appName = theApp.name;
				var appId   = theApp.id;
				newCode += '<li class="appname" id="applist_' + appId + '">';
				newCode += '<img src="img/pencil_small.png" class="applist-edit-pencil" onclick="javascript:appsUI.form.startForm(\'' + appId + '\');" title="Edit this app..." />';
				newCode += '<img src="img/common/cross_small.png" class="applist-delete-cross" onclick="javascript:apps.deleteApp(\'' + appId + '\', \'' + appName + '\');" title="Delete this app" />';
				newCode += '<span onclick="javascript:apps.appClicked(this);" alt="' + appId + '" title="' + parseInt(theApp.users) + ' users">' + appName + (theApp.variant && theApp.variant.length>0 ? ('<br /><small>' + theApp.variant + '</small>') : '') + '</span>';
				newCode += '</li>';
				apps.appsHub.addApp(appId, {name:appName, variant:theApp.variant, count:theApp.users});
			}
		}
		newCode += '<li class="bottom_li"><a href="#NewApp" onclick="appsUI.form.startForm();return false;">New App...</a></li>';

		$("applist").innerHTML = newCode;
		
		if (applist.length>0 ) {
			Shimmer.hideWelcomeArea();
			var hashAppID = Shimmer.state.getAppIdFromHash();
			
			if (hashAppID) {
				apps.changeToApp(hashAppID);
			// If the current App has been set
			} else if (apps.appsHub.currentAppID) {
				apps.changeToApp(apps.appsHub.currentAppID);
			// In all other cases, switch to the first App in the list
			} else {
				apps.changeToFirstApp();
			}
		} else {
			// Switch to NO APP, to show welcome message + big blue button
		}
	},
	
	appInfoFromClickedApplistItem: function(theDiv) {
		var theId = theDiv.readAttribute('alt');
		if (!theId) return false;
		var returnItems = {id:theId};
		var theUsers = theDiv.readAttribute('title');
		if (theUsers) returnItems.users = parseInt(theUsers);
		return returnItems;
	},
	
}
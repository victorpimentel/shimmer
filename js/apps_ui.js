// AppsUI: Controls the display of app data

appsUI = {

	'regenerateAppList': function(applist, reloadVersionsToo) {
		if (typeof reloadVersionsToo == "undefined") reloadVersionsToo=true;
		apps.clearKnownApps();
		var newCode = "";
		if (applist.length>0) {
			for (var i=0; i<applist.length; i++) {
				var theApp  = applist[i];
				var appName = theApp.name;
				var appId   = theApp.id;
				newCode += '<li class="appname" id="applist_' + appId + '">';
				newCode += '<img src="img/pencil_small.png" class="applist-edit-pencil" onclick="javascript:appsUI.form.startForm(\'' + appName + '\');" title="Edit this app..." />';
				newCode += '<img src="img/common/cross_small.png" class="applist-delete-cross" onclick="javascript:apps.deleteApp(\'' + appName + '\');" title="Delete this app" />';
				newCode += '<span onclick="javascript:apps.appClicked(this);" alt="' + appName + '" title="' + parseInt(theApp.users) + ' users">' + appName + '</span>';
				newCode += '</li>';
				apps.addKnownApp(appName);
			}
		}
		newCode += '<li class="bottom_li"><a href="#NewApp" onclick="appsUI.form.startForm();return false;">New App...</a></li>';

		$("applist").innerHTML = newCode;
		
		if (applist.length>0 ) {
			Shimmer.hideWelcomeArea();
			var urlAppName = Shimmer.state.getAppNameFromURL();
			
			// If this is the first app list load, and a valid app has been set in the URL hash
			if (apps.hasReloadedAppList==false && urlAppName!=undefined && apps.indexOfApp(urlAppName)>-1) {
				apps.switchApp(unescape(urlAppName),reloadVersionsToo);
			// If the current App has been set
			} else if (apps.currentAppIndex>=0) {
				apps.switchToAppAtIndex(apps.currentAppIndex, reloadVersionsToo);
			// In all other cases, switch to the first App in the list
			} else {
				apps.switchToAppAtIndex(0, reloadVersionsToo);
			}
		} else {
			apps.switchApp('');
		}

		if (urlAppName=="Settings" && applist.length>0 ) showSettingsArea();
	},
	
	appNameFromClickedApplistItem: function(theDiv) {
		var theName = theDiv.readAttribute('alt');
		if (!theName) return false;
		var returnItems = {name:theName};
		var theUsers = theDiv.readAttribute('title');
		if (theUsers) returnItems.users = parseInt(theUsers);
		return returnItems;
	},
	
	storedUserCountForAppName: function(appName) {
		var allApplistItems = $$('ul#applist li.appname span');
		for (var i=0; i < allApplistItems.length; i++) {
			var li = allApplistItems[i];
			if (li.readAttribute('alt') == appName) {
				var titleAttribute = li.readAttribute('title');
				if (titleAttribute) return parseInt(titleAttribute);
			}
		};
		return 0;
	}	
	
}
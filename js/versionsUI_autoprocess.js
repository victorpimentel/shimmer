versionsUI.autoload = {
	active: false,
	setActive: function(isActive) {
		versionsUI.autoload.active = isActive;
		var textOverride = (isActive ? 'loading...' : false);
		versionsUI.autoload.setState($(versionsUI.autoload.urlField),  !isActive, false);
		versionsUI.autoload.setState($(versionsUI.autoload.sizeField), !isActive, true, textOverride);
		versionsUI.autoload.setState($(versionsUI.autoload.sigField),  !isActive, true, textOverride);		
	},
	
	// Element IDs
	urlField:  'field_url',
	sizeField: 'field_size',
	sigField:  'field_signature',
	
	// Sets the state of an input field, with optional color and text change
	setState: function(field, isEnabled, changeColor, setText) {
		if (typeof setText == "undefined") setText=false;
		isEnabled ? Form.Element.enable(field) : Form.Element.disable(field);
		if (changeColor) field.setStyle({color:(isEnabled ? 'black' : '#444')});
		if (setText) field.value = setText;
	},
	
	go: function() {
		if (!versionsUI.autoload.active) {
			versionsUI.autoload.setActive(true);
			var theURL = $('field_url').value;
			if (theURL.length > 0) {
				new Ajax.Request('?ajax&type=autoprocess', {
					method: 'get',
					parameters: {
						action: "process.go",
						 appID: apps.appsHub.currentAppID,
						source: theURL
					},
					onSuccess: function(transport) {
						var response = transport.responseText;
						var theResponse = JSON.parse(response);
						if (theResponse.wasOK) {
							$(versionsUI.autoload.sizeField).value = theResponse.filesize;
							$(versionsUI.autoload.sigField).value  = theResponse.signature;
						} else {
							$(versionsUI.autoload.sizeField).value = '0';
							$(versionsUI.autoload.sigField).value  = '';
						}
						versionsUI.autoload.setActive(false);
					}
				});
			}
		}
	}
};
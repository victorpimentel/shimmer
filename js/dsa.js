dsa = {
	// Aliases
	pub:   1,
	priv:  2,
	
	// Public Key Elements
	publicKeyStatusId:          'public-key-status',
	publicKeyInput:             'public-key-input',
	publicTimestampInput:       'key-timestamp-input-public',
	publicUsedSessionKeyInput:  'public-used-session',
	publicFrame:                'dsa_public_iframe',
	publicForm:                 'dsa_public_form',
	
	// Private Key Elements
	privateKeyStatusId:         'private-key-status',
	privateKeyInput:            'private-key-input',
	privateTimestampInput:      'key-timestamp-input-private',
	privateUsedSessionKeyInput: 'private-used-session',
	privateFrame:               'dsa_private_iframe',
	privateForm:                'dsa_private_form',
	
	timestampInputBase:         'key-timestamp-input',
	
	setDescription:     'Already Set',
	
	showChooser: function(type) {
		dsa.statusForType(type).hide();
		// dsa.poller.addPoll(1,2,'123456789', {startNow:true});
		// return false;
		
		var theInput = dsa.inputForType(type);
		theInput.show();
		if (Shimmer.util.isSafari()) theInput.click();
	},
	
	showStatus: function(type) {
		dsa.inputForType(type).hide();
		dsa.statusForType(type).show();
	},
	
	statusForType:     function(type) { return $(type==dsa.pub ? dsa.publicKeyStatusId         : dsa.privateKeyStatusId);        },
	inputForType:      function(type) { return $(type==dsa.pub ? dsa.publicKeyInput            : dsa.privateKeyInput);           },
	formForType:       function(type) { return $(type==dsa.pub ? dsa.publicForm                : dsa.privateForm);               },
	stampForType:      function(type) { return $(type==dsa.pub ? dsa.publicTimestampInput      : dsa.privateTimestampInput)      },
	usedStampForType:  function(type) { return $(type==dsa.pub ? dsa.publicUsedSessionKeyInput : dsa.privateUsedSessionKeyInput) },
	
	// Start Submission
	initUpload: function(type) {
		var theInput = dsa.inputForType(type);
		if (theInput && theInput.value && theInput.value.length>0) {
			var theSession = Shimmer.util.timestamp();
			dsa.stampForType(type).value = theSession;
			
			dsa.formForType(type).submit();
			dsa.setStatusForType(type, 'Uploading...');
			dsa.poller.addPoll("?ajax&type=dsa&action=upload.check&key_type=" + (type==dsa.pub ? 'public' : 'private'),  dsa.checkPoll, theSession, {
				startNow: true,
				stall: {
					min:      10,
					callback: dsa.stallCallback
				},
				restart: dsa.restartCallbackForType(type),
				type: type
			});
		} else dsa.showChooseStatusForType(type);
	},
	
	restartCallbackForType: function(type) {
		return (type==dsa.pub ? dsa.restartPublicUpload : dsa.restartPrivateUpload);
	},
	
	restartPublicUpload:  function() { dsa.restartUpload(dsa.pub);  },
	restartPrivateUpload: function() { dsa.restartUpload(dsa.priv); },
	
	restartUpload: function(type) {
		dsa.cancelPollForType(type);
		dsa.initUpload(type);
	},
	
	cancelPollForType: function(type) {
		var removals = [];
		for (var i=0; i < dsa.poller.list.length; i++) {
			if (dsa.poller.list[i].options.type==type) removals.push(dsa.poller.list[i].session);
		};
		
		for (var r=0; r < removals.length; r++) dsa.poller.removePoll(removals[r]);
	},
	
	stallCallback: function(session) {
		var target = dsa.poller.indexOfSession(session);
		var code = 'The ' + (dsa.poller.list[target].options.type==dsa.pub ? 'public' : 'private') + ' key upload may be stalled.';
		if (dsa.poller.list[target].options.restart) code += ' <a href="#Restart" id="restart-dsa">Restart</a>';
		notify.update(code, 0, 'key-stalled');
		$('restart-dsa').observe('click', function() {
			dsa.poller.list[target].options.restart();
			return false;
		});
	},
	
	showChooseStatusForType: function(type) {
		var status = '<a href="#ChooseDSA" onclick="dsa.showChooser(dsa.' + (type==dsa.pub ? 'pub' : 'priv') + ');return false;" class="action-choose-link">Choose...</a>'
		dsa.setStatusForType(type, status);
	},
	
	setStatusForType: function(type, status, show) {
		if (typeof show == "undefined") show = true;
		dsa.statusForType(type).innerHTML = status;
		if (show) dsa.showStatus(type);
	},
	
	setUsedStampForType: function(type, stamp) { dsa.usedStampForType(type).value = stamp; },
	
	// Returns true or false
	checkPoll: function(result) {
		if (result.updated) {
			var theType = (result.type=='public' ? dsa.pub : dsa.priv);
			if (result.failed) {
				dsa.setStatusForType(theType, 'Upload failed. Please <a href="#ChooseDSA" onclick="dsa.showChooser(' + theType + ');return false;" class="action-choose-link">try again</a>.');
				notify.hide('key-stalled');
				dsa.setUsedStampForType(theType, '');
				return false;
			} else if (result.key_ok) {
				dsa.setStatusForType(theType, 'Upload complete. Click save to finish.');
				notify.update((theType==dsa.pub ? 'Public' : 'Private') + ' key uploaded successfully.',5);
				dsa.setUsedStampForType(theType, result.session);
				return false;
			} else if (result.key_ok==false) {
				dsa.setStatusForType(theType, 'The key was invalid. Please <a href="#ChooseDSA" onclick="dsa.showChooser(' + theType + ');return false;" class="action-choose-link">try again</a>.');
				notify.update((theType==dsa.pub ? 'Public' : 'Private') + ' key was not valid.',5);
				dsa.setUsedStampForType(theType, result.session);
				return false;
			}
		}
		return true;
	},
	
	poller: {
		list: [],
		
		// The callback must return a boolean indicating whether to re-turtle or terminate.
		addPoll: function(target, callback, session, options) {
			// First, populate options with defaults if need be
			if (typeof options == "undefined") options = {};
			if (!options.delay) options.delay = 1;
			options.hits = 0;
			
			var newPoll = {
				timer: false,
				target: target,
				callback: callback,
				session: session,
				options: options
			}
			dsa.poller.list.push(newPoll);
			if (options.startNow) dsa.poller.turtle(session);
		},
		
		indexOfSession: function(session) {
			for (var i=0; i < dsa.poller.list.length; i++) {
				if (dsa.poller.list[i].session == session) return i;
			};
			return -1;
		},
		
		// Starts the delay timer
		turtle: function(session) {
			var target = dsa.poller.indexOfSession(session);
			if (target>-1) {
				clearTimeout(dsa.poller.list[target].timer);
				dsa.poller.list[target].timer = false;
				dsa.poller.list[target].timer = setTimeout('dsa.poller.go(' + session + ');', 1000*dsa.poller.list[target].options.delay);
			}
		},
		
		// After the timer finishes, actually hits the server. Can re-turtle after finished.
		go: function(session) {
			var target = dsa.poller.indexOfSession(session);
			if (target>-1) {
				new Ajax.Request(dsa.poller.list[target].target, {
					method: 'get',
					parameters: {
						app:     apps.currentApp,
						session: session
					},
					onSuccess: function(transport) {
						var response = transport.responseText;
						var theResponse = JSON.parse(response);
						var shouldReTurtle = dsa.poller.list[target].callback(theResponse);
						if (shouldReTurtle) {
							dsa.poller.turtle(session);
							dsa.poller.list[target].options.hits++;
							if (dsa.poller.list[target].options.stall && !dsa.poller.list[target].options.stall.shown) {
								if (dsa.poller.list[target].options.hits >= dsa.poller.list[target].options.stall.min) {
									dsa.poller.list[target].options.stall.shown = true;
									dsa.poller.list[target].options.stall.callback(session);
								}
							}
						}
					}
				});
			}
		},
		
		removePoll: function(session) {
			for (var i=0; i < dsa.poller.list.length; i++) {
				if (dsa.poller.list[i].session == session) {
					clearTimeout(dsa.poller.list[i].timer);
					dsa.poller.list[i].timer = false;
					dsa.poller.list.splice(i,1);
					break;
				}
			};
		}
	}
};
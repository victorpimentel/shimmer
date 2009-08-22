////////////// NOTIFY ///////////////
// notify.* is used to control the //
// yellow information box at the   //
// top of the page.                //
/////////////////////////////////////

notify = {
	
	////////////// PUBLIC METHODS //////////////
	
	// update the box message, and show the box. hideAfter=0 for no hide. id='' for no id
	'update': function(message, hideAfter, id) {
		$('notify').innerHTML = message;
		this.show(hideAfter,id);
	},

	// show the box, and start the timer
	'show': function(hideAfter, id) {
		if (typeof hideAfter == "undefined") hideAfter = 15;
		if (typeof id == "undefined") id = "";
		this.visibleId = id;
		if (notify.fadeOutEffect) {
			notify.fadeOutEffect.cancel();
			$('notify').setStyle({opacity:'1.0'});
		}
		$('notify').show();
		this.startTimer(hideAfter);
		this.callback();
	},
	
	// hide the box. supply parameter to only hide notifications with a specific id
	'hide': function(idRestriction, noFade) {
		if (typeof idRestriction == "undefined") idRestriction = this.visibleId;
		if (typeof noFade        == "undefined") noFade        = false;
		if (this.visibleId == idRestriction) {
			if (noFade) {
				$('notify').hide();
			} else {
				notify.fadeOutEffect = new Effect.Fade( $('notify'), {duration:2.5});
			}
			this.stopTimer();
			this.visibleId = "";
			this.callback();
		}
	},
	
	'visible': function() {
		return $('notify').visible();
	},
	
	'addCallback': function(method) {
		this.callbacks.push(method);
	},
	
	////////////// PRIVATE METHODS //////////////
	
	'visibleId': '',
	
	// Pointer to the fade out Effect, so it can be cancelled if need be
	'fadeOutEffect': false,
	
	// List of callback functions to be called when hiding/showing
	'callbacks': new Array(),
	
	'callback': function() {
		for (var i=0; i < this.callbacks.length; i++) {
			this.callbacks[i]();
		};
	},
	
	// start the timer
	'startTimer': function(hideAfter) {
		this.stopTimer();
		if (hideAfter>0) this.hideTimer = setTimeout('notify.hide();',hideAfter*1000);
	},
	
	// cancel the timer
	'stopTimer': function() {
		clearTimeout(this.hideTimer);
		this.hideTimer = false;		
	},
	
	// the timer object
	'hideTimer':false
}
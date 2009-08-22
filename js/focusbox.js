focusBox = {
	boxIsInAStateOfFlux: false,
	boxIsVisible: function() {
		return $('focusBox').visible();
	},
	present: function(options) {
		if (options) {
			var boxContent = options.content;
			if (boxContent) {
				this.setWidth(options.width);
				this.setHeader(options.titleString, options.titleImage);
				this.setContent(boxContent);
				if (typeof options.beforeDisplay != "undefined") options.beforeDisplay();
				if (typeof options.autofocus == "undefined") options.autofocus = true;
				this.show(options.autofocus, options.callback);
			}
		}
	},
	show: function(autofocus, callback) {
		if ( this.boxIsVisible()==false && !this.boxIsInAStateOfFlux ) {
			this.boxIsInAStateOfFlux = true;
			$('focusBox').setStyle({opacity:'0.1',display:'table'});
			new Effect.Parallel([
				new Effect.Appear( $('focusBackground'), { sync: true, to:0.7 } ),
				new Effect.Appear( $('focusBox'),        { sync: true } )
			], {
					duration: 0.5,
					afterFinish: function() {
						if (autofocus) {
							var firstInput = $$('#focus_content input[type=text]')[0];
							if (firstInput) firstInput.focus();
						}
						focusBox.boxIsInAStateOfFlux = false;
						if (typeof callback!="undefined") callback();
					}
				}
			);
		}
	},
	hide: function(options) {
		if (typeof options == "undefined") options = {};
		if (typeof options.dropout == "undefined") options.dropout = false;
		if ( this.boxIsVisible() && !this.boxIsInAStateOfFlux ) {
			this.boxIsInAStateOfFlux = true;
			new Effect.Parallel([
			  new Effect.Fade( $('focusBackground'), { sync:true } ),
			  new (options.dropout ? Effect.DropOut : Effect.Fade)( $('focusBox'),{ sync: true } )
			], {duration:1.0, afterFinish: function() {
				$('focus_content').innerHTML='';
				if (typeof options.callback != "undefined") options.callback();
				focusBox.boxIsInAStateOfFlux = false;
			} });
		}
	},
	
	setWidth: function(width) {
		if (typeof width == "undefined") width = "250";
		$('focusBox').setStyle({width:width+'px'});
	},
	
	setHeader: function(boxTitle,boxTitleImage) {
		var headerHTML = "";
		if (typeof boxTitle != "undefined" && typeof boxTitleImage != "undefined") {
			headerHTML = '<div class="title-replace" style="background-image:url(\'img/titles/' + boxTitleImage + '\');"' + boxTitle + '</div>';
		} else if (typeof boxTitle != "undefined") {
			headerHTML = '<div style="padding-top:5px;padding-bottom:5px;">' + boxTitle + '</div>';
		}
		$('focusHeader').innerHTML = headerHTML;
	},
	
	setContent: function(boxContent) {
		$('focus_content').innerHTML = boxContent;
	},
	
	standardSaveCode: function(command,optionalId,optionalCancelCommand) {
		var idAttribute = "";
		if (typeof optionalId != "undefined") idAttribute = ' id="' + optionalId + '"';
		if (typeof optionalCancelCommand == "undefined") optionalCancelCommand = 'focusBox.hide({dropout:true});return false;';
		return '  <input type="submit" class="small-save-button"' + idAttribute + '" value="Save" onclick="javascript:' + command + '" /> or <a class="cancel_edit_link" onclick="javascript:' + optionalCancelCommand + '" href="#Cancel">cancel</a>'
	},
	
	doneButtonCode: function(command) {
		return '<input type="submit" class="small-done-button" value="Done" onclick="javascript:' + command + '" />';
	}
}
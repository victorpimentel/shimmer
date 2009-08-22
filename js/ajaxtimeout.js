function requestIsRunning(xmlhttp) {
	switch (xmlhttp.readyState) {
		case 1: case 2: case 3:
			return true;
			break;
		default:
			return false;
			break;
	}
}

Ajax.Responders.register( {
	onCreate: function(request) {
		var timeoutValue = request.options['timeout'];
		if (typeof timeoutValue=="undefined" || typeof timeoutValue=="number") {
			request['timeoutId'] = window.setTimeout(
				function() {
					if (requestIsRunning(request.transport)) {
						request.transport.abort();
						if (request.options['onFailure']) request.options['onFailure'](request.transport, request.json);
					}
				},
				(typeof timeoutValue=="undefined" ? 10 : parseInt(timeoutValue)) * 1000
			);
		}
	},
	onComplete: function(request) {
		// Clear the timeout, the request completed ok
		if (request.options.timeout) {
			window.clearTimeout(request['timeoutId']);
		}
	}
});

Ajax.Responders.register( {
	onCreate: Shimmer.progress.updateLoadingCountDisplay,
	onComplete: Shimmer.progress.updateLoadingCountDisplay
});
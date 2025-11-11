// var __FIDELO__ = __FIDELO__ || {};
//
// (function (Fidelo) {

	var widget = window.__FIDELO__ || {};
	var scriptUrl = null;

	function splitScriptUrl() {
		if (scriptUrl) {
			return scriptUrl;
		}

		if (!document.currentScript) {
			scriptUrl = splitScriptUrlIE11();
			return scriptUrl;
		}

		var scriptSrc = document.currentScript.src;

		scriptUrl = new URL(scriptSrc);
		return scriptUrl;
	}

	function splitScriptUrlIE11() {
		var scriptSrc;

		// https://github.com/JamesMGreene/currentExecutingScript/blob/master/src/main.js
		var stack;
		try {
			throw new Error();
		} catch (e) {
			stack = e.stack;
		}

		// Try to use stack trace for current script URL
		if (stack) {
			var match = stack.match(/at.*(https:\/\/.+?):/);
			if (match && match[1]) {
				scriptSrc = match[1];
			}
		}

		// Fallback
		if (!scriptSrc) {
			// This may not work if any script injects scripts dynamically (like GTM injects Facebook Pixel)
			var scripts = document.getElementsByTagName('script');
			scriptSrc = scripts[scripts.length - 1].src;
		}

		if (!scriptSrc) {
			console.error('Could not split script URL for document.currentScript fallback.');
		}

		console.log('IE11 script URL:', scriptSrc);

		// IE11 does not support URL API
		var a = document.createElement('a');
		a.href = scriptSrc;
		return a;
	}

	/**
	 * Host of THIS script
	 *
	 * @returns {string}
	 */
	function getHost() {
		var url = splitScriptUrl();
		return url.protocol + '//' + url.host;
	}

	function getPath(key) {
		if (
			!widget.paths ||
			!widget.paths[key]
		) {
			throw 'Fidelo Widget: Path with key "' + key + '" does not exist';
		}
		return getHost() + '/' + widget.paths[key].replace('proxy://', '');
	}

	function splitPath(path) {
		var url = [path];
		if (path.indexOf('proxy://') === 0) {
			url[0] = getHost() + '/' + path.replace('proxy://', '');
		}

		// Convert ?callback=func to a callable function
		var match = path.match(/callback=?([^&]*)/);
		if (match && match[1]) {
			// TODO Improve callback functionality if needed
			match[1] = match[1].replace('__FIDELO__.', '');
			if (widget.hasOwnProperty(match[1])) {
				url[1] = widget[match[1]];
			} else {
				console.error('Callback not found', match);
			}
		}

		return url;
	}

	function loadScript(path) {
		var url = splitPath(path);
		var script = document.createElement('script');
		script.src = url[0];
		document.head.appendChild(script);
		if (url[1]) {
			script.onload = url[1];
		}
	}

	function loadStyle(path) {
		var url = splitPath(path);
		var link = document.createElement('link');
		link.rel = 'stylesheet';
		link.type = 'text/css';
		link.href = url[0];
		document.head.appendChild(link);
		// if (url[1]) {
		// 	link.onload = url[1];
		// }
	}

	/**
	 * Widget hosted in iframe: Create correponding iframe element in given container
	 * @param data
	 */
	function initIframe(data) {
		var container = document.querySelector(data.container);
		if (!container) {
			throw 'No container element <' + data.container + '> found';
		}

		// Pass query params or cookies to iframe
		// Would be much easier if Internet Explorer was already dead
		var src = data.src;
		var separator = src.indexOf('?') === -1 ? '?' : '&';
		data.params.forEach(function (arg) {
			var match;
			arg = arg.split(':');
			if (arg[0] === 'cookie') {
				match = document.cookie.match(RegExp('(^| )' + arg[1] + '=([^;]+)'));
				if (match) {
					src += separator + arg[1] + '=' + encodeURIComponent(match[2]);
					separator = '&';
				}
			} else { // param
				match = RegExp('[?&]' + arg[1] + '=([^&]*)').exec(window.location.search);
				if (match) {
					src += separator + arg[1] + '=' + match[1];
					separator = '&';
				}
			}
		});

		var iframe = document.createElement('iframe');
		iframe.id = data.id;
		iframe.src = src;
		iframe.referrerPolicy = 'no-referrer-when-downgrade'; // logUsage referrer
		iframe.style.display = 'block';
		iframe.style.width = '100%';
		iframe.style.border = '0';
		iframe.style.overflow = 'hidden';
		container.appendChild(iframe);
	}

	/**
	 * Async: Will be called by loadScript.onload AFTER iframe-resizer has been loaded
	 */
	function initIframeCallback() {
		if (!window.hasOwnProperty('iFrameResize')) {
			throw 'iframe-resizer is missing';
		}

		var iframe = document.querySelector(widget.iframe.container + ' iframe');
		if (!iframe) {
			throw 'No iframe found in container <' + widget.iframe.container + '>';
		}

		window.iFrameResize({
			checkOrigin: [widget.iframe.origin],
			// heightCalculationMethod: 'taggedElement'
			// log: true,
			// onInit: function () {
			// 	iframe.iFrameResizer.sendMessage(document.cookie, widget.iframe.origin);
			// }
		}, iframe);
	}

	function initWidget() {
		// Webpack 5 migration: Provide process.env for Vuelidate
		// https://github.com/vuelidate/vuelidate/issues/365
		if (!window.process || !window.process.env) {
			window.process = { env: {} };
		}

		if (!widget) {
			throw 'Fidelo Widget: Required namespace does not exist!';
		}

		if (widget.error) {
			throw 'Fidelo Widget: ' + widget.error;
		}

		// Safari ignores iframe.referrerPolicy if higher Referrer Policy is set
		var referrer = document.querySelector('meta[name="referrer"]');
		if (referrer && (referrer.content === 'no-referrer' || referrer.content === 'same-origin')) {
			throw `Fidelo Widget: Wrong Referrer Policy (${referrer.content})`;
		}

		// window.iFrameResizer = {
		// 	onMessage: function (message) { }
		// };

		if (widget.styles) {
			widget.styles.forEach(function (path) {
				loadStyle(path);
			});
		}

		if (widget.scripts) {
			widget.scripts.forEach(function (path) {
				loadScript(path);
			});
		}

		if (widget.iframe) {
			initIframe(widget.iframe);
		}
	}

	// Must be executed when the script is loaded
	splitScriptUrl();

	// // Export
	// Fidelo.getHost = getHost;
	// Fidelo.getPath = getPath;
	// Fidelo.initWidget = initWidget;
	// Fidelo.initIframeCallback = initIframeCallback;

	export {
		getHost,
		getPath,
		initWidget,
		initIframeCallback
	};

// })(__FIDELO__);

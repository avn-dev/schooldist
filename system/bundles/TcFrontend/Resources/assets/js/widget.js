/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (function() { // webpackBootstrap
/******/ 	// runtime can't be in strict mode because a global variable is assign and maybe created.
/******/ 	var __webpack_modules__ = ({

/***/ "./system/bundles/TcFrontend/Resources/js/widget.js":
/*!**********************************************************!*\
  !*** ./system/bundles/TcFrontend/Resources/js/widget.js ***!
  \**********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   getHost: function() { return /* binding */ getHost; },\n/* harmony export */   getPath: function() { return /* binding */ getPath; },\n/* harmony export */   initIframeCallback: function() { return /* binding */ initIframeCallback; },\n/* harmony export */   initWidget: function() { return /* binding */ initWidget; }\n/* harmony export */ });\n// var __FIDELO__ = __FIDELO__ || {};\n//\n// (function (Fidelo) {\n\nvar widget = window.__FIDELO__ || {};\nvar scriptUrl = null;\nfunction splitScriptUrl() {\n  if (scriptUrl) {\n    return scriptUrl;\n  }\n  if (!document.currentScript) {\n    scriptUrl = splitScriptUrlIE11();\n    return scriptUrl;\n  }\n  var scriptSrc = document.currentScript.src;\n  scriptUrl = new URL(scriptSrc);\n  return scriptUrl;\n}\nfunction splitScriptUrlIE11() {\n  var scriptSrc;\n\n  // https://github.com/JamesMGreene/currentExecutingScript/blob/master/src/main.js\n  var stack;\n  try {\n    throw new Error();\n  } catch (e) {\n    stack = e.stack;\n  }\n\n  // Try to use stack trace for current script URL\n  if (stack) {\n    var match = stack.match(/at.*(https:\\/\\/.+?):/);\n    if (match && match[1]) {\n      scriptSrc = match[1];\n    }\n  }\n\n  // Fallback\n  if (!scriptSrc) {\n    // This may not work if any script injects scripts dynamically (like GTM injects Facebook Pixel)\n    var scripts = document.getElementsByTagName('script');\n    scriptSrc = scripts[scripts.length - 1].src;\n  }\n  if (!scriptSrc) {\n    console.error('Could not split script URL for document.currentScript fallback.');\n  }\n  console.log('IE11 script URL:', scriptSrc);\n\n  // IE11 does not support URL API\n  var a = document.createElement('a');\n  a.href = scriptSrc;\n  return a;\n}\n\n/**\n * Host of THIS script\n *\n * @returns {string}\n */\nfunction getHost() {\n  var url = splitScriptUrl();\n  return url.protocol + '//' + url.host;\n}\nfunction getPath(key) {\n  if (!widget.paths || !widget.paths[key]) {\n    throw 'Fidelo Widget: Path with key \"' + key + '\" does not exist';\n  }\n  return getHost() + '/' + widget.paths[key].replace('proxy://', '');\n}\nfunction splitPath(path) {\n  var url = [path];\n  if (path.indexOf('proxy://') === 0) {\n    url[0] = getHost() + '/' + path.replace('proxy://', '');\n  }\n\n  // Convert ?callback=func to a callable function\n  var match = path.match(/callback=?([^&]*)/);\n  if (match && match[1]) {\n    // TODO Improve callback functionality if needed\n    match[1] = match[1].replace('__FIDELO__.', '');\n    if (widget.hasOwnProperty(match[1])) {\n      url[1] = widget[match[1]];\n    } else {\n      console.error('Callback not found', match);\n    }\n  }\n  return url;\n}\nfunction loadScript(path) {\n  var url = splitPath(path);\n  var script = document.createElement('script');\n  script.src = url[0];\n  document.head.appendChild(script);\n  if (url[1]) {\n    script.onload = url[1];\n  }\n}\nfunction loadStyle(path) {\n  var url = splitPath(path);\n  var link = document.createElement('link');\n  link.rel = 'stylesheet';\n  link.type = 'text/css';\n  link.href = url[0];\n  document.head.appendChild(link);\n  // if (url[1]) {\n  // \tlink.onload = url[1];\n  // }\n}\n\n/**\n * Widget hosted in iframe: Create correponding iframe element in given container\n * @param data\n */\nfunction initIframe(data) {\n  var container = document.querySelector(data.container);\n  if (!container) {\n    throw 'No container element <' + data.container + '> found';\n  }\n\n  // Pass query params or cookies to iframe\n  // Would be much easier if Internet Explorer was already dead\n  var src = data.src;\n  var separator = src.indexOf('?') === -1 ? '?' : '&';\n  data.params.forEach(function (arg) {\n    var match;\n    arg = arg.split(':');\n    if (arg[0] === 'cookie') {\n      match = document.cookie.match(RegExp('(^| )' + arg[1] + '=([^;]+)'));\n      if (match) {\n        src += separator + arg[1] + '=' + encodeURIComponent(match[2]);\n        separator = '&';\n      }\n    } else {\n      // param\n      match = RegExp('[?&]' + arg[1] + '=([^&]*)').exec(window.location.search);\n      if (match) {\n        src += separator + arg[1] + '=' + match[1];\n        separator = '&';\n      }\n    }\n  });\n  var iframe = document.createElement('iframe');\n  iframe.id = data.id;\n  iframe.src = src;\n  iframe.referrerPolicy = 'no-referrer-when-downgrade'; // logUsage referrer\n  iframe.style.display = 'block';\n  iframe.style.width = '100%';\n  iframe.style.border = '0';\n  iframe.style.overflow = 'hidden';\n  container.appendChild(iframe);\n}\n\n/**\n * Async: Will be called by loadScript.onload AFTER iframe-resizer has been loaded\n */\nfunction initIframeCallback() {\n  if (!window.hasOwnProperty('iFrameResize')) {\n    throw 'iframe-resizer is missing';\n  }\n  var iframe = document.querySelector(widget.iframe.container + ' iframe');\n  if (!iframe) {\n    throw 'No iframe found in container <' + widget.iframe.container + '>';\n  }\n  window.iFrameResize({\n    checkOrigin: [widget.iframe.origin]\n    // heightCalculationMethod: 'taggedElement'\n    // log: true,\n    // onInit: function () {\n    // \tiframe.iFrameResizer.sendMessage(document.cookie, widget.iframe.origin);\n    // }\n  }, iframe);\n}\nfunction initWidget() {\n  // Webpack 5 migration: Provide process.env for Vuelidate\n  // https://github.com/vuelidate/vuelidate/issues/365\n  if (!window.process || !window.process.env) {\n    window.process = {\n      env: {}\n    };\n  }\n  if (!widget) {\n    throw 'Fidelo Widget: Required namespace does not exist!';\n  }\n  if (widget.error) {\n    throw 'Fidelo Widget: ' + widget.error;\n  }\n\n  // Safari ignores iframe.referrerPolicy if higher Referrer Policy is set\n  var referrer = document.querySelector('meta[name=\"referrer\"]');\n  if (referrer && (referrer.content === 'no-referrer' || referrer.content === 'same-origin')) {\n    throw \"Fidelo Widget: Wrong Referrer Policy (\".concat(referrer.content, \")\");\n  }\n\n  // window.iFrameResizer = {\n  // \tonMessage: function (message) { }\n  // };\n\n  if (widget.styles) {\n    widget.styles.forEach(function (path) {\n      loadStyle(path);\n    });\n  }\n  if (widget.scripts) {\n    widget.scripts.forEach(function (path) {\n      loadScript(path);\n    });\n  }\n  if (widget.iframe) {\n    initIframe(widget.iframe);\n  }\n}\n\n// Must be executed when the script is loaded\nsplitScriptUrl();\n\n// // Export\n// Fidelo.getHost = getHost;\n// Fidelo.getPath = getPath;\n// Fidelo.initWidget = initWidget;\n// Fidelo.initIframeCallback = initIframeCallback;\n\n\n\n// })(__FIDELO__);\n\n//# sourceURL=webpack://fidelo-framework/./system/bundles/TcFrontend/Resources/js/widget.js?\n}");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The require scope
/******/ 	var __webpack_require__ = {};
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./system/bundles/TcFrontend/Resources/js/widget.js"](0,__webpack_exports__,__webpack_require__);
/******/ 	var __webpack_export_target__ = (__FIDELO__ = typeof __FIDELO__ === "undefined" ? {} : __FIDELO__);
/******/ 	for(var __webpack_i__ in __webpack_exports__) __webpack_export_target__[__webpack_i__] = __webpack_exports__[__webpack_i__];
/******/ 	if(__webpack_exports__.__esModule) Object.defineProperty(__webpack_export_target__, "__esModule", { value: true });
/******/ 	
/******/ })()
;
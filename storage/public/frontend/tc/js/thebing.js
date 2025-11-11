if(typeof oThebingInit === "undefined") {	
	var oThebingInit = {};
	var aThebingModules = new Array();
}

function ThebingInit(oThebingUtil) {
	this.oThebingUtil = oThebingUtil;
	this.oModules = {};
}

ThebingInit.prototype = {
	
	onLoad : function() {
		
		for(var i = 0; i < aThebingModules.length; ++i) {
			
			var oModule = aThebingModules[i];
			var sInstanceHash = oModule.onload(this.oThebingUtil, oModule, this);			
			
			if(
				this.oThebingUtil.checkIsUndefined(sInstanceHash) ||
				sInstanceHash.length == 0
			) {
				// Generate random instance hash
				sInstanceHash = oThebing.generateRandomInt();
			}
			
			if(!oModule.hasOwnProperty('instance_hash')) {
				oModule.instance_hash = sInstanceHash;
			}
			
			this.oModules[sInstanceHash] = oModule;
		}

	},
	
	getModuleByInstanceHash : function(sInstanceHash) {
		
		if(this.oModules.hasOwnProperty(sInstanceHash)) {
			return this.oModules[sInstanceHash];
		}
		
		return null;
	}
	
}

/**
 * 
 * Thebing Util
 * 
 */

var oThebing = {};
var aThebingEvents = new Array();

/**
 * Thebing Javascript Object Class
 * @author Christian Wielath
 */
function Thebing () {

   this.mDummyVariable = "dummy";

}

/**
 * If the AJAX Request is complete
 * the Request Result will be send to this Function
 * @author Christian Wielath
 */
Thebing.prototype.requestCallback = function (oHttp, iRequestId, oSourceObject, oObject, oAdditionalCallbackFunction) {

	return new Promise(function(resolve) {

		// Check HTTP Status
		if (oHttp.readyState == 4) {
			var sResponseText = oHttp.responseText;
			window.clearTimeout(iRequestId);

			var oResponseData = null;

			if(sResponseText){
				oResponseData = JSON.parse(sResponseText);
				// Call the Method
				if(oResponseData.error){
					for(var i in oResponseData.error) {
						var oError = oResponseData.error[i];
						this.requestCallbackError(oError);
					}
				} else if(oResponseData.method){

					resolve(oResponseData.data);

					if(oSourceObject) {
						eval('oSourceObject.'+oResponseData.method+'(oResponseData.data, oObject, oAdditionalCallbackFunction)');
					} else {
						eval('this.'+oResponseData.method+'(oResponseData.data, oObject, oAdditionalCallbackFunction)');
					}
				}

				if(
					oResponseData.request &&
					this.aRequests &&
					this.aRequests[oResponseData.request]
				) {
					var oRequestObject = this.aRequests[oResponseData.request];
					delete this.aRequests[oResponseData.request];
					this.requestCallbackRemoveHook(oRequestObject);
				}

				this.requestCallbackHook(oResponseData);

			}

		}
	}.thebingBind(this));

}

Thebing.prototype.requestCallbackError = function (oError) {
	
	console.error('Thebing registration error: ' + oError.key);
	
	this.requestCallbackErrorHook(oError);
}

Thebing.prototype.requestCallbackErrorHook = function (oError) { }

Thebing.prototype.requestCallbackRemoveHook = function (oObject) { }

Thebing.prototype.requestCallbackHook = function (oData) { }

/**
 * Cancel the AJAX Request if Timeout
 * @author Christian Wielath
 */
Thebing.prototype.cancelRequest = function(oHttp) {

	// Cancel Request
	oHttp.abort();

	// Display Error
	this.displayError('Thebing Error: The request took too long!');

}

/**
 * Display Thebing Errors
 * @author Christian Wielath
 */
Thebing.prototype.displayError = function(sError){
	alert(sError);
}

/**
 * Send an AJAX Request to Thebing Server Snippet
 * @author Christian Wielath
 */
Thebing.prototype.request = function (sParameters, oObject, sUrl, oSourceObject, oAdditionalCallbackFunction){

	return new Promise(function(resolve, reject) {

		if(!this.aRequests) {
			this.aRequests = new Object();
		}

		var oHttp = null;
		var iRequestId = 12345;

		// Check HTTP Type
		if (window.XMLHttpRequest) {
			oHttp = new XMLHttpRequest();
		} else if (window.ActiveXObject) {
			oHttp = new ActiveXObject("Microsoft.XMLHTTP");
		}

		// If HTTP
		if (oHttp != null) {

			if(!sUrl) {
				sUrl = '?get_request=1';
			}

			// Open POST
			oHttp.open("POST", sUrl, true);

			// Define Callback Methode
			oHttp.onreadystatechange = function()
			{

				this.requestCallback(oHttp, iRequestId, oSourceObject, oObject, oAdditionalCallbackFunction).then(function(response) {
					resolve(response);
				});

			}.thebingBind(this);

			oHttp.setRequestHeader(
				"Content-Type",
				"application/x-www-form-urlencoded"
			);
			// Wenn der Request von einem Objekt ausgeführt wurde
			if(oObject) {
				// eindeutige id für den request erzeugen
				var iRequest = parseInt(Math.floor(Math.random() * 1000000) + 1);
				// Objekt zu dem Request speichern
				this.aRequests[iRequest] = oObject;
				// Request-id mitschicken
				sParameters += '&request='+iRequest;
			}

			oHttp.send(sParameters);

			// Set Timeoout
			// iRequestId = window.setTimeout("oThebing.cancelRequest()", 5500, oHttp);

		}

	}.thebingBind(this));

}

/**
 * Default Callback Method
 * @author Christian Wielath
 */
Thebing.prototype.defaultCallback = function (oData){

}

Thebing.prototype.log = function(mData, sType) {
	
	if(!this.logIsAvailable()) {
		return;
	}
	
	if(typeof sType === 'undefined') {
		sType = 'log';
	}
	
	switch(sType) {
		case 'info':
			console.info(mData);
			break;
		case 'error':
			console.error(mData);
			break;
		case 'log':
		default:
			console.log(mData);
			break;	
	}
};

Thebing.prototype.logIsAvailable = function() {
	return console && console.log && console.error;
};

Thebing.prototype.getObjectLength = function(oObject) {
    var iSize = 0;
    for (var mKey in oObject) {
        if (oObject.hasOwnProperty(mKey)) {
			iSize++;
		}
    }
	
    return iSize;
}

Thebing.prototype.waitForInputEvent = function() {

	var sFunction = arguments[0];
	var oEvent = arguments[1];

	var oElement = oEvent.element();

	var bDirect = false;
	// Checkboxen und Selects direkt ausführen, alles andere mit timeout
	if(
		(
			oElement.tagName == 'INPUT' &&
			oElement.type == 'checkbox'
		) ||
		oElement.tagName == 'SELECT' ||
		oElement.tagName == 'IMG' ||
		oElement.tagName == 'TD'
	) {

		bDirect = true;

	} else {

		if(!this.aWaitForInputEventObserver) {
			this.aWaitForInputEventObserver = new Array();
		}

		if(this.aWaitForInputEventObserver[oElement.id]){
			clearTimeout(this.aWaitForInputEventObserver[oElement.id]);
		}

	}

	// Funktion muss per eval aufgerufen werden
	var sFunctionCall;
	if(bDirect) {
		sFunctionCall = "this."+sFunction+"(";
	} else {
		sFunctionCall = "this.aWaitForInputEventObserver[oElement.id] = setTimeout(this."+sFunction+".thebingBind(this, ";
	}

	if(arguments.length > 2) {
		var bFirst = true;
		for(var iArgument=2; iArgument<arguments.length; iArgument++) {
			if(!bFirst){
				sFunctionCall += ', ';
			}
			sFunctionCall += 'arguments['+iArgument+']';
			bFirst = false;
		}
	}

	if(bDirect) {
		sFunctionCall += ");";
	} else {
		sFunctionCall += "), 800);";
	}

	eval(sFunctionCall);

}

/**
 * Copy from Inet ;)
 * get All Elements By CSS Class Name
 * @author Christian Wielath
 */
Thebing.prototype.getElementsByClass = function(searchClass, node, tag) {

	var classElements = new Array();

	if ( node == null )

		node = document;

	if ( tag == null )

		tag = '*';

	var els = node.getElementsByTagName(tag);

	var elsLen = els.length;

	var pattern = new RegExp("(^|\\s)"+searchClass+"(\\s|$)");
	
	var j = 0;

	for (var i = 0;  i < elsLen; i++) {

		if ( pattern.test(els[i].className) ) {

			classElements[j] = els[i];

			j++;

		}

	}

	return classElements;

}

Thebing.prototype.getParentElementByClass = function (oObject, sClass) {
	while ((oObject = oObject.parentElement) && !oObject.classList.contains(sClass));
	return oObject;
}

/**
 * @description Get the Value of the given Element
 * Only for Input, Select, Textarea
 * @author Christian Wielath
 */
Thebing.prototype.getValue = function(oElement) {

	var mValue = '';

	if(oElement){

		if(oElement.tagName == 'INPUT'){

			if(
				(
					oElement.type === 'checkbox' ||
					oElement.type === 'radio'
				) &&
				!oElement.checked
			) {
				mValue = 0;
			} else {
				mValue = oElement.value;
			}

		} else if(oElement.tagName == 'SELECT'){

			if(
				oElement.options
			){

				if(
					!oElement.multiple &&
					oElement.selectedIndex >= 0
				){

					var oOption = oElement.options[oElement.selectedIndex];
					mValue = oOption.value;

				} else if(oElement.multiple){

					mValue = new Array();

					for(var i = 0; i < oElement.options.length; i++) {
						var aOption = oElement.options[i];

						if(aOption.selected) {
							mValue.push(aOption.value || aOption.text);
						}
					}

				}
			}

		} else if(oElement.tagName == 'TEXTAREA'){

			mValue = oElement.value;

		}

	}

	return mValue;

}


/**
 * write Options of Select new and selected the Old values if they exist in the new options
 * @author Christian Wielath
 */
Thebing.prototype.updateSelectOptions = function (oSelect, aOptionData){

	var aOldValues = this.getValue(oSelect);

	if ( oSelect.hasChildNodes() )
	{
		while ( oSelect.childNodes.length >= 1 )
		{
			oSelect.removeChild( oSelect.firstChild );       
		} 
	}

	aOptionData.thebingEach(function(aOption){
		
		if(aOption) {		
			// Schauen ob es unter options gibt
			// wenn ja müssen option groups erstellt werden
			// ist unteranderem bei Locations optionalerweise möglich
			if(!aOption.options){				
				var oOption = this.createSelectOptions(aOption, aOldValues, aOption.disabled);
				oSelect.appendChild(oOption);
			} else {
				var oOptionGroup = document.createElement('optgroup');
					oOptionGroup.label = aOption.text;

				aOption.options.thebingEach(function(aSubOption){

					if(!aSubOption.options) {
						var oOption = this.createSelectOptions(aSubOption, aOldValues);						
						oOption = this.manipulateSelectOption(oSelect, oOption);
						
						oOptionGroup.appendChild(oOption);
					} else {
						var aDisabled = new Object();
						aDisabled.text = aSubOption.text;
						var oDisabled = this.createSelectOptions(aDisabled, aOldValues, true);
						oOptionGroup.appendChild(oDisabled);

						aSubOption.options.thebingEach(function(aSubSubOption){
							var oOption = this.createSelectOptions(aSubSubOption, aOldValues);
							oOption = this.manipulateSelectOption(oSelect, oOption);
							oOptionGroup.appendChild(oOption);
						}.thebingBind(this));

					}							
				}.thebingBind(this));

				oSelect.appendChild(oOptionGroup);
			}
		}
	}.thebingBind(this));

}

Thebing.prototype.getSelectOptions = function (oSelect, bRemoveEmptyOption) {
	if(!oSelect) {
		return new Array();
	}
	
	var aReturnOptions = new Array();
	var iCount = 0;
	
	var aOptions = oSelect.getElementsByTagName('option');
	for(var iKey in aOptions) {
		var oOption = aOptions[iKey];
		
		if(typeof oOption.value !== 'undefined') {
			
			if(
				bRemoveEmptyOption === true &&
				oOption.value == 0
			) {
				continue;
			}
			
			var aOption = new Array();
			aOption['text'] = oOption.innerHTML;
			aOption['value'] = oOption.value;
			aReturnOptions[iCount] = aOption;

			++iCount;
		}
	}
	
	return aReturnOptions;
}

Thebing.prototype.createSelectOptions = function (aOption, aOldValues, bDisabled){
	
	var oOption = document.createElement('option');

	if(typeof aOption.value != 'undefined'){
		oOption.value = aOption.value;
	}

	if(typeof aOption.text != 'undefined'){
		oOption.text = aOption.text;
	}

	if(aOldValues){

		if(
			this.checkIsObject(aOldValues) ||
			this.checkIsArray(aOldValues)
		){

			aOldValues.thebingEach(function(mValue){
				if(mValue == oOption.value){
					oOption.selected = true;
				}
			});

		} else if(
			(
				this.checkIsString(aOldValues) ||
				this.checkIsNumber(aOldValues)
			) &&
			aOldValues == oOption.value
		){
			oOption.selected = true;
		}
	}
	
	if(bDisabled) {
		oOption.disabled = 'disabled';
	}

	return oOption;
}

Thebing.prototype.manipulateSelectOption = function (oSelect, oOption){
	return oOption;
}

Thebing.prototype.checkIsUndefined = function (mCheckVariable){

	return typeof mCheckVariable === "undefined";

}

Thebing.prototype.checkIsArray = function (mCheckVariable){

	if(
		typeof(mCheckVariable) == 'object' &&
		(mCheckVariable instanceof Array)
	){
		return true;
	}

	return false;
}

Thebing.prototype.checkIsObject = function (mCheckVariable){

	if(typeof(mCheckVariable) == 'object') {
		return true;
	}

	return false;
}

Thebing.prototype.checkIsString = function (mCheckVariable){

	if(typeof(mCheckVariable) == 'string') {
		return true;
	}

	return false;
}

Thebing.prototype.checkIsNumber = function (mCheckVariable){

	if(typeof(mCheckVariable) == 'number') {
		return true;
	}

	return false;
}

Thebing.prototype.generateRandomInt = function (iMin, iMax){
	return Math.floor(Math.random() * (9999999 - 1000000 + 1)) + 10000000;
}

Thebing.prototype.setEvent = function(oNode, sEvent, mHandleEvent, capture)
{
	if(aThebingEvents){
		
		if(!(oNode in aThebingEvents))
		{
			// aThebingEvents stores references to nodes
			aThebingEvents[oNode] = {};
		}

		if(!(sEvent in aThebingEvents[oNode]))
		{
			// each entry contains another entry for each event type
			aThebingEvents[oNode][sEvent] = [];
		}
		// capture reference
		aThebingEvents[oNode][sEvent].push([mHandleEvent, capture]);

		oNode.addEventListener(sEvent, mHandleEvent, capture);
		
	}
}

Thebing.prototype.removeEvent = function(oNode, sEvent)
{
	if(aThebingEvents){
		if(oNode in aThebingEvents)
		{
			var handlers = aThebingEvents[oNode];

			if(sEvent in handlers)
			{
				var eventHandlers = handlers[sEvent];

				for(var i = eventHandlers.length; i--;)
				{
					var handler = eventHandlers[i];

					oNode.removeEventListener(sEvent, handler[0], handler[1]);
				}
			}
		}
	}
}

Thebing.prototype.toQueryString = function(aElements) {
    
	var aResults = [];

	for (var sKey in aElements) {
		
		var aValues = aElements[sKey];

		sKey = encodeURIComponent(sKey);

		if (aValues && typeof aValues == 'object') {
			if (this.checkIsArray(aValues)) {
				aValues.thebingEach(function(sValue) {
					aResults.push(this.toQueryPair(sKey, sValue));
				}.thebingBind(this));
			}
		} else {
			aResults.push(this.toQueryPair(sKey, aValues));
		}

	}
	
	var sQueryString = aResults.join('&');

	return sQueryString;

}

Thebing.prototype.toQueryPair = function(key, value) {
    if (this.checkIsUndefined(value)) return key;
    return key + '=' + encodeURIComponent(this.interpretString(value));
}

Thebing.prototype.interpretString = function(sString) {
	return sString == null ? '' : String(sString);
}

Thebing.prototype.serializeForm = function(oForm) {

	var sTagName = oForm.tagName.toLowerCase();
	
	// Element has to be form
	if(sTagName != 'form') {
		return;
	}

	var aElements = this.getElements(oForm);

    var key, value, submitted, submit = false;

	var aData = {};
	aElements.thebingEach(function(oElement) {

		if (this.checkElementForSerilization(oElement)) {
			key = oElement.name;
			value = this.getValue(oElement);
			if (
				value != null && 
				oElement.type != 'file' && 
				(
					oElement.type != 'submit' || 
					(
						!submitted &&
						submit !== false && 
						(
							!submit || 
							key == submit
						) &&
						(
							submitted = true
						)
					)
				)
			) {
				if (key in aData) {					
					if(
						oElement.tagName === 'INPUT' &&
						oElement.type === 'radio'
					) {
						if(oElement.checked) {
							aData[key] = value;
						}
					} else {					
						if (!this.checkIsArray(aData[key])) {
							aData[key] = [aData[key]];
						}
						
						aData[key].push(value);					
					}
				} else {
					aData[key] = value;										
				}
			}
		}

    }.thebingBind(this));

	var sQueryString = this.toQueryString(aData);

    return sQueryString;

}

Thebing.prototype.checkElementForSerilization = function(oElement) {
	return (!oElement.disabled && oElement.name);
}

/**
 * HTMLElement.prototype
 */

Thebing.prototype.getElements = function(oForm) {

	var elements = oForm.getElementsByTagName('*'),
        element,
        arr = [ ];
	var serializers = {
		'input' : 1,
		'select' : 1,
		'textarea' : 1
	};

	for (var i = 0; element = elements[i]; i++) {
		arr.push(element);
	}

	var aElements = [];
	arr.thebingEach(function(oChild) {
		if (serializers[oChild.tagName.toLowerCase()]) {
			aElements.push(oChild);
		}
	});
	
	return aElements;

}

  
//
// create Bind Method
// (copy from inet)
//
Function.prototype.thebingBind = function(oScope) {
	var oSelf = this;
	var aArgs = null;
	var oFunction = function() {};
	var slice = Array.prototype.slice;

	if(arguments.length > 1) {
		aArgs = slice.call(arguments, 1)
	}

	var oReturn = function() {
		var oContext = oScope;
		var iLength = arguments.length;

		if (this instanceof oReturn){
			oFunction.prototype = oSelf.prototype;
			oContext = new oFunction;
		}

		var oResult = null;

		if(!aArgs && !iLength) {
			oResult = oSelf.call(oContext);
		} else {
			var aApplyArgs = aArgs || arguments;
			if(aArgs && iLength) {
				aApplyArgs = aArgs.concat(slice.call(arguments));
			}
			oSelf.apply(oContext, aApplyArgs);
		}

		return oContext == oScope ? oResult : oContext;
	};

	return oReturn;
}

/**
 * Each Funktion for Thebing Frontend
 * @author Christian Wielath
 ACHTUNG nicht dem Object erweitern
 Löst bei Jquery Datepicker fehler aus
 */
Array.prototype.thebingEach = function(oFunction) {
	if(typeof oFunction == 'function'){
		var oArray = this;
		var iLength = oArray.length;
		for(var i = 0; i < iLength; i++){
			oFunction(oArray[i], i);
		}
	}
}

/**
 * 
 * Initialization
 * 
 */

function addThebingWindowLoadEvent(oFunction) {
	
	var oCurrentOnLoad = window.onload;
	
	if(typeof window.onload != 'function') {
		window.onload = oFunction;
	} else {
		window.onload = function() {
			if(oCurrentOnLoad) {
				oCurrentOnLoad();
			}
			oFunction();
		}
	}
}

addThebingWindowLoadEvent(function() {
	
	oThebing = new Thebing();
	
	var oThebingInit = new ThebingInit(oThebing);
	oThebingInit.onLoad();
});


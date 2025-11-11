
function GUI_Template() {
	var arrTemplate = new Array();

	function requestTemplateCallback(objResponse) {
		var strResponse = objResponse.responseText;
		var arrResponse = strResponse.evalJSON();
		arrTemplate = arrResponse;
	}

	this.requestTemplate = function(strItem) {
		var strParameters = 'component=gui&task=template&item='+strItem+'&';

		new Ajax.Request(strAjaxAppPath,
			{
				method:'post',
	    		parameters: strParameters,
				onComplete: requestTemplateCallback
	  		}
		);

	}

	this.getTemplate = function() {

		return arrTemplate;

	}

}

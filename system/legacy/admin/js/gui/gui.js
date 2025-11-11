
var strAjaxAppPath = '/system/applications/ajax.php';

function GUI_uploadFile(oInput, sTargetScript, oOptions) {
	var oForm = oInput.form;

	var n = 'f' + Math.floor(Math.random() * 99999);
	var d = document.createElement('DIV');
	d.innerHTML = '<iframe style="display:none" src="about:blank" id="'+n+'" name="'+n+'" onload="GUI_uploadFile_ready(\''+n+'\', \''+oInput.id+'\', \''+oForm.id+'\')"></iframe>';
	document.body.appendChild(d);

	oForm.setAttribute('target', n);
	oForm.setAttribute('action', sTargetScript);

	var sContent = '<img src="/admin/media/indicator.gif" style="margin-bottom:4px; vertical-align:bottom;" alt="" />';
	$(oInput.id+'_response').update(sContent);

	var i = document.getElementById(n);
	if (oOptions && typeof(oOptions.onComplete) == 'function') {
	    i.onComplete = oOptions.onComplete;
	}

}

function GUI_uploadFile_ready(sId, sInputId, sFormId) {

	var i = document.getElementById(sId);
	if (i.contentDocument) {
	    var d = i.contentDocument;
	} else if (i.contentWindow) {
	    var d = i.contentWindow.document;
	} else {
	    var d = window.frames[id].document;
	}
	if (d.location.href == "about:blank") {
	    $(sFormId).submit();
	}

	if (typeof(i.onComplete) == 'function') {
	    i.onComplete(sInputId, d.body.innerHTML);
	}

}

function GUI_uploadFile_complete(sInputId, sContent) {
	if (sContent) {
		$(sInputId + '_response').update(sContent);
	}
}

function GUI(strItem) {

	this.strItem = strItem;
	this.arrTemplate = [];
	this.intAccordionCount = 0;

	this.getTemplate = function() {
	
		var objTemplate = new GUI_Template();
		objTemplate.requestTemplate(this.strItem);
	
		this.waitTemplate(objTemplate);
	
	};

	this.waitTemplate = function(objTemplate) {
		this.arrTemplate = objTemplate.getTemplate();
	
		if(this.arrTemplate.length == 0) {
			setTimeout(function() { objTable.waitTemplate(objTemplate)}, 500);
		} else {
			return this.arrTemplate;
		}
	};

	this.printFormInput = function(strLabel, strName, strValue, strOptions) {

		var strCode = '';
		if(strLabel.length > 0) {
			strCode += '<div class="GUIDialogRow form-group form-group-sm"><label class="GUIDialogRowLabelDiv col-sm-4 control-label" for="'+strName+'">'+strLabel+'</label>';
		}
		strCode += '<div class="GUIDialogRowInputDiv col-sm-8"><input type="text" class="txt form-control input-sm" id="'+strName+'" name="'+strName+'" value="'+strValue+'" '+strOptions+' /></div></div>';

		return strCode;
	};

	this.printFormCheckbox = function(strLabel, strName, bChecked, strStyles) {
		
		var strCode = '';

		strCode += '<div class="form-group form-group-sm"><div class="col-sm-offset-4 col-sm-8"><div class="checkbox"><label for="'+strName+'">';

		strCode += '<input type="checkbox" id="'+strName+'" name="'+strName+'" ';

		if(bChecked === '1'){
			strCode += 'checked ';
		} 
		strCode += '/> '+strLabel+'</label></div></div></div>';

		return strCode;
	};

	this.printFormFile = function(strLabel, strName, strValue, strOptions, strTargetScript) {

		var strCode = '';
		if(strLabel.length > 0)
		{
			strCode += '<label for="'+strName+'" style="margin-right:3px;">'+strLabel+'</label>';
		}
		strCode += '<input type="file" class="txt" id="'+strName+'" name="'+strName+'" value="'+strValue+'" '+strOptions+' onchange="GUI_uploadFile(this, \''+strTargetScript+'\', {\'onComplete\' : GUI_uploadFile_complete});" />';
		strCode += '<div id="'+strName+'_response" class="response"></div>';

		return strCode;
	};

	this.printFormUpload = function(sLabel, sName) {

		var sCode = '';
		if(sLabel.length > 0)
		{
			sCode += '<label for="'+sName+'" style="margin-right:3px;">'+sLabel+'</label>';
		}
		sCode += '<input type="file" class="txt" id="'+sName+'" name="'+sName+'" />';

		return sCode;
	};

	this.printFormPicture = function(sName, sPath) {
		var sCode = '';
		if(!(sPath === null || sPath === undefined)){
			sCode += '<img alt="Delete Customer Logo" src="/admin/media/cross.png" />';
			sCode += '<img alt="Customer Logo" src="'+sPath+'" id="'+sName+'" />';
		}
		return sCode;
	};

	this.printFormHidden = function(sName, sValue) {
		var strCode = '';
		strCode += '<input type="hidden" id="'+sName+'" name="'+sName+'" value="'+sValue+'" />';
		return strCode;
	};

	this.printFormPassword = function(strLabel, strName, strValue, strOptions) {

		var strCode = '';
		if(strLabel.length > 0) {
			strCode += '<div class="form-group form-group-sm"><label class="col-sm-4 control-label" for="'+strName+'">'+strLabel+'</label>';
		}
		strCode += '<div class="col-sm-8"><input type="password" class="txt form-control input-sm" id="'+strName+'" name="'+strName+'" value="'+strValue+'" '+strOptions+' /></div></div>';

		return strCode;
	};

	this.printFormInputNumbers = function(strLabel, strName, strValue, strOptions, strHint) {

		var strCode = '';
		if(strLabel.length > 0)
		{
			strCode += '<label for="'+strName+'" style="margin-right:3px;">'+strLabel+'</label>';
		}
		strCode += '<input type="text" class="txt alignRight w80" id="'+strName+'" name="'+strName+'" value="'+strValue+'" '+strOptions+' /> '+strHint+'<br />';

		return strCode;
	};

	this.printFormSelect = function(strLabel, strName, arrOptions, mSelected, strOptions, strHint) {

		var strCode = '';
		var strSelected = '';
		if(strLabel.length > 0) {
			strCode += '<div class="GUIDialogRow form-group form-group-sm">' +
				'<label class="GUIDialogRowLabelDiv col-sm-4 control-label" for="'+strName+'">'+strLabel+'</label>' +
				'<div class="GUIDialogRowInputDiv col-sm-8">';
		}
		strCode += '<select class="txt form-control input-sm" id="'+strName+'" name="'+strName+'" '+strOptions+'>';
		for(var i = 0; i < arrOptions.length; i++) {
			strSelected = '';
			if(mSelected == arrOptions[i][0]) {
				strSelected = 'selected="selected"';
			}
			strCode += '<option value="'+arrOptions[i][0]+'" '+strSelected+'>'+arrOptions[i][1]+'</option>';
		}
		strCode += '</select>';

		if(strHint) {
			strCode += '<span class="help-block">'+strHint+'</span>';
		}

		if(strLabel.length > 0) {
			strCode += '</div></div>';
		}

		return strCode;
	};

	this.printFormMultiSelect = function(strLabel, strName, arrOptions, aSelected, strOptions) {

		var strCode = '';
		var strSelected = '';
		if(strLabel.length > 0) {
			strCode += '<div class="form-group form-group-sm"><label class="col-sm-4 control-label" for="'+strName+'">'+strLabel+'</label><div class="col-sm-8">';
		}
		strCode += '<select class="txt form-control input-sm" id="'+strName+'" name="'+strName+'"  '+strOptions+'>';
		
		for(var i = 0; i < arrOptions.length; i++) {
			
			strSelected = '';
			for (var f = 0; f < aSelected.length; f++) {

				if(arrOptions[i][0] == aSelected[f]) {
					strSelected = 'selected="selected"';
					break;
				}
				
			}
			
			strCode += '<option value="' + arrOptions[i][0] + '" ' + strSelected + '>' + arrOptions[i][1] + '</option>';
	
		}
		
		strCode += '</select>';

		if(strLabel.length > 0) {
			strCode += '</div></div>';
		}

		return strCode;
	};

	this.printFormTextarea = function(strLabel, strName, strValue, iRows, iCols, strOptions) {

		var strCode = '';
		if(strLabel.length > 0) {
			strCode += '<div class="form-group form-group-sm"><label class="col-sm-4 control-label" for="'+strName+'">'+strLabel+'</label><div class="col-sm-8">';
		}
		strCode += '<textarea class="txt form-control input-sm" id="'+strName+'" name="'+strName+'" rows="'+iRows+'" cols="'+iCols+'" '+strOptions+'>'+strValue+'</textarea>';

		if(strLabel.length > 0) {
			strCode += '</div></div>';
		}

		return strCode;
	};

	this.printFormButton = function(strLabel, strAction, strName, strOptions, strDivOptions) {
	
		var strCode = '';
		strCode += '<div class="divCleaner divButton" '+strDivOptions+'><button onclick="'+strAction+'" name="'+strName+'" id="'+strName+'" class="btn" '+strOptions+'>'+strLabel+'</button></div>';

		return strCode;

	};

	this.printSubheadline = function(strLabel) {

		var strCode = '';
		strCode += '<h2>'+strLabel+'</h2>';

		return strCode;

	};

	this.printHeadline = function(strLabel) {

		var strCode = '';
		strCode += '<h1>'+strLabel+'</h1>';

		return strCode;

	};

	this.startFieldset = function(strLegend, sParameter) {

		var strCode = '';
		strCode += '<fieldset '+sParameter+'>';
		strCode += '<legend>'+strLegend+'</legend>';

		return strCode;

	};

	this.endFieldset = function() {

		var strCode = '';
		strCode += '</fieldset>';

		return strCode;

	};

	this.startRow = function() {

		var strCode = '';
		strCode += '<div class="row">';

		return strCode;

	};

	this.endRow = function() {

		var strCode = '';
		strCode += '</div>';

		return strCode;

	};
	

	this.startCol = function(strInput) {

		var strCode = '';
		strCode += '<div class="col-md-6">';

		return strCode;

	};

	this.endCol = function() {

		var strCode = '';
		strCode += '</div>';

		return strCode;

	};
	
	this.startAccordionContainer = function(strInput) {

		var strCode = '';
		strCode += '<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">';

		return strCode;

	};

	this.endAccordionContainer = function() {

		var strCode = '';
		strCode += '</div>';

		return strCode;

	};

	this.startAccordion = function(strTitle) {

		var strCode = '';
//		strCode += '<h2 class="accordionTitle" id="accordion_'+this.intAccordionCount+'">';
//			strCode += '<div class="accordionTitleBorderDiv" style="cursor:pointer;">';
//				strCode += '<div style="border: 1px solid #CCC; padding:3px; background-color:#F8F8F6;">';
//					strCode += '<div style="float:left;">'+strTitle+'</div>';
//					strCode += '<div class="divArrow" style="float:right; position:relative; top:2px;" id="accordion_'+this.intAccordionCount+'_arrow"></div>';
//					strCode += '<div style="clear:both;"></div>';
//				strCode += '</div>';
//			strCode += '</div>';
//		strCode += '</h2>';
//
//		strCode += '<div class="accordionContent">';

		strCode += '<div class="panel panel-default">';
			strCode += '<div class="panel-heading" role="tab" id="heading'+this.intAccordionCount+'">';
			  strCode += '<h4 class="panel-title">';
				strCode += '<a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse'+this.intAccordionCount+'" aria-expanded="false" aria-controls="collapse'+this.intAccordionCount+'">';
				  strCode += ''+strTitle+'';
				strCode += '</a>';
			  strCode += '</h4>';
			strCode += '</div>';
			strCode += '<div id="collapse'+this.intAccordionCount+'" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading'+this.intAccordionCount+'">';
			  strCode += '<div class="panel-body">';


		this.intAccordionCount++;

		return strCode;
	};

	this.endAccordion = function() {

		var strCode = '';
		strCode += '</div></div></div>';

		return strCode;
	}

}

var arrBoxOpen = [];
function switchBox(strBoxId) {

	if(arrBoxOpen[strBoxId] == true) {	
		Effect.SlideUp(strBoxId);
		arrBoxOpen[strBoxId] = false;
	} else {
		Effect.SlideDown(strBoxId);
		arrBoxOpen[strBoxId] = true;
	}

}

Position.getWindowSize = function(w) {
	var array = [];

	w = w ? w : window;
	array.width = array[0] = w.innerWidth || (w.document.documentElement.clientWidth || w.document.body.clientWidth);
	array.height = array[1] = w.innerHeight || (w.document.documentElement.clientHeight || w.document.body.clientHeight);

	return array;
};
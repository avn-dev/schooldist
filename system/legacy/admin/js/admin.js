
var bolReadyState = 1;

function _doUnload() {
	if(bolReadyState) {
        window.setTimeout("_testUnload()", 5);
	}
}

function _testUnload() {
    if(bolReadyState) {
		
		var intOffset;
		if (typeof window.pageYOffset != 'undefined') {
		   intOffset = window.pageYOffset;
		}
		else if (typeof document.compatMode != 'undefined' &&
		     document.compatMode != 'BackCompat') {
		   intOffset = document.documentElement.scrollTop;
		}
		else if (typeof document.body != 'undefined') {
		   intOffset = document.body.scrollTop;
		}
		
		if(document.getElementById('myMessage'))
		{
			document.getElementById('myMessage').style.display = '';
			document.getElementById('myMessage').style.top = intOffset+'px';
		}
    }

}

function processLoading(event) {

	var intCounter = 0;
	var arrRows = $$('table.highlightRows tr');
	for (var index = 0; index < arrRows.length; index++) { 

		if(arrRows[index].className.indexOf('noHighlight') == -1) {
			if(!arrRows[index].id) {
				arrRows[index].id = 'tr_'+intCounter;
			}
			arrRows[index].observe('mouseout', resetHighlightRow); 
			arrRows[index].observe('mousemove', setHighlightRow);
			intCounter++;
		}
		
	}

	if(typeof tinymce !== 'undefined') {
		tinymce.init({
			selector: "textarea.tinymce",
			// General options
			mode: "none",
			theme: "modern",
			skin: "lightgray",
			plugins: [
				"advlist autolink lists link image charmap print preview hr anchor pagebreak",
				"searchreplace wordcount visualblocks visualchars code fullscreen",
				"insertdatetime media nonbreaking save table contextmenu directionality",
				"emoticons template paste textcolor colorpicker textpattern responsivefilemanager"
			],
			menubar: false,
			branding: false,
			statusbar: false,
			toolbar1: "undo redo | styleselect | searchreplace pastetext visualblocks visualchars | bold italic underline | alignleft aligncenter alignright | bullist numlist outdent indent | preview code fullscreen",
			toolbar2: "forecolor backcolor | link image | charmap table | responsivefilemanager",
			toolbar_items_size: 'small',
			image_advtab: true,
			theme_modern_toolbar_location: "top",
			theme_modern_toolbar_align: "left",
			theme_modern_statusbar_location: "none",
			theme_modern_resizing: true,
			theme_modern_path: false,
			readonly: false,
			forced_root_block: false,
			verify_html: false,
			convert_urls: false,
			remove_script_host: true,
			resize: "both",
			external_filemanager_path: "/tinymce/resource/filemanager/",
			filemanager_title: "Responsive Filemanager",
			external_plugins: { 
				"filemanager" : "/tinymce/resource/filemanager/plugin.min.js"
			},
			extended_valid_elements: "style[*]",
			valid_children: "+body[style]"
		});
	}
	
}

function setHighlightRow(event) {
	var objRow = this;
	if(objRow) {
		objRow.addClassName('highlightRow');
	}			
}

function resetHighlightRow(event) {
	var objRow = this; 
	if(objRow) {
		objRow.removeClassName('highlightRow');
	}
}

function showRow(id,on) {

	var oPopupTr = document.getElementById('tr_'+id);
	
	if(on==1) {
		oPopupTr.style.backgroundColor = '#d4ddf0';
	} else {
		oPopupTr.style.backgroundColor = '';
	}

}

function openEditor(obj) {
	window.open('editor.html?target='+obj,'editor','status=no,resizable=yes,menubar=no,scrollbars=yes,width=770,height=550');
}

function go(url) {
	document.location.href = url;
}

//Tooltip
function updateHint(e) {
	//x = (document.all) ? window.event.x + document.body.scrollLeft : e.pageX;
	//y = (document.all) ? window.event.y + document.body.scrollTop  : e.pageY;
	
	eV = window.event;

	if(document.all)
	{
		xPos = screenX;
		yPos = screenY;
	}
	else
	{
		xPos = eV.screenX;
		yPos = eV.screenY;
	}
	if (hint != null) {
		hint.style.left = (xPos - 45) + "px";
		hint.style.top 	= (yPos + 20) + "px";
	}
}

//document.onmousemove = updateHint;
function showHint(id) {
	hint = document.getElementById(id);
	hint.style.display = "block";
	updateHint(document);
}

function hideHint() {
	hint.style.display = "none";
}

function setTranslationMode(oSelect) {

	var bValue = 0;
	if($(oSelect) && $(oSelect).checked) {
		bValue = 1;
	}

	var strParameters = 'component=toolbar&task=set_translation_mode&value='+bValue;

	new Ajax.Request(strAjaxAppPath,
		{
			method:'post',
    		parameters: strParameters
  		}
	);

}

function setDebugmode(oSelect) {

	var strParameters = 'component=toolbar&task=set_debug_mode&value='+$F(oSelect);

	new Ajax.Request(strAjaxAppPath,
		{
			method:'post',
    		parameters: strParameters
  		}
	);

}

/**
 * @author DG
 * @since 28.04.2011
 */
function openUserMessageDialog(oMessages) {
	
	this.message = '<div style="padding:10px" id="LB_messages">';
	$A(oMessages).each(function(obj) {
		this.message += generateUserMessageLine(obj.user, obj.userid,obj.created, obj.message);
	}.bind(this));
	this.message += '</div>';
	
	return new LITBox(this.message, {type:'alert', overlay:true, height:300, width:500, resizable:false, opacity:.9, title: LB_translations.title, fixed: true});
	
}

/**
 * @author DG
 * @since 28.04.2011
 */
function generateUserMessageLine(sUser, iToUser, sFormattedDate, sMessage) {
	
	user_message_count++;
	var html = '';
	
	if(typeof objGUI == 'undefined') {
		objGUI = new GUI;
	}

	html += '<div style="float:left;">'+user_message_count+'. <strong>'+sUser+'</strong>, <em>'+sFormattedDate+'</em></div><div style="float:right"><a title="'+LB_translations.send_to+'" style="cursor:pointer;" id="LB_AnswerDialogLink_'+user_message_count+'" onclick="hideUserMessageNote('+user_message_count+');$(\'LB_AnswerDialog_'+user_message_count+'\').style.display=\'block\';return false;"><img src="/admin/media/comment_edit.png" alt="A"></a></div><div class="divCleaner"></div>';
	html += '<div style="margin:8px 0 10px">'+sMessage+'</div><div style="display:none;" id="LB_AnswerDialog_'+user_message_count+'">';
	html += objGUI.printFormTextarea(LB_translations.message+':', 'LB_AnswerDialogTextarea_'+user_message_count, '', '5', '5', 'style="width:100%;height:60px;"');
	html += objGUI.printFormButton(LB_translations.send, 'sendUserMessage('+iToUser+','+user_message_count+')', '', '', 'style="padding:0"');
	html += '</div><br /><br />';
	return html;
	
}

/**
 * @author DG
 * @since 03.05.2011
 */
function openUserMessageDialogSend(sUser, iToUser) {
	
	if(typeof objGUI == 'undefined') {
		objGUI = new GUI;
	}
	
	if(typeof LB_translations == 'undefined') {
		new Ajax.Request('/system/applications/ajax.php', {
			method: 'post',
			evalJSON: 'force',
			parameters: 'component=user_message&task=getTranslations',
			onSuccess: function(oRequest) {
				LB_translations = oRequest.responseJSON.translations;
				generateUserMessageSendDialog(sUser, iToUser);
			}
		});
	} else {
		generateUserMessageSendDialog(sUser, iToUser);
	}
}

/**
 * @author DG
 * @since 03.05.2011
 */
function generateUserMessageSendDialog(sUser, iToUser) {
	
	if(typeof user_message_count_write == 'undefined') {
		user_message_count_write = 0;
	}
	
	user_message_count_write++;
	
	var html = '';
	html += '<div style="padding:10px"><div id="LB_AnswerDialogSend_'+user_message_count_write+'">';
	html += objGUI.printFormTextarea(LB_translations.message+':', 'LB_AnswerTextareaSend_'+user_message_count_write, '', '5', '5', 'style="width:100%;height:100px;"');
	html += objGUI.printFormButton(LB_translations.send, 'sendUserMessage('+iToUser+','+user_message_count_write+',\'send\')', '', '', 'style="padding:0"');
	html += '</div></div>';
	
	return new LITBox(html, {type:'alert', overlay:true, height:200, width:500, resizable:false, opacity:.9, title: LB_translations.title_send_to.replace(/%s/, sUser), fixed: true});
}

/**
 * @author DG
 * @since 03.05.2011
 */
function sendUserMessage(iToUser, iMessageId, sMode)
{
	var sAnswerDialogTextarea = 'LB_AnswerDialogTextarea_'+iMessageId;
	var sLoadingIndicator = 'LB_LoadingIndicator'+iMessageId;
	var sDivId = 'LB_Note_'+iMessageId;
	var sFormPartId = 'LB_AnswerDialog_'+iMessageId;
	
	if(sMode == 'send') {
		sAnswerDialogTextarea = 'LB_AnswerTextareaSend_'+iMessageId;
		sLoadingIndicator = 'LB_LoadingIndicatorSend'+iMessageId;
		sDivId = 'LB_Note_Send_'+iMessageId;
		sFormPartId = 'LB_AnswerDialogSend_'+iMessageId;
	}
	
	var oFormPart = $(sFormPartId);
	var oLoadingIndicator = new Element('img',{
		src: '/admin/media/indicator.gif',
		alt: '...',
		id: sLoadingIndicator
	});
	
	new Ajax.Request('/system/applications/ajax.php', {
		method: 'post',
		evalJSON: 'force',
		parameters: 'component=user_message&task=send&recipient='+iToUser+'&message='+encodeURIComponent($(sAnswerDialogTextarea).value),
		onLoading: function() {
			oFormPart.insert({before:oLoadingIndicator});
			oFormPart.style.display = 'none';
		},
		onSuccess: function(oRequest) {
			if(oRequest.responseJSON.status == 'success') {
				oLoadingIndicator.remove();
				var oDiv = new Element('div',{
					id: 'LB_Note_'+iMessageId
				}).update('<span style="color:green">'+LB_translations.success+'</span>');
				oFormPart.insert({before:oDiv});
				
			}
		},
		onFailure: function() {
				oLoadingIndicator.remove();
				var oDiv = new Element('div',{
					id: sDivId
				}).update('<span style="color:red">'+LB_translations.failure+'</span>');
				oFormPart.insert({before:oDiv});
		}
	});
}

/**
 * @author DG
 * @since 03.05.2011
 */
function hideUserMessageNote(iMessageId) {
	
	var oNote = $('LB_Note_'+iMessageId);
	if(oNote != null) oNote.remove();
	
}

Event.observe(window, 'load', processLoading);

/* ==================================================================================================== */

function __out(mVar)
{
	var sCode = '';

	var mLength = '';

	if(mVar)
	{
		var sType = typeof mVar;
	}
	else
	{
		var sType = 'Null';
	}

	if(sType == 'object' || sType == 'string')
	{
		mLength = mVar.length;

		if(sType == 'object')
		{
			sType = 'Array';
		}

		if(mLength == undefined)
		{
			var i = 0;

			for(var s in mVar)
			{
				i++;
			}

			mLength = i;

			sType = 'Object';
		}

		if(sType == 'string')
		{
			sType = 'String';
		}
	}

	if(sType == 'number')
	{
		if(parseInt(mVar) != parseFloat(mVar))
		{
			sType = 'Float';
		}
		else
		{
			sType = 'Int';
		}
	}
	else if(sType == 'boolean')
	{
		sType = 'Boolean';

		if(mVar)
		{
			mVar = 'TRUE';
		}
		else
		{
			mVar = 'FALSE';
		}
	}
	else if(sType == 'Null')
	{
		var mVar = 'UNDEFINED';
	}
	else if(sType == 'Array' || sType == 'Object')
	{
		mVar = __outHelp(mVar, 0);
	}

	var sOutType = sType;

	if(mLength != '')
	{
		sOutType = sType + ' (' + mLength + ')'
	}

	sCode += '-------------------- TYPE: ' + sOutType + ' --------------------';

	var sEnd = '';
	for(var i = 0; i < sCode.length; i++)
	{
		sEnd += '-';
	}

	if(sType == 'String' || sType == 'Float' || sType == 'Int' || sType == 'Boolean' || sType == 'Null')
	{
		sCode += "\n\t" + mVar;
	}
	else
	{
		sCode += "\n" + mVar;
	}

	sCode = sCode + "\n" + sEnd;

	alert(sCode);
}

function __outHelp(mVar, iLevel)
{
	var sCode = '';

	if(mVar == null)
	{
		return '';
	}

	var sType = typeof mVar;

	if(sType != 'object')
	{
		return mVar;
	}
	else if(String(mVar).substr(0,8) == '[object ' && String(mVar).substr(0,15) != '[object Object]')
	{
		return String(mVar);
	}

	iLevel++;

	var sLevel = "";
	for(var n = 0; n < iLevel-1; n++)
	{
		sLevel += "\t";
	}

	if(mVar.length == undefined)
	{
		sCode += "Object\n" + sLevel + "{";

		for(var s in mVar)
		{
			sCode += "\n\t" + sLevel + '[' + s + '] => ' + __outHelp(mVar[s], iLevel);
		}
	}
	else
	{
		sCode += "Array\n" + sLevel + "{";

		for(var i = 0; i < mVar.length; i++)
		{
			sCode += "\n\t" + sLevel + '[' + i + '] => ' + __outHelp(mVar[i], iLevel);
		}
	}

	sCode += "\n" + sLevel + "}";

	return sCode;
}

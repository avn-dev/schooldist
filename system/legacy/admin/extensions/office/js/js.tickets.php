<?php
 
include_once(\Util::getDocumentRoot().'system/legacy/admin/includes/main.inc.php');

header('Content-type: text/javascript');

/* ==================================================================================================== */

if(!$_SESSION['gui']['ajax_table'][$_VARS['hash']]) {
	die();
}

/* ==================================================================================================== */

$aConfigArray		= $_SESSION['gui']['ajax_table'][$_VARS['hash']];
$oGUI_Ajax_Table	= new $aConfigArray['ajax_data']['class']($aConfigArray, $_VARS['hash']);
$aConfig 			= $oGUI_Ajax_Table->getConfigArray();
$aAjaxData 			= $aConfig['ajax_data'];
$aHeaderData 		= $aConfig['header_data'];
$aIconsData 		= $aConfig['icon_data'];
$aLayoutData 		= $aConfig['layout_data'];
$aEditDialogData 	= $aConfig['edit_data'];
$aQueryData 		= $aConfig['query_data'];
$aDialogData 		= $aConfig['dialog_data'];
$aRandomData 		= $aConfig['random_data'];
$sRandom 			= $aConfig['random'];

/* ==================================================================================================== */

if(empty($aConfig['edit_id']))
{
	$aConfig['edit_id'] = 'id';
}

/* ==================================================================================================== */

?>

var bRequest = false;
var oOfficeTicketTinyMceConfig = {
	language: "de",
	mode: "none",
	plugins: [
		"template preview paste code fullscreen searchreplace wordcount visualblocks visualchars"
	],
	menubar: false,
	toolbar: "undo redo | template | searchreplace pastetext visualblocks visualchars | bold italic underline | preview code fullscreen",
	toolbar_items_size: 'small',
	theme_modern_toolbar_location: "top",
	theme_modern_toolbar_align: "left",
	theme_modern_statusbar_location: "none",
	theme_modern_resizing: true,
	theme_modern_path: false,
	readonly: false,
	forced_root_block: false,
	verify_html: false,
	relative_urls: false,
	remove_script_host: true,
	resize: "both",
	templates: "/wdmvc/office/document/templates",
	height: 200
};

/* ==================================================================================================== */

function <?=$sRandom?>_loadTableList()
{
	$('<?=$sRandom?>_toolbar_loading').show();

	var strRequestUrl = <?=$aAjaxData['table_url']?>;
	var strParameters = <?=$aAjaxData['table_param']?>;

	if($('<?=$sRandom?>_search_project'))
	{
		// Do nothing
	}
	else
	{
		<?=$sRandom?>_createFilters();
	}

	strParameters += '&filter_project='	+ $('<?=$sRandom?>_search_project').value;
	strParameters += '&filter_state='	+ $('<?=$sRandom?>_search_state').value;
	strParameters += '&filter_type='	+ $('<?=$sRandom?>_search_type').value;

	if($('<?=$sRandom?>_search_author'))
	{
		strParameters += '&filter_author='	+ $('<?=$sRandom?>_search_author').value;
	}

	if($('<?=$sRandom?>_search_user'))
	{
		strParameters += '&filter_user='	+ $('<?=$sRandom?>_search_user').value;
	}

	if($('<?=$sRandom?>_search_cleared').checked)
	{
		strParameters += '&filter_cleared=1';
	}
	else
	{
		strParameters += '&filter_cleared=0';
	}

	var objAjax = new Ajax.Request(
		strRequestUrl,
		{
			method : '<?=$aAjaxData['table_method']?>',
			parameters 	: strParameters,
			onSuccess 	: <?=$sRandom?>_loadTableListCallback,
			onFailure 	: printErrorMessage
		}
	);
}

/* ==================================================================================================== */

function <?=$sRandom?>_loadTableListCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();

	var objList = objData['data']['data'];

	<?=$sRandom?>_iconData = objData['data']['icon'];

	var tbody = $("tbl_tables_<?=$sRandom?>");

	while(tbody.hasChildNodes())
	{
		tbody.removeChild(tbody.firstChild);
	}

	var c 			= 0;
	var tr    		= _Tr.cloneNode(true);
    var objTr   	= _Tr.cloneNode(true);

	if(!objList)
	{
		objList = new Array();
	}

	objList.each(function(aValue, index)
	{
		objTr = tr.cloneNode(false);
		tbody.appendChild(objTr);
		var strId = "<?=$sRandom?>_tr_"+aValue[0];
		objTr.id = strId;

		Event.observe(objTr, 'click', <?=$sRandom?>_checkRow.bindAsEventListener(c, strId));
		Event.observe(objTr, 'dblclick', <?=$sRandom?>_executeAction.bindAsEventListener(c, 'edit'));
		Event.observe(objTr, 'mouseout', resetHighlightRow); 
		Event.observe(objTr, 'mousemove', setHighlightRow);

		<?=$sRandom?>_addCells(objTr, aValue[0], aValue);

		c++;
	});

    tbody = null;

	$('<?=$sRandom?>_toolbar_loading').hide();

	<?=$sRandom?>_checkFilters(objData);

	<?=$sRandom?>_selectedRow = 0;

	<? if($aLayoutData['icons']['show'] != 0) { ?>
		<?=$sRandom?>_checkTableToolbar();
	<? } ?>

	<? if($aLayoutData['sortable']== 1) { ?>
		$('tbl_tables_<?=$sRandom?>').childElements().each(function(oTr)
		{
			oTr.style.cursor = 'move';
		});

		$('tbl_tables_<?=$sRandom?>').style.position = "static";

		Position.includeScrollOffsets = true;

		Sortable.create('tbl_tables_<?=$sRandom?>',
		{
			constraint:'vertical',
			tag:'tr',
			scroll:'<?=$sRandom?>_tableArticles-body',
			onUpdate: function()
			{
				if(
					$('<?=$sRandom?>_search_state').value != '1_5' &&
					$('<?=$sRandom?>_search_state').value != '1'
				) {
					alert('<?=L10N::t('Die Sortierung kann nur unter den Filtern "Neu" und "Bereit zur Umsetzung" vorgenommen werden.')?>');

					<?=$sRandom?>_loadTableList();

					return false;
				}

				new Ajax.Request(<?=$aAjaxData['sort_url']?>, {  
					method: "post",  
					parameters: <?=$aAjaxData['sort_param']?>+'&'+Sortable.serialize("tbl_tables_<?=$sRandom?>")
				});
			}
		});
	<? } ?>

	<?=$sRandom?>_checkTableListHeight();
}

function <?=$sRandom?>_checkFilters(oData)
{
	$('<?=$sRandom?>_search_state').disabled	= false;
	$('<?=$sRandom?>_search_type').disabled		= false;
	$('<?=$sRandom?>_search_cleared').disabled	= false;

	if($('<?=$sRandom?>_search_user'))
	{
		$('<?=$sRandom?>_search_user').disabled	= false;
	}

	if($('<?=$sRandom?>_search_author'))
	{
		$('<?=$sRandom?>_search_author').disabled	= false;
	}

	if
	(
		$('<?=$sRandom?>_filter_search').value != '' &&
		!isNaN($('<?=$sRandom?>_filter_search').value) &&
		oData &&
		oData['data'] &&
		oData['data']['data'] &&
		oData['data']['data'].length == 1 &&
		oData['data']['data'][0][0] == $('<?=$sRandom?>_filter_search').value
	)
	{
		$('<?=$sRandom?>_search_state').disabled	= true;
		$('<?=$sRandom?>_search_type').disabled		= true;
		$('<?=$sRandom?>_search_cleared').disabled	= true;

		if($('<?=$sRandom?>_search_user'))
		{
			$('<?=$sRandom?>_search_user').disabled	= true;
		}
		if($('<?=$sRandom?>_search_author'))
		{
			$('<?=$sRandom?>_search_author').disabled	= true;
		}

		if(oData['single_project'])
		{
			for(var i = 0; i < $('<?=$sRandom?>_search_project').length; i++)
			{
				if($('<?=$sRandom?>_search_project').options[i].value == oData['single_project'])
				{
					$('<?=$sRandom?>_search_project').selectedIndex = i;

					break;
				}
			}
		}
	}
}

/* ==================================================================================================== */

function <?=$sRandom?>_prepareAddDialog()
{
	<?=$sRandom?>_openEditDialog(false);

	$('editRow_notices').hide();

	$('editRow_done').remove();

	$('save[description]').insert({ after: '<div id="uploader"></div>'});

	try {
		tinyMCE.EditorManager.createEditor('save[description]', oOfficeTicketTinyMceConfig).render();
	} catch(e){}

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	var oButtonsDIV = $('editRow_notices').next();

	oButtonsDIV.innerHTML = '';

	var sCode = '<input type="button" class="btn" value="<?=L10N::t('Speichern als inaktiv')?>" onclick="<?=$sRandom?>_save(0);" style="opacity:1; filter:alpha(opacity=100);" />';
	sCode += '<input type="button" class="btn" value="<?=L10N::t('Speichern als neu')?>" onclick="<?=$sRandom?>_save(1);" style="opacity:1; filter:alpha(opacity=100);" />';

	oButtonsDIV.innerHTML = sCode;

	if(!iFrontend)
	{
		Event.observe($('save[type]'), 'change', function()
		{
			showBilling($('save[type]'));
		});
	}
}

/* ==================================================================================================== */

function <?=$sRandom?>_prepareEditDialog(intRowId)
{
	var strRequestUrl = <?=$aAjaxData['edit_url']?>;
	var strParameters = <?=$aAjaxData['edit_param']?>+'&row_id='+intRowId;

	if(bRequest)
	{
		return false;
	}

	bRequest = true;

	var objAjax = new Ajax.Request(
			strRequestUrl,
			{
				method : '<?=$aAjaxData['edit_method']?>',
				parameters : strParameters,
				onComplete : <?=$sRandom?>_openEditTicketDialog
			}
	);
}

/* ==================================================================================================== */

function <?=$sRandom?>_openEditTicketDialog(oResponse)
{
	var aData = oResponse.responseText.evalJSON();

	bRequest = false;

	<?=$sRandom?>_selectedRow = '<?=$sRandom?>_tr_' + aData['id'];

	<?=$sRandom?>_openEditDialog(oResponse);

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create the notices list

	var sCode = '';

	var i = 0;

	aData['notices'].each(function(aNotice)
	{
		// BG color
		var sColorBG = '#F5F5F5';

		if((i % 2) == 0)
		{
			sColorBG = '#FCFCFC';
		}

		sCode += '<div style="width:650px;">';
			sCode += '<div style="border: 2px solid #CCC; background-color:' + sColorBG + '; margin: 3px 0;">';
				sCode += '<div style="padding:3px;">';
					sCode += '<div style="border-bottom: 1px solid #CCC; padding:2px;">';
						sCode += '<div style="float:left;">';
							if(aNotice['contact_id'] > 0)
							{
								var sFrom = aNotice['contact'];
							}
							else if(aNotice['user_id'] > 0)
							{
								var sFrom = aNotice['user'];
							}
							else
							{
								var sFrom = aNotice['company'];
							}
							sCode += aNotice['created'] + ' / ' + sFrom;
						sCode += '</div>';

						sCode += '<div style="float:right;">';
							sCode += 'Status: ' + aNotice['out_state'];
							if(aNotice['done'] > 0)
							{
								sCode += ', Fortschritt: ' + aNotice['done'] + '%';
							}
						sCode += '</div>';

						sCode += '<div style="clear:both;"></div>';
					sCode += '</div>';

					

					sCode += '<div style="padding:2px;">';
						sCode += aNotice['text'];
					sCode += '</div>';

					if(aNotice['files'])
					{
						sCode += '<div style="border-top: 1px solid #CCC; padding:2px;">Dateien: | ';
							aNotice['files'].each(function(aFile)
							{
								sCode += '<a href="/'+aData['upload_path']+'' + aFile[0] + '" onclick="window.open(this.href); return false;">' + aFile[1] + '</a> | ';
							});
						sCode += '</div>';
					}
				sCode += '</div>';
			sCode += '</div>';
		sCode += '</div>';

		i++;
	});

	if(aData['state'] != 0)
	{
		sCode += '<textarea class="txt" id="newNotice" style="padding:2px; width:650px; height:100px;"></textarea>';
	}

	var sDoneSelect = '';

	if(aData['state'] == 2 && !iFrontend)
	{
		sCode += '<div style="width:650px; margin-top:3px; height:18px; line-height:18px;">';
			sDoneSelect += '<select id="save[done_dd]" class="txt">';
				for(var n = 0; n <= 100; n += 5)
				{
					sDoneSelect += '<option value="' + n + '">' + n + ' %</option>';
				}
			sDoneSelect += '</select>';

			sCode += '<div style="float:left;">';
				sCode += '<b><?=L10N::t('Fortschritt:')?></b> ';
				sCode += sDoneSelect;
			sCode += '</div>';
			sCode += '<div style="float:right; margin-right:2px;">';
				sCode += '<a href="" onclick="$(\'uploader\').toggle(); return false;"><?=L10N::t('Upload')?></a>';
			sCode += '</div>';
			sCode += '<div style="clear:both;"></div>';
		sCode += '</div>';
	}
	else if(!iFrontend)
	{
		sCode += '<div style="width:650px; margin-top:3px; height:18px; line-height:18px;">';
			sCode += '<div style="float:right; margin-right:2px;">';
				sCode += '<a href="" onclick="$(\'uploader\').toggle(); return false;"><?=L10N::t('Upload')?></a>';
			sCode += '</div>';
			sCode += '<div style="clear:both;"></div>';
		sCode += '</div>';
	}

	var sHidden = '';

	if(!iFrontend)
	{
		sHidden = 'style="display:none;"';
	}

	sCode += '<div id="uploader" ' + sHidden + '></div>';

	$('noticesList').innerHTML = sCode;

	// Ticket ID reinschreiben
	var sTicketId = '<div style="background-color:#EEEEEE; margin:5px;padding: 2px 5px;">Ticket ID: '+aData['id']+'</div>';
	$('dialog_content').insert({top:sTicketId});
	
	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	if(aData['state'] != 0)
	{
		$('save[title]').readOnly	= true;
		$('save[area]').readOnly	= true;
		$('save[type]').disabled	= true;
	}

	if($('save[billing]'))
	{
		if(aData['type'] == 'ext' && aData['state'] != 0)
		{
			$('save[billing]').disabled = true;
		}
		else if(aData['state'] != 0 && aData['state'] != 1 && aData['state'] != 3 && iFrontend)
		{
			$('editRow_billing').hide();
		}
	}

	if((aData['state'] == 1 || aData['state'] == 3) && iFrontend)
	{
		$('save[type]').disabled = false;
		$('save[billing]').disabled = false;

		if(aData['type'] == 'bug')
		{
			$('editRow_billing').hide();
		}
	}

	if(aData['billing'] == 0 && (aData['hours'] > 0 || aData['money'] > 0))
	{
		if(aData['hours'] > 0)
		{
			var sCosts = aData['hours'] + ' Std.';
		}
		else if(aData['money'] > 0)
		{
			var sCosts = aData['money'] + ' €';
		}
	}
	else if(aData['billing'] == 1 && aData['type'] == 'ext')
	{
		var sCosts = 'Nach Aufwand';
	}
	else
	{
		var sCosts = '';
	}

	var sCode = '';
	sCode += '<div style="margin:5px; background-color:rgb(238, 238, 238); padding: 0 5px;" id="editRow_costs" class="row_edit">';
		sCode += '<label for="costs" style="padding: 4px 0px; width: 100px;">Rechnung:</label>';
		sCode += '<div style="float:left;">';
			sCode += '<div style="padding: 4px 4px 2px;">';
				sCode += sCosts;
			sCode += '</div>';
		sCode += '</div>';
		sCode += '<div style="overflow:hidden; clear:both; height:1px;"/></div>';
	sCode += '</div>';

	if(aData['type'] == 'ext' && aData['state'] != 0)
	{
		$('editRow_description').insert({before: sCode});
	}

	var sDescription = $('save[description]').value;

	var sFiles = '';

	if(aData['descr_files'] && aData['descr_files'].length > 0)
	{
		sFiles += '<div style="padding:2px;">Dateien: | ';
			aData['descr_files'].each(function(aFile)
			{
				sFiles += '<a href="/'+aData['upload_path']+'' + aFile[0] + '" onclick="window.open(this.href); return false;">' + aFile[1] + '</a> | ';
			});
		sFiles += '</div>';
	}

	if(aData['state'] != 0)
	{
		$('save[description]').replace(aData['desc_contact'] + '<br />' + sDescription + sFiles);
	}
	else
	{
		$('save[description]').insert({after: sFiles});

		try {
			tinyMCE.EditorManager.createEditor('save[description]', oOfficeTicketTinyMceConfig).render();
		} catch(e){}
	}

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	if(aData['state'] != 2 && aData['state'] != 6)
	{
		if($('editRow_done'))
		{
			$('editRow_done').remove();
		}
	}
	else
	{
		if(aData['done'])
		{
			$('save[done]').replace(aData['done'] + '%');
		}
		else
		{
			$('editRow_done').remove();
		}
	}

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Manage buttons

	var oButtonsDIV = $('editRow_notices').next();

	oButtonsDIV.innerHTML = '';

	var sCode		= '';
	var sInputs		= '';

	sCode += '<div style="float:right;">';
		sCode += '<input type="hidden" id="lastState" value="' + aData['state'] + '" />';

		if(aData['state'] == 2 && !iFrontend)
		{
			sCode += '<input type="button" class="btn" value="Speichern" ondblclick="return false;" onclick="<?=$sRandom?>_save(2);" style="opacity:1; filter:alpha(opacity=100);" />';
		}

		aData['buttons'].each(function(aButton)
		{
			sCode += '<input type="button" class="btn" value="' + aButton['title'] + '" ondblclick="return false;" onclick="<?=$sRandom?>_save(' + aButton['state'] + ');" style="opacity:1; filter:alpha(opacity=100);" />';

			if(aButton['inputs'])
			{
				sInputs += '<div style="float:left; margin: 5px 0 0 5px;">';
					sInputs += '<b><?=L10N::t('Aufwand:')?></b>';
					sInputs += ' <input class="txt w50" id="hours" /> Std. / <input class="txt w50" id="money" /> €';
				sInputs += '</div>';
			}
		});

	sCode += '</div><div style="clear:both;"></div>';

	oButtonsDIV.innerHTML = sInputs + sCode;

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Frontend addition

	get_flash_uploader();

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	bRequest = false;
}

/* ==================================================================================================== */

function showBilling(oType)
{
	if(oType.value == 'ext')
	{
		$('editRow_billing').show();
	}
	else
	{
		$('editRow_billing').hide();
	}
}

/* ==================================================================================================== */

function <?=$sRandom?>_save(iState)
{
	var sParams = 'action=save_ticket';

	if(bRequest)
	{
		return false;
	}

	bRequest = true;

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	if($('lastState'))
	{
		sParams += '&lastState=' + $('lastState').value;
	}

	if(!$('newNotice')) {
		oNotice = tinyMCE.get('save[description]');
		var sNotice = oNotice.getContent();
	} else {
		sNotice = $('newNotice').value;
	}

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	sParams += '&project_id='	+ $('<?=$sRandom?>_search_project').value;
	sParams += '&ticket_id='	+ $('<?=$sRandom?>_EditDialogId').value;
	sParams += '&state='		+ iState;
	sParams += '&notice='		+ encodeURIComponent(sNotice);
	sParams += '&title='		+ encodeURIComponent($('save[title]').value);
	sParams += '&area='			+ encodeURIComponent($('save[area]').value);
	sParams += '&type='			+ $('save[type]').value;
	sParams += '&hash='			+ '<?=$_VARS['hash']?>_<?=session_id()?>';

	if($('save[billing]'))
	{
		sParams += '&billing='	+ $('save[billing]').value;
	}

	if(!iFrontend)
	{
		if(iState == 4 && $('lastState') && $('lastState') != 4 && $('hours'))
		{
			sParams += '&hours='	+ $('hours').value;
			sParams += '&money='	+ $('money').value;
		}

		if(iState == 2 && $('save[done_dd]') && parseInt($('save[done_dd]').value) > 0)
		{
			sParams += '&done=' + $('save[done_dd]').value;
		}

		if($('<?=$sRandom?>_EditDialogId').value == 0)
		{
			// New ticket over backend
			sParams += '&backend_new=1';
		}
	}

	var oAjax = new Ajax.Request(
			'/admin/extensions/office/ticket.ajax.php',
			{
				method		: 'post',
				parameters	: sParams,
				onComplete	: <?=$sRandom?>_saveCallback
			}
	);
}

function <?=$sRandom?>_saveCallback(oResponse)
{
	var aData = oResponse.responseText.evalJSON();

	if(aData['errors'])
	{
		if(aData['errors']['EMPTY'] == 'title')
		{
			updateLBTitle('<span style="color:red;"><?=L10N::t('Fehler! Bitte geben Sie ein Titel ein.')?></span>');
		}
		else if(aData['errors']['EMPTY'] == 'notice')
		{
			updateLBTitle('<span style="color:red;"><?=L10N::t('Fehler! Bitte geben Sie Ihre Nachricht / Beschreibung ein.')?></span>');
		}
		else if(aData['errors']['EMPTY'] == 'costs')
		{
			updateLBTitle('<span style="color:red;"><?=L10N::t('Fehler! Bitte geben Sie die Aufwandsschätzung ein.')?></span>');
		}

		bRequest = false;

		return;
	}

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	bRequest = false;

	updateLBTitle('<span style="color:green;"><?=L10N::t('Eintrag erfolgreich gespeichert.')?></span>');

	<?=$sRandom?>_loadTableList();

	<?=$sRandom?>_prepareEditDialog(aData['id']);
}

/* ==================================================================================================== */

function <?=$sRandom?>_setLive()
{
	var sParams = 'action=set_live';

	var aCBs = $A($$('.flag'));

	aCBs.each(function(oCB)
	{
		if(oCB.checked)
		{
			sParams += '&flags[]=' + oCB.value;
		}
	});

	var oAjax = new Ajax.Request(
			'/admin/extensions/office/ticket.ajax.php',
			{
				method		: 'post',
				parameters	: sParams,
				onComplete	: <?=$sRandom?>_loadTableList
			}
	);

	$('flag_Main').checked = false;
}

/* ==================================================================================================== */

function checkCBs(bMain, sClass)
{
	var aCBs = $A($$('.' + sClass));

	if(bMain == 1)
	{
		aCBs.each(function(oCB)
		{
			oCB.checked = $(sClass + '_Main').checked;
		});
	}
	else
	{
		var iChecked = 0;
		aCBs.each(function(oCB)
		{
			if(oCB.checked == true)
			{
				iChecked++;
			}
		});

		if(iChecked == aCBs.length)
		{
			$(sClass + '_Main').checked = true;
		}
		else
		{
			$(sClass + '_Main').checked = false;
		}
	}
}

/* ==================================================================================================== */
/* ==================================================================================================== */ // UPLOAD
/* ==================================================================================================== */

// Major version of Flash required
var requiredMajorVersion = 9;
// Minor version of Flash required
var requiredMinorVersion = 0;
// Minor version of Flash required
var requiredRevision = 0;

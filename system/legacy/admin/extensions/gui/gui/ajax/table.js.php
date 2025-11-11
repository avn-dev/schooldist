<?PHP

require_once(Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");
header("Content-type: text/javascript");

if(!$_SESSION['gui']['ajax_table'][$_VARS['hash']]) {
	die();
}

$aConfigArray = $_SESSION['gui']['ajax_table'][$_VARS['hash']];

$oGUI_Ajax_Table = new $aConfigArray['ajax_data']['class']($aConfigArray,$_VARS['hash']);

$aConfig = $oGUI_Ajax_Table->getConfigArray();
$aAjaxData = $aConfig['ajax_data'];
$aHeaderData = $aConfig['header_data'];
$aIconsData = $aConfig['icon_data'];
$aLayoutData = $aConfig['layout_data'];
$aEditDialogData = $aConfig['edit_data'];
$aQueryData = $aConfig['query_data'];
$aDialogData = $aConfig['dialog_data'];
$sRandom = $aConfig['random'];
$sHtmlPath = $aConfig['html_path'];

if(empty($aConfig['edit_id'])) {
	$aConfig['edit_id'] = 'id';
}

?>

var iTableHeight_<?=$sRandom?> = '<?=$aConfig['layout_data']['table_height']?>';
var masterSelectedRow = '';
<? if($aLayoutData['icons']['show'] != 0){ ?>
var <?=$sRandom?>_arrIcons = 
	[
<?
	$i = 1;
	$iIcons = count($aIconsData);
	foreach((array)$aIconsData as $aIcon){	
?>
		'<?=$sRandom?>_toolbar_<?=$aIcon['action']?>'
<?	
		if($i < $iIcons){
?>
		,
<?	
		}
	}
?>
	];
<? } ?>
var <?=$sRandom?>_arrIconState = [];



var <?=$sRandom?>_bCheckToolbarInProgress = 0;
var <?=$sRandom?>_selectedRow = 0;
var <?=$sRandom?>_iconData;
var <?=$sRandom?>_globalData = new Array();
var _Tr = new Element("tr");
var _Td = new Element("td");
var <?=$sRandom?>_pagination_offset = 0;
var <?=$sRandom?>_pagination_total = 0;
var <?=$sRandom?>_pagination_end = 0;
var <?=$sRandom?>_pagination_show = 0;

var <?=$sRandom?>_sOrderString = '';
var <?=$sRandom?>_sOrder = '';

var <?=$sRandom?>_oOldThTag = '';
/* ====================================================================== */

function <?=$sRandom?>_table_sort(sOrderString, sDefaultOrder, oTh) {

	$$('.sortdesc').each(function(oOldTh){
		oOldTh.className='';
	});
	
	$$('.sortasc').each(function(oOldTh){
		oOldTh.className='';
	});
	
	if(<?=$sRandom?>_sOrderString != sOrderString){
		<?=$sRandom?>_sOrder = '';
	}

	if( <?=$sRandom?>_sOrder == ''){
		<?=$sRandom?>_sOrder = sDefaultOrder;
	}
	
	if(<?=$sRandom?>_sOrder == 'ASC'){
		<?=$sRandom?>_sOrder = 'DESC';
		oTh.className='sortdesc';
	}else{
		<?=$sRandom?>_sOrder = 'ASC';
		oTh.className='sortasc';
	}
	<?=$sRandom?>_sOrderString = sOrderString;
	
	<?=$sRandom?>_loadTableList();
}


var sLastClass = ""; 

function <?=$sRandom?>_checkRow(e, strId) {
	var objRow = $(strId);
	if(
		<?=$sRandom?>_selectedRow && 
		$(<?=$sRandom?>_selectedRow)
	) {
		$(<?=$sRandom?>_selectedRow).className = sLastClass;
	}
	if(objRow.className != "selectedRow"){
		sLastClass = objRow.className;
		objRow.className = "selectedRow";
	} else {
		objRow.className = sLastClass;
	}

	<?=$sRandom?>_selectedRow = strId;
<? if($aLayoutData['icons']['show'] != 0){ ?>
	<?=$sRandom?>_checkTableToolbar();
<? } ?>
		<?
	if($aAjaxData['otherTableRandomString'] != ""){
		?>
		masterSelectedRow = <?=$sRandom?>_selectedRow;
		<?=$aAjaxData['otherTableRandomString']?>_loadTableList();
		<?
	}
	?>
}

/* ====================================================================== */
<? if($aLayoutData['icons']['show'] != 0){ ?>
function <?=$sRandom?>_checkTableToolbar() {

	if(!<?=$sRandom?>_bCheckToolbarInProgress) {

		<?=$sRandom?>_bCheckToolbarInProgress = 1;

		if(<?=$sRandom?>_selectedRow) {
			
			var intRowId = <?=$sRandom?>_selectedRow.replace(/<?=$sRandom?>_tr_/, '');
			<?=$sRandom?>_checkTableToolbarCallback(intRowId);
			
		} else {

		<?
			$i = 1;
			$iIcons = count($aIconsData);
			foreach((array)$aIconsData as $aIcon){	
				if($aIcon['active'] == 1){
		?>
				<?=$sRandom?>_switchTableToolbarIcon('<?=$sRandom?>_toolbar_<?=$aIcon['action']?>',true);
		<?	
		
				} else {
		?>
				<?=$sRandom?>_switchTableToolbarIcon('<?=$sRandom?>_toolbar_<?=$aIcon['action']?>', 0);
		<?
				}
			}
		?>
			<?=$sRandom?>_bCheckToolbarInProgress = 0;

		}

	}

}

/* ====================================================================== */

function <?=$sRandom?>_checkTableToolbarCallback(rowId) {

	<?=$sRandom?>_bCheckToolbarInProgress = 1;
	var arrList = <?=$sRandom?>_iconData[rowId];

	arrList = Object.values(arrList);

	<?=$sRandom?>_arrIcons.each(function(strIcon) {

		var iActive = 0;
		
		arrList.each(function(strIcon2) {
	
			var strIcon_ = '<?=$sRandom?>_toolbar_'+strIcon2;
			if(strIcon_ == strIcon) {
				iActive = 1;
			}
	
		});
		
		if(iActive == 1){
			<?=$sRandom?>_switchTableToolbarIcon(strIcon, 1);
		} else {
			<?=$sRandom?>_switchTableToolbarIcon(strIcon, 0);
		}
		

	});

	<?=$sRandom?>_bCheckToolbarInProgress = 0;
}

/* ====================================================================== */

function <?=$sRandom?>_switchTableToolbarIcon(strIcon, bolShow) {

	if(strIcon != undefined){

		var objIcon = $(strIcon);

		if(bolShow) {
			
			if(
				<?=$sRandom?>_arrIconState[strIcon] == undefined ||
				<?=$sRandom?>_arrIconState[strIcon] == 0
			) {
				$j(objIcon).fadeIn();
				<?=$sRandom?>_arrIconState[strIcon] = 1;
			}
			
		} else {
			if(
				<?=$sRandom?>_arrIconState[strIcon] == undefined ||
				<?=$sRandom?>_arrIconState[strIcon] == 1
			) {
				$j(objIcon).fadeTo(0.2);
				<?=$sRandom?>_arrIconState[strIcon] = 0;
			}
			
		}
	}	
}

<? } ?>

/* ====================================================================== */

function <?=$sRandom?>_export_csv() {
	
	var paginationParameters = '&offset='+<?=$sRandom?>_pagination_offset;

	var strRequestUrl = <?=$aAjaxData['export_csv_url']?>;
	var strParameters = <?=$aAjaxData['export_csv_param']?>+paginationParameters;
	
	go(strRequestUrl+'?'+strParameters);

}

function <?=$sRandom?>_export_xls() {
	
	var paginationParameters = '&offset='+<?=$sRandom?>_pagination_offset;

	var strRequestUrl = <?=$aAjaxData['export_xls_url']?>;
	var strParameters = <?=$aAjaxData['export_xls_param']?>+paginationParameters;
	
	go(strRequestUrl+'?'+strParameters);
	
}

/* ====================================================================== */
function <?=$sRandom?>_loadPagination(){
	<?=$sRandom?>_loadTableList();
}

function <?=$sRandom?>_loadTableList() {
	
	var paginationParameters = '&offset='+<?=$sRandom?>_pagination_offset;

	var oderParameters = '&sOrderString='+<?=$sRandom?>_sOrderString+'&sOrder='+<?=$sRandom?>_sOrder;

	<?=$sRandom?>_showToolbarLoading();
	var strRequestUrl = <?=$aAjaxData['table_url']?>;
	var strParameters = <?=$aAjaxData['table_param']?>+paginationParameters+oderParameters;
	
	if(masterSelectedRow) {
		var aParts = masterSelectedRow.split(/_/);
		var iMasterRows = aParts[3];
		strParameters += '&master_selected_row='+iMasterRows;
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

function <?=$sRandom?>_hideToolbarLoading(){
	if($('<?=$sRandom?>_toolbar_loading')){
		$('<?=$sRandom?>_toolbar_loading').hide();
	}
}

function <?=$sRandom?>_showToolbarLoading(){
	if($('<?=$sRandom?>_toolbar_loading')){
		$('<?=$sRandom?>_toolbar_loading').show();
	}
}

/* ====================================================================== */

function printErrorMessage(){
	alert('Fehler beim Laden der Daten');
}

function <?=$sRandom?>_loadTableListCallback(objResponse) {

	<?=$sRandom?>_checkTableListHeight();

	var objData 		= objResponse.responseText.evalJSON();
	var objList 		= objData['data'];
	var objPagination 	= objData['pagination'];
	// Daten global speichern für Edit Dialog
	<?=$sRandom?>_globalData		= objData['data'];
	// Icon daten globalisieren
	<?=$sRandom?>_iconData		= objData['icon'];
	

	var tbody 		= $("tbl_tables_<?=$sRandom?>");
	while(tbody.hasChildNodes()) {
		tbody.removeChild(tbody.firstChild);
	}

	var c 			= 0;
	
	var tr    		= _Tr.cloneNode(true);

    var objTr   	= _Tr.cloneNode(true);
	if(!objList){
		objList = new Array;
	}
	<?
	if($aLayoutData['sortable']== 1){
?>
		objTr.style.cursor='pointer';
<?
	}
?>
	objList.each(function(aValue, index) {
		
	 	objTr = tr.cloneNode(false);
	    tbody.appendChild(objTr);
	    var strId = "<?=$sRandom?>_tr_"+aValue[0];
	    objTr.id = strId;
	    Event.observe(objTr, 'click', <?=$sRandom?>_checkRow.bindAsEventListener(c, strId));
<? if($aLayoutData['icons']['show'] != 0){ ?>
		Event.observe(objTr, 'dblclick', <?=$sRandom?>_executeAction.bindAsEventListener(c, 'edit'));
<? } ?>
		Event.observe(objTr, 'mouseout', resetHighlightRow); 
		Event.observe(objTr, 'mousemove', setHighlightRow);

		<?=$sRandom?>_addCells(objTr, aValue[0],aValue);
		c++;
	});
    tbody = null;
    
	<?=$sRandom?>_hideToolbarLoading();

	<?=$sRandom?>_selectedRow = 0;
<? if($aLayoutData['icons']['show'] != 0){ ?>
	<?=$sRandom?>_checkTableToolbar();

	<?
	}
	if($aLayoutData['pagination']['show'] != 0){ ?>
	
	<?=$sRandom?>_pagination_offset = objPagination['offset'];
	<?=$sRandom?>_pagination_end = objPagination['end'];
	<?=$sRandom?>_pagination_total = objPagination['total'];
	<?=$sRandom?>_pagination_show = objPagination['show'];

	if(<?=$sRandom?>_pagination_total > 0) {
		$('<?=$sRandom?>_pagination_offset').update(<?=$sRandom?>_pagination_offset + 1);
		$('<?=$sRandom?>_pagination_end').update(<?=$sRandom?>_pagination_end);
		$('<?=$sRandom?>_pagination_total').update(<?=$sRandom?>_pagination_total);
	} else {
		$('<?=$sRandom?>_pagination_offset').update(0);
		$('<?=$sRandom?>_pagination_end').update(0);
		$('<?=$sRandom?>_pagination_total').update(0);
	}	

	<?
	}
	if($aLayoutData['sortable']== 1){
		?>
	$('tbl_tables_<?=$sRandom?>').childElements().each(function(oTr){
		oTr.style.cursor = 'move';
	});
	$('tbl_tables_<?=$sRandom?>').style.position = "static";
	Position.includeScrollOffsets = true;
	Sortable.create('tbl_tables_<?=$sRandom?>',{
		constraint:'vertical',
		tag:'tr',
		scroll:'<?=$sRandom?>_tableArticles-body',
		onUpdate: function() {  
		  new Ajax.Request(<?=$aAjaxData['sort_url']?>, {  
			  method: "post",  
			  parameters: <?=$aAjaxData['sort_param']?>+'&'+Sortable.serialize("tbl_tables_<?=$sRandom?>")
			});
		}
	});
<?
	}
?>
	<?=$sRandom?>_checkTableListHeight();
}

function <?=$sRandom?>_pagination_first(){
	
	<?=$sRandom?>_pagination_offset = 0;
	<?=$sRandom?>_loadPagination();
}
function <?=$sRandom?>_pagination_last(){
	
	var iPage = Math.floor(<?=$sRandom?>_pagination_total / <?=$sRandom?>_pagination_show);
	
	<?=$sRandom?>_pagination_offset = iPage * <?=$sRandom?>_pagination_show;
	<?=$sRandom?>_loadPagination();

}
function <?=$sRandom?>_pagination_next(){

	if((<?=$sRandom?>_pagination_offset + <?=$sRandom?>_pagination_show) < <?=$sRandom?>_pagination_total){
		<?=$sRandom?>_pagination_offset = <?=$sRandom?>_pagination_offset + <?=$sRandom?>_pagination_show;
		<?=$sRandom?>_loadPagination();
	}
	
}
function <?=$sRandom?>_pagination_back(){
	
	if((<?=$sRandom?>_pagination_offset - <?=$sRandom?>_pagination_show) >= 0){
		<?=$sRandom?>_pagination_offset = <?=$sRandom?>_pagination_offset - <?=$sRandom?>_pagination_show;
		<?=$sRandom?>_loadPagination();
	}
	
}

function savePosition(aJsonArray){
	var strRequestUrl = <?=$aAjaxData['sort_url']?>;
	var strParameters = <?=$aAjaxData['sort_param']?>+'&aSortJSON='+aJsonArray;

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method : 'post',
								parameters : strParameters
							}
	); 
}

/* ====================================================================== */

function <?=$sRandom?>_addCells(tr, cnt, arrList) {
	
<?
	$i = 1;
	foreach((array)$aHeaderData as $aHeader){	
?>
		var td<?=$i?> = _Td.cloneNode(true);
        tr.appendChild(td<?=$i?>);

		// TODO: edit by sk@plan-i.de
		// prevent ie error (no cell, when no value is insert)
		if(arrList[<?=$i?>] == '')
		{
			arrList[<?=$i?>] = '&nbsp;';
		}

		td<?=$i?>.innerHTML = arrList[<?=$i?>];
<?
		$i++;
		
	}
?>     

}

/* ====================================================================== */

var <?=$sRandom?>_loadTableListObserver;
var <?=$sRandom?>_loadTableListEventElement;

function <?=$sRandom?>_prepareLoadTableList(objEvent) {

	if(objEvent) {
		<?=$sRandom?>_loadTableListEventElement = objEvent;
	}

	if(<?=$sRandom?>_loadTableListObserver) clearTimeout(<?=$sRandom?>_loadTableListObserver);
	<?=$sRandom?>_loadTableListObserver = setTimeout(<?=$sRandom?>_loadTableList.bind(), 500);

}

/* ====================================================================== */

<? if($aLayoutData['icons']['show'] != 0){ ?>
function <?=$sRandom?>_executeAction(strId, strAction)
{
	if(<?=$sRandom?>_arrIconState['<?=$sRandom?>_toolbar_'+strAction] == 0)
	{
		alert("Diese Aktion ist nicht zulässig!");
		return false;
	}

	var intRowId = 0;

	if(strAction != 'new' && <?=$sRandom?>_selectedRow)
	{
		intRowId = <?=$sRandom?>_selectedRow.replace(/<?=$sRandom?>_tr_/, '');
	}
	switch(strAction)
	{
	
<?
	foreach((array)$aIconsData as $aIcon){	
?>
	case "<?=$aIcon['action']?>":
		<?=$sRandom?>_<?=$aIcon['function']?>
		break;
<?
	}
?>
		default:
			alert("Diese Aktion ist nicht zulässig!!");
			break;
	}
}
<? } ?>
/* ====================================================================== */



function <?=$sRandom?>_checkTableListHeight() {

	<?=$sRandom?>_hideToolbarLoading();

	var intHeight = 0;

	intHeight = document.documentElement.clientHeight;
	intWidth = document.documentElement.clientWidth;

	if(intHeight == 0) {
		intHeight = document.body.clientHeight;
	}
	if(intWidth == 0) {
		intWidth = document.body.clientWidth;
	}

	intWidth -= 50;
	
	var iHeadlineHeight = $('gui_list_headline').getHeight();
	
	console.debug(iHeadlineHeight);
	console.debug(intHeight);
	
	$('gui_list_content').style.height = (intHeight-iHeadlineHeight) + 'px'
	
	var iHeightBar = 120;
	
	var aItems = $$('.divHeader');

	if(
		aItems &&
		aItems[0]
	) {
		iHeightBar = aItems[0].getHeight();
	}

	var aTables = $$('.scroll-table-body');

	var iCountTables = aTables.length;

	if(iCountTables > 1 && <?=$aLayoutData['manual_height']?> == 0){
		
		intHeight = intHeight - (iCountTables * iHeightBar);
		
		intHeight = (intHeight / iCountTables);
		
		aTables.each(function(oTable){
			oTable.style.height = intHeight + 'px'
			oTable.style.width = intWidth + 'px'			
		});
		
	} else {

		intHeight = intHeight - iHeightBar - iHeadlineHeight - 50;

		var objTable = $('<?=$sRandom?>_tableArticles-body');
		var objTableHead = $('<?=$sRandom?>_tableArticles-head');
		if(objTableHead) {
			var iTableHead = objTableHead.getHeight();
			intHeight = intHeight - iTableHead - 5;
			intHeight = intHeight+'px';
		} else {
			if(iTableHeight_<?=$sRandom?> != 'auto'){
				intHeight = iTableHeight_<?=$sRandom?>;
			} else {
				intHeight = intHeight+'px';
			}
		} 
		
		if($('<?=$sRandom?>_tableArticles-head')){
			var objTableHead = objTableHead.down('table');
		}
		 
		if(objTable) {

			objTable.style.height = intHeight;

			objTable.style.width = intWidth + 'px';

			if(objTable.down('table')){
				objTable.down('table').style.width = (parseInt(intWidth) - 20) + 'px';
				if(objTableHead){
					objTableHead.style.width = (parseInt(intWidth) - 20) + 'px';
				}
			}			
		} else {
			// was macht das??
			//document.write('<style>.scroll-table-body { height: ' + intHeight + '; width: ' + intWidth + 'px; }</style>');
		}

	}

	//resetTableSize();

}

/* ====================================================================== */
var <?=$sRandom?>_objLitBox;
function <?=$sRandom?>_prepareEditDialog(intRowId)
{
	var strRequestUrl = <?=$aAjaxData['edit_url']?>;
	var strParameters = <?=$aAjaxData['edit_param']?>+'&row_id='+intRowId;

	if(masterSelectedRow) {
		var aParts = masterSelectedRow.split(/_/);
		var iMasterRows = aParts[3];
		strParameters += '&master_selected_row='+iMasterRows;
	}

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method : '<?=$aAjaxData['edit_method']?>',
								parameters : strParameters,
								onComplete : <?=$sRandom?>_openEditDialog
							}
	); 
}


function <?=$sRandom?>_prepareAddDialog(){
	<?=$sRandom?>_openEditDialog(false);
}

/* ====================================================================== */

function <?=$sRandom?>_changeTabs(sElementId){
	$$('.tab').each(function(oDiv){
		if(sElementId != oDiv.id){
			oDiv.hide();
			if($(oDiv.id+'_btn')){
				$(oDiv.id+'_btn').removeClassName('infoBoxTabsHeadActive');
			}
		}else {
			oDiv.show();
			if($(oDiv.id+'_btn')){
				$(oDiv.id+'_btn').addClassName('infoBoxTabsHeadActive');
			}
		}
	});
}


function <?=$sRandom?>_uploadFile(oInput, oOptions) {
	var oForm = $('<?=$sRandom?>_editDialogForm');

	var n = 'f' + Math.floor(Math.random() * 99999);
	var d = document.createElement('DIV');
	d.innerHTML = '<iframe style="display:none" src="about:blank" id="'+n+'" name="'+n+'" onload="<?=$sRandom?>_uploadFile_ready(\''+n+'\', \''+oInput.id+'\')"></iframe>';
	document.body.appendChild(d);

	oForm.setAttribute('target', n);
	oForm.setAttribute('action', '/admin/extensions/gui/gui/ajax/table.ajax.php?hash=<?=$_VARS['hash']?>');

	var sContent = '<img src="/admin/media/indicator.gif" style="margin-bottom:4px; vertical-align:bottom;" alt="" />';
	$(oInput.id+'_response').update(sContent);

	var i = document.getElementById(n);
	if (oOptions && typeof(oOptions.onComplete) == 'function') {
	    i.onComplete = oOptions.onComplete;
	}

}

function <?=$sRandom?>_uploadFile_ready(sId, sInputId) {
	var oForm = $('<?=$sRandom?>_editDialogForm');

	var i = document.getElementById(sId);
	if (i.contentDocument) {
	    var d = i.contentDocument;
	} else if (i.contentWindow) {
	    var d = i.contentWindow.document;
	} else {
	    var d = window.frames[id].document;
	}
	if (d.location.href == "about:blank") {
	    oForm.submit();
	}

	if (typeof(i.onComplete) == 'function') {
	    i.onComplete(sInputId, d.body.innerHTML);
	}

}

function <?=$sRandom?>_uploadFile_complete(sInputId, sContent) {
	if (sContent) {
		$(sInputId + '_response').update(sContent);
	}
}

function <?=$sRandom?>_openEditDialog(objResponse) {

	var intRowId = 0;
	var arrList = new Array();

	if(objResponse != false){
		arrList = objResponse.responseText.evalJSON();
		if(<?=$sRandom?>_selectedRow) {
			intRowId = <?=$sRandom?>_selectedRow.replace(/<?=$sRandom?>_tr_/, '');
		} else if(arrList['id']) {
			intRowId = arrList['id'];
		}
	}

	var oGUI = new GUI;
	
	var HTML_hidden = "";
	var HTML = '';
	HTML	+= '<form method="post" id= "<?=$sRandom?>_editDialogForm" enctype="multipart/form-data"><input type="hidden" name="<?=$aConfig['edit_id']?>" id="<?=$sRandom?>_EditDialogId" value="'+intRowId+'" />';

<?
	$iTabId = 0;
?>
	
<?
	$bTabs = false;
	foreach((array)$aEditDialogData as $aEdit){
		if($aEdit['type'] == 'tab') { 
			$bTabs = true;
		}
	}
	if($bTabs){

?>
		HTML	+= '<div class="infoBox infoBoxTabs" style="margin-top: 5px;">';
		HTML	+= '<div class="infoBoxTabsHead">';
		HTML	+= '<ul id="infoBoxTabCharts" class="infoBoxTabsNav">';
<?
	}
	
	$sTabBtnClass = 'infoBoxTabsHeadActive';
	
	foreach((array)$aEditDialogData as $aEdit){
		if($aEdit['type'] == 'tab') { 
			$iTabId++;
?>		
			HTML	+= '<li class="<?=$sTabBtnClass?>" id="<?=$sRandom?>_tab_<?=$iTabId?>_btn" onclick="<?=$sRandom?>_changeTabs(\'<?=$sRandom?>_tab_<?=$iTabId?>\');" style="cursor: pointer;<?=$aEdit['style']?>"><?=$aEdit['value']?></li>';
<?	
			$sTabBtnClass = '';
		}
	}
	if($bTabs){
?>
		HTML	+= '</ul><div class="divCleaner"></div></div>';
<?
	}
?>
		HTML	+= '<div style="clear:both;"></div>';
<?
	
	$iTabId = 0;
	foreach((array)$aEditDialogData as $aEdit){

		$aEdit['value'] = addslashes($aEdit['value']);

?>
		
		if(!arrList['<?=$aEdit['column']?>'] ){
			arrList['<?=$aEdit['column']?>'] = "";
		}

<? 
if($aEdit['type'] == 'tab') { 
		
	$iTabId++;
	$sStyle = "display:none;";
	if($iTabId == 1){
		$sStyle = '';
	} else if($iTabId > 1){
?>
		HTML	+= '</div>';
<?
	}
?>
		HTML	+= '<div id="<?=$sRandom?>_tab_<?=$iTabId?>" class="tab" style="<?=$sStyle?>" >';
<? 
} 
?>
		
		
<?
		if($aEdit['type'] != 'tab' && $aEdit['type'] != "h1" && $aEdit['type'] != "h2" && $aEdit['type'] != "h3" && $aEdit['type'] != "code") {
?>
			HTML	+= '<div class="row_edit" id="editRow_<?=$aEdit['column']?>" ';
			HTML	+= 'style="<?=$aDialogData['row_style']?>">';
			HTML	+= '<label style="<?=$aDialogData['label_style']?>" for="<?=$aEdit['column']?>"><?=$aEdit['value']?>:';
<?
			if($aEdit['type'] == "multiselect" && $aEdit['invert_selection'] !== false) {
?>
			HTML += '<br/><a href="javascript:;" onclick="<?=$sRandom?>_invertSelection(\'save[<?=$aEdit['column']?>]\');" class="note"><?=L10N::t('Auswahl umkehren')?></a>';
<?
			}
?>
			HTML	+= '</label>';	
			HTML	+= '<div style="float:left;">';
			HTML 	+= '<div style="<?=$aDialogData['input_style']?>">';
<?
		}
		
		if($aEdit['type'] == "h1" || $aEdit['type'] == "h2" || $aEdit['type'] == "h3"){
?>
			HTML	+= '<div class="row_edit" id="editRow_<?=$aEdit['column']?>" ';
			HTML	+= 'style="<?=$aDialogData['row_style']?>">';
<?
		}
		
		if($aEdit['type'] == "multi") {
			$aFields = $aEdit['fields'];
		} else {
			$aFields = array($aEdit);
		}

		foreach($aFields as $aField) {
			
			if($aField['type'] == "code")
			{
	?>
				HTML	+= '<?=$aField['value']?>';
	<?
			}

			if($aField['type'] == "input"){
	?>

			HTML	+= '<input style="<?=$aField['style']?>" type="text" class="txt" name="save[<?=$aField['column']?>]" id="save[<?=$aField['column']?>]" value="'+arrList['<?=$aField['column']?>']+'" <?=$aField['additional']?> />';

	<?	
			} elseif($aField['type'] == "calendar"){
	?>

			HTML	+= '<input style="<?=$aField['style']?>" type="text" class="txt calendar_input" name="save[<?=$aField['column']?>]" id="save[<?=$aField['column']?>]" value="'+arrList['<?=$aField['column']?>']+'" <?=$aField['additional']?> /><img id="date_crs_from" style="padding-left:5px; vertical-align:top;" class="calendar_img" src="/admin/media/calendar.png" />';

	<?	
			} elseif(
				$aField['type'] == "date" ||
				$aField['type'] == "time" ||
				$aField['type'] == "date_time"
			) {
	?>
			HTML	+= '<input style="<?=$aField['style']?>" type="text" class="txt" name="save[<?=$aField['column']?>]" id="save[<?=$aField['column']?>]" value="'+arrList['<?=$aField['column']?>']+'" <?=$aField['additional']?> />';

	<?	
			} elseif($aField['type'] == "text"){
	?>
			HTML	+= '<div style="<?=$aField['style']?>" class="txt" id="save[<?=$aField['column']?>]" <?=$aField['additional']?>>'+arrList['<?=$aField['column']?>']+'</div>';			
	<?
			} elseif($aField['type'] == "html"){
	?>
			HTML	+= '<div style="<?=$aField['style']?>" class="txt html_input" id="save[<?=$aField['column']?>]" <?=$aField['additional']?>>'+arrList['<?=$aField['column']?>']+'</div>';			
	<?
			} elseif($aField['type'] == "h1"){
	?>
			HTML	+= '<div style="<?=$aField['style']?>" class="txt" id="save[<?=$aField['column']?>]" <?=$aField['additional']?>><h1><?=$aField['value']?></h1></div>';			
	<?
			} elseif($aField['type'] == "h2"){
	?>
			HTML	+= '<div style="<?=$aField['style']?>" class="txt" id="save[<?=$aField['column']?>]" <?=$aField['additional']?>><h2><?=$aField['value']?></h2></div>';			
	<?
			} elseif($aField['type'] == "h3"){
	?>
			HTML	+= '<div style="<?=$aField['style']?>" class="txt" id="save[<?=$aField['column']?>]" <?=$aField['additional']?>><h3><?=$aField['html']?></h3></div>';			
	<?
			}elseif($aField['type'] == "space"){
	?>
			HTML	+= '<div style="<?=$aField['style']?>" class="txt" id="save[<?=$aField['column']?>]" <?=$aField['additional']?>>&nbsp;</div>';			
	<?
			} elseif($aField['type'] == "hidden"){
	?>
			HTML_hidden	+= '<input style="<?=$aField['style']?>" class="txt" name="save[<?=$aField['column']?>]" <?=$aField['additional']?> id="save[<?=$aField['column']?>]" type="hidden" value="'+arrList['<?=$aField['column']?>']+'"/>';			
	<?
			} elseif($aField['type'] == "textarea"){
	?>
			HTML	+= '<textarea style="<?=$aField['style']?>" class="txt" name="save[<?=$aField['column']?>]" <?=$aField['additional']?> id="save[<?=$aField['column']?>]">'+arrList['<?=$aField['column']?>']+'</textarea>';			
	<?
			} elseif($aField['type'] == "checkbox"){	
			$sChecked ='checked="checked"';
	?>
			HTML	+= '<input type="checkbox" style="<?=$aField['style']?>" class="txt" name="save[<?=$aField['column']?>]" id="save[<?=$aField['column']?>]"  value="1" <?=$aField['additional']?> ';
			if(parseInt(arrList['<?=$aField['column']?>']) == 1){
				HTML	+= ' <?=$sChecked?> ';
			}
			HTML	+= '/>';			
	<?
			} elseif($aField['type'] == "upload"){	
	?>
			HTML += '<input type="file" class="txt" style="<?=$aField['style']?>" id="save[<?=$aField['column']?>]" name="save[<?=$aField['column']?>]" value="'+arrList['<?=$aField['column']?>']+'" onchange="<?=$sRandom?>_uploadFile(this, {\'onComplete\' : <?=$sRandom?>_uploadFile_complete});" />';
			HTML += '<div id="save[<?=$aField['column']?>]_response"></div>'; 
	<?
			} elseif($aField['type'] == "select"){

	?>
			HTML	+= '<select style="<?=$aField['style']?>" class="txt" name="save[<?=$aField['column']?>]" id="save[<?=$aField['column']?>]" onchange="<?=$aField['onchange']?>" <?=$aField['additional']?>>';		
	<?
				foreach((array)$aField['data_array'] as $sKey => $sOption){
	?>
					HTML	+= '<option value="<?=$sKey?>"';

					if(arrList['<?=$aField['column']?>'] == '<?=$sKey?>'){
						HTML	+= 'selected="selected"';
					}

					HTML	+= "><?=convertHtmlEntities($sOption)?></option>";
	<?
				}
	?>
			HTML	+= '</select>';		

	<?

			} elseif($aField['type'] == "multiselect") {

	?>
			HTML	+= '<select multiple="multiple" size="<?=$aField['size']?>" style="<?=$aField['style']?>" class="txt" name="save[<?=$aField['column']?>][]" id="save[<?=$aField['column']?>]" <?=$aField['additional']?>>';		
	<?
				foreach((array)$aField['data_array'] as $sKey => $sOption){
	?>
					HTML	+= '<option value="<?=$sKey?>"';
					arrList_ = new Array();
					if(arrList['<?=$aField['column']?>'] != 0 && arrList['<?=$aField['column']?>'] != null && arrList['<?=$aField['column']?>'] != undefined){
						arrList_ = arrList['<?=$aField['column']?>'].evalJSON();
						if(arrList_.length > 0){
							arrList_.each(function(value){
								if(value == '<?=$sKey?>'){
									HTML	+= 'selected="selected"';
								}
							});
						} else {
							if(arrList['<?=$aField['column']?>'] == '<?=$sKey?>'){
								HTML	+= 'selected="selected"';
							}
						}
					}
					HTML	+= "><?=convertHtmlEntities($sOption)?></option>";
	<?
				}
	?>
			HTML	+= '</select>';		

	<?

			}
		
		}
		
		
		
		if($aEdit['type'] != 'tab' && $aEdit['type'] != "h1" && $aEdit['type'] != "h2" && $aEdit['type'] != "h3" && $aEdit['type'] != "code"){
?>
			HTML += '</div></div><div style="clear:both; height: 1px; overflow: hidden;"></div></div>';
<? 
		}
		if($aEdit['type'] == "h1" || $aEdit['type'] == "h2" || $aEdit['type'] == "h3"){
?>
			HTML += '<div style="clear:both; height: 1px; overflow: hidden;"></div></div>';
<? 			
		}
	}

if($iTabId > 0) { ?>
		HTML	+= '</div>';
<?	
} 
?>	
	
	HTML	+= '';
	HTML	+= '<div class="divCleaner" style="padding: 0 5px 3px 0; text-align: right;">';
<?
	if($aDialogData['save_button'] == 1){
?>
	HTML	+= '<input style="opacity:1; filter:alpha(opacity=100);" type="button" class="btn" value="<?=L10N::t('Speichern')?>" onclick="<?=$sRandom?>_saveEditDialog();" />';
<?
	}
?>
	HTML 	+=	HTML_hidden;
	
	HTML	+= '</div>';
<?php
	if($bTabs) {
?>
		HTML	+= '</div>';
<?php
	}
?>
	HTML	+= '</form></div>';
	
	<?=$sRandom?>_prepareDialog(HTML);

	var i = 0;
	$$('.calendar_input').each(function(oInput){
			oInput.id = "calendar_input_"+i;
			i++;
		});
	var i = 0;
	$$('.calendar_img').each(function(oInput){
			oInput.id = "calendar_img_"+i;
			i++;
	});

	$$('.calendar_input').each(function(oInputs){
		Calendar.prepare({
			dateField      : oInputs.id,
			triggerElement : oInputs.next('img').id
		});
	});
		
	$$('.html_input').each(function(oInputs) {

		 var oFCK_textDIV   		= $(oInputs.id);
	     var oFCKeditorS    		= new FCKeditor(oInputs.id+'_html');
	     oFCKeditorS.BasePath  		= '/admin/editor/';
	     oFCKeditorS.Value   		= oInputs.innerHTML;
	     oFCKeditorS.ToolbarSet 	= 'Basic';
	     oFCKeditorS.Height  	 	= '200';
	     oFCKeditorS.Width   		= '100%';
	     oFCK_textDIV.innerHTML  	= oFCKeditorS.CreateHtml();
	     
     });

	if(arrList['individual_select_options']) {
		$H(arrList['individual_select_options']).each(function(aField){
			
			var aSelected = "";
			if(arrList[aField.key] != "" && arrList[aField.key] != 0 && arrList[aField.key] != null && arrList[aField.key] != undefined) {
				aSelected = arrList[aField.key].evalJSON();
				if(aSelected.length > 0) {
					
				} else {
					aSelected = arrList[aField.key];
				}
			}
			<?=$sRandom?>_fillSelect('save['+aField.key+']', aField.value, aSelected);

		});
	}

}

/* ====================================================================== */

function <?=$sRandom?>_saveEditDialog(){
	
	var strRequestUrl = <?=$aAjaxData['save_url']?>;
	var formParameters = $('<?=$sRandom?>_editDialogForm').serialize();
	var strParameters = <?=$aAjaxData['save_param']?>+'&'+formParameters;
	
	$$('.html_input').each(function(oInputs){
		if($(oInputs.id+'_html')){
			var oEditorStart = FCKeditorAPI.GetInstance(oInputs.id+'_html');
			var sHTML = oEditorStart.GetHTML();
			strParameters += '&'+oInputs.id+'='+encodeURIComponent(sHTML);
		}
	});
	
	if(masterSelectedRow) {
		var aParts = masterSelectedRow.split(/_/);
		var iMasterRows = aParts[3];
		strParameters += '&master_selected_row='+iMasterRows;
	}
	
	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method : '<?=$aAjaxData['save_method']?>',
								parameters 	: strParameters,
								onSuccess 	: <?=$sRandom?>_saveCallback,
								onFailure 	: <?=$sRandom?>_saveFalseCallback
							}
	);
}

function <?=$sRandom?>_saveCallback(objResponse){
	var aId 	= objResponse.responseText.evalJSON();
	updateLBTitle("<span style='color:green'><?=L10N::t('Erfolgreich gespeichert!')?></span>");
	$('<?=$sRandom?>_EditDialogId').value = aId[0];
	<?=$sRandom?>_loadTableList();
	clearLbTitle();
}

function <?=$sRandom?>_saveFalseCallback(objResponse){
	var aId 	= objResponse.responseText.evalJSON();
	updateLBTitle("<span style='color:red'><?=L10N::t('Fehler beim Speichern!')?></span>");
	$('<?=$sRandom?>_EditDialogId').value = aId[0];
	clearLbTitle();
}

function updateLBTitle(HTML, clearTitle){

	if(clearTitle == null){
		clearTitle = true;
	}
	if($('LB_title')){
		$('LB_title').update(HTML);
		if(clearTitle){
			clearLbTitle();
		}
	}
}

function clearLbTitle(){
	
	new PeriodicalExecuter(function(pe) {
	  	if($('LB_title')){
	  		$('LB_title').update("");
	  	}
	    pe.stop();
	}, 5);
	
}

function <?=$sRandom?>_deleteRow(intRowId){
	
	if (confirm("<?=$oL10N->translate("Wirklich löschen?")?>")) {
     	var strRequestUrl = <?=$aAjaxData['delete_url']?>;
		var strParameters = <?=$aAjaxData['delete_param']?>+'&row_id='+intRowId;
		
		if(masterSelectedRow) {
			var aParts = masterSelectedRow.split(/_/);
			var iMasterRows = aParts[3];
			strParameters += '&master_selected_row='+iMasterRows;
		}
		
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method : '<?=$aAjaxData['delete_method']?>',
									parameters : strParameters,
									onComplete : <?=$sRandom?>_loadTableList
								}
		); 
    }

	

}
var sLastHoverStyle = ""; 
var iHoverActive = 0;
function setHighlightRow(event) {
	var objRow = this;
	if(objRow) {
		if(iHoverActive != 1){
			sLastHoverStyle = objRow.style.backgroundColor;
			iHoverActive = 1;
		}
		objRow.style.backgroundColor = '#d4ddf0';
	}			
}

function resetHighlightRow(event) {
	var objRow = this; 
	if(objRow) {
		if(iHoverActive == 1){
			objRow.style.backgroundColor = sLastHoverStyle;
			iHoverActive = 0;
		}
	}
}

function prepareDialog(HTML){
	<?=$sRandom?>_prepareDialog(HTML)
}

function <?=$sRandom?>_prepareDialog(HTML){
	
	var iWidth = <?=(int)$aDialogData['width']?> - 20;
	// Error scroll Box
	var HTML_ = '<div id="divLitboxError" style="overflow: visible; display: none"></div>';
	
	
	HTML_ += '<div id="dialog_content"></div>';
	
	if(!<?=$sRandom?>_objLitBox){
		<?=$sRandom?>_objLitBox = new LITBox(HTML_, {type:'alert', overlay:true,height:<?=(int)$aDialogData['height']?>, width:<?=(int)$aDialogData['width']?>, resizable:false, opacity:.9});
	} else {
		if(!$('dialog_content')){
			<?=$sRandom?>_objLitBox.getWindow();
			<?=$sRandom?>_objLitBox.d4.innerHTML = HTML_;
			//<?=$sRandom?>_objLitBox.d4.update(HTML_);
			<?=$sRandom?>_objLitBox.display();

		}
	}

	<?=$sRandom?>_openDialog(HTML);
	
	// nachträgliches Anpassen der Höhe
	if($('LB_content') && $('divFlexBox')){				
		var iBox = $('LB_content').getHeight()
		$('divFlexBox').style.height = (iBox - 24) + 'px';
	}
			
	var aAllTabs = $$('.tab');
	
	if(aAllTabs.length > 0) {
		
		var iTempHeight = <?=(int)$aDialogData['height']?>;
		$$('.LB_content').each(function(oBox){
			oBox.style.overflow = 'hidden';
		});
		
		$$('.LB_window').each(function(oBox){
			iTempHeight = oBox.style.height;
		});
			
		iTempHeight = parseInt(iTempHeight.replace('px',''));
		
		aAllTabs.each(function(oTab){
			oTab.style.height = iTempHeight - 88;
			oTab.style.overflow = 'auto';
		});

	} else {
		$$('.LB_content').each(function(oBox){
			oBox.style.overflow = 'auto';
		});
	}

}

function openDialog(HTML){
	<?=$sRandom?>_openDialog(HTML);
}

function <?=$sRandom?>_openDialog(HTML){

	$('dialog_content').update(HTML);
	<?=$sRandom?>_hideToolbarLoading();
}

function <?=$sRandom?>_invertSelection(sId) {
	var oSelect = $(sId);
	var aBoxes = $A(oSelect.options);

	aBoxes.each(function(oBox) {
		if(oBox.selected) {
			oBox.selected = false;
		} else {
			oBox.selected = true;
		}
	});
}

function <?=$sRandom?>_fillSelect(sId, aOptions, aSelected) {
	var oSelect = $(sId);
	
	oSelect.length = 0;  

	$H(aOptions).each(function(aOption) {
		bSelected = false;
		
		if(aSelected.length > 0) {
			aSelected.each(function(value){
				if(value == aOption.key){
					bSelected = true;
				}
			});
		} else {
			if(aSelected == aOption.key){
				bSelected = true;
			}
		}
		
		var oOption = new Option(aOption.value, aOption.key, false, bSelected);  
		oSelect.options[oSelect.length] = oOption;  
	});

}

function <?=$sRandom?>_edit_flexible_list(sTable) {

	var strRequestUrl = <?=$aAjaxData['url']?>;
	var strParameters = '&hash=<?=$_VARS['hash']?>&task=getTableFlexHtml&table=' + sTable;

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method : '<?=$aAjaxData['table_method']?>',
								parameters 	: strParameters,
								onSuccess 	: <?=$sRandom?>_edit_flexible_listCallback,
								onFailure 	: printErrorMessage
							}
	); 

	
}

function <?=$sRandom?>_edit_flexible_listCallback(objResponse) {

	// show box
	<?=$sRandom?>_prepareDialog(objResponse.responseText);
	
	
	// Sortable List
	Sortable.create('flexible_list',{
		constraint:'vertical',
		tag:'li',
		onChange: function() {  
		  new Ajax.Request(<?=$aAjaxData['url']?>, {  
			  method: "post",  
			  parameters: '&hash=<?=$_VARS['hash']?>&task=saveFlexListOrder&'+Sortable.serialize("flexible_list")
			});
		}
	});

}

function <?=$sRandom?>_saveFlexList() {

	var strRequestUrl = <?=$aAjaxData['url']?>;	
	var strParameters = '?task=saveFlexList&hash=<?=$_VARS['hash']?>';
	var sForm = $('form_flex_list').serialize();
	
	strParameters += '&'+sForm;
	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method : '<?=$aAjaxData['table_method']?>',
								parameters 	: strParameters,
								onSuccess 	: <?=$sRandom?>_saveFlexListCallback,
								onFailure 	: printErrorMessage
							}
	); 
}

function <?=$sRandom?>_saveFlexListCallback(objResponse) {
	
	var oData = objResponse.responseText.evalJSON();

	if(oData.success) {
			updateLBTitle("<span style='color:green'>"+oData.headerBox+"</span>");
			self.location.reload();
	} else {
		displayErrors(oData);
	}
}

// Function displays Error slide-down "div" in LightBox
function displayErrors(aInfoBox) {
	if(aInfoBox.headerBox){
		updateLBTitle("<span style='color:red'>"+aInfoBox.headerBox+"</span>", false);	
	}
	if(aInfoBox.errorsBox) {
		var sErrorHTML = '<ul>';
		aInfoBox.errorsBox.each(function(oError) {
			sErrorHTML += '<li>'+oError+'</li>';
		});
		sErrorHTML += '</ul>';

		$('divLitboxError').update(sErrorHTML);
		Effect.SlideDown('divLitboxError', {afterUpdate: updateFlexboxHeight, afterFinish: updateFlexboxHeight});
	}
}

function updateFlexboxHeight(mOptions) {
	var iErrorHeight = $('divLitboxError').getHeight();
	var iContentHeight = $('LB_content').getHeight();
	$('divFlexBox').style.height = (iContentHeight - 26 - iErrorHeight)+'px';
}

// toggle left Frame
function <?=$sRandom?>_toggleSide(bMode) {
	go('<?=$sHtmlPath?>?left_frame='+bMode);
}

// Function Displays specific HTML Content in Box
function <?=$sRandom?>_displayBox(sTask) {
	var strRequestUrl = <?=$aAjaxData['url']?>;	
	var strParameters = '?task=' + sTask + '&hash=<?=$_VARS['hash']?>';

	var objAjax = new Ajax.Request(
					strRequestUrl,
					{
						method : '<?=$aAjaxData['table_method']?>',
						parameters 	: strParameters,
						onSuccess 	: <?=$sRandom?>_displayBoxCallback,
						onFailure 	: printErrorMessage
					}
	); 
}

function <?=$sRandom?>_displayBoxCallback(objResponse) {
	var oData = objResponse.responseText.evalJSON();
	var sHtml = '';
	if(oData.html){
		sHtml += '<div id="divFlexBox" class="flexList" style="overflow: auto;">';
		sHtml += oData.html;
		sHtml += '</div>';
	}
	if(oData.html_button){
		sHtml += '<div class="row_btn">';
		sHtml += oData.html_button;
		sHtml += '</div>';
	}
	
	<?=$sRandom?>_prepareDialog(sHtml);
			
	if(oData.success) {
		if(oData.headerBox){
			updateLBTitle("<span style='color:green'>"+oData.headerBox+"</span>");
			self.location.reload();
		}
	} else {
		displayErrors(oData);
	}
}

function <?=$sRandom?>_init(bLoadTable, iLeftFrame) {

	<?=$sRandom?>_checkTableListHeight();

	ScrollableTable.load();

	if(bLoadTable) {
		<?=$sRandom?>_loadTableList();
	}

}
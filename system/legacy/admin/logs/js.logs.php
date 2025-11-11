<?PHP
require_once(Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

header("Content-type: text/javascript");

if(!$_SESSION['gui']['ajax_table'][$_VARS['hash']]){
	die();
}
$aConfigArray = $_SESSION['gui']['ajax_table'][$_VARS['hash']];

$oExt_gui_ajax = new Ext_Logs_Logs($aConfigArray,$_VARS['hash']);

$aConfig = $oExt_gui_ajax->getConfigArray();
$aAjaxData = $aConfig['ajax_data'];
$aHeaderData = $aConfig['header_data'];
$aIconsData = $aConfig['icon_data'];
$aLayoutData = $aConfig['layout_data'];
$aEditDialogData = $aConfig['edit_data'];
$aQueryData = $aConfig['query_data'];
$aDialogData = $aConfig['dialog_data'];
$sRandom = $aConfig['random'];


?>

function reload(){

	<?=$sRandom?>_loadTableList();

}

function <?=$sRandom?>_loadTableList() {

	var paginationParameters = '&offset='+<?=$sRandom?>_pagination_offset;

	var oderParameters = '&sOrderString='+<?=$sRandom?>_sOrderString+'&sOrder='+<?=$sRandom?>_sOrder;

	<?=$sRandom?>_showToolbarLoading();
	var strRequestUrl = <?=$aAjaxData['table_url']?>;
	var strParameters = <?=$aAjaxData['table_param']?>+paginationParameters+oderParameters;
	
	if($('filter_sort')){
		strParameters = strParameters+'&filter_sort='+$F('filter_sort');
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
<?php
include (\Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

$oManual = new Ext_Manual_Manual();

if($_VARS['task'] == 'saveTree'){
	$oManual->saveTree($_VARS['tree']);
}
if($_VARS['task'] == 'addTreeBranch'){
	$aBack['id'] = $oManual->saveNewTreeBranch($_VARS['parent_id']);
	$aBack['html'] = $oManual->getInnerHtmlOfLi($aBack['id'],"",1);
	
	echo json_encode($aBack);
}

if($_VARS['task'] == 'deleteTreeBranch'){
	$oManual->deleteTreeBranch($_VARS['id']);
}

if($_VARS['task'] == 'loadEdit'){
	$aBack = $oManual->getPageData($_VARS['id']);
	echo json_encode($aBack);
}
if($_VARS['task'] == 'savePage'){
	
	$oPurifier = new HTMLPurifierWrapper('all');

	$_VARS['html'] = $oPurifier->purify($_VARS['html']);

	$oManual->setField('id',$_VARS['id']);
	$oManual->setField('title',$_VARS['title']);
	$oManual->setField('content',$_VARS['html']);
	$oManual->save();
	$aBack['id'] = $_VARS['id'];
	$aBack['title'] = $_VARS['title'];
	echo json_encode($aBack);
	
}
?>

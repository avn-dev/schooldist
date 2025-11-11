<?php
include_once(\Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");
__pout($_SESSION['mosaik']['r'][$_VARS['r']],1);
if($_VARS['r']){
	header('Content-type: image/jpeg');
	imagejpeg($_SESSION['mosaik']['r'][$_VARS['r']]);
	die();
}
?>
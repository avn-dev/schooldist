<?php

require_once(Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

$sHash = $_VARS['hash'];

$aConfigArray = $_SESSION['gui']['ajax_table'][$sHash];
$oGUI_Ajax_Table = new $aConfigArray['ajax_data']['class']($aConfigArray,$sHash);

$oGUI_Ajax_Table->switchAjaxRequests($_VARS);

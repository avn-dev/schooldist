<?

if($_COOKIE['incms'] == 1) {
	include(\Util::getDocumentRoot().'system/legacy/admin/includes/main.inc.php');
} else {
	include(\Util::getDocumentRoot()."system/includes/admin.inc.php");
	include(\Util::getDocumentRoot()."system/includes/functions.inc.php");
	include(\Util::getDocumentRoot()."system/includes/autoload.inc.php");
	include(\Util::getDocumentRoot()."system/includes/config.inc.php");
	include(\Util::getDocumentRoot()."system/includes/dbconnect.inc.php");
	$session_data['public'] = 1;
	include(\Util::getDocumentRoot()."system/includes/variables.inc.php");
	include(\Util::getDocumentRoot()."system/includes/access.inc.php");
}

$sHash = $_VARS['hash'];

$aConfigArray = $_SESSION['gui']['ajax_table'][$sHash];
$oGUI_Ajax_Table = new $aConfigArray['ajax_data']['class']($aConfigArray,$sHash);

$oGUI_Ajax_Table->switchAjaxRequests($_VARS);

?>
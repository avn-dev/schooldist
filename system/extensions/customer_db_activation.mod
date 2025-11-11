<?

if(!$objWebDynamics) {
	global $objWebDynamics;
}

global $parent_config;
$table_name = $parent_config->table_name;

global $db_class_is_declared;
if (!$db_class_is_declared) {
    require (\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/customer_db_class.inc.php");
}

global $customer_db_functions_is_declared;
if(!$customer_db_functions_is_declared) {
    include(\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/customer_db_functions.inc.php");
}

$SQL_handler = new customer_db;

$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);



if ($_VARS['task'] == "activate" AND intval($_VARS['activation_key']) > 0 AND intval($_VARS['id_user']) > 0) {
	
	// Lade zu aktivierende ID
	$query = "SELECT id_user FROM `customer_db_activation` WHERE id_user = ".intval($_VARS['id_user'])." AND activation_key = ".intval($_VARS['activation_key']);
    $my = get_data(db_query($query));
    if($my) {
		
		$id = intval($my['id_user']);
		db_query("UPDATE `".$table_name."` SET active = 1, created = NOW() WHERE id = ".$id." LIMIT 1");

		$query2 = "SELECT * FROM `".$table_name."` WHERE id = '".$id."'";
		$my_new = get_data(db_query($query2));

		// Aktivierung erfolgreich
		echo $config->msg_activate_success;
		db_query("DELETE FROM customer_db_activation WHERE id_user = '".$my['id_user']."' AND activation_key = '".intval($_VARS['activation_key'])."'");

		// Admin E-Mail
		if ($config->sendinfo) 
		{
			$strAdminMessage = $config->email_message;
			
			//replace palceholders with data from customer db
			foreach($my_new as $mixKey=>$strItem){
				$strAdminMessage = str_replace("<#".$mixKey."#>", $strItem, $strAdminMessage);
			}
			
			$strAdminMessage = str_replace("<#email#>", $my_new['email'], $strAdminMessage);
			$strAdminMessage = str_replace("<#nickname#>", $my_new['nickname'], $strAdminMessage);
			wdmail(\System::d('admin_email'), $config->email_subject, $strAdminMessage);
			
		}
		
		// User E-Mail
		if ($config->sendinfo2user) 
		{
			$strUserMessage = $config->email_message2user;

			//replace palceholders with data from customer db
			foreach($my_new as $mixKey=>$strItem){
				$strUserMessage = str_replace("<#".$mixKey."#>", $strItem, $strUserMessage);
			}

			$strUserMessage = str_replace("<#email#>", $my_new['email'], $strUserMessage);
			$strUserMessage = str_replace("<#nickname#>", $my_new['nickname'], $strUserMessage);
			wdmail($my_new['email'], $config->email_subject2user, $strUserMessage);
			
		}
		
		$arrTransfer = array();
		$arrTransfer['usermessage'] = $strUserMessage;
		$arrTransfer['adminmessage'] = $strAdminMessage;
		$arrTransfer['config'] = $config;
		$arrTransfer['user'] = $my_new;

		/*
		* set hook if not in edit mode
		*/
		\System::wd()->executeHook('customer_db_activation_'.$element_data['content_id'], $arrTransfer);
		
	} else {
		// Aktivierung fehlgeschlagen
		echo $config->msg_activate_failed;
    }

} elseif ($_VARS['task'] == "deactivate" AND intval($_VARS['activation_key']) > 0 AND intval($_VARS['id_user']) > 0) {
	$query3 = "SELECT id_user FROM customer_db_activation WHERE id_user = ".intval($_VARS['id_user'])." AND activation_key = ".intval($_VARS['activation_key']);

    if ($my = get_data(db_query($query3))) {
		db_query("UPDATE ".$table_name." SET active = 0 WHERE id = ".$my['id_user']);

		// Kündigung erfolgreich
		echo $config->msg_deactive_success;
		db_query("DELETE FROM customer_db_activation WHERE id_user = ".$my['id_user']);
	} else {
		// Deaktivierung fehlgeschlagen
		echo $config->msg_deactivate_failed;
	}
} else {
	// keine Änderung möglich
    echo $config->msg_failed;
}

?>

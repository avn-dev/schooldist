<?
global $parent_config;
$table_name=trim($parent_config->table_name);



global $db_class_is_declared;
if (!$db_class_is_declared) {
    require (\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/customer_db_class.inc.php");
}

global $customer_db_functions_is_declared;
if(!$customer_db_functions_is_declared) {
    include(\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/customer_db_functions.inc.php");
}

$SQL_handler = new customer_db;
$config = new \Cms\Helper\ExtensionConfig($page_id, $element_data["number"]);


#$destination_table=$config->destination_table;


if($_REQUEST['task']=="deactivate" AND intval($_REQUEST['activation_key'])>0 AND intval($_REQUEST['id_user'])>0)
{
    $query_fields="SELECT * FROM $table_name LIMIT 0,1";

    $sFieldList="nickname,email,";

    $res=db_query($query_fields);



    $my_field=get_data($res);
    #var_dump($my_field);

    foreach($my_field as $key => $value)
    {
        if(0===strpos($key,"ext_")) $sFieldList.=$key.",";
    }

    #$sFieldList.="ext_".$my_field['fnr'].",";



    $query ="SELECT id_user FROM customer_db_activation WHERE id_user=".intval($_REQUEST['id_user']);
    $query.=" AND activation_key=".intval($_REQUEST['activation_key']);


    if($my=get_data(db_query($query)))
    {
        $sFieldList=substr($sFieldList,0,-1);

        $query="INSERT INTO customer_db_".$config->destination_table." ($sFieldList) SELECT $sFieldList FROM $table_name WHERE id=".$my['id_user'];
        db_query($query);

        $id=get_insert_id();
        db_query("UPDATE customer_db_".$config->destination_table." SET active=1 WHERE id=$id");


        $res=db_query("SELECT * FROM $table_name WHERE id=".$my['id_user']);
        $aData=get_data($res);

        #die($query."<br /><br />Gestoppt!");
        db_query("DELETE FROM $table_name WHERE id=".$my['id_user']);


		///////////////////////////////////////////////////
		///////////////////////////////////////////////////

        // Hier muss eine Mail an den abgelehnten User verschickt werden!
		$config->email_message = str_replace("<#nickname#>", $aData["nickname"],$config->email_message);
		$config->email_message = str_replace("<#email#>", $aData["email"],$config->email_message);

		// Ersetze m�gliche ext-Tags
        $counter=0; # Notfall-Abbruch-Bedingung
		while(strpos($config->email_message, "<#ext_")!==FALSE)
		{
		    $parts=explode("<#ext_",$config->email_message);
		    $number=intval($parts[1]);
            $config->email_message = str_replace("<#ext_$number#>", $aData["ext_$number"],$config->email_message);

            $counter++;
            if($counter>250) {break;}
        }

        wdmail($aData["email"], $config->email_subject, $config->email_message, $system_data['mail_from']);#

		///////////////////////////////////////////////////
		///////////////////////////////////////////////////




    }
 /* */

}

#die("ZBLAG!");
?>

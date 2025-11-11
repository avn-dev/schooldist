<?

// iframe, der die m�glichen Kategorien anzeigt

include (\Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");
require (\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/customer_db_class.inc.php");
require (\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/customer_db_functions.inc.php");
require (\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/tree_db_functions.inc.php");

#var_dump($_VARS);

if($db_name AND $tree_table AND $selected_tree AND $id AND $field_name AND $Form_ID AND $cat_number)
{
    new_select_category($db_name, $tree_table, $selected_tree, $id,$frame_id,$field_name,$Form_ID,$cat_number);
}

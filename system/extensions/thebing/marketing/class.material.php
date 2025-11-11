<?php
/* 
 * @property int $id 	
 * @property int $changed 	
 * @property int $created 	
 * @property int $active 	
 * @property int $user_id 	
 * @property int $school_id 	
 * @property string $name 	
 * @property string $description 	
 * @property int $orderable 
 */
class Ext_Thebing_Marketing_Material extends Ext_Thebing_Basic{

	// Tabellenname
	protected $_sTable = 'kolumbus_material_orders_items';
	
	protected $_aFormat = array(
	  		'name' => array(
				'validate'	=> 'TEXT',
				'required'	=> true
			)
		);

}

?>

<?php

use Communication\Traits\Model\Log\WithModelRelations;

/**
 * @property string|int $id
 * @property string $message_id
 * @property string $type
 * @property $address
 * @property $name
 */
class Ext_TC_Communication_Message_Address extends Ext_TC_Basic
{
	use WithModelRelations;
	
	protected $_sTable = 'tc_communication_messages_addresses';
	protected $_sTableAlias = 'tc_cma';

	protected $_aJoinTables = array(
		'relations' => array(
			'table' => 'tc_communication_messages_addresses_relations',
			'foreign_key_field'=>array('relation', 'relation_id'),
			'primary_key_field'=>'address_id'
		)
	);

	/**
	 * Gibt anhand der Relation den Typ der Message zurück
	 * @return string
	 */
	public function getRelationObjectType()
	{
		return '';
	}

	/**
	 * Liefert die Übersetzung des Objekttypes
	 * @return string
	 */
	public function getRelationObjectTypeLabel()
	{
		$sReturn = '';
		$sType = $this->getRelationObjectType();

		// …

		if(!empty($sReturn)) {
			$sReturn = Ext_TC_Communication::t($sReturn);
		}

		return $sReturn;
	}

}
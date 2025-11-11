<?php

/**
 * WDBASIC der Flags
 */
class Ext_TC_Communication_Message_Flag extends Ext_TC_Basic {
	
	protected $_sTable = 'tc_communication_messages_flags';
	protected $_sTableAlias = 'tc_cmf';

	protected $_aJoinTables = array(
		'relations' => array(
			'table' => 'tc_communication_messages_flags_relations',
			'foreign_key_field' => array('relation', 'relation_id'),
			'primary_key_field' => 'flag_id'
		)
	);

}
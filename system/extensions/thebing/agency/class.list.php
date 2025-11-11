<?php

/**
 * @property int $id
 * @property string $created
 * @property string $changed
 * @property int $active
 * @property int $user_id
 * @property int $client_id
 * @property string $name
 * @property string $discription
 * 
 */
class Ext_Thebing_Agency_List extends Ext_Thebing_Basic
{
	// Tabellenname
	protected $_sTable = 'kolumbus_agency_lists';

	protected $_aFormat = [
		'changed' => [
			'format' => 'TIMESTAMP'
		],
		'created' => [
			'format' => 'TIMESTAMP'
		],
		'client_id' => [
			'validate' => 'INT_POSITIVE',
			'required' => true
		],
		'name' => [
			'required' => true
		]
	];

	protected $_aJoinTables = [
		'join_agencies'=> [
			'table'=>'kolumbus_agency_lists_agencies',
			'foreign_key_field'=>'agency_id',
			'primary_key_field'=>'list_id'
		]
	];
}
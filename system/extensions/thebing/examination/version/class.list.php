<?php


class Ext_Thebing_Examination_Version_List extends Ext_Thebing_Basic
{
	// Tabellenname
	protected $_sTable = 'kolumbus_examination_version';

	protected $_aSections = array();

	protected $_sTableAlias = 'kexv';

	protected $_aJoinedObjects = array(
		'kex'=>array(
			'class'=>'Ext_Thebing_Examination',
			'key'=>'examination_id'
		)
	);

}
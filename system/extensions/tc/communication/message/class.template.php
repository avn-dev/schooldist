<?php
/**
 * WDBASIC der Messages der Template-Relations
 */
class Ext_TC_Communication_Message_Template extends Ext_TC_Basic {

	protected $_sTable = 'tc_communication_messages_templates';
	protected $_sTableAlias = 'tc_cmt';

	protected $_aJoinTables = array(
		'layouts' => array(
			'table' => 'tc_communication_messages_templates_to_layouts',
			'foreign_key_field' => 'layout_id',
			'primary_key_field' => 'messagetemplate_id'
		)
	);

	public function getTemplate()
	{
		return Factory::getInstance(\Ext_TC_Communication_Template::class, $this->template_id);
	}

}
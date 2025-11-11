<?php
class Ext_Gui2_Html_Input extends Ext_Gui2_Html_Abstract {

	protected $sStartTag	= '<input />';
	protected $sEndTag		= '';
	public $bAllowReadOnly = true;
	protected $aOptions = array(
		'type' => 'text'
	);

}
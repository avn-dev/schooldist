<?php


class Ext_Thebing_System_Checks_System_Config_Color extends Ext_Thebing_System_Checks_System_Config_Abstract
{
	/**
	 * Siehe Beschreibung Parent
	 */
	protected function _getConfigChangeString()
	{
		return 'color';
	}
	
	/**
	 * Siehe Beschreibung Parent
	 */
	protected function _getConfigChangeData()
	{
		return array(
			'system_color' => array(
				'value'			=> '#0a50a1',
				'description'	=> 'System Color'
			),
			'system_color_light' => array(
				'value'			=> '#72A9E0',
				'description'	=> 'System Color Light'
			),
		);
	}
}
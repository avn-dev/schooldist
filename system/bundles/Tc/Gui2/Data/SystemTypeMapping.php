<?php

namespace Tc\Gui2\Data;

class SystemTypeMapping extends \Ext_TC_Gui2_Data {

	public static function getDialog(\Ext_Gui2 $gui) 
	{
		$dialog = $gui->createDialog($gui->t('System-Typ "{name}" editieren'), $gui->t('System-Typ anlegen'));

		$dialog->setElement($dialog->createRow($gui->t('Bezeichnung'), 'input', [
			'db_alias' => 'tc_stm',
			'db_column' => 'name',
			'required' => true
		]));

		$dialog->setElement($dialog->createRow($gui->t('Funktionen'), 'select', [
			'db_alias' => 'tc_stm',
			'db_column' => 'system_types',
			'select_options' => \Factory::executeStatic(\Ext_TC_Object::class, 'getSystemTypes', [$gui->getOption('entity_type', \Ext_TC_User::MAPPING_TYPE)]),
			'style' => 'height: 60px;',
			'multiple' => 5,
			'jquery_multiple' => 1
		]));

		return $dialog;
	}

    public static function getContactListWhere() {
        return ['tc_stm.type' => \Ext_TC_Contact::MAPPING_TYPE];
    }

    public static function getUserListWhere() {
        return ['tc_stm.type' => \Ext_TC_User::MAPPING_TYPE];
    }

}
<?php

namespace TsCompany\Gui2\Data;

class Industry extends \Ext_Thebing_Gui2_Data {

	public static function getDialog(\Ext_Gui2 $gui2): \Ext_Gui2_Dialog {

		$dialog = $gui2->createDialog($gui2->t('Branche "{name}" editieren'), $gui2->t('Neue Branche anlegen'));

		$dialog->setElement($dialog->createRow($gui2->t('Name'), 'input', [
			'db_column' => 'name',
			'db_alias' => 'ts_ci',
			'required' => true
		]));

		$dialog->setElement($dialog->createRow($gui2->t('Abkürzung'), 'input', [
			'db_column' => 'short_name',
			'db_alias' => 'ts_ci'
		]));

		$dialog->setElement($dialog->createRow($gui2->t('Beschreibung'), 'textarea', [
			'db_column' => 'description',
			'db_alias' => 'ts_ci'
		]));

		return $dialog;
	}

	public static function getWhere(\Ext_Gui2 $gui2) {

		$where = ['active' => 1];

		if($gui2->getParent() === null) {
			// Obere Liste
			$where['parent_id'] = 0;
		}

		return $where;
	}

	public static function getOrderby(){
		return [
			'ts_ci.name' => 'ASC'
		];
	}

}

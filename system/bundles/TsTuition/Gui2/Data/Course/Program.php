<?php

namespace TsTuition\Gui2\Data\Course;

use TsTuition\Gui2\Selection\Course\ProgramServices;
use TsTuition\Entity\Course\Program\Service;

class Program extends \Ext_Thebing_Gui2_Data {

	public static function getDialog(\Ext_Gui2 $gui2): \Ext_Gui2_Dialog {

		$dialog = $gui2->createDialog($gui2->t('Programm bearbeiten'), $gui2->t('Programm erstellen'));
		$dialog->save_as_new_button = true;
		$dialog->save_bar_options = true;
		$dialog->save_bar_default_option = 'open';

		$services = $dialog->createJoinedObjectContainer('services', ['min' => 1, 'max' => 10]);

		$services->setElement($services->createRow($gui2->t('Leistungstyp'), 'select', [
			'db_alias' => 'ts_tcps',
			'db_column' => 'type',
			'select_options' => [
				Service::TYPE_COURSE => $gui2->t('Kurs')
			],
			'value' => 'course',
			'required' => true,
			'row_style' => 'display: none;'
		]));

		$services->setElement($services->createRow($gui2->t('Leistung'), 'select', [
			'db_alias' => 'ts_tcps',
			'db_column' => 'type_id',
			'selection' => new ProgramServices(),
			'required' => true,
			'dependency' => [
				['db_alias' => 'ts_tcps', 'db_column' => 'type']
			]
		]));

		$services->setElement($services->createRow($gui2->t('Von'), 'calendar', [
			'db_alias' => 'ts_tcps',
			'db_column' => 'from',
			'required' => true,
			'format' => new \Ext_Thebing_Gui2_Format_Date()
		]));

		$services->setElement($services->createRow($gui2->t('Bis'), 'calendar', [
			'db_alias' => 'ts_tcps',
			'db_column' => 'until',
			'required' => true,
			'format' => new \Ext_Thebing_Gui2_Format_Date()
		]));

		$dialog->setElement($services);

		return $dialog;
	}

	public static function getOrderby(): array {
		return [];
	}

	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null) {

		switch($sError) {
			case 'JOURNEY_COURSES_FOUND':
				return $this->t('Es sind noch Buchungen vorhanden. Das Programm kann nicht gelöscht werden!');
			default:
				return parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}

	}

}

<?php

namespace TsTuition\Gui2\Data;

class TeacherAvailability extends \Ext_Thebing_Gui2_Data {
	
	const L10NPath = "Thebing » Tuition » Teachers » Availability";
	
	public static function getOrderby() {
		return ['ts_t.lastname'=>'ASC', 'ts_t.firstname'=>'ASC', 'kts.idDay'=>'ASC'];
	}
	
	static public function getDialog(\Ext_Gui2 $oGui) {
		
		$aDays = \Ext_Thebing_Util::getDays();
		
		$oDialog = $oGui->createDialog($oGui->t('Verfügbarkeit von Lehrer "{teacher_name}" bearbeiten'), $oGui->t('Verfügbarkeit anlegen'));
		$oDialog->sDialogIDTag = 'TEACHER_SCHEDULE_';
		$oDialog->aOptions['section'] = 'teachers_availability';

		$oDialog->setElement(
			$oDialog->createRow(
				\L10N::t('Lehrer', $oGui->gui_description),
				'select',
				array(
					'db_column'			=> 'idTeacher',
					'db_alias'			=> 'kts',
					'select_options'	=> \Ext_Thebing_Teacher::getSelectOptions(false, true),
					'required'			=> true
				)
			)
		);
		
		$oDialog->setElement(
			$oDialog->createRow(
				\L10N::t('Priorität', $oGui->gui_description),
				'select',
				array(
					'db_column'			=> 'priority',
					'db_alias'			=> 'kts',
					'select_options'	=> self::getPriorityOptions(),
					'required'			=> true
				)
			)
		);
		
		$oDialog->setElement(
			$oDialog->createRow(
				\L10N::t('Tag', $oGui->gui_description),
				'select',
				array(
					'db_column'			=> 'idDay',
					'db_alias'			=> 'kts',
					'select_options'	=> \Ext_Thebing_Util::addEmptyItem($aDays),
					'required'			=> true
				)
			)
		);
		
		$oDialog->setElement(
			$oDialog->createRow(
				\L10N::t('Von', $oGui->gui_description),
				'input',
				array(
					'db_column'	=> 'timeFrom',
					'db_alias'	=> 'kts',
					'format'	=> new \Ext_Thebing_Gui2_Format_Time(),
					'required'	=> true
				)
			)
		);
		$oDialog->setElement(
			$oDialog->createRow(
				\L10N::t('Bis', $oGui->gui_description),
				'input',
				array(
					'db_column'	=> 'timeTo',
					'db_alias'	=> 'kts',
					'format'	=> new \Ext_Thebing_Gui2_Format_Time(),
					'required'	=> true
				)
			)
		);
		$oDialog->setElement(
			$oDialog->createRow(
				\L10N::t('Gültig von', $oGui->gui_description),
				'calendar',
				array(
					'db_column'	=> 'valid_from',
					'db_alias'	=> 'kts',
					'format'	=> new \Ext_Thebing_Gui2_Format_Date('convert_null'),
				)
			)
		);
		$oDialog->setElement(
			$oDialog->createRow(
				\L10N::t('Gültig bis', $oGui->gui_description),
				'calendar',
				array(
					'db_column'	=> 'valid_until',
					'db_alias'	=> 'kts',
					'format'	=> new \Ext_Thebing_Gui2_Format_Date('convert_null'),
				)
			)
		);
		$oDialog->setElement(
			$oDialog->createRow(
				\L10N::t('Kommentar', $oGui->gui_description),
				'textarea',
				array(
					'db_column'	=> 'comment',
					'db_alias'	=> 'kts'
				)
			)
		);
		
		return $oDialog;
	}
		
	static public function getPriorityOptions() {
		
		$aOptions = [
			1 => \L10N::t('Sehr hoch', self::L10NPath),
			2 => \L10N::t('Hoch', self::L10NPath),
			3 => \L10N::t('Normal', self::L10NPath),
			4 => \L10N::t('Niedrig', self::L10NPath),
			5 => \L10N::t('Sehr niedrig', self::L10NPath),
		];
		
		return $aOptions;
	}
	
}

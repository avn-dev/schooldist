<?php

namespace TsScreen\Gui2\Data;

use TsScreen\Hook\NavigationLeftHook;

class ScreenData extends \Ext_Thebing_Gui2_Data {

	/**
	 * Den Dialog aufbauen
	 * @param \Ext_Gui2 $oGui
	 * @return \Ext_Gui2_Dialog $oDialog
	 * @throws \Exception
	 */
	public static function getDialog(\Ext_Gui2 $oGui) {

		// Dialog
		$oDialog = $oGui->createDialog($oGui->t('Bildschirm "{name}" editieren'), $oGui->t('Neuen Bildschirm anlegen'));
		
		$oTab = $oDialog->createTab($oGui->t('Einstellungen'));
		
		$oTab->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
			'db_alias' => 'ts_scr',
			'db_column' => 'name',
			'required'	=> true,
		)));
		
		$oUpload = new \Ext_Gui2_Dialog_Upload($oGui, $oGui->t('Logo'), $oDialog, 'logo', 'ts_scr', '/storage/public/screens/');
		$oUpload->bAddColumnData2Filename = 0;
		$oTab->setElement($oUpload);
				
		$oTab->setElement($oDialog->createRow($oGui->t('Farbe'), 'color', array(
			'db_alias' => 'ts_scr',
			'db_column' => 'color',
			'required'	=> true,
		)));
		
		$oTab->setElement($oDialog->createRow($oGui->t('CSS'), 'textarea', array(
			'db_alias' => 'ts_scr',
			'db_column' => 'css',
		)));
		
		$oDialog->setElement($oTab);
		
		
		$oTab = $oDialog->createTab($oGui->t('Zeitplan'));
		
		$oGuiFactory = new \Ext_Gui2_Factory('tsScreen_schedule');
		$oGuiSchedule = $oGuiFactory->createGui();
		
		$oTab->setElement($oGuiSchedule);
		
		$oDialog->setElement($oTab);
		
		return $oDialog;
	}

	public static function getTypeOptions() {

		$aTypes = [
			''=>'',
			'ticker'=>\L10N::t('Laufschrift',NavigationLeftHook::TRANSLATION_PATH),
			'schedule'=>\L10N::t('Stundenplan',NavigationLeftHook::TRANSLATION_PATH),
			'roomplan'=>\L10N::t('Raumplan',NavigationLeftHook::TRANSLATION_PATH),
			'editor'=>\L10N::t('Freier Inhalt',NavigationLeftHook::TRANSLATION_PATH)
		];

		return $aTypes;
	}

	public static function getScheduleDialog(\Ext_Gui2 $oGui) {

		// Dialog
		$oDialog = $oGui->createDialog($oGui->t('Eintrag "{type}" editieren'), $oGui->t('Neuen Eintrag anlegen'));

		$aTypes = self::getTypeOptions($oGui);
		
		$oDialog->setElement($oDialog->createRow($oGui->t('Typ'), 'select', [
			'db_column'=>'type', 'select_options' => $aTypes,
			'child_visibility' => [
				[
					'db_column' => 'time_from',
					'on_values' => ['ticker', 'schedule', 'editor']
				],
				[
					'db_column' => 'time_to',
					'on_values' => ['ticker', 'schedule', 'editor']
				],
				[
					'db_column' => 'date_from',
					'on_values' => ['ticker', 'schedule', 'editor']
				],
				[
					'db_column' => 'date_to',
					'on_values' => ['ticker', 'schedule', 'editor']
				],
				[
					'db_column' => 'content',
					'on_values' => ['ticker']
				],
				[
					'db_column' => 'school_id',
					'on_values' => ['roomplan', 'schedule']
				],
				[
					'db_column' => 'buildings',
					'on_values' => ['roomplan', 'schedule']
				],
				[
					'db_column' => 'autoplay_speed',
					'on_values' => ['roomplan']
				],
				[
					'db_column' => 'html',
					'on_values' => ['editor']
				]
			],
		]));
		
		$oDialog->setElement($oDialog->createRow($oGui->t('Aktiv'), 'checkbox', ['db_column'=>'visible']));
		$oDialog->setElement($oDialog->createRow($oGui->t('Beschreibung'), 'textarea', ['db_column'=>'description']));
		
		$aTimes = \Ext_Thebing_Util::getTimeRows('format', 15, 0, 85500);
		
		$oDialog->setElement($oDialog->createRow($oGui->t('Zeit: Start'), 'select', ['db_column'=>'time_from', 'select_options' => \Util::addEmptyItem($aTimes, '', ''), 'format' => new \Ext_Thebing_Gui2_Format_Time()]));
		$oDialog->setElement($oDialog->createRow($oGui->t('Zeit: Ende'), 'select', ['db_column'=>'time_to', 'select_options' => \Util::addEmptyItem($aTimes, '', ''), 'format' => new \Ext_Thebing_Gui2_Format_Time()]));
		
		$oDialog->setElement($oDialog->createRow($oGui->t('Datum: Start'), 'calendar', ['db_column'=>'date_from', 'format' => new \Ext_Thebing_Gui2_Format_Date()]));
		$oDialog->setElement($oDialog->createRow($oGui->t('Datum: Ende'), 'calendar', ['db_column'=>'date_to', 'format' => new \Ext_Thebing_Gui2_Format_Date()]));
		
		$oDialog->setElement($oDialog->createRow($oGui->t('Inhalt'), 'input', ['db_column'=>'content']));

		$oDialog->setElement($oDialog->createRow($oGui->t('Anzeigedauer in Sekunden'), 'input', ['db_column'=>'autoplay_speed', 'format' => new \Ext_Thebing_Gui2_Format_Int()]));
		
		$oDialog->setElement($oDialog->createRow($oGui->t('Schule'), 'select', ['db_column'=>'school_id', 'selection'=> new \TsScreen\Gui2\Selection\School()]));
		
		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Gebäude'), 
				'select', 
				[
					'db_column'=>'buildings', 
					'selection'=> new \TsScreen\Gui2\Selection\Building(),
					'dependency' => [
						[
							'db_alias' => '',
							'db_column' => 'school_id',
						],
					],
					'multiple' => 5,
					'jquery_multiple' => 1,
				]
			)
		);
		
		$oDialog->setElement($oDialog->createRow($oGui->t('Inhalt'), 'html', ['db_column'=>'html', 'advanced' => 'filemanager']));
		
		return $oDialog;
	}

}


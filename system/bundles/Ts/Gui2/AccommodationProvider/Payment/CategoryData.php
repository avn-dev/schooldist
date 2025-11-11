<?php

namespace Ts\Gui2\AccommodationProvider\Payment;

use Ts\Gui2\AccommodationProvider\PaymentPeriodDisplaysFormat;

class CategoryData extends \Ext_Thebing_Gui2_Data {

	/**
	 * @inheritdoc
	 */
	protected function _getJoinedItemsErrorLabel($sLabel) {
		if($sLabel === 'accommodations_validities') {
			$sLabel = 'accommodation_selaries';
		}

		return parent::_getJoinedItemsErrorLabel($sLabel);
	}


	/**
	 * @return array
	 */
	public function getComparativeOperators() {
		
		$aReturn = array(
			'>' => $this->t('mehr als'),
			'<' => $this->t('weniger als'),
			'=' => $this->t('genau')
		);

		return $aReturn;
	}
	
	/**
	 * @return string
	 */
	public function getBasisDateOptions() {

		$aReturn = array(
			'accommodation_start' => $this->t('Unterkunftsstart'),
			// latest, nicht last, da es nicht die endgültig letzte Zahlung ist, sondern nur die neueste
			'latest_payment' => $this->t('Letzte Zahlung')
		);

		return $aReturn;
	}
	
	/**
	 * @return string
	 */
	public function getLogicalOperators() {

		$aReturn = array(
			'and' => $this->t('Und'),
			// latest, nicht last, da es nicht die endgültig letzte Zahlung ist, sondern nur die neueste
			'or' => $this->t('Oder')
		);

		return $aReturn;
	}
	
	/**
	 * @return \Ext_Gui2_Dialog 
	 */
	public static function getDialog(\Ext_Gui2 $oGui){

		$oGuiData = $oGui->getDataObject(); /** @var static $oGuiData */
		$oClient = \Ext_Thebing_Client::getInstance();
		$aInboxes = $oClient->getInboxList('use_id');
		$aSchools = \Ext_Thebing_Client::getSchoolList(true);

		$oDialog = $oGui->createDialog($oGui->t('Abrechnungskategorie "{name}" editieren'), $oGui->t('Neue Abrechnungskategorie anlegen'));

		$oDialog->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
			'db_column' => 'name',
			'required'	=> true,
		)));

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Schulen'),
				'select',
				[
					'db_alias' => '',
					'db_column' => 'schools',
					'multiple' => 5,
					'select_options' => $aSchools,
					'jquery_multiple' => 1,
					'searchable' => 1,
					'required' => 1,
				]
			)
		);

		$oPeriods = $oDialog->createJoinedObjectContainer('periods', array(
			'min' => 1,
			'max' => 20,
		));

		$aDays = \Ext_Thebing_Util::getDays('%a');
		$aPeriodUnits = $aDays + [
			8 => $oGui->t('Tage'),
			9 => $oGui->t('Wochen'),
			10 => $oGui->t('Monate')
		];
		
		$aBasedOnOptions = [
			'service_period' => $oGui->t('Leistungszeitraum'), 
			'accommodation_start' => $oGui->t('Unterkunftsstart'),
			'accommodation_end' => $oGui->t('Unterkunftsende')
		];
		
		$aDirectionOptions = [
			'pre' => $oGui->t('Aktuelles Datum minus'),
			'post' => $oGui->t('Aktuelles Datum plus'),
			'start_month_minus' => $oGui->t('Anfang des Monats minus'),
			'start_month_plus' => $oGui->t('Anfang des Monats plus'),
			'end_month_minus' => $oGui->t('Ende des Monats minus'),
			'end_month_plus' => $oGui->t('Ende des Monats plus'),
		];

		$aBillingPeriodTypes = [
			'relative_weeks' => $oGui->t('Relative Wochen'), // Wochenanzahl
			'absolute_weeks' => $oGui->t('Absolute Wochen'), // Wochenanzahl und Wochenbeginn
			'absolute_month' => $oGui->t('Absoluter Monat')
		];

		$oPeriods->setElement($oPeriods->createMultiRow($oGui->t('Zuweisung aus dem Zeitraum'), array(
			'create_multirow' => true,
			'db_alias' => 'ts_appcp',
			'grid' => true,
			'items' => array(
				// Zeile 1
				array(
					'db_column' => 'before_direction',
					'input' => 'select',
					'select_options' => $aDirectionOptions,
					'text_after' => '&nbsp;',
					'text_after_spaces' => false,
					'class' => 'txt form-control auto_width',
					'grid_cols' => 3,
				),
				array(
					'db_column' => 'before_quantity',
					'input' => 'input',
					'text_after' => '&nbsp;',
					'text_after_spaces' => false,
					'class' => 'condition_week txt form-control w50',
					'grid_cols' => 1,
				),
				array(
					'db_column' => 'before_unit',
					'input' => 'select',
					'select_options' => $aPeriodUnits,
					'text_after_spaces' => false,
					'class' => 'txt form-control auto_width',
					'grid_cols' => 8,
				),
				// Zeile 2
				array(
					'db_column' => 'after_direction',
					'input' => 'select',
					'select_options' => $aDirectionOptions,
					'text_after' => '&nbsp;',
					'text_after_spaces' => false,
					'class' => 'txt form-control auto_width',
					'grid_cols' => 3,
				),
				array(
					'db_column' => 'after_quantity',
					'input' => 'input',
					'text_after' => '&nbsp;',
					'text_after_spaces' => false,
					'class' => 'condition_week txt form-control w50',
					'grid_cols' => 1,
				),
				array(
					'db_column' => 'after_unit',
					'input' => 'select',
					'select_options' => $aPeriodUnits,
					//'text_after' => '<br>'.$oGui->t('basierend auf').'&nbsp;',
					//'text_after_spaces' => false,
					'class' => 'txt form-control auto_width',
					'grid_cols' => 8,
				),
				array(
					'db_column' => 'basedon',
					'input' => 'select',
					'select_options' => $aBasedOnOptions,
					'text_before' => $oGui->t('basierend auf').'&nbsp;',
					'class' => 'txt form-control auto_width',
					'grid_cols' => 4,
				)
			)
		)));

		$oPeriods->setElement($oPeriods->createRow($oGui->t('Abzurechnender Zeitraum'), 'select', array(
			'db_column' => 'period_type',
			'db_alias' => 'ts_appcp',
			'required' => true,
			'select_options' => $aBillingPeriodTypes
		)));
		
		$oPeriods->setElement($oPeriods->createRow($oGui->t('Zu bezahlende Zeiträume, pro Zuweisung'), 'input', array(
			'db_column' => 'weeks',
			'db_alias' => 'ts_appcp',
			'required' => true
		)));
		
		$oPeriods->setElement($oPeriods->createRow($oGui->t('Starttag der Abrechnungswoche'), 'select', array(
			'db_column' => 'period_start_day',
			'db_alias' => 'ts_appcp',
			'required' => true,
			'select_options' => $aDays,
			'dependency_visibility' => array(
				'db_column' => 'period_type',
				'db_alias' => 'ts_appcp',
				'on_values' => array('absolute_weeks')
			)
		)));

		$oPeriods->setElement($oDialog->createNotification($oGui->t('Achtung'), $oGui->t('Einträge werden nur angezeigt, wenn mindestens ein nicht bezahlter Eintrag in den Zeitraum fällt.'), 'info'));
		
		$oPeriods->setElement($oPeriods->createRow($oGui->t('Einträge anzeigen pro'), 'select', array(
			'db_column' => 'display',
			'db_alias' => 'ts_appcp',
			'select_options' => PaymentPeriodDisplaysFormat::getDisplayOptions($oGui)
		)));

		$oPeriods->setElement($oPeriods->createRow($oGui->t('Nur Zahlungen für Zeiträume in der Vergangenheit anzeigen'), 'checkbox', array(
			'db_column' => 'only_past_periods',
			'db_alias' => 'ts_appcp',
			'value' => 1
		)));

		$oPeriods->setElement($oPeriods->createRow($oGui->t('Abhängigkeit von der Dauer'), 'checkbox', array(
			'db_column' => 'duration_dependency',
			'db_alias' => 'ts_appcp',
			'value' => 1,
			'child_visibility' => array(
				array(
					'class' => 'conditions_row',
					'on_values' => array('1')
				)
			)
		)));

		$oPeriods->setElement($oPeriods->createMultiRow($oGui->t('Nur anzeigen, wenn'), array(
			'create_multirow' => true,
			'row_class' => 'conditions_row',
			'db_alias' => 'conditions',
			'input_container' => true,
			'multi_rows' => true,
			'items' => array(
				array(
					'db_column' => 'logic_operator',
					'input' => 'select',
					'select_options' => $oGuiData->getLogicalOperators(),
					'text_after' => '&nbsp;',
					'text_after_spaces' => false,
					'class' => 'txt auto_width first-parent-not-visible ',
					'style' => '',
					'jointable' => true
				),
				array(
					'db_column' => 'basis_date',
					'input' => 'select',
					'select_options' => $oGuiData->getBasisDateOptions(),
					'text_after' => '&nbsp;',
					'text_after_spaces' => false,
					'class' => 'txt auto_width',
					'jointable' => true
				),
				array(
					'db_column' => 'comparative_operators',
					'input' => 'select',
					'select_options' => $oGuiData->getComparativeOperators(),
					'text_after' => '&nbsp;',
					'text_after_spaces' => false,
					'class' => 'txt auto_width',
					'jointable' => true
				),
				array(
					'db_column' => 'weeks',
					'input' => 'input',
					'text_after' => $oGui->t('Wochen zurückliegt'),
					'class' => 'condition_week txt w50',
					'jointable' => true
				)
			)
		)));
		
		$oPeriods->setElement(
			$oPeriods->createRow(
				$oGui->t('Inbox'),
				'select',
				array(
					'db_column'			=> 'inboxes',
					'db_alias'			=> 'ts_appcp',
					'required'			=> 1,
					'select_options'	=> $aInboxes,
					'multiple'			=> 3,
					'jquery_multiple'	=> 1,
				)
			)
		);

		$oDialog->setElement($oPeriods);

		return $oDialog;
	}

	/**
	 * @inheritDoc
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave = true, $sAction = 'edit', $bPrepareOpenDialog = true) {

		$aData = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);

		// Zusätzliche Aliase funktionieren bei Containern nicht
		foreach((array)$aData['error'] as $iKey => $aError) {
			if(is_array($aError)) {
				if(
					$aError['input']['dbalias'] === 'periods' &&
					$aError['input']['dbcolumn'] === 'before_quantity'
				) {
					preg_match('/periods\[(-?\d+)\]/', $aError['identifier'], $aMatches);
					$aData['error'][$iKey]['error_id'] = '[before_quantity][ts_appcp]['.$aMatches[1].'][periods]';
				}
			}

		}

		return $aData;

	}

}
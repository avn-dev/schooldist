<?php

class Ext_TC_Communication_AutomaticTemplate_Gui2_Data extends Ext_TC_Gui2_Data
{

	/**
	 * @inheritdoc
	 */
	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true)
    {

		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		$aData['types_with_condition'] = Factory::executeStatic('Ext_TC_Communication_AutomaticTemplate', 'getTypesWithCondition');

		return $aData;

	}

	/**
	 * @return array
	 */
	public function getSelectOptionsTypes()
    {

		$aTypes = [
			'registration_mail' => $this->t('Registrierungs-E-Mail')
		];

		return $aTypes;

	}

	/**
	 * @return array
	 */
	public function getSelectOptionsEvents()
    {

		return[];
	}

	public static function getDialog(\Ext_Gui2 $oGui)
	{

		$oGuiData = $oGui->getDataObject();
		$aTemplates = Ext_TC_Util::addEmptyItem(Ext_TC_Factory::executeStatic('Ext_TC_Communication_AutomaticTemplate', 'getSelectOptionTemplates'));
		$aTypes = Ext_TC_Util::addEmptyItem($oGuiData->getSelectOptionsTypes());
		$aRecipients = Ext_TC_Communication_AutomaticTemplate::getSelectOptionsRecipients();
		$aHours = Ext_TC_Util::getHours();

		$oDialog = $oGui->createDialog($oGui->t('E-Mail-Vorlage "{name}"'), $oGui->t('Neue E-Mail-Vorlage'));

		$oDialog->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
			'db_column' => 'name',
			'required' => 1
		)));
		$oDialog->setElement($oDialog->createRow($oGui->t('Vorlage'), 'select', array(
			'db_column' => 'layout_id',
			'select_options' => $aTemplates,
			'required' => 1
		)));
		$oDialog->setElement($oDialog->createRow($oGui->t('Art'), 'select', array(
			'db_column' => 'type',
			'select_options' => $aTypes,
			'required' => 1
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Ausführungszeit'), 'select', array(
			'db_column' => 'execution_time',
			'select_options' => $aHours,
			'dependency_visibility' => [
				'db_column' => 'type',
				'on_values' => Factory::executeStatic('Ext_TC_Communication_AutomaticTemplate', 'getTypesWithExecutionTime')
			]
		)));

		$oDialog->setElement($oDialog->createMultiRow($oGui->t('Bedingung'), [
			'row_class' => 'condition_row',
			'items' => [
				[
					'db_column' => 'days',
					'input' => 'input',
					'class' => 'txt w50',
					'style' => 'width: 50px;', // w50 funktioniert nicht
					'text_after' => $oGui->t('Tage').'&nbsp;'
				],
				[
					'db_column' => 'temporal_direction',
					'input' => 'select',
					'class' => 'txt auto_width',
					'select_options' => [
						'before' => $oGui->t('vor'),
						'after' => $oGui->t('nach')
					],
					'text_after' => '&nbsp;'
				],
				[
					'db_column' => 'event_type',
					'input' => 'select',
					'class' => 'txt auto_width',
					'select_options' => $oGuiData->getSelectOptionsEvents()
				]
			]
		]));

		$oDialog->setElement($oDialog->createRow($oGui->t('Stornierung ignorieren'), 'checkbox', array(
			'db_column' => 'ignore_cancellation',
			'dependency_visibility' => [
				'db_column' => 'type',
				'on_values' => ['booking_mail']
			]
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Minimale Anzahl an vergangenen Tagen seit letzter Korrespondenz'), 'input', array(
			'db_column' => 'days_after_last_message',
			'dependency_visibility' => [
				'db_column' => 'type',
				'on_values' => array_keys(Factory::executeStatic('Ext_TC_Communication_AutomaticTemplate', 'getTypesWithCondition'))
			]
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Empfänger-Typ'), 'select', array(
			'db_column' => 'recipient_type',
			'select_options' => [
				'' => '',
				'all_customers' => $oGui->t('Alle Schüler'),
				'current_customers' => $oGui->t('Aktuelle Schüler'),
				'current_and_future_customers' => $oGui->t('Aktuelle und zukünftige Schüler'),
				'current_and_old_customers' => $oGui->t('Aktuelle und alte Schüler')
			],
			'required' => true,
			'dependency_visibility' => [
				'db_column' => 'type',
				'on_values' => ['birthday_mail']
			]
		)));

        //$oTab->setElement('<div id="cronjob_container_1">');
        //$oTab->setElement($oDialog->createRow($oGui->t('Ausgehend von'), 'select', array(
        //	'db_column' => 'event_id',
        //	'select_options' => $aEvents,
        //	'required' => 1
        //)));
        //$oTab->setElement($oDialog->createRow($oGui->t('Zusatz'), 'select', array(
        //	'db_column'=>'additional_id',
        //	'select_options' => Ext_TC_Util::addEmptyItem($aAdditional)
        //)));
        //$oTab->setElement($oDialog->createRow($oGui->t('Tage'), 'input', array(
        //	'db_column'=>'days',
        //	'class'=>'w50 txt'
        //)));
        //$oTab->setElement($oDialog->createRow($oGui->t('Termin'), 'select', array(
        //	'db_column'=>'date_id',
        //	'select_options' => $aDates
        //)));
        //$oTab->setElement($oDialog->createRow($oGui->t('Datum'), 'calendar', array(
        //	'db_column'=>'date',
        //	'format' => Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date')
        //)));
        //$oTab->setElement('</div>');

		$oDialog->setElement($oDialog->createRow($oGui->t('Empfänger'), 'select', array(
			'db_column' => 'recipients',
			'multiple' => 3,
			'jquery_multiple' => 1,
			'select_options' => $aRecipients,
			'required' => true
		)));
		$oDialog->setElement($oDialog->createRow($oGui->t('An'), 'input', array(
			'db_column'	=> 'to',
			'required' => true,
			'dependency_visibility' => [
				'db_column' => 'recipients',
				'on_values' => ['individual']
			]
		)));

        // CC und BCC in TS ausblenden, da dies über die E-Mail-Vorlagen geht
		if(Ext_TC_Util::getSystem() !== 'school') {
			$oDialog->setElement($oDialog->createRow($oGui->t('CC'), 'input', array(
				'db_column'	=> 'cc'
			)));
			$oDialog->setElement($oDialog->createRow($oGui->t('BCC'), 'input', array(
				'db_column'	=> 'bcc',
			)));
		}

		return $oDialog;
	}

	public static function getOrderby()
	{

		return ['name' => 'ASC'];
	}

}

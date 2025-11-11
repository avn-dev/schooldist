<?php

/**
 * @see \Ext_TS_Payment_Gui2_Html::getPaymentData
 */
class Ext_TS_Payment_Condition_Gui2_Data extends Ext_Gui2_Data {

	const L10N_PATH = 'Thebing » Accounting » Payment Conditions';
	
	/**
	 * @param \Ext_TC_Gui2 $oGui
	 * @return \Ext_Gui2_Dialog
	 */
	public static function getDialog($oGui) {

		$oDialog = $oGui->createDialog($oGui->t('Zahlungsbedingung editieren'), $oGui->t('Neue Zahlungsbedingung anlegen'));

		$oDialog->setElement($oDialog->createRow($oGui->t('Titel'), 'input', [
			'db_column'=> 'name',
			'required' => true
		]));

		$oDialog->setElement($oDialog->createRow($oGui->t('Kommentar'), 'textarea', [
			'db_column'=> 'comment'
		]));

		$oDialog->setElement($oDialog->createMultiRow($oGui->t('Aufschlag').'/'.$oGui->t('Rabatt'), [
			'items' => [
				[
					'db_column' => 'surcharge_amount',
					'input' => 'input',
					'format' => new Ext_Thebing_Gui2_Format_Amount(),
					'style' => 'width: 60px;',
					'text_after' => '&nbsp;'
				],
				[
					'db_column' => 'surcharge_type',
					'input' => 'select',
					'select_options' => [
						'' => '',
						'amount' => $oGui->t('Betrag'),
						'percent' => $oGui->t('Prozent')
					],
					'style' => 'width: auto',
					'text_before' => $oGui->t('Typ'),
					'text_after' => '&nbsp;'
				],
				[
					'db_column' => 'surcharge_calculation',
					'input' => 'select',
					'select_options' => [
						'' => '',
						'one_time' => $oGui->t('einmalig'),
						'per_month' => $oGui->t('pro Monat'),
//						'per_installment' => $oGui->t('pro Rate')
					],
					'style' => 'width: auto',
					'text_before' => $oGui->t('Berechnung'),
					'text_after' => '&nbsp;'
				],
				[
					'db_column' => 'surcharge_on',
					'input' => 'select',
					'select_options' => [
						'' => '',
//						'deposit' => $oGui->t('Anzahlung'),
//						'final' => $oGui->t('Restzahlung'),
//						'installments' => $oGui->t('Raten'),
						'course_fees' => $oGui->t('Kursgebühren')
					],
					'style' => 'width: auto',
					'text_before' => $oGui->t('Aufschlagen auf'),
					'text_after' => '&nbsp;'
				],
				[
					'db_column'=> 'surcharge_description',
					'input'=>'input',
					'placeholder' => $oGui->t('Rechnungsposition'),
				]
			]
		]));
		
		$oDialog->setElement($oDialog->create('h4')->setElement($oGui->t('Einstellungen')));

		$oJoinContainer = $oDialog->createJoinedObjectContainer('settings', ['min' => 0, 'max' => 5]);

		$oJoinContainer->setElement($oJoinContainer->createRow($oGui->t('Typ'), 'select', [
			'db_alias' => 'settings',
			'db_column'=> 'type',
			'required' => true,
			'class' => 'settings_type',
			'select_options' => Ext_Thebing_Util::addEmptyItem(Ext_TS_Payment_Condition_Gui2_Data::getTypeOptions())
		]));

		$oJoinContainer->setElement($oJoinContainer->createMultiRow($oGui->t('Betrag'), [
			'create_multirow' => true,
			'multi_rows' => true,
			'db_alias' => 'amounts',
			'input_container' => true,
			'row_class' => 'settings_deposit_container',
			'items' => [
				[
					'db_column' => 'amount',
					'input' => 'input',
					//'format' => new Ext_Thebing_Gui2_Format_Amount(), // TODO Funktioniert nicht
					'style' => 'width: 60px;',
					'text_after' => '&nbsp;',
					'jointable' => true
				],
				[
					'db_column' => 'type_combined',
					'input' => 'select',
					'select_options' => self::getPrepayOptions($oGui),
					'style' => 'width: auto;',
					'jointable' => true,
					'skip_value_handling' => true,
				],
				[
					'db_column' => 'setting',
					'input' => 'hidden',
					'jointable' => true
				],
				[
					'db_column' => 'type',
					'input' => 'hidden',
					'jointable' => true
				],
				[
					'db_column' => 'type_id',
					'input' => 'input',
					// 0 wird aus irgendeinem Grund bei type=hidden nicht geschrieben
					'style' => 'display: none;',
					'jointable' => true
				]
			]
		]));

		$oJoinContainer->setElement($oJoinContainer->createRow($oGui->t('Typ'), 'select', [
			'db_alias' => 'settings',
			'db_column' => 'installment_type',
			'select_options' => [
				'weekly' => $oGui->t('wöchentlich'),
				'monthly' => $oGui->t('monatlich'),
				'fixed_number' => $oGui->t('Feste Anzahl an Raten'),
			],
			'dependency_visibility' => [
				'db_column' => 'type',
				'db_alias' => 'settings',
				'on_values' => ['installment']
			],
		]));

		$oJoinContainer->setElement($oJoinContainer->createRow($oGui->t('Aufteilung'), 'select', [
			'db_alias' => 'settings',
			'db_column' => 'installment_split',
			'select_options' => [
				//'service_period' => $oGui->t('Leistungszeitraum'),
				'percentage' => $oGui->t('prozentual'),
				'quarterly_month' => $oGui->t('auf Viertelmonate'),
				'monthly' => $oGui->t('monatlich'),
			],
			'dependency_visibility' => [
				'db_column' => 'type',
				'db_alias' => 'settings',
				'on_values' => ['installment']
			],
		]));

		$oJoinContainer->setElement($oJoinContainer->createRow($oGui->t('Abrechnung alle'), 'input', [
			'db_alias' => 'settings',
			'db_column' => 'installment_charging',
			'dependency_visibility' => [
				'db_column' => 'type',
				'db_alias' => 'settings',
				'on_values' => ['installment']
			],
			'input_div_addon' => $oGui->t('Monate / Wochen'),
		]));

		$oJoinContainer->setElement($oJoinContainer->createMultiRow($oGui->t('Fälligkeit'), [
			'db_alias' => 'settings',
			'items' => [
				[
					'db_column' => 'due_days',
					'input' => 'input',
					'style' => 'width: 60px;',
					'text_after' => ' '.$oGui->t('Tage').' '
				],
				[
					'db_column' => 'due_direction',
					'input' => 'select',
					'select_options' => [
						'before' => $oGui->t('vor'),
						'after' => $oGui->t('nach')
					],
					'style' => 'width: auto',
					'text_after' => '&nbsp;'
				],
				[
					'db_column' => 'due_type',
					'input' => 'select',
					'select_options' => [
						'document_date' => $oGui->t('Rechnungsdatum'),
						'course_start_date' => $oGui->t('Kursstart'),
						'course_start_date_month_end' => $oGui->t('Monatsende (Kursbeginn)'),
						'begin' => $oGui->t('Anfang der Abrechnungsperiode'),
						'end' => $oGui->t('Ende der Abrechnungsperiode'),
						'start_of_month' => $oGui->t('Monatsanfang')
					],
					'style' => 'width: auto'
				],
			]
		]));

		$oJoinContainer->setElement($oJoinContainer->createRow($oGui->t('Zusatzgebühren vollständig mit erster Rate berechnen'), 'checkbox', [
			'db_alias' => 'settings',
			'db_column' => 'additional_fees_in_first_installment',
			'dependency_visibility' => [
				'db_column' => 'type',
				'db_alias' => 'settings',
				'on_values' => ['installment']
			]
		]));

		$oJoinContainer->setElement($oJoinContainer->createRow($oGui->t('Erste Rate mit zweiter zusammenfassen, falls erste Rate unvollständig ist'), 'checkbox', [
			'db_alias' => 'settings',
			'db_column' => 'combine_partial_instalments',
			'dependency_visibility' => [
				'db_column' => 'type',
				'db_alias' => 'settings',
				'on_values' => ['installment']
			]
		]));

		$oJoinContainer->setElement($oJoinContainer->createRow($oGui->t('Letzte Rate mit zweiter zusammenfassen, falls letzte Rate unvollständig ist'), 'checkbox', [
			'db_alias' => 'settings',
			'db_column' => 'combine_last_partial_instalments',
			'dependency_visibility' => [
				'db_column' => 'type',
				'db_alias' => 'settings',
				'on_values' => ['installment']
			]
		]));
		
		$oDialog->setElement($oJoinContainer);

		return $oDialog;
	}

	static public function getTypeOptions($includeInterim=false) {
		
		$options = [
			'deposit' => L10N::t('Anzahlung', Ext_TS_Payment_Condition_Gui2_Data::L10N_PATH),
			'final' => L10N::t('Restzahlung', Ext_TS_Payment_Condition_Gui2_Data::L10N_PATH),
			'installment' => L10N::t('Ratenzahlung', Ext_TS_Payment_Condition_Gui2_Data::L10N_PATH)
		];
		
		if($includeInterim) {
			$options['interim'] = L10N::t('Zwischenzahlung', Ext_TS_Payment_Condition_Gui2_Data::L10N_PATH);
		}
		
		return $options;
	}
	
	/**
	 * @param Ext_Gui2 $oGui
	 * @return array
	 */
	private static function getPrepayOptions(Ext_Gui2 $oGui) {

		$oClient = \Ext_Thebing_Client::getFirstClient();
		$aSchools = \Ext_Thebing_Client::getSchoolList(false, 0, true);
		$aCurrencies = $oClient->getSchoolsCurrencies();

		$aOptions = [];
		foreach($aCurrencies as $iCurrencyId => $sSign) {
			$aOptions['amount_currency_'.$iCurrencyId] = $sSign;
		}

		$aOptions['percent_all_0'] = $oGui->t('% auf').' '.$oGui->t('gesamten Preis');
		$aOptions['percent_course_0'] = $oGui->t('% auf').' '.$oGui->t('gesamten Kurspreis');
		$aOptions['percent_accommodation_0'] = $oGui->t('% auf').' '.$oGui->t('gesamten Unterkunftspreis');
		$aOptions['percent_insurance_0'] = $oGui->t('% auf').' '.$oGui->t('gesamten Versicherungspreis');

		foreach($aSchools as $oSchool) {

			$oCourseList = $oSchool->getCourseListObject();
			$aCourses = $oCourseList->getObjectList();
			$aAccCategories = $oSchool->getAccommodationCategoriesList();

			foreach($aCourses as $oCourse) {
				$aAdditionalCosts = $oCourse->getAdditionalCosts(false);
				foreach($aAdditionalCosts as $oCost) {
					$aOptions['percent_additionalcourse_'.$oCost->id] = $oGui->t('% auf').' '.$oSchool->short.' – '.$oCost->getName();
				}
			}

			foreach($aAccCategories as $oCategory) {
				$aAdditionalCosts = $oCategory->getAdditionalCosts();
				foreach($aAdditionalCosts as $oCost) {
					$aOptions['percent_additionalaccommodation_'.$oCost->id] = $oGui->t('% auf').' '.$oSchool->short.' – '.$oCost->getName();
				}
			}

		}

		return $aOptions;

	}

	/**
	 * @inheritdoc
	 */
	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null) {

		if($sError === 'PAYMENT_CONDITION_INSTALLMENT_WITH_FINAL') {
			$sMessage = $this->t('Eine Ratenzahlung darf nicht mit einer Restzahlung kombiniert werden.');
		} elseif($sError === 'PAYMENT_CONDITION_NO_FINAL') {
			$sMessage = $this->t('Die Zahlungsbedingung benötigt eine Restzahlung.');
		} elseif($sError === 'PAYMENT_CONDITION_INSTALLMENT_COUNT') {
			$sMessage = $this->t('Die Zahlungsbedingung kann nur eine Ratenzahlung enthalten.');
		} elseif($sError === 'PAYMENT_CONDITION_FINAL_COUNT') {
			$sMessage = $this->t('Die Zahlungsbedingung kann nur eine Restzahlung enthalten.');
		} else {
			$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}

		return $sMessage;

	}

}

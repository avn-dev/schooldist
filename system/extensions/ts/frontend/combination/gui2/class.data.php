<?php

/**
 * @property Ext_TS_Frontend_Combination $oWDBasic
 */
class Ext_TS_Frontend_Combination_Gui2_Data extends Ext_TC_Frontend_Combination_Gui2_Data {

	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true) {

		if(
			$sIconAction === 'new' ||
			$sIconAction === 'edit'
		) {

			$sIconKey = self::getIconKey($sIconAction, $sAdditional);

			if(!$this->oWDBasic) {
				$this->_getWDBasicObject($aSelectedIds);
			}

			$oDialog = self::getDialog($this->_oGui, $this->oWDBasic);
			$this->aIconData[$sIconKey]['dialog_data'] = $oDialog;
			$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

			return $aData;

		} else {
			return parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);
		}

	}

	static public function getDialog(Ext_Gui2 $gui, ?Ext_TC_Frontend_Combination $combination=null) {

		$oDialog = parent::getDialog($gui, $combination);

		if(!$combination) {
			return $oDialog;
		}

		$interfaceLanguage = \Ext_Thebing_School::getSchoolFromSessionOrFirstSchool()->getInterfaceLanguage();
				
		switch($combination->usage) {
			case 'student_login':
				self::addStudentLoginOptions($gui, $oDialog);
				break;
			case Ext_Thebing_Form::TYPE_REGISTRATION_NEW:
			case Ext_Thebing_Form::TYPE_REGISTRATION_V3:
			case Ext_Thebing_Form::TYPE_ENQUIRY:
				self::addInquiryOptions($gui, $oDialog, $combination);
				break;
			case 'pricelist':
				self::addPricelistOptions($gui, $oDialog);
				break;
			case 'feedback_form':
				self::addLanguageSelect($oDialog);
				break;
			case 'agency_login':
				self::addAgencyLoginOptions($gui, $oDialog);
				self::_addPasswordSecuritySelect($oDialog);
				break;
			case 'payment_form':
				self::addPaymentFormOptions($oDialog);
				self::addWidgetCombinationSettings($oDialog, $combination);
				break;
			case 'placementtest':
				
				self::addSchoolSelect($oDialog);
				self::addSchoolLanguageSelect($oDialog);
				
				break;
			case 'course_details':
				
				self::addCourseField($oDialog);
				self::addLanguageSelect($oDialog);
				self::addOverwriteOption($oDialog);
				
				break;
			case 'course_list':

				self::addLanguageSelect($oDialog, true);

				$oH3 = new \Ext_Gui2_Html_H3();
				$oH3->setElement($gui->t('Kurse nach folgenden Kriterien filtern'));
				$oDialog->setElement($oH3);
				
				// Kategorie
				$dummyCategory = \Ext_Thebing_Tuition_Course_Category::getInstance();
				$oDialog->setElement(
					$oDialog->createRow(
						$gui->t('Kategorie'),
						'select',
						array(
							'db_column'      => 'items_course_category',
							'db_alias'	     => 'tc_fc',
							'select_options' => \Ext_Thebing_Util::addEmptyItem($dummyCategory->getArrayList(true, 'name_'.$interfaceLanguage))
						)
					)
				);

				// Schule
				self::addSchoolsSelect($oDialog, false);

				// Flex-Selects
				$dummySuperordinateCourse = \TsTuition\Entity\SuperordinateCourse::getInstance();
				$flexFields = $dummySuperordinateCourse->getFlexibleFields();
				
				foreach($flexFields as $flexField) {
					if(
						$flexField->type == 5 || 
						$flexField->type == 8
					) {
						
						$aOptions = \Ext_TC_Flexibility::getOptions($flexField->id, $interfaceLanguage);
						$fieldOptions = [
							'db_column'      => 'items_flex_'.$flexField->id,
							'db_alias'	     => 'tc_fc',
							'select_options' => $aOptions
						];
						
						if($flexField->type == 8) {
							$fieldOptions['multiple'] = 5;
							$fieldOptions['jquery_multiple'] = true;
							$fieldOptions['searchable'] = true;
						} else {
							$fieldOptions['select_options'] = \Ext_Thebing_Util::addEmptyItem($fieldOptions['select_options']);
						}
						
						$oDialog->setElement(
							$oDialog->createRow(
								$flexField->title,
								'select',
								$fieldOptions
							)
						);
						
					}
				}
				
				break;
			case 'course_categories':				
			case 'accommodation_categories':
			case 'course_category':	
			case 'accommodation_category':
				
				self::addSchoolSelect($oDialog, false);
				self::addLanguageSelect($oDialog);
				
				if($combination->usage == 'course_category') {

					$dummyCategory = \Ext_Thebing_Tuition_Course_Category::getInstance();
					$oDialog->setElement(
						$oDialog->createRow(
							$gui->t('Kategorie'),
							'select',
							[
								'db_column'      => 'items_course_category',
								'db_alias'	     => 'tc_fc',
								'select_options' => \Ext_Thebing_Util::addEmptyItem($dummyCategory->getArrayList(true, 'name_'.$interfaceLanguage)),
								'required' => $bRequired
							]
						)
					);
					
				} elseif($combination->usage == 'accommodation_category') {
					
					$dummyCategory = \Ext_Thebing_Accommodation_Category::getInstance();
					$oDialog->setElement(
						$oDialog->createRow(
							$gui->t('Kategorie'),
							'select',
							[
								'db_column'      => 'items_accommodation_category',
								'db_alias'	     => 'tc_fc',
								'select_options' => \Ext_Thebing_Util::addEmptyItem($dummyCategory->getArrayList(true, 'name_'.$interfaceLanguage)),
								'required' => $bRequired
							]
						)
					);
					
				}
				
				break;
		}

		return $oDialog;
	}

	static private function addOverwriteOption($oDialog) {
		$oDialog->setElement(
			$oDialog->createRow(
				$oDialog->oGui->t('Eigenschaften überschreibbar?'),
				'checkbox', 
				array(
					'db_column'	=> 'overwritable',
					'db_alias'	=> 'tc_fc'
				)
			)
		);
	}
	
	static private function addCourseField($oDialog) {
		
		$oDialog->setElement(
			$oDialog->createRow(
				$oDialog->oGui->t('Kurs'),
				'input',
				array(
					'db_column' => 'items_course_slug',
					'db_alias' => 'tc_fc'
				)
			)
		);
	}
	
	static private function addSchoolSelect($oDialog, $bRequired=true) {
		
		$aSchools = Ext_Thebing_Client::getSchoolList(true);
		$aSchools = Ext_Thebing_Util::addEmptyItem($aSchools, '', '');

		$oDialog->setElement(
			$oDialog->createRow(
				$oDialog->oGui->t('Schule'),
				'select',
				array(
					'db_column' => 'items_school',
					'db_alias' => 'tc_fc',
					'select_options' => $aSchools,
					'required' => $bRequired
				)
			)
		);
	}
	
	static private function addSchoolLanguageSelect($oDialog) {
		
		$oDialog->setElement(
			$oDialog->createRow(
				$oDialog->oGui->t('Sprache'),
				'select',
				array(
					'db_column' => 'items_language',
					'db_alias' => 'ts_fc',
					'selection' => new Ext_Thebing_Gui2_Selection_School_Languages('items_school', true),
					'required' => true
				)
			)
		);

	}
	
	/**
	 * @param Ext_Gui2_Dialog $oDialog
	 */
	static private function addLanguageSelect($oDialog, $bRequired=false) {

		$aLanguages = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getLanguages', array(true));

		if(!empty($aLanguages)) {
			$aLanguages = Util::addEmptyItem($aLanguages);
		}

		$oDialog->setElement(
			$oDialog->createRow(
				$oDialog->oGui->t('Sprache'),
				'select',
				array(
					'db_column'      => 'items_language',
					'db_alias'	     => 'tc_fc',
					'select_options' => $aLanguages,
					'required' => $bRequired
				)
			)
		);

	}

	/**
	 * @param Ext_Gui2 $oGui
	 * @param Ext_Gui2_Dialog $oDialog
	 */
	static private function addPricelistOptions(Ext_Gui2 $oGui, Ext_Gui2_Dialog $oDialog) {

		self::addSchoolSelect($oDialog);
		self::addSchoolLanguageSelect($oDialog);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Saisons'),
				'select',
				array(
					'db_column' => 'items_seasons',
					'db_alias' => 'tc_fc',
					'selection' => new Ext_Thebing_Gui2_Selection_Saisons('items_school', false),
					'multiple' => 5,
					'jquery_multiple' => 1,
					'required' => true,
					'dependency' => array(
						array(
							'db_column' => 'items_school',
							'db_alias' => 'tc_fc'
						)
					)
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Kurse'),
				'select',
				array(
					'db_column' => 'items_courses',
					'db_alias' => 'tc_fc',
					'selection' => new Ext_Thebing_Gui2_Selection_School_Courses('items_school', false),
					'multiple' => 5,
					'jquery_multiple' => 1,
					'sortable' => true,
					'required' => true,
					'dependency' => array(
						array(
							'db_column' => 'items_school',
							'db_alias' => 'tc_fc'
						)
					)
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Unterkunftskombinationen'),
				'select',
				array(
					'db_column' => 'items_accommodationcombinations',
					'db_alias' => 'tc_fc',
					'selection' => new Ext_Thebing_Gui2_Selection_School_Accommodations('items_school', false),
					'multiple' => 5,
					'jquery_multiple' => 1,
					'sortable' => true,
					'required' => true,
					'dependency' => array(
						array(
							'db_column' => 'items_school',
							'db_alias' => 'tc_fc'
						)
					)
				)
			)
		);

	}

	private function addSchoolsSelect(Ext_Gui2_Dialog $oDialog, $bRequired=true) {
		
		$oGui = $oDialog->oGui;
		
		$aSchools = Ext_Thebing_Client::getSchoolList(true);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Schulen'),
				'select',
				array(
					'db_column' => 'items_schools',
					'db_alias' => 'ts_fc',
					'select_options' => $aSchools,
					'multiple' => 5,
					'jquery_multiple' => 1,
					'searchable' => true,
					'required' => $bRequired
				)
			)
		);
	}
	
	/**
	 * @param Ext_Gui2 $oGui
	 * @param Ext_Gui2_Dialog $oDialog
	 */
	static private function addStudentLoginOptions(Ext_Gui2 $oGui, Ext_Gui2_Dialog $oDialog) {

		self::addSchoolsSelect($oDialog);

		$aLanguages = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getLangList', array(true));
		$aLanguages = Ext_Thebing_Util::addEmptyItem($aLanguages, Ext_Thebing_L10N::getEmptySelectLabel('please_choose'));

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Sprache'),
				'select',
				array(
					'db_column' => 'items_language',
					'db_alias' => 'ts_fc',
					'select_options' => $aLanguages,
					'required' => true
				)
			)
		);

		$aPasswordSecurityStatus = array();
		$aPasswordSecurityStatus['low'] = $oGui->t('niedrig');
		$aPasswordSecurityStatus['medium'] = $oGui->t('normal');
		$aPasswordSecurityStatus['heigh'] = $oGui->t('hoch');
		$aPasswordSecurityStatus = Ext_Thebing_Util::addEmptyItem(
			$aPasswordSecurityStatus,
			Ext_Thebing_L10N::getEmptySelectLabel('please_choose')
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Passwort Sicherheit'),
				'select',
				array(
					'db_column' => 'items_password_security',
					'db_alias' => 'ts_fc',
					'select_options' => $aPasswordSecurityStatus,
					'required' => true
				)
			)
		);

	}

	/**
	 * @param Ext_Gui2 $oGui
	 * @param Ext_Gui2_Dialog $oDialog
	 */
	static private function addInquiryOptions(Ext_Gui2 $oGui, Ext_Gui2_Dialog $oDialog, Ext_TC_Frontend_Combination $combination) {

		if(
			$combination->usage !== Ext_Thebing_Form::TYPE_REGISTRATION_V3 &&
			$combination->usage !== Ext_Thebing_Form::TYPE_REGISTRATION_NEW &&
			$combination->usage !== Ext_Thebing_Form::TYPE_ENQUIRY
		) {
			throw new UnexpectedValueException('Combination->usage is not in whitelist');
		}

		$aFormsTmp = Ext_Thebing_Form::getRepository()->findBy(array('type' => $combination->usage));
		/* @var $aFormsTmp Ext_Thebing_Form[] */

		$aFormsList = array();
		$aFormsOptions = array();
		foreach($aFormsTmp as $oForm) {
			$aFormsList[$oForm->id] = $oForm;
			$aFormsOptions[$oForm->id] = $oForm->title;
		}
		$aFormsOptions = Ext_Thebing_Util::addEmptyItem($aFormsOptions);
		unset($aFormsTmp);
		/* @var $aFormsList Ext_Thebing_Form[] */

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Formular'),
				'select',
				array(
					'db_column' => 'items_form',
					'db_alias' => 'ts_fc',
					'select_options' => $aFormsOptions,
					'required' => true,
					'events'=>array(
						array(
							'event' => 'change',
							'function' => 'reloadDialogTab',
							'parameter' => 'aDialogData.id, 0'
						)
					)
				)
			)
		);

		$iSelectedForm = (int)$combination->items_form;

		if(
			!$iSelectedForm ||
			!isset($aFormsList[$iSelectedForm])
		) {
			return;
		}

		$oSelectedForm = $aFormsList[$iSelectedForm];

		$aSchoolsTmp = $oSelectedForm->schools;
		$aSchoolsOptions = Ext_Thebing_Client::getSchoolList(true);
		foreach(array_keys($aSchoolsOptions) as $iSchoolId) {
			if(!in_array($iSchoolId, $aSchoolsTmp)) {
				unset($aSchoolsOptions[$iSchoolId]);
			}
		}
		$aSchoolsOptions = Ext_Thebing_Util::addEmptyItem($aSchoolsOptions);

		$sLabelSchool = $oGui->t('Schule');
		if($combination->usage === Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
			$sLabelSchool = $oGui->t('Standard-Schule');
		}

		$oDialog->setElement(
			$oDialog->createRow(
				$sLabelSchool,
				'select',
				array(
					'db_column' => 'items_school',
					'db_alias' => 'ts_fc',
					'select_options' => $aSchoolsOptions,
					'required' => true
				)
			)
		);

		// Das Formular hat eigentlich schon eine Default-Sprache, die Kombination aber fix immer eine Sprache
		if($combination->usage !== Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
			$aLanguagesTmp = $oSelectedForm->languages;
			$aLanguagesOptions = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getLangList', array(true));
			foreach(array_keys($aLanguagesOptions) as $sLanguageKey) {
				if(!in_array($sLanguageKey, $aLanguagesTmp)) {
					unset($aLanguagesOptions[$sLanguageKey]);
				}
			}
			$aLanguagesOptions = Ext_Thebing_Util::addEmptyItem($aLanguagesOptions);

			$oDialog->setElement(
				$oDialog->createRow(
					$oGui->t('Sprache'),
					'select',
					array(
						'db_column' => 'items_language',
						'db_alias' => 'ts_fc',
						'select_options' => $aLanguagesOptions,
						'required' => true
					)
				)
			);
		}

		// RegForm V3
		if($combination->usage === Ext_Thebing_Form::TYPE_REGISTRATION_V3) {

			$aTemplates = Ext_TC_Frontend_Template::getRepository()->findBy(['usage' => Ext_Thebing_Form::TYPE_REGISTRATION_V3]);
			$aTemplates = collect($aTemplates)->mapWithKeys(function (Ext_TC_Frontend_Template $oTemplate) {
				return [$oTemplate->id => $oTemplate->name];
			})->prepend('', '');

			$oDialog->setElement($oDialog->createRow($oGui->t('Template beim Submit ausführen'), 'select', [
				'db_column' => 'items_template_submit_success',
				'db_alias' => 'ts_fc',
				'select_options' => $aTemplates
			]));

			self::addWidgetCombinationSettings($oDialog, $combination);

		}

	}

	/**
	 * In den Einstellungen für den Agenturlogin muss immer die Sprache festgelegt werden.
	 *
	 * @param Ext_Gui2 $oGui
	 * @param Ext_Gui2_Dialog $oDialog
	 *
	 * @return void
	 */
	static public function addAgencyLoginOptions(Ext_Gui2 $oGui, Ext_Gui2_Dialog $oDialog) {

		$aLanguages = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getLangList', array(true));
		$aLanguages = Ext_Thebing_Util::addEmptyItem($aLanguages, Ext_Thebing_L10N::getEmptySelectLabel('please_choose'));

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Sprache'),
				'select',
				[
					'db_column' => 'items_language',
					'db_alias' => 'ts_fc',
					'select_options' => $aLanguages,
					'required' => true
				]
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Vorlage für Passwort-E-Mail'),
				'select',
				[
					'db_column' => 'items_template_id',
					'db_alias' => 'ts_fc',
					'select_options' => Util::addEmptyItem(Ext_Thebing_Email_Template::getRepository()->getAllForSelect()),
					'required' => true,
				]
			)
		);

	}

	static private function addPaymentFormOptions(Ext_Gui2_Dialog $oDialog) {

		$oDialog->setElement($oDialog->createRow($oDialog->oGui->t('Anbieter'), 'select', [
			'db_column' => 'payment_providers',
			'required' => true,
			'select_options' => (new \TsFrontend\Factory\PaymentFactory())->getOptions(\TsFrontend\Interfaces\PaymentProvider\PaymentForm::class),
			'multiple' => 5,
			'jquery_multiple' => true
		]));

	}

}

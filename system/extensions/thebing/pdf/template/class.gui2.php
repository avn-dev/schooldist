<?php


class Ext_Thebing_Pdf_Template_Gui2 extends Ext_Thebing_Gui2_Data
{

	use \Tc\Traits\Gui2\ImageTab;

	public function switchAjaxRequest($_VARS)
    {

		switch($_VARS['task']) {
			case 'showPreviewPdf':

				try {
					$iSchool = $_VARS['iSchool'];
					$sLang = $_VARS['sLang'];
					$iTemplate = $_VARS['iTemplate'];

					$oSchool = Ext_Thebing_School::getInstance($iSchool);

					if(empty($sLang)){
						$sLang = $oSchool->getLanguage();
					}

					$oPdfTemplate = Ext_Thebing_Pdf_Template::getInstance($iTemplate);

					$oPDF = new Ext_Thebing_Pdf_Basic($iTemplate, $iSchool);

					$oPDF->sDocumentType = '';

					$oPDF->setLanguage($sLang);

					// Vorbereiten der Daten für PDF
						$aData = array();
						$aData['txt_intro']	= $oPdfTemplate->getStaticElementValue($sLang, 'text1');
						$aData['txt_outro']	= $oPdfTemplate->getStaticElementValue($sLang, 'text2');
						$aData['txt_subject'] = $oPdfTemplate->getStaticElementValue($sLang, 'subject');
						$aData['txt_address'] = $oPdfTemplate->getStaticElementValue($sLang, 'address');
						$aData['date'] = $oPdfTemplate->getStaticElementValue($sLang, 'date');
						$aData['txt_signature']	= $oPdfTemplate->getOptionValue($sLang, $iSchool, 'signatur_text');
						$aData['signature']	= $oPdfTemplate->getOptionValue($sLang, $iSchool, 'signatur_img');
					/////////////////////////////
					$aTable = array();
					$aTable['type'] = 'invoice_document';
					$aTable['body'] = array( 1 );

					$oPDF->createDummyDocument($aData, $aTable, array(), array(), true);

					$oPDF->outputPDF('Preview', 'D');
				} catch (Exception $exc) {
					
					$oLog = Log::getLogger();
					$oLog->addError('showPreviewPdf failed', ['variables' => $_VARS, 'message' => $exc->getMessage(), 'trace' => $exc->getTraceAsString()]);

					echo '<span style="color:red">'.$this->_oGui->t('Es ist ein Fehler aufgetreten. Bitte überprüfen sie ihre PDF Hintergründe.').'</span>';

				}

				$this->_oGui->save();
				die();

				break;
			default:
				parent::switchAjaxRequest($_VARS);
				break;
		}
	}

    protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false)
    {

		$aLanguages = Ext_Thebing_Data::getSystemLanguages();

		$aSelectedIds = (array)$aSelectedIds;

		if(count($aSelectedIds) > 1)
		{
			return array();
		}
		else
		{
			$iSelectedId = (int) reset($aSelectedIds);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oTemplate		= Ext_Thebing_Pdf_Template::getInstance($iSelectedId);
		$oType			= new Ext_Thebing_Pdf_Template_Type($oTemplate->template_type_id);
		$oFirst			= reset($oDialogData->aElements);
		$aSchools		= $oTemplate->schools;
		$aElements		= array($oFirst);
		$aTypeElements	= $oType->getElements();

		$sHtmlType = 'html';
		if($oType->html_as_textarea) {
			$sHtmlType = 'textarea';
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create new tabs

		foreach((array)$oTemplate->languages as $sLanguage)
		{
			$sLanguageLabel = $aLanguages[$sLanguage];

//			$sLabel	= $this->_oGui->t('Sprache - %s');
//			$sLabel	= sprintf($sLabel, $sLanguageLabel);
			$sLabel = $sLanguageLabel;
			$oTab	= $oDialogData->createTab($sLabel);

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Standard fields

			if($oType->element_date == 1) {
				$oTab->setElement(
					$oDialogData->createRow(
						$this->_oGui->t('Datum'),
						'input',
						array(
							'db_column' => 'lang_tab_default_date-' . $sLanguage
						)
					)
				);
			}

			if($oType->element_address == 1) {
				$oTab->setElement(
					$oDialogData->createRow(
						$this->_oGui->t('Adresse'),
						'textarea',
						array(
							'db_column' => 'lang_tab_default_address-' . $sLanguage
						)
					)
				);
			}

			if($oType->element_subject == 1) {
				$oTab->setElement(
					$oDialogData->createRow(
						$this->_oGui->t('Betreff'),
						'input',
						array(
							'db_column' => 'lang_tab_default_subject-' . $sLanguage
						)
					)
				);
			}

			if($oType->element_text1 == 1) {
				$oTab->setElement(
					$oDialogData->createRow(
						$this->_oGui->t('Text oben'),
						$sHtmlType,
						array(
							'db_column'	=> 'lang_tab_default_text1-' . $sLanguage,
							'style' => 'height:380px;',
							'advanced' => true
						)
					)
				);
			}

			if($oType->element_text2 == 1) {
				$oTab->setElement(
					$oDialogData->createRow(
						$this->_oGui->t('Text unten'),
						$sHtmlType,
						array(
							'db_column' => 'lang_tab_default_text2-' . $sLanguage,
							'style' => 'height:380px;',
							'advanced' => true
						)
					)
				);
			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Type elements fields

			foreach($aTypeElements as $oElement) {

				if($oElement->element_type == 'text') {
					$oTab->setElement(
						$oDialogData->createRow(
							$oElement->name,
							'input',
							array(
								'db_column' => 'lang_tab_elements_' . $oElement->id . '-' . $sLanguage
							)
						)
					);
				} elseif($oElement->element_type == 'html') {
					$oTab->setElement(
						$oDialogData->createRow(
							$oElement->name,
							$sHtmlType,
							array(
								'db_column' => 'lang_tab_elements_' . $oElement->id . '-' . $sLanguage,
								'style' => 'height:190px;',
								'advanced' => true
							)
						)
					);
				}
				else if($oElement->element_type == 'img')
				{
					$oTab->setElement(
						$oDialogData->createRow(
							$oElement->name,
							'select',
							array(
								'db_column' => 'lang_tab_elements_' . $oElement->id . '-' . $sLanguage,
								'select_options' => array('default_customer_picture' => $this->_oGui->t('Schülerbild'))
							)
						)
					);
				}
				else if($oElement->element_type == 'date')
				{
					$oTab->setElement(
						$oDialogData->createRow(
							$oElement->name,
							'calendar',
							array(
								'db_column' => 'lang_tab_elements_' . $oElement->id . '-' . $sLanguage
							)
						)
					);
				} 
			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // School fields

			$aNewSchools = array(); /** @var Ext_Thebing_School[] $aNewSchools */

			foreach((array)$aSchools as $iKey => $iSchoolId){
				$oTempSchool = Ext_Thebing_School::getInstance($iSchoolId);

				// Nur aktive Schulen anzeigen
				if($oTempSchool->active != 1){
					continue;
				}

				$aNewSchools[strtolower($oTempSchool->ext_1)] = $oTempSchool;
			}

			// Sort schools
			ksort($aNewSchools);

			foreach($aNewSchools as $oTempSchool){
			
				// Hier muss jetzt nochmal geprüft werden  ob die Schule auch die Sprache hat
				$aSchoolLanguages = $oTempSchool->getLanguageList(true);
			
				if(!isset($aSchoolLanguages[$sLanguage])){
					continue;
				}
			
			
				$aFiles				= $oTempSchool->getSchoolFiles(1, $sLanguage, true);
				// Signaturen holen (sprachunabhängig)
				$aFilesSignatures	= $oTempSchool->getSchoolFiles(2, null, true);
				$aFilesAttachments	= $oTempSchool->getSchoolFiles(5, $sLanguage, true);

				$aFilesSignatures[0] = array('id' => 0, 'description' => '');
				ksort($aFilesSignatures);

				$aFilesDD = $aFilesSignaturesDD = $aFilesAttachmentsDD = array();

				foreach((array)$aFiles as $aFile)
				{
					$aFilesDD[$aFile['id']] = $aFile['description'];
				}
				foreach((array)$aFilesSignatures as $aFile)
				{
					$aFilesSignaturesDD[$aFile['id']] = $aFile['description'];
				}
				foreach((array)$aFilesAttachments as $aFile)
				{
					$aFilesAttachmentsDD[$aFile['id']] = $aFile['description'];
				}

				$sOnClick = 'aGUI[\''.$this->_oGui->hash.'\'].showPreviewPdf('.$oTemplate->id.', '.$oTempSchool->id.', \''.$sLanguage.'\'); return false;';

				$sPreview = '<div onclick="'.$sOnClick.'" class="btn btn-xs btn-default pull-right">
								<i class="fa '.Ext_Thebing_Util::getIcon('pdf').'" alt="'.$this->_oGui->t('Vorschau').'" title="'.$this->_oGui->t('Vorschau').'"></i> 
								 '.$this->_oGui->t('Vorschau').' <small>('.$this->_oGui->t('Bei Änderung der Einstellungen bitte zuerst speichern').')</small>
							</div>';
				
				$oTab->setElement($sPreview);
		
				$oH3 = $oDialogData->create('h4');
				$sTitle = $oTempSchool->short.str_repeat('&nbsp;', 10);
				$oH3->setElement($sTitle);
				$oTab->setElement($oH3);

				$sColumn = 'lang_tab_school_' . $oTempSchool->id . '-' . $sLanguage . '-filename';

				$oTab->setElement(
					$oDialogData->createNotification(
						$this->_oGui->t('Verfügbare Platzhalter'),
						'{firstname}, {surname}, {document_number}, {id}, {version}, {date}',
						'info'
					)
				);
				
				$oTab->setElement(
					$oDialogData->createRow(
						$this->_oGui->t('Dateiname (PDF)'),
						'input',
						array(
							'db_column' => $sColumn,
							'info_text_key' => 'lang_tab_school_filename'
						)
					)
				);

				$sColumn = 'lang_tab_school_' . $oTempSchool->id . '-' . $sLanguage . '-first_page_pdf_template';

				$oTab->setElement(
					$oDialogData->createRow(
						$this->_oGui->t('Erste Seite - PDF Hintergrund'),
						'select',
						array(
							'db_column'			=> $sColumn,
							'select_options'	=> $aFilesDD
						)
					)
				);

				$sColumn = 'lang_tab_school_' . $oTempSchool->id . '-' . $sLanguage . '-additional_page_pdf_template';

				$oTab->setElement(
					$oDialogData->createRow(
						$this->_oGui->t('Folgeseiten - PDF Hintergrund'),
						'select',
						array(
							'db_column'			=> $sColumn,
							'select_options'	=> $aFilesDD
						)
					)
				);

				if($oTemplate->user_signature != 1) {
					if($oType->element_signature_img == 1) {
						$sColumn = 'lang_tab_school_' . $oTempSchool->id . '-' . $sLanguage . '-signatur_img';

						$oTab->setElement(
							$oDialogData->createRow(
								$this->_oGui->t('Signatur Bild'),
								'select',
								array(
									'db_column'			=> $sColumn,
									'select_options'	=> $aFilesSignaturesDD,
									'style'				=> $sStyle
								)
							)
						);
					}
					if($oType->element_signature_text == 1) {
						$sColumn = 'lang_tab_school_' . $oTempSchool->id . '-' . $sLanguage . '-signatur_text';

						$oTab->setElement(
							$oDialogData->createRow(
								$this->_oGui->t('Signatur Text'),
								$sHtmlType,
								array(
									'db_column' => $sColumn,
									'style' => 'height:190px;',
									'advanced' => true
								)
							)
						);
					}
				}

				// Anhänge werden anscheinend nirgendwo beachtet, deswegen die Auswahl-Option entfernt (#5144)
/*
				$sColumn = 'lang_tab_school_' . $oTempSchool->id . '-' . $sLanguage . '-attachments';

				$oTab->setElement(
					$oDialogData->createRow(
						$this->_oGui->t('Anhänge'),
						'select',
						array(
							'db_column' => $sColumn,
							'multiple' => 5,
							'select_options' => $aFilesAttachmentsDD,
							'jquery_multiple' => 1,
							'searchable' => 1
						)
					)
				);
*/

			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			$oDialogData->setElement($oTab);

			$aElements[] = $oTab;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Set placeholder

		//wieso kann man bei einem getter nicht getten?
		if(!$this->oWDBasic instanceof WDBasic){
			$this->_getWDBasicObject($aSelectedIds);
		}
		
		
		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDialogData->aElements = $aElements;

		$aData = parent::getEditDialogHTML($oDialogData, $aSelectedIds);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Set placeholder

		$sType = $this->oWDBasic->type;

		$sSmartyHtml = $sHtml = null;
		if(!empty($sType)) {

			$sHtml = $this->oWDBasic->getPlaceholderTabContent();
			
			if($sHtml) {
				$iNewTabCount = count($aData['tabs']);
				$aData['tabs'][$iNewTabCount]['no_scrolling'] = 1;
				$aData['tabs'][$iNewTabCount]['no_padding'] = 1;
				$aData['tabs'][$iNewTabCount]['title'] = $this->t('Platzhalter');
				$aData['tabs'][$iNewTabCount]['html'] = $sHtml;
			}
			
			if(!$this->oWDBasic->use_smarty) {
				$sSmartyHtml = $this->oWDBasic->getSmartyPlaceholderTabContent();
				if($sSmartyHtml) {
					$iNewTabCount = count($aData['tabs']);
					$aData['tabs'][$iNewTabCount]['no_scrolling'] = 1;
					$aData['tabs'][$iNewTabCount]['no_padding'] = 1;
					$aData['tabs'][$iNewTabCount]['title'] = $this->t('Erweiterte Platzhalter');
					$aData['tabs'][$iNewTabCount]['html'] = $sSmartyHtml;
				}
			}			
			
		}
		
		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Bilder

		$iNewTabCount = count($aData['tabs']);
		$aData['tabs'][$iNewTabCount]['title'] = $this->t('Bilder');
		$aData['tabs'][$iNewTabCount]['html'] = $this->writeImageTabHTML();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
		return $aData;
	}

	public static function getPlaceholderTabContent($sType)
    {

		$aLinks = Ext_Thebing_Pdf_Template::getPlaceholderData($sType);

		if(!empty($aLinks)) {

			$oPlaceholder = new $aLinks['class']();

			$sPlaceholderHtml = $oPlaceholder->displayPlaceholderTable(1, $aLinks, $sType);
			
			$oPlaceholderHtml = new Ext_TC_Placeholder_Html();
			$sHtml = $oPlaceholderHtml->createPlaceholderContent($sPlaceholderHtml);

		}

		return $sHtml;
	}

	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true)
    {

		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		$this->_getWDBasicObject($aSelectedIds);

		$oLayout = Ext_Thebing_Pdf_Template_Type::getInstance($this->oWDBasic->template_type_id);

		$aData['positions_table'] = $oLayout->element_inquirypositions;

		// rechnungspositions anzeige
		$aData['show_positions_table_select'] = 0;

		if($aData['positions_table'] != 0) {

			// Rechnungspositionen nur für LOA und sonstige PDF zulässig
			if(
				self::checkPositionsTable($this->oWDBasic->type)
			) {
				$aData['show_positions_table_select'] = 1;
			} else {
				$aData['show_positions_table_select'] = 0;
			}

		}

		$bShow = Ext_Thebing_Pdf_Template::checkStudentAppReleaseWhitelist($this->oWDBasic->type);

		$aData['show_dialog_row_app_release'] = 0;
		if($bShow === true) {		
			$aData['show_dialog_row_app_release'] = 1;
		}
		
		return $aData;

	}

	public static function getPositionTableOptions()
    {

		$aOptions = array();

		$aOptions[1] = L10N::t('Abhängig von der Zahlungsart der Buchung', 'Thebing » Admin » Vorlagen');
		$aOptions[2] = L10N::t('Kundenansicht', 'Thebing » Admin » Vorlagen');
		$aOptions[3] = L10N::t('Agenturansicht', 'Thebing » Admin » Vorlagen');

		return $aOptions;

	}

	public static function checkPositionsTable($sType)
    {

		if(
			$sType == 'document_loa' ||
			$sType == 'document_studentrecord_additional_pdf'
		) {
			return true;
		} else {
			return false;
		}
	}

	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null)
    {

		if($sError === 'PDF_LAYOUT_DATE_ELEMENT_MISSING') {
			$sMessage = $this->t('Das Layout enthält kein Datumsfeld, allerdings wurde ein Typ für Rechnungen ausgewählt.');
		} else {
			$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}

		return $sMessage;

	}

	static public function getOrderby()
    {

		return ['kpt.name' => 'ASC'];
	}

	public static function getWhere()
    {

		$oClient = Ext_Thebing_Client::getInstance();

		return ['kpt.client_id' => (int)$oClient->id];
	}

	public static function getTypeSelectOptions(\Ext_Gui2 $oGui) {

		$aTypes	= Ext_Thebing_Pdf_Template::getApplications();
		$aTypesSearch = Ext_Gui2_Util::addLabelItem($aTypes, $oGui->t('Typen'));

		asort($aTypesSearch);

		return $aTypesSearch;
	}

	static public function getDialog(\Ext_Gui2 $oGui)
    {

		$oClient = Ext_Thebing_Client::getInstance();
		$oDialog = $oGui->createDialog(
			$oGui->t('PDF Vorlage "{name}" bearbeiten'),
			$oGui->t('Neue PDF Vorlage')
		);

		$oDialog->save_as_new_button		= true;
		$oDialog->save_bar_options			= true;
		$oDialog->save_bar_default_option	= 'open';

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Tab "Settings"

		$aLayouts = Ext_Thebing_Pdf_Template::getAvailableTemplateTypes();
		$aLayouts = Ext_Thebing_Util::addEmptyItem($aLayouts);
		$aTypes	= Ext_Thebing_Pdf_Template::getApplications();
		$aTypesSearch = Ext_Gui2_Util::addLabelItem($aTypes, $oGui->t('Typen'));
		$aTypes = Ext_Thebing_Util::addEmptyItem($aTypes);

		asort($aTypesSearch);
		asort($aTypes);

		$oSchool = Ext_Thebing_School::getInstance();
		$aSchoolOptions = $oSchool->getArrayList(true, 'short');

		$aSchools	= Ext_Thebing_Client::getSchoolList(true);
		$aLanguages	= Ext_Thebing_Client::getLangList(true);

		$oClient = Ext_Thebing_Client::getInstance($oClient->id);

		$aClientSchools = $oClient->getSchools(true);
		$aClientSchools = Ext_Gui2_Util::addLabelItem($aSchoolOptions, $oGui->t('Schulen'));

		$aInboxes = Ext_Thebing_System::getInboxList('use_id', true);

		$aPositionTableOptions = Ext_Thebing_Pdf_Template_Gui2::getPositionTableOptions();

		$oTab = $oDialog->createTab($oGui->t('Einstellungen'));
		$oTab->aOptions = ['section' => 'pdf_templates'];

		$sText = $oGui->t('Bitte speichern Sie zuerst die Einstellungen, um die sprachabhängige Texte zu hinterlegen.');
		$oTab->setElement($sText);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Name'),
				'input',
				array(
					'db_column'			=> 'name',
					'db_alias'			=> 'kpt',
					'required'			=> 1
				)
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Layout'),
				'select',
				array(
					'db_column'			=> 'template_type_id',
					'db_alias'			=> 'kpt',
					'select_options'	=> $aLayouts,
					'required'			=> 1,
					'events'			=> array(
						array(
							'event' 		=> 'change',
							'function' 		=> 'reloadDialogTab',
							'parameter'		=> 'aDialogData.id, -1'
						)
					)
				)
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Typ'),
				'select',
				array(
					'db_column'			=> 'type',
					'db_alias'			=> 'kpt',
					'select_options'	=> $aTypes,
					'required'			=> 1,
					'class'				=> 'document_type',
					'events'			=> array(
						array(
							'event' 		=> 'change',
							'function' 		=> 'reloadDialogTab',
							'parameter'		=> 'aDialogData.id, -1'
						),
						array(
							'event' 		=> 'change',
							'function' 		=> 'switchInboxSelect'
						)
					)
				)
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Schulen'),
				'select',
				array(
					'db_column'			=> 'schools',
					'multiple'			=> 5,
					'select_options'	=> $aSchools,
					'jquery_multiple'	=> 1,
					'searchable'		=> 1,
					'required'			=> 1
				)
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Inboxen'),
				'select',
				array(
					'db_column'			=> 'inboxes',
					'db_alias'			=> 'kpt',
					'multiple'			=> 5,
					'select_options'	=> $aInboxes,
					'jquery_multiple'	=> 1,
					'searchable'		=> 1,
					'required'			=> 1
				)
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Sprachen'),
				'select',
				array(
					'db_column'			=> 'languages',
					'multiple'			=> 5,
					'select_options'	=> $aLanguages,
					'jquery_multiple'	=> 1,
					'searchable'		=> 1,
					'required'			=> 1
				)
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Benutzerspezifische Signatur'),
				'checkbox',
				array(
					'db_column'	=> 'user_signature',
					'db_alias'	=> 'kpt'
				)
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Rechnungspositionen'),
				'select',
				array(
					'db_column'			=> 'inquirypositions_view',
					'db_alias'			=> 'kpt',
					'select_options'	=> $aPositionTableOptions,
					'row_id'			=> 'positions_table_settings'
				)
			)
		);

		if(Ext_Thebing_Access::hasRight('thebing_release_documents_sl')){
			$oTab->setElement(
				$oDialog->createRow(
					$oGui->t('Standardeinstellung für Schüler-App-Freigabe'),
					'checkbox',
					array(
						'db_column'	=> 'app_release',
						'db_alias'	=> 'kpt',
						'row_id'	=> 'dialog_row_app_release'
					)
				)
			);
		}

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Smarty-Template-Engine verwenden'),
				'checkbox',
				array(
					'db_column'	=> 'use_smarty',
					'db_alias'	=> 'kpt',
					'dependency_visibility' => array(
						'db_column' => 'type',
						'db_alias' => 'kpt',
						'on_values' => [
							'document_studentrecord_additional_pdf',
							'document_attendance',
							'document_loa'
						]
					),
					'events'=>array(
						array(
							'event' 		=> 'click',
							'function' 		=> 'reloadDialogTab',
							'parameter'		=> 'aDialogData.id, -1'
						)
					)
				)
			)
		);

		$oDialog->setElement($oTab);

		return $oDialog;
	}

	static public function getSchoolSelectOptions(\Ext_Gui2 $oGui)
    {

		$oSchool = Ext_Thebing_School::getInstance();
		$aSchoolOptions = $oSchool->getArrayList(true, 'short');

		$aClientSchools = Ext_Gui2_Util::addLabelItem($aSchoolOptions, $oGui->t('Schulen'));

		return $aClientSchools;
	}

	static public function getFormatParamsSchools()
    {

		$oSchool = Ext_Thebing_School::getInstance();
		$aSchoolOptions = $oSchool->getArrayList(true, 'short');

        return $aSchoolOptions;
	}

}
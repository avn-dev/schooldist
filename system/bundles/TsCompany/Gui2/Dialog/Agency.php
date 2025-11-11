<?php

namespace TsCompany\Gui2\Dialog;

use TsCompany\Traits\Gui2\Dialog\CompanyFields;
use TsHubspot\Service\Api;
use TsHubspot\Service\Helper\General;

class Agency extends AbstractDialog {
	use CompanyFields;

	public function getTitle(): string {
		return $this->t('Neuen Agenten anlegen');
	}

	public function getEditTitle(): string {
		return $this->t('Agent "{ext_1}" editieren');
	}

	public function build(): void {

		$this->tab($this->t('Daten'), function($tab) {
			$tab->aOptions = ['section' => 'agencies_details'];

			$this->withActiveRow();

			/*
			 * Sofern das Recht 'thebing_marketing_agencies_edit_agency_number' gesetzt ist, wird der
			 * Installation erlaubt die Agenturnummer zu ändern. Dabei wird allerdings die Id des Nummernkreises
			 * auf 0 gesetzt. Die Agenturnummern können dadurch mehrfach vorkommen.
			 */
			if(\Ext_Thebing_Access::hasRight('thebing_marketing_agencies_edit_agency_number')) {
				$this->withNumberRow();
			}

			$this->withNameBlock();

			if (\TsHubspot\Handler\ExternalApp::isActive()) {

				$hubspotHelper = new General();
				$api = new Api();

				$hubspotCompanies[$hubspotHelper::SELECT_CREATEHUBSPOTCOMPANY_ID] = ' -- '.\L10N::t('Neues Unternehmen in Hubspot anlegen').' -- ';
				$hubspotCompanies += $hubspotHelper->getAllHubspotAgencies($api->oHubspot);
				$hubspotCompanies = \Ext_Thebing_Util::addEmptyItem($hubspotCompanies);

				$tab->setElement($this->dialog->createRow($this->t('Hubspot Unternehmen'), 'select', [
					'db_alias'			=> 'ka',
					'db_column'			=> 'hubspot_id',
					'required'			=> 1,
					'select_options'	=> $hubspotCompanies,
				]));
			}

			$agencyCategories = \Ext_Thebing_Agency::getCategoryList();
			$agencyCategories = \Ext_Thebing_Util::addEmptyItem($agencyCategories);

			$tab->setElement($this->dialog->createRow($this->t('Kategorie'), 'select', array(
				'db_alias'			=> 'ka',
				'db_column'			=> 'ext_39',
				'required'			=> 0,
				'select_options'	=> $agencyCategories,
			)));

			$groups = \Ext_Thebing_Agency::getGroupList(true);
			if(in_array(' --- ', $groups)) {
				unset($groups[0]);
			}

			$tab->setElement($this->dialog->createRow($this->t('Gruppen'), 'select', array(
				'db_alias' => '',
				'db_column' => 'groups',
				'required' => 0,
				'select_options' => $groups,
				'multiple' => 5,
				'jquery_multiple' => 1,
				'searchable' => 1
			)));

			$tab->setElement($this->dialog->createRow($this->t('Verfügbarkeit bei Schulen beschränken'), 'checkbox', array(
				'db_alias' => '',
				'db_column' => 'schools_limited',
				'required' => 0,
			)));
			
			$tab->setElement($this->dialog->createRow($this->t('Schulen'), 'select', array(
				'db_alias' => '',
				'db_column' => 'schools',
				'required' => 0,
				'select_options' => \Ext_Thebing_Client::getSchoolList(true),
				'multiple' => 5,
				'jquery_multiple' => 1,
				'searchable' => 1,
				'dependency_visibility' => [
					'db_column' => 'schools_limited',
					'on_values' => ['1']
				],
			)));

			$this
				->withWebRow()
				->withAddressBlock()
			;

			$this->heading($this->t('Unteragentur'));
			
			$tab->setElement($this->dialog->createRow($this->t('Unteragentur'), 'select', [
				'db_alias' => 'ka',
				'db_column' => 'subagency_id',
				'selection' => new \TsCompany\Gui2\Selection\SubAgencies(),
				'child_visibility' => [
					[
						'class' => 'subagency-commission-row',
						'on_values' => [
							'!0'
						]
					]
			]
			]));
				
			$tab->setElement($this->dialog->createMultiRow(
				$this->t('Provision'),
				[
					'db_alias' => 'ka',
					'row_class' => 'subagency-commission-row',
					'items' => [
						[
							'input' => 'input',
							'db_column' => 'subagency_commission',
							'style' => 'width: 100px;',
							'required' => true,
							'text_after' => '% '
						],
//						[
//							'text_before' => $this->t('basierend auf'),
//							'input' => 'select',
//							'db_column' => 'subagency_commission_basedon',
//							'select_options' => [
//								0 => '',
//								'gross' => $this->t('Bruttobetrag'),
//								'net' => $this->t('Nettobetrag')								
//							],
//							'required' => true
//						]
					]
				]
			));			
			
			$this->heading($this->t('Sonstiges'));

			$tab->setElement($this->dialog->createRow($this->t('Tracking-Code'), 'input', [
				'db_alias' => 'ka',
				'db_column' => 'tracking_key',
				'format' => new \Ext_Gui2_View_Format_Null()
			]));

			$this
				->withCommentRow()
				->withCorrespondenceLanguageRow()
			;

		});

		$this->tab($this->t('Info'), function($tab) {
			$tab->aOptions = ['section' => 'agencies_info'];

			$this->withInfoBlock();

			$tab->setElement($this->dialog->createRow($this->t('Partnerschulen im Kursort'), 'textarea', [
				'db_alias' => 'ka',
				'db_column' => 'partner_schools',
				'required' => 0,
			]));

			$tab->setElement($this->dialog->createRow($this->t('Anzahl der Kunden'), 'input', [
				'db_alias' => 'ka',
				'db_column' => 'customers',
				'required' => 0,
				'max_length' => 5,
			]));
			
			$this->heading($this->t('Buchhaltung'));

			$currencies = \Ext_Thebing_Currency_Util::getAllSchoolsCurrencyList(2);
			$currencies = \Ext_Thebing_Util::addEmptyItem($currencies);

			$tab->setElement($this->dialog->createRow($this->t('Währung'), 'select', [
				'db_alias' => 'ka',
				'db_column' => 'ext_23',
				'required' => 1,
				'select_options' => $currencies,
			]));

			$tab->setElement($this->dialog->createRow($this->t('Zahlungsmethode'), 'select', [
				'db_alias' => 'ka',
				'db_column' => 'ext_26',
				'required' => 0,
				'select_options' => \Ext_Thebing_Agency::getAgencyPaymentMethods(),
			]));

			$tab->setElement($this->dialog->createRow($this->t('Kommentar Zahlungsart'), 'textarea', [
				'db_alias' => 'ka',
				'db_column' => 'ext_38',
				'required' => 0,
			]));

			$tab->setElement($this->dialog->createRow($this->t('Steuernummer'), 'input', [
				'db_alias' => 'ka',
				'db_column' => 'ext_24',
				'required' => 0,
			]));

			$tab->setElement($this->dialog->createRow($this->t('USt.-ID'), 'input', [
				'db_alias' => 'ka',
				'db_column' => 'vat_number',
				'required' => 0,
			]));

			$tab->setElement($this->dialog->createRow($this->t('Recipient Code'), 'input', [
				'db_alias' => 'ka',
				'db_column' => 'recipient_code',
				'required' => 0,
			]));

			/*** Benötigte Dokumente ***/

			$this->heading($this->t('Benötigte Dokumente'));

			$invoiceTypes = array(
				1 => $this->t('Brutto'),
				2 => $this->t('Netto'),
			);

			$tab->setElement($this->dialog->createRow($this->t('Rechnung'), 'select', [
				'db_alias' => 'ka',
				'db_column' => 'invoice',
				'required' => 0,
				'select_options' => $invoiceTypes,
			]));

			$tab->setElement($this->dialog->createRow($this->t('LoA'), 'select', [
				'db_alias' => 'ka',
				'db_column' => 'ext_29',
				'required' => 0,
				'select_options' => \Ext_Thebing_Util::getYesNoArray(true),
			]));

			$tab->setElement($this->dialog->createRow($this->t('Kommentar'), 'textarea', [
				'db_alias' => 'ka',
				'db_column' => 'ext_32',
				'required' => 0,
			]));
			
		});

		$this->tab($this->t('Bank'), function($tab) {
			$tab->aOptions = ['section' => 'agencies_bank'];
			$this->withBankBlock(false);
		});

		$this->tab($this->t('Provision'), function($tab) {
			$tab->aOptions = ['section' => 'agencies_provision'];
			$this->withProvisionGui();
		});

		$this->tab($this->t('Bezahlgruppe'), function($tab) {
			$tab->aOptions = ['section' => 'agencies_payment_groups'];
			$this->withPaymentGroupGui();
		});

		$this->tab($this->t('Vorortkosten'), function($tab) {
			$this->withCostGui();
		});

		$this->tab($this->t('Storno'), function($tab) {
			$this->withValidityGui('agency', 'cancellation_group', 'Stornogruppe');
		});

	}

	/**
	 * TODO: Auf config-file umstellen
	 *
	 * @return $this
	 * @throws \Exception
	 */
	private function withProvisionGui() {
		global $user_data;
		
		$oInnerGui = $this->gui2->createChildGui(md5('thebing_marketing_agencies_provisions'), 'Ext_Thebing_Agency_Provision_Gui2_Data');
		$oInnerGui->query_id_column		= 'id';
		$oInnerGui->query_id_alias		= '';
		$oInnerGui->foreign_key			= 'agency_id';
		$oInnerGui->foreign_key_alias	= '';
		$oInnerGui->parent_primary_key	= 'id';
		$oInnerGui->load_admin_header	= false;
		$oInnerGui->multiple_selection  = false;
		$oInnerGui->row_style			= new \Ext_Thebing_Gui2_Style_Teacher_Salary_Row();
		$oInnerGui->calendar_format		= new \Ext_Thebing_Gui2_Format_Date();
		$oInnerGui->row_icon_status_active = new \Ext_Thebing_Gui2_Icon_Teacher_Salary();

		$oInnerGui->setWDBasic('Ext_Thebing_Agency_Provision_Group');
		$oInnerGui->setTableData('limit', 30);
		$oInnerGui->setTableData('orderby', array('valid_from'=>'DESC'));

		// Neu Anlegen
		$oInnerDialog1 = $oInnerGui->createDialog($this->t('Neue Provisionskategorie anlegen'), $this->t('Neue Provisionskategorie anlegen'));
		$oInnerDialog1->sDialogIDTag = 'AGENCY_PROVISION_';

		$oInnerDialog1->setElement($oInnerDialog1->createRow($this->t('Bezeichung'), 'input', array('db_alias'=>'kapg', 'db_column'=>'description', 'required'=>true)));
		$oInnerDialog1->setElement($oInnerDialog1->createRow($this->t('Provisionsgruppe'), 'select', array('db_alias'=>'kapg', 'db_column'=>'group_id', 'selection'=> new \TsCompany\Gui2\Selection\CommissionCategory(), 'required'=>true)));
		$oInnerDialog1->setElement($oInnerDialog1->createRow($this->t('Schule'), 'select', array(
			'db_alias'=>'kapg', 
			'db_column'=>'school_id', 
			'selection'=> new \TsCompany\Gui2\Selection\CommissionCategorySchool(),
			'dependency' => [
                [
                    'db_alias' => 'kapg',
                    'db_column' => 'group_id',
                ],
            ])));
		$oInnerDialog1->setElement($oInnerDialog1->createRow($this->t('Gültig ab'), 'calendar', array('db_alias'=>'kapg', 'db_column'=>'valid_from', 'format'=>new \Ext_Thebing_Gui2_Format_Date(), 'required'=>true)));
		$oInnerDialog1->setElement($oInnerDialog1->createRow($this->t('Kommentar'), 'textarea', array('db_alias'=>'kapg', 'db_column'=>'comment')));

		$oInnerDialog1->width = 850;
		$oInnerDialog1->height = 300;

		// Editieren OHNE Datum
		$oInnerDialog2 = $oInnerGui->createDialog($this->t('Provisionskategorie "{description}" bearbeiten'), $this->t('Provisionskategorie "{description}" bearbeiten'));
		$oInnerDialog2->sDialogIDTag = 'AGENCY_PROVISION_';

		$oInnerDialog2->setElement($oInnerDialog2->createRow($this->t('Bezeichung'), 'input', array('db_alias'=>'kapg', 'db_column'=>'description', 'required'=>true)));
		$oInnerDialog2->setElement($oInnerDialog2->createRow($this->t('Provisionsgruppe'), 'select', array('db_alias'=>'kapg', 'db_column'=>'group_id', 'selection'=> new \TsCompany\Gui2\Selection\CommissionCategory(), 'required'=>true)));
		$oInnerDialog2->setElement($oInnerDialog2->createRow($this->t('Schule'), 'select', array('db_alias'=>'kapg', 'db_column'=>'school_id', 'selection'=> new \TsCompany\Gui2\Selection\CommissionCategorySchool(),
			'dependency' => [
                [
                    'db_alias' => 'kapg',
                    'db_column' => 'group_id',
                ],
            ])));
		$oInnerDialog2->setElement($oInnerDialog2->createRow($this->t('Kommentar'), 'textarea', array('db_alias'=>'kapg', 'db_column'=>'comment')));

		$oInnerDialog2->width = 850;
		$oInnerDialog2->height = 300;

		# START - Leiste 2 #
		$oBar = $oInnerGui->createBar();
		$oBar->width = '100%';

		/*$oLabelGroup = $oBar->createLabelGroup($this->t('Aktionen'));
		$oBar ->setElement($oLabelGroup);*/

		if(\Ext_Thebing_Access::hasRight("thebing_marketing_agencies_edit")) {

			$oIcon = $oBar->createNewIcon(
				$this->t('Neuer Eintrag'),
				$oInnerDialog1,
				$this->t('Neuer Eintrag')
			);
			$oBar ->setElement($oIcon);

			$oIcon = $oBar->createEditIcon(
				$this->t('Editieren'),
				$oInnerDialog2,
				$this->t('Editieren')
			);
			$oBar ->setElement($oIcon);

			$oIcon = $oBar->createDeleteIcon(
				$this->t('Löschen'),
				$this->t('Löschen')
			);
			$oBar ->setElement($oIcon);

		} else {
			$oInnerDialog2->bReadOnly = true;

			$oIcon = $oBar->createIcon(\Ext_TC_Util::getIcon('info'), 'openDialog', $this->t('Anzeigen'));
			$oIcon->action = 'edit';
			$oIcon->label = $this->t('Anzeigen');
			$oIcon->dialog_data = $oInnerDialog2;
			$oBar ->setElement($oIcon);
		}

		$oInnerGui->setBar($oBar);
		# ENDE - Leiste 2 #

		# START - Leiste 3 #
		$oBar = $oInnerGui->createBar();
		$oBar->width = '100%';
		$oBar->position = 'top';

		$oPagination = $oBar->createPagination();
		$oBar ->setElement($oPagination);

		$oLoading = $oBar->createLoadingIndicator();
		$oBar->setElement($oLoading);

		$oInnerGui->setBar($oBar);
		# ENDE - Leiste 2 #

		$oBarLegend = $oInnerGui->createBar();
		$oBarLegend->position = 'bottom';
		$sGreen = \Ext_Thebing_Util::getColor('marked');
		$sHtmlLegend = '<div style="float: left"><b>' . $this->t('Status') . ': </b>&nbsp;</div>';
		$sHtmlLegend .= '<div style="float: left">' . $this->t('Aktiv') . '</div> <div class="colorkey" style="background-color: '.$sGreen.'" ></div>';
		$oHtml = $oBarLegend->createHtml($sHtmlLegend);
		$oBarLegend ->setElement($oHtml);
		$oInnerGui->setBar($oBarLegend);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'group_id';
		$oColumn->db_alias = '';
		$oColumn->title = $this->t('Provisionsgruppe');
		$oColumn->width = \Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize = true;
		$oColumn->format = new \Ext_Thebing_Gui2_Format_Agency_Provisiongroup();
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'description';
		$oColumn->db_alias = 'kapg';
		$oColumn->title = $this->t('Bezeichnung');
		$oColumn->width = \Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize = true;
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'valid_from';
		$oColumn->db_alias = 'kapg';
		$oColumn->title = $this->t('Gültig ab');
		$oColumn->width = \Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->width_resize = false;
		$oColumn->format		= new \Ext_Thebing_Gui2_Format_Date();
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column		= 'valid_until';
		$oColumn->db_alias		= 'kapg';
		$oColumn->title			= $this->t('Gültig bis');
		$oColumn->width			= \Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->width_resize	= false;
		$oColumn->format		= new \Ext_Thebing_Gui2_Format_Date();
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column		= 'comment';
		$oColumn->db_alias		= 'kapg';
		$oColumn->title			= $this->t('Kommentar');
		$oColumn->width			= 200;
		$oColumn->width_resize	= true;
		$oInnerGui->setColumn($oColumn);

		$oDefaultColumn = $oInnerGui->getDefaultColumn();
		$oDefaultColumn->setAliasForAll('kapg');
		$oInnerGui->setDefaultColumn($oDefaultColumn);

		$oInnerGui->addDefaultColumns();

		$this->currentElement->setElement($oInnerGui);

		return $this;
	}

	private function withPaymentGroupGui() {

		$aSchools = \Ext_Thebing_Client::getSchoolList(true);
		$aSchoolsValidity = \Illuminate\Support\Arr::except($aSchools, 0);
		$aSchoolsValidity = \Ext_Gui2_Util::addLabelItem($aSchoolsValidity, $this->t('Alle Schulen'));

		$oValidityGui = new \Ext_TC_Validity_Gui2('ts_agencies_payment_conditions_validity');
		$oValidityGui->setWDBasic('Ext_TS_Agency_PaymentConditionValidity');
		$paymentConditions = \Ext_TC_Util::addEmptyItem(\Ext_TS_Payment_Condition::getSelectOptions());
		$oValidityGui->setValiditySelectSettings($paymentConditions);
		$oValidityGui->setValidityDependency($this->t('Schule'), $aSchoolsValidity);
		$oValidityGui->parent_hash = $this->gui2->hash;
		$oValidityGui->foreign_key = 'agency_id';
		$oValidityGui->parent_primary_key = 'id';
		$oValidityGui->setOption('validity_show_comment_field', true);
		$oValidityGui->gui_description = $this->gui2->gui_description;

		$this->currentElement->setElement($oValidityGui);

		return $this;
	}

	private function withCostGui() {

		$aCostList = \Ext_Thebing_Price::getCostListByType();

		foreach( $aCostList as $sSchoolTitle => $aCostTypes) {

			$this->heading($sSchoolTitle);

			foreach($aCostTypes as $sH3 => $aData) {

				foreach($aData as $iId => $sTitle) {

					if(!$sTitle) {
						continue;
					}

					$sTitle = $sH3.': '.$sTitle;

					$oInput			= new \Ext_Gui2_Html_Input();
					$oInput->type	= 'checkbox';
					$oInput->value	= '1';
					$oInput->id		= 'costs_'.$iId.'';
					$oInput->name	= 'save[costs]['.$iId.']';

					$this->currentElement->setElement($this->dialog->createRow($sTitle, $oInput));
				}

			}

		}

		return $this;
	}

}

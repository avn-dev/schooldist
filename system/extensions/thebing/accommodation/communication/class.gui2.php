<?php

class Ext_Thebing_Accommodation_Communication_Gui2 extends Ext_Thebing_Inquiry_Gui2
{
	use \Communication\Traits\Gui2\WithCommunication;

    public function executeGuiCreatedHook()
    {
        $this->_oGui->name = 'ts_accommodation_communcation';
        $this->_oGui->set = '';

        $oColumnGroupCustomer = $this->_oGui->createColumnGroup();
        $oColumnGroupCustomer->title = $this->_oGui->t('Kundendaten');

        $oInquiryAdditionalDocuments = new Ext_Thebing_Inquiry_Document_Additional();
        $oInquiryAdditionalDocuments->add_label_group = false;
        $oRowIconActive = new Ext_Thebing_Gui2_Icon_Accommodation_Communication_Icon();
        $oInquiryAdditionalDocuments->icon_status_active = $oRowIconActive;
        $oInquiryAdditionalDocuments->icons_bar_position = 2;
        $oInquiryAdditionalDocuments->add_label_group = true;
        $oInquiryAdditionalDocuments->use_template_type = 'document_accommodation_communication';
        $oInquiryAdditionalDocuments->access_document_edit = 'thebing_accommodation_communicate_documents';
        $oInquiryAdditionalDocuments->access_document_open = 'thebing_accommodation_communicate_display_pdf';
        $oInquiryAdditionalDocuments->column_group_corresponding_language = $oColumnGroupCustomer;

        $this->_oGui->addAdditionalDocumentsOptions($oInquiryAdditionalDocuments);

        $this->_oGui->addJs('gui2/util.js');
        $this->_oGui->addJs('gui2/payment.js');
        $this->_oGui->addJs('gui2/studentlists.js');

    }

    public static function getWhere()
    {
        return [
			'ts_i_j.schoool_id' => \Core\Handler\SessionHandler::getInstance()->get('sid'),
			'ki.active' => 1,
			'kia.active' => 1
		];
    }

    public static function getBasedOnFilter(\Ext_Gui2 $oGui)
    {
        return [
            'accommodation_period' => $oGui->t('Unterkunftszeitraum'),
            'accommodation_start' => $oGui->t('Unterkunftsstart')
        ];
    }

    public static function getOrderby()
    {
        return ['kia.from' => 'ASC'];
    }

    public static function getDefaultFilterFrom()
    {
        return \Ext_Thebing_Format::LocalDate(\Carbon\Carbon::today()->subWeek());
    }

    public static function getDefaultFilterUntil()
    {
		return \Ext_Thebing_Format::LocalDate(\Carbon\Carbon::today()->addWeeks(3));
    }

    public static function getInboxes()
    {
        $oSchool = \Ext_Thebing_Client::getFirstSchool();
        if(is_object($oSchool)) {
            $oClient = $oSchool->getClient();
        } else {
            $oSchool = \Ext_Thebing_School::getSchoolFromSession();
            $oClient = $oSchool->getClient();
        }

        $aInboxes = $oClient->getInboxList(true, true);

        return $aInboxes;
    }

    public static function getDocumentTypeOptions(\Ext_Gui2 $oGui)
    {
        $oFilter = [
            'invoice'	=>	$oGui->t('nur Rechnungen'),
            'proforma'	=>	$oGui->t('nur Proforma')
        ];

        return $oFilter;
    }

    public static function getAgencyOptions()
    {
		$oClient = \Ext_Thebing_Client::getFirstClient();

        $aAgencyOptions = $oClient->getAgencies(true);

		return $aAgencyOptions;
    }

    public static function getTransferFilterQueries()
    {
        $aOptions = array();
        $aOptions['1'] = " `arrival_transferdata_exist` = 1";
        $aOptions['2'] = " `arrival_transferdata_exist` = 0";

        return $aOptions;
    }

    public static function getProviderFilterQueries()
    {
        $aOptions = array();
        $aOptions['1'] = " `accommodation_accommodation_confirmed` > 0 AND (`inactive_accommodation_allocations` IS NULL OR `inactive_accommodation_confirmed` IS NULL) ";
        $aOptions['2'] = " `accommodation_accommodation_confirmed` = 0";
        return $aOptions;
    }

    public static function getAccommodationFilterQueries( )
    {
        $aOptions = array();
		$aOptions['1'] = " `accommodation_customer_agency_confirmed` > 0 AND (`inactive_accommodation_allocations` IS NULL OR `inactive_customer_agency_confirmed` IS NULL) ";
		$aOptions['2'] = " `accommodation_customer_agency_confirmed` = 0";
        return $aOptions;
    }

    public static function getAllocationFilterQueries()
    {
        $aOptions = array();
		$aOptions['1'] = " `kaal`.`active` = 1 AND `kaal`.`status` = 0 AND `kaal`.`room_id` > 0";
		$aOptions['2'] = " (`kaal`.`id` IS NULL OR `kaal`.`active` != 1 OR `kaal`.`status` != 0  OR `kaal`.`room_id` = 0) ";
        return $aOptions;
    }

    protected function getDialogHTML(&$sIconAction, &$oDialogData, $aSelectedIds = array(), $sAdditional=false) {
		global $_VARS;

		$aSelectedIds	= (array)$aSelectedIds;
		$iSelectedId	= (int)reset($aSelectedIds);

		// get dialog object
		switch($sIconAction) {
			case 'history':

				$oDialog = $this->getHistoryDialog($iSelectedId);

				$aData = $oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash);

				break;
			default:

				// Bei Unterkunftskommunikationsliste die SelectedIds decoden
				if($sAdditional == 'accommodation_communication') {
					$aSelectedIds = $this->_oGui->decodeId($aSelectedIds, 'accommodation_inquiry_id');
				}

				$aData = parent::getDialogHTML($sIconAction, $oDialogData, $aSelectedIds, $sAdditional);

				if($sIconAction == 'request_availability') {
					$this->updateRequestAvailabiltyDialogContent($aData, $iSelectedId);
				}

				break;
		}

		return $aData;
	}

	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true) {
		
		if($sAction == 'request_availability') {
			
			if($bSave) {
				
				try {
					$success = $this->sendRequests(reset($aSelectedIds));
				} catch(\Throwable $e) {
					$success = false;
				}
			}
			
			$dialogData = $this->prepareOpenDialog($sAction, $aSelectedIds, false, $sAdditional, false);

			$transfer = [
				'data' => $dialogData,
				'action' => 'saveDialogCallback',
				'error' => []
			];

			if($bSave) {
				if($success) {
					$transfer['success_message'] = $this->t('Die Anfragen wurden erfolgreich versendet!');
				} else {
					$transfer['alert_messages'] = [
						$this->t('Es konnten nicht alle Nachrichten versendet werden. Bitte kontrollieren Sie die E-Mail-Vorlage und die Kommunikationseinstellungen!')
					];
				}
			}
	
			return $transfer;
						
		} else {		
			return parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
		}
		
	}

	public function sendRequests($selectedId) {
			
		$request = $this->_oGui->getRequest();
		
		$inquiryAccommodationId	= (int)$this->_oGui->decodeId($selectedId, 'id');

		$inquiryAccommodation = Ext_TS_Inquiry_Journey_Accommodation::getInstance($inquiryAccommodationId);
		$inquiry = $inquiryAccommodation->getInquiry();
		$school = $inquiry->getSchool();
		
		$providers = $request->input('providers');
		
		$templateId = $request->input('template_id');
		
		$template = Ext_Thebing_Email_Template::getInstance($templateId);
		
		$accommodationRequest = TsAccommodation\Entity\Request::getInstance();
		$accommodationRequest->inquiry_accommodation_id = $inquiryAccommodation->id;
		
		foreach($providers as $providerId) {
			
			$accommodationProvider = Ext_Thebing_Accommodation::getInstance($providerId);
			
			$recipient = $accommodationRequest->getJoinedObjectChild('recipients');
			$recipient->accommodation_provider_id = $providerId;
			
			do {
				
				$recipient->key = \Util::generateRandomString(32);
							
				$result = $recipient->validate();
					
			} while (
				$result !== true &&
				in_array('NOT_UNIQUE', $result['ts_arr.key'])
			);
			
			$recipient->save();
			
			$placeholder = $recipient->getPlaceholderObject();

			// E-Mail zusammenstellen und senden.
			$oEmail = new \Ts\Service\Email($template, $accommodationProvider->getLanguage());

			$oEmail->setSchool($school);
			$oEmail->setEntity($recipient);
			$oEmail->setPlaceholder($placeholder);

			$aTo = [$accommodationProvider->email];

			$success = $oEmail->send($aTo);

			if($success) {
				$recipient->sent = time();
			} else {
				throw new \RuntimeException('Not all messages could be sent.');
			}
			
		}
		
		$accommodationRequest->save();
		
		return true;
	}
	
	public function updateRequestAvailabiltyDialogContent(array &$data, int $selectedId): void {
	
		$request = $this->_oGui->getRequest();
		
		$inquiryAccommodationId	= (int)$this->_oGui->decodeId($selectedId, 'id');

		$inquiryAccommodation = Ext_TS_Inquiry_Journey_Accommodation::getInstance($inquiryAccommodationId);

		$inquiry = $inquiryAccommodation->getInquiry();
		$school = $inquiry->getSchool();
		$student = $inquiry->getCustomer();

		$providers = $inquiryAccommodation->getPossibleProviders((bool)$request->get('ignore_category'), (bool)$request->get('ignore_roomtype'));
		
		$providers = array_filter($providers, function($provider) {
			foreach($provider['rooms'] as $room) {
				if($room['isAssignable']) {
					return true;
				}
			}
			return false;
		});
		
		$smarty = new \Core\Service\Templating();

		$templateOptions = \Ext_TC_Communication_Template::getSelectOptions('mail', [
			'application' => 'accommodation_communication_provider_requests'
		])->forget(0)->toArray();

		$providerStatus = $inquiryAccommodation->getProviderRequestStatus();
		
		$smarty->assign('ignore_category', (bool)$request->get('ignore_category'));
		$smarty->assign('ignore_roomtype', (bool)$request->get('ignore_roomtype'));
		$smarty->assign('ignore_requirement', (bool)$request->get('ignore_requirement'));
		$smarty->assign('provider_search', (string)$request->get('provider_search'));

		$smarty->assign('l10n_path', $this->getGui()->gui_description);
		$smarty->assign('providers', $providers);
		$smarty->assign('providerStatus', $providerStatus);
		$smarty->assign('inquiryAccommodation', $inquiryAccommodation);
		$smarty->assign('matching', $inquiry->getMatchingData());
		$smarty->assign('templateOptions', $templateOptions);
		$smarty->assign('timzone', $school->timezone);
		$smarty->assign('language', \System::getInterfaceLanguage());
		
		$bundleHelper = new \Core\Helper\Bundle();
		$bundleDir = $bundleHelper->getBundleDirectory('TsAccommodation');
		
		$data['html'] = $smarty->fetch($bundleDir.'/Resources/views/communication/request_availability.tpl');
		$data['js'] = $smarty->fetch($bundleDir.'/Resources/views/communication/request_availability.js.tpl');

	}
	
	static public function getRequestAvailabiltyDialog(Ext_Gui2 $gui2): Ext_Gui2_Dialog {
		
		$dialog = $gui2->createDialog($gui2->t('Verfügbarkeit für "{name}" anfragen'), $gui2->t('Verfügbarkeit für "{name}" anfragen'));
		$dialog->sDialogIDTag = 'REQUEST_AVAILABILITY_';
		
		$dialog->setElement(new Ext_Gui2_Html_Div());

		$dialog->save_button = false;
		$dialog->aButtons = [
			[
				'label' => $gui2->t('Anfragen senden'),
				'task' => 'saveDialog',
				'action' => 'request_availability'
			]
		];

		return $dialog;
	}
	
	/**
	 * Gui im History Dialog
	 */
	public function getHistoryDialog($iSelectedId){

		//die InquiryAccommodationId ist doch schon kodiert, warum dieser unnötiger Weg um an die InquiryAccommodation zu kommen?

		#$iAccommodadionAllocationId = $this->_oGui->decodeId($iSelectedId, 'allocation_id');
		#$oAccommodationAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($iAccommodadionAllocationId);

		$iInquiryAccommodationId	= (int)$this->_oGui->decodeId($iSelectedId, 'id');

		#$oInquiryAccommodation = $oAccommodationAllocation->getInquiryAccommodation();

		$oInquiryAccommodation		= Ext_TS_Inquiry_Journey_Accommodation::getInstance($iInquiryAccommodationId);

		$oInquiry = $oInquiryAccommodation->getInquiry();
		$oCustomer = $oInquiry->getCustomer();

		$oInnerGui = $this->_oGui->createChildGui(md5('thebing_accommodation_communication_history'), 'Ext_Thebing_Accommodation_Communication_History_Gui2');
		$oInnerGui->gui_title 			= L10N::t('Historie', $this->_oGui->gui_description);
		$oInnerGui->query_id_column		= 'id';
		$oInnerGui->query_id_alias		= 'kaal';
		$oInnerGui->load_admin_header	= false;
		$oInnerGui->multiple_selection  = false;
		//$oInnerGui->row_style			= new Ext_Thebing_Gui2_Style_Teacher_Salary_Row();
		$oInnerGui->calendar_format		= new Ext_Thebing_Gui2_Format_Date();
		$oInnerGui->class_js			= 'UtilGui';

		$oInnerGui->setWDBasic('Ext_Thebing_Accommodation_Allocation');
		$oInnerGui->setTableData('limit', 30);
		$oInnerGui->setTableData('orderby', array('active'=>'DESC', 'accommodation_confirmed'=>'ASC'));
		$oInnerGui->setTableData('where', array(
				'inquiry_accommodation_id' => (int)$oInquiryAccommodation->id,
			)
		);

		# START - Leiste 2 #

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

			$oBar = $oInnerGui->createBar();
			$oBar->visible = false;

			// Communicationsicons sollen nur in der GUI sichtbar sein nicht in der Bar
			$oIcon = $oBar->createCommunicationIcon($this->t('Unterkunft bestätigen'), 'accommodation_communication_history_accommodation_confirmed');
			$oBar->setElement($oIcon);

			$oIcon = $oBar->createCommunicationIcon($this->t('Unterkunft absagen'), 'accommodation_communication_history_accommodation_canceled');
			$oBar->setElement($oIcon);

			$oIcon = $oBar->createCommunicationIcon($this->t('Kunde/Agentur bestätigen'), 'accommodation_communication_history_customer_confirmed');
			$oBar->setElement($oIcon);

			$oIcon = $oBar->createCommunicationIcon($this->t('Kunde/Agentur absagen'), 'accommodation_communication_history_customer_canceled');
			$oBar->setElement($oIcon);

			$oInnerGui->setBar($oBar);
		# ENDE - Leiste 2 #

		/**
		 * Columngroups
		 */
		$oColumnGroupAccommodation = $oInnerGui->createColumnGroup();
		$oColumnGroupAccommodation->title = L10N::t('Unterkunft', $oInnerGui->gui_description);
		$oColumnGroupCustomer = $oInnerGui->createColumnGroup();
		$oColumnGroupCustomer->title = L10N::t('Kunde/Agentur', $oInnerGui->gui_description);
		$oColumnGroupEmpty = $oInnerGui->createColumnGroup();
		$oColumnGroupEmpty->title = ' ';

		//@todo: mysql umändern in timestamp
		$oFormatDate = new Ext_Thebing_Gui2_Format_Date(true,0,WDDate::DB_DATETIME);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'id';
		$oColumn->db_alias = 'kaal';
		$oColumn->title = L10N::t('Status', $oInnerGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('short_name');
		$oColumn->width_resize = true;
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Communication_AllocationStatus();
		$oColumn->group			= $oColumnGroupEmpty;
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'from';
		$oColumn->db_alias = 'kaal';
		$oColumn->title = L10N::t('Von', $oInnerGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('date_short');
		$oColumn->width_resize = false;
		$oColumn->format		= $oFormatDate;
		$oColumn->group			= $oColumnGroupEmpty;
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'until';
		$oColumn->db_alias = 'kaal';
		$oColumn->title = L10N::t('Bis', $oInnerGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('date_short');
		$oColumn->width_resize = false;
		$oColumn->format		= $oFormatDate;
		$oColumn->group			= $oColumnGroupEmpty;
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'room_id';
		$oColumn->db_alias = 'kaal';
		$oColumn->title = L10N::t('Provider', $oInnerGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize = false;
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Accommodation_ProviderByRoom();
		$oColumn->group			= $oColumnGroupEmpty;
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'accommodation_confirmed';
		$oColumn->db_alias = 'kaal';
		$oColumn->title = L10N::t('Bestätigt', $oInnerGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->width_resize = false;
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Accommodation_AccommodationConfirmed('accommodation_confirmed');
		$oColumn->style			= new Ext_Thebing_Gui2_Style_Accommodation_Communication_Icon('accommodation_confirmed');
		$oColumn->group			= $oColumnGroupAccommodation;
		$oColumn->event	= new Ext_Thebing_Gui2_Event_Accommodation_Communication('accommodation_confirmed');
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'accommodation_canceled';
		$oColumn->db_alias = 'kaal';
		$oColumn->title = L10N::t('Abgesagt', $oInnerGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->width_resize = false;
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Accommodation_AccommodationConfirmed('accommodation_canceled');
		$oColumn->style			= new Ext_Thebing_Gui2_Style_Accommodation_Communication_Icon('accommodation_canceled');
		$oColumn->group			= $oColumnGroupAccommodation;
		$oColumn->event	= new Ext_Thebing_Gui2_Event_Accommodation_Communication('accommodation_canceled');
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'customer_agency_confirmed';
		$oColumn->db_alias = 'kaal';
		$oColumn->title = L10N::t('Bestätigt', $oInnerGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->width_resize = false;
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Accommodation_AccommodationConfirmed('customer_agency_confirmed');
		$oColumn->style			= new Ext_Thebing_Gui2_Style_Accommodation_Communication_Icon('customer_agency_confirmed');
		$oColumn->group			= $oColumnGroupCustomer;
		$oColumn->event	= new Ext_Thebing_Gui2_Event_Accommodation_Communication('customer_agency_confirmed');
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'customer_agency_canceled';
		$oColumn->db_alias = 'kaal';
		$oColumn->title = L10N::t('Abgesagt', $oInnerGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->width_resize = false;
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Accommodation_AccommodationConfirmed('customer_agency_canceled');
		$oColumn->style			= new Ext_Thebing_Gui2_Style_Accommodation_Communication_Icon('customer_agency_canceled');
		$oColumn->group			= $oColumnGroupCustomer;
		$oColumn->event	= new Ext_Thebing_Gui2_Event_Accommodation_Communication('customer_agency_canceled');
		$oInnerGui->setColumn($oColumn);

		$oDefaultColumn = $oInnerGui->getDefaultColumn();
		$oDefaultColumn->setColGroupForAll($oColumnGroupEmpty);
		$oInnerGui->setDefaultColumn($oDefaultColumn);

		$oInnerGui->addDefaultColumns();

		// Farblegende

		$sHtmlLegend = '';

		$sHtmlLegend .= '<div style="float: left"><b>' . L10N::t('Status', $oInnerGui->gui_description) . ': </b>&nbsp;</div>';
		$sHtmlLegend .= '<div style="float: left">' . L10N::t('verschickt', $oInnerGui->gui_description) . '</div> <div class="colorkey" style="background-color: '.Ext_Thebing_Util::getColor('good').'" ></div>';
		$sHtmlLegend .= '<div style="float: left">' . L10N::t('noch nicht verschickt', $oInnerGui->gui_description) . '</div> <div class="colorkey" style="background-color: '.Ext_Thebing_Util::getColor('bad').'" ></div>';

		$oBarLegend = $oInnerGui->createBar();
		$oBarLegend->position = 'bottom';
		$oHtml = $oBarLegend->createHtml($sHtmlLegend);
		$oBarLegend ->setElement($oHtml);

		$oInnerGui->setBar($oBarLegend);

		$oDialog = $oInnerGui->createDialog($oInnerGui->t('Historie').' '. $oCustomer->name);
		$oDialog->sDialogIDTag = 'HISTORY_';

		$oDialog->setElement($oInnerGui);

		$oDialog->readonly = true;
		$oDialog->width = 950;
		$oDialog->height = 650;
		$oDialog->save_button = false;

		return $oDialog;

	}

	public function switchAjaxRequest($_VARS) {

		if ($_VARS['task'] == 'request' && $_VARS['action'] == 'communication') {

			$application = $_VARS['additional'];

			[$allocations, $journeyAccommodations] = collect($this->_oGui->decodeId($_VARS['id']))
				->partition(fn ($decode) => $decode['allocation_id'] !== null);

			$notifiables = collect();

			if ($allocations->isNotEmpty()) {
				$notifiables = $notifiables->merge(\Ext_Thebing_Accommodation_Allocation::query()->whereIn('id', $allocations->pluck('allocation_id'))->get());
			}

			if ($journeyAccommodations->isNotEmpty()) {
				$notifiables = $notifiables->merge(\Ext_TS_Inquiry_Journey_Accommodation::query()->whereIn('id', $journeyAccommodations->pluck('id'))->get());
			}

			$access = $this->readCommunicationAccessFromIconData($application);

			$this->openCommunication($notifiables, application: $application, access: $access);

		} else if(
			$_VARS['action'] == 'confirm_customer_agency' ||
			$_VARS['action'] == 'revoce_customer_agency'
		) {

			$aSelectedIds = (array)$_VARS['id'];

			foreach((array)$aSelectedIds as $iId){

				$iAccommodadionAllocationId = $this->_oGui->decodeId($iId, 'allocation_id');

				if($iAccommodadionAllocationId <= 0){
					$aTransfer['action'] = 'showError';
					$aTransfer['error'][] = $this->t('Bitte makieren Sie nur zugewiesene Einträge!');
					echo json_encode($aTransfer);
					$this->_oGui->save();
					die();
				}
				
			}

			foreach((array)$aSelectedIds as $iId){

				$iAccommodadionAllocationId = $this->_oGui->decodeId($iId, 'allocation_id');
				
				$oInquiryAccommodation = Ext_Thebing_Accommodation_Allocation::getInstance($iAccommodadionAllocationId);
				if($_VARS['action'] == 'confirm_customer_agency'){
					$oInquiryAccommodation->customer_agency_confirmed = time();
				}else{
					$oInquiryAccommodation->customer_agency_confirmed = 0;
				}
				$oInquiryAccommodation->save();

				$oInquiry = $oInquiryAccommodation->getInquiryAccommodation()?->getJourney()?->getInquiry();

				if ($oInquiry) {
					Ext_Gui2_Index_Registry::insertRegistryTask($oInquiry);
				}

			}

			$aTransfer['action'] = 'showSuccess';
			$aTransfer['load_table'] = true;
			$sMessage = '';
			if($_VARS['action'] == 'confirm_customer_agency'){
				$sMessage = 'Die Unterkunft wurde dem Kunden / der Agentur bestätigt.';
			}else{
				$sMessage = 'Die Unterkunft wurde bei dem Kunden / der Agentur widerrufen.';
			}
			$aTransfer['message'] = $this->t($sMessage);

			echo json_encode($aTransfer);

		} elseif(
			$_VARS['action'] == 'confirm_provider' ||
			$_VARS['action'] == 'revoce_provider'
		) {

			$aSelectedIds = (array)$_VARS['id'];

			foreach((array)$aSelectedIds as $iId){

				$iAccommodadionAllocationId = $this->_oGui->decodeId($iId, 'allocation_id');

				if($iAccommodadionAllocationId <= 0){
					$aTransfer['action'] = 'showError';
					$aTransfer['error'][] = $this->t('Bitte makieren Sie nur zugewiesene Einträge!');
					echo json_encode($aTransfer);
					$this->_oGui->save();
					die();
				}

			}

			foreach((array)$aSelectedIds as $iId){

				$iAccommodadionAllocationId = $this->_oGui->decodeId($iId, 'allocation_id');

				$oInquiryAccommodation = Ext_Thebing_Accommodation_Allocation::getInstance($iAccommodadionAllocationId);
				if($_VARS['action'] == 'confirm_provider'){
				    $oInquiryAccommodation->accommodation_confirmed = time();
                    \System::wd()->executeHook('ts_accommodation_confirm_provider', $oInquiryAccommodation);
				}else{
					$oInquiryAccommodation->accommodation_confirmed = 0;
                    \System::wd()->executeHook('ts_accommodation_revoke_provider', $oInquiryAccommodation);
				}
				$oInquiryAccommodation->save();

			}



			$aTransfer['action'] = 'showSuccess';
			$aTransfer['load_table'] = true;
			$sMessage = '';
			if($_VARS['action'] == 'confirm_provider'){
				$sMessage = 'Die Unterkunft wurde dem Unterkunftsanbieter bestätigt.';
			}else{
				$sMessage = 'Die Unterkunft wurde bei dem Unterkunftsanbieter widerrufen.';
			}
			$aTransfer['message'] = $this->t($sMessage);
			
			echo json_encode($aTransfer);

		} else {

			// TODO: Rausfinden was das hier genau macht! Ich denke das ist nur für die sonstigen Dokumente
			if(
				$_VARS['task'] != 'openDialog' &&
				$_VARS['action'] != 'additional_document' &&
				$_VARS['task'] != 'updateIcons' &&
				$_VARS['action'] != 'document_edit' &&
				$_VARS['task'] != 'loadTable' &&
				$_VARS['task'] != 'reloadPositionsTable'
			) {
				#$_VARS['id']	= (array)$this->_oGui->decodeId((array)$_VARS['id'], 'accommodation_inquiry_id');
			}
			
			/*
			 * da pro Buchungen mehrere Unterkunft hinzugefügt werden können muss auch für jede Unterkunft
			 * eine Dokument erzeugt werden. Bei "true" wird nur für die Buchung ein Dokument erzeugt
			 */
			$this->_bUniqueInquiriesDocuments = false;
			
			parent::switchAjaxRequest($_VARS);

		}

	}

	/**
	 * 
	 * @param <type> $sSelect
	 * @return <array>
	 */
	static public function getFilterSelect($sSelect, Ext_Gui2 $gui2) {

		$aOptions = array();
		//$aOptions[''] = '';

		switch($sSelect) {
			case 'transfer':
					$aOptions['1'] = $gui2->t('Ankunftsdaten vorhanden');
					$aOptions['2'] = $gui2->t('Ankunftsdaten nicht vorhanden');
				break;
			case 'accommodation':
					$aOptions['1'] = $gui2->t('Kunde / Agentur bestätigt');
					$aOptions['2'] = $gui2->t('Kunde / Agentur nicht bestätigt');
				break;
			case 'provider':
					$aOptions['1'] = $gui2->t('Anbieter bestätigt');
					$aOptions['2'] = $gui2->t('Anbieter nicht bestätigt');
				break;
			case 'allocation':
					$aOptions['1'] = $gui2->t('Zuweisung vorhanden');
					$aOptions['2'] = $gui2->t('keine Zuweisung');
				break;
		}

		return $aOptions;

	}

	/**
	 * Wrapper um zu definieren welcher Info text aufklappbar sein soll
	 */
//	public function getRowIconInfoText(&$aIds, &$aRowData, &$oIcon) {
//
//		$iGuiId = (int) reset($aIds);
//
//		$iInquiryId = (int) $this->_oGui->decodeId($iGuiId, 'accommodation_inquiry_id');
//
//		$aInquiryIds = array(
//			$iInquiryId
//		);
//
//		$sHtml = parent::getRowIconInfoText($aInquiryIds, $aRowData, $oIcon);
//
//		return $sHtml;
//	}

	/**
	 * @inheritdoc
	 */
	public function prepareColumnListByRef(&$aColumnList)
    {

        parent::prepareColumnListByRef($aColumnList);

        if (System::d('debugmode') == 2) {
            $oColumn = new Ext_Gui2_Head();
            $oColumn->db_column = 'id_kaa';
            $oColumn->title = 'KAA-ID';
            $oColumn->width = 50;
            $oColumn->sortable = false;
            array_unshift($aColumnList, $oColumn);

            $oColumn = new Ext_Gui2_Head();
            $oColumn->db_column = 'id_ija';
            $oColumn->title = 'IJA-ID';
            $oColumn->width = 50;
            $oColumn->sortable = false;
            array_unshift($aColumnList, $oColumn);
        }

    }
	
}

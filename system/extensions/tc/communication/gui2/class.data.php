<?php

use Communication\Enums\MessageStatus as SourceEnum;

class Ext_TC_Communication_Gui2_Data extends Ext_TC_Gui2_Data {

	/**
	 * Holt die Daten für die Nachrichtenvorschau
	 * 
	 * @param int $iMessageId
	 * @return array 
	 */
	protected function _getMessagePreview($iMessageId) {

		$this->_getWDBasicObject($iMessageId);

		if ($this->oWDBasic->direction === 'in') {
			// Nachricht als gelesen setzen (auch auf dem IMAP-Server)
			$this->oWDBasic->setFlag('unseen', false, true);
			if ($this->oWDBasic->status !== \Communication\Enums\MessageStatus::SEEN->value) {
				$this->oWDBasic->status = \Communication\Enums\MessageStatus::SEEN->value;
				$this->oWDBasic->seen_at = time();
			}
		}
		$this->oWDBasic->save();

		$oDateTimeFormat = Factory::getObject(Ext_TC_Gui2_Format_Date_Time::class);

		$aPreview = array(
			'date' => (string)$oDateTimeFormat->format($this->oWDBasic->date, $oDummy, $aDummy),
			'from' => (string)\Util::getEscapedString($this->oWDBasic->getFormattedContacts('from'), 'htmlall'),
			'to' => (string)\Util::getEscapedString($this->oWDBasic->getFormattedContacts('to'), 'htmlall'),
			'cc' => (string)\Util::getEscapedString($this->oWDBasic->getFormattedContacts('cc'), 'htmlall'),
			'bcc' =>(string)\Util::getEscapedString($this->oWDBasic->getFormattedContacts('bcc'), 'htmlall'),
			'subject' => (string)\Util::getEscapedString($this->oWDBasic->subject, 'htmlall'),
			'content' => (string)$this->oWDBasic->getContent(),
			'attachments' => (string)$this->oWDBasic->getFormattedAttachments(),
			'flags' => (string)$this->oWDBasic->getFormattedFlags(),
			'type' => (string)$this->oWDBasic->getFormattedType()
		);

		return $aPreview;

	}

	public function switchAjaxRequest($_VARS, $bReturn = false) {

		$aTransfer = $this->_switchAjaxRequest($_VARS);

		if($_VARS['task'] == 'updateIcons') {
			
			if(
				!empty($_VARS['id']) &&
				count($_VARS['id']) == 1
			) {

				$aTransfer['preview'] = $this->_getMessagePreview($_VARS['id']);

			}

		} elseif(isset($_VARS['action']) && $_VARS['action'] == 'sync') {
			
			// E-Mails synchronisieren
			$oCronjob = new Ext_TC_System_CronJob_Update_Imap;
			$oCronjob->executeUpdate();
			
			$aTransfer['action'] = 'loadTable';
			
		} elseif($_VARS['task'] == 'contextMenu') {
			
			$iSelectedId = reset($_VARS['id']);
			$oMessage = Ext_TC_Factory::getInstance('Ext_TC_Communication_Message', $iSelectedId);
			
			$oMessage->category_id = (int)$_VARS['key'];
			
			if($oMessage->validate(true)) {
				$oMessage->save();
			}
			
		}

		if(!$bReturn) {
			echo json_encode($aTransfer);
		} else {
			return $aTransfer;
		}

	}

	protected function _buildQueryParts(&$sSql, &$aSql, &$aSqlParts, &$iLimit) {

		parent::_buildQueryParts($sSql, $aSql, $aSqlParts, $iLimit);

		// Bei genereller Kommunikationsliste Rechte auf E-Mail-Accounts prüfen
		if(
			!$this->_oGui->getOption('inner') &&
			$this->_oGui->getOption('type') === 'message'
		) {

			$aSql['account_ids'] = array_keys(Ext_TC_Communication_EmailAccount::getSelectOptions(true));
			$aSql['relation_class'] = Factory::getClassName('Ext_TC_Communication_EmailAccount');

			$aSqlParts['where'] .= " AND (`tc_cm`.`type` != 'email' OR (`relations`.`relation` = :relation_class AND `relations`.`relation_id` IN (:account_ids))) ";

		}

	}
	
	public function getPreviewContainer() {

		$sHash = $this->_oGui->hash;

		$sHtml = '<div id="preview_container_'.$sHash.'" class="communication_preview_container" style="display: none;">';
		$sHtml .= '
			<div id="preview_header_'.$sHash.'" class="header">
				<div class="meta-data">
					<div class="contact-avatar">
						<span class="fa fa-user"></span>
					</div>
					<div class="mail-details">
						<div class="row">
							<span id="preview_from_'.$sHash.'"></span>
							<span class="label">&centerdot;</span>
							<span id="preview_date_'.$sHash.'" class="label date"></span>
						</div>
						<div class="row">
							<span class="label">'.$this->t('An').':</span>
							<span id="preview_to_'.$sHash.'"></span>
						</div>
						<div class="row">
							<span class="label">'.$this->t('Cc').':</span>
							<span id="preview_cc_'.$sHash.'"></span>
						</div>
						<div class="row">
							<span class="label">'.$this->t('Bcc').':</span>
							<span id="preview_bcc_'.$sHash.'"></span>
						</div>
					</div>
					<div class="preview_containers flags" id="preview_flags_container">
						<div id="preview_flags_'.$sHash.'"></div>
					</div>
				</div>
				<div class="preview_containers attachments" id="preview_attachments_container_'.$sHash.'">
					<div id="preview_attachments_'.$sHash.'"></div>
				</div>
				<div class="">
					<div class="label">'.$this->t('Art').'</div>
					<div style="float: left;" id="preview_type_'.$sHash.'"></div>
					<div class="divCleaner"></div>
				</div>
			</div>
			';
		//$sHtml .= '<div id="preview_content_'.$sHash.'">…</div>';
		$sHtml .= '<iframe id="preview_content_'.$sHash.'" sandbox style="border: none;"></iframe>';
		$sHtml .= '</div>';

		return $sHtml;		
	}

	public static function createGui($bInner = false, Ext_Gui2 $oParentGui = null, $aAccess = array(), $sType = 'message') {

		if($sType === 'notice') {
			$oGui = $oParentGui->createChildGui(md5('tc_core_communication_'.$sType), Ext_TC_Factory::getClassName('Ext_TC_Communication_Message_Notice_Gui2_Data'));			

			if($oGui->gui_description === null) {
				$oGui->gui_description = $oParentGui->gui_description . ' » Communication';
				$oGui->gui_title = $oParentGui->gui_title . ' » Kommunikation';
			}
		} else {
			$oGui = Ext_TC_Factory::getObject('Ext_TC_Communication_Gui2', [md5('tc_core_communication_'.$sType), Ext_TC_Factory::getClassName('Ext_TC_Communication_Gui2_Data'), null, $oParentGui->instance_hash ?? null]);
			if($oParentGui) {
				$oGui->setParent($oParentGui);
			}

			if($oGui->gui_description === null) {
				$oGui->gui_description = 'Communication';
			}
			if($oGui->gui_title === null) {
				$oGui->gui_title = 'Kommunikation';
			}
		}

		// Rechte setzen
		if(!$bInner) {
			if (empty($aAccess)) {
				$oGui->access = array('core_communication', 'list');
			} else {
				$oGui->access = $aAccess;
			}
		} else {
			if($aAccess !== null) {
				$oGui->access = $aAccess;
			}
		}

		$oGui->multiple_selection = true;
		$oGui->query_id_column = 'id';
		$oGui->query_id_alias = 'tc_cm';
		$oGui->calendar_format = Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date');
		$oGui->showLeftFrame = false;
		$oGui->class_js = 'CommunicationGui';
		$oGui->setOption('inner', $bInner);
		$oGui->setOption('type', $sType);

		$oGui->row_style = new Ext_TC_Communication_Gui2_Style($sType);

		if(in_array($sType, ['message', 'spool'])) {
			$oGui->row_contextmenu = new Ext_TC_Communication_Gui2_ContextMenu_Categories();
			$oGui->row_icon_status_active = new Ext_TC_Communication_Gui2_Icon_Active();

			$oGui->include_jquery = true;
			$oGui->include_jquery_contextmenu = true;
		}

		// ORDER BY
		$oGui->setWDBasic(Ext_TC_Factory::getClassName('Ext_TC_Communication_Message'));
		$oGui->setTableData('orderby', array('tc_cm.date'=>'DESC'));

		// LIMIT
		if(in_array($sType, ['message', 'spool'])) {
			$oGui->setTableData('limit', 30);
		}

		// WHERE
		if($bInner) {

			$oGui->parent_hash = $oParentGui->hash;
			$oGui->foreign_key = 'relation_id';
			$oGui->foreign_key_alias = 'relations';
			$oGui->parent_primary_key = 'id';

			// Falls mal eine Allocation in der Factory angepasst wurde existieren noch alte Einträge
			$aRelationClasses = array_unique([$oParentGui->class_wdbasic, Factory::getClassName($oParentGui->class_wdbasic)]);

			if (count($aRelationClasses) > 1) {
				$aWhereData = ['relations.relation' => ['IN', $aRelationClasses]];
			} else {
				$aWhereData = ['relations.relation' => reset($aRelationClasses)];
			}

			if($sType === 'notice') {
				$aWhereData['tc_cm.type'] = 'notice';
			}

			$oGui->setTableData('where', $aWhereData);

		} else if ($sType === 'spool') {
			$oGui->setTableData('where', ['tc_cm.direction' => 'out', 'tc_cm.sent' => 0]);
		}

		if(in_array($sType, ['message', 'spool'])) {

			$oBar = $oGui->createBar();

			$oFilter = $oBar->createFilter();
			$oFilter->db_column = array('content', 'subject');
			$oFilter->db_alias = array('tc_cm', 'subjects');
			$oFilter->id = 'search';
			$oFilter->placeholder = $oGui->t('Suche').'…';
			$oBar->setElement($oFilter);

			$oBar->setElement($oBar->createSeperator());
			
			$oDateFormat = Factory::getObject('Ext_TC_Gui2_Format_Date');
			$oFilter = $oBar->createTimeFilter($oDateFormat);
			$oFilter->db_from_column = array('date');
			$oFilter->db_from_alias	= array('tc_cm');
			$oFilter->search_type		= 'between';
			$oFilter->data_function		= 'DATE';
			$oFilter->label				= $oGui->t('Von');
			$oFilter->label_between		= $oGui->t('bis');
			#$oFilter->text_after		= $oGui->t('Basierend auf dem Erstellungsdatum');

			$iDefaultFromTimestamp = strtotime("-24 month", time());
			if(is_null($oParentGui)) {
				$iDefaultFromTimestamp = strtotime("-1 week", time());
			}
			$oFilter->default_from = $oDateFormat->format($iDefaultFromTimestamp);
			
			$oFilter->default_until = $oDateFormat->format(time());
			$oBar->setElement($oFilter);
			
			$oBar->setElement($oBar->createSeperator());

			$oFilter = $oBar->createFilter('select');
			$oFilter->db_column = 'type';
			$oFilter->db_alias	= array('tc_cm');
			$oFilter->select_options = Ext_TC_Util::addEmptyItem(static::getTypes(), '--'.$oGui->t('Art').'--');
			$oBar->setElement($oFilter);

			if($sType === 'message') {
				$aDirections = Ext_TC_Communication::getSelectFilterDirections();
				$aDirections = Ext_TC_Util::addEmptyItem($aDirections, '--' . $oGui->t('Richtung') . '--');

				$oFilter = $oBar->createFilter('select');
				$oFilter->db_column = 'direction';
				$oFilter->db_alias = array('tc_cm');
				$oFilter->select_options = $aDirections;
				$oFilter->value = '';
				$oFilter->filter_query = array(
					'',
					'in' => " `direction` = 'in' ",
					'out' => " `direction` = 'out' "
				);
				$oBar->setElement($oFilter);

				$aEmailAccountsACL = Ext_TC_Communication_EmailAccount::getSelectOptions(true);
				$aEmailAccounts = Ext_Gui2_Util::addLabelItem($aEmailAccountsACL, $oGui->t('E-Mail-Konto'));
				$aEmailAccountsFilterQuery = [];
				$sRelationClass = Factory::getClassName('Ext_TC_Communication_EmailAccount');
				foreach(array_keys($aEmailAccountsACL) as $iAccountId) {
					$aEmailAccountsFilterQuery[$iAccountId] = " `relations_filter_account`.`relation` = '{$sRelationClass}' AND `relations_filter_account`.`relation_id` = '{$iAccountId}' ";
				}

				$oFilter = $oBar->createFilter('select');
				$oFilter->db_column = '';
				$oFilter->db_alias = '';
				$oFilter->select_options = $aEmailAccounts;
				$oFilter->filter_query = $aEmailAccountsFilterQuery;
				$oFilter->filter_join = " LEFT JOIN
					`tc_communication_messages_relations` `relations_filter_account` ON
						`relations_filter_account`.`message_id` = `tc_cm`.`id` ";
				$oBar->setElement($oFilter);
			}
			
			$aCategorys = Ext_TC_Communication_Category::getSelectOptions();
			$aCategorys = Ext_TC_Util::addEmptyItem($aCategorys, '--'.$oGui->t('Kategorie').'--');
			$oFilter = $oBar->createFilter('select');
			$oFilter->db_column = 'category_id';
			$oFilter->db_alias = 'categories';
			$oFilter->select_options = $aCategorys;
			$oFilter->db_operator = '=';
			$oFilter->value	= '';
			$oBar->setElement($oFilter);

			if(!in_array($sType, ['spool'])) {
				$stateCollection = collect(SourceEnum::cases());
				$filterQueries = [];
				foreach ($stateCollection as $stateObject) {
					// Den Case "null" braucht man hier nicht, ist aber bei ::cases dabei
					if ($stateObject !== SourceEnum::NULL) {
						$states[$stateObject->value] = $stateObject->getLabelText($oGui->getLanguageObject());
						$filterQueries[$stateObject->value] = sprintf("`tc_cm`.`status` = '%s'", $stateObject->value);
					}
				}

				// TODO den Status mal vereinheitlichen (Bit?)
				$states['not_sent'] = $oGui->t('Nicht versendet');
				$filterQueries['not_sent'] = "`tc_cm`.`direction` = 'out' AND `tc_cm`.`sent` = 0";

				$oFilter = $oBar->createFilter('select');
				$oFilter->db_column = 'status';
				$oFilter->db_alias = 'tc_cm';
				$oFilter->select_options = Ext_TC_Util::addEmptyItem($states, '--' . $oGui->t('Status') . '--');
				$oFilter->filter_query = $filterQueries;
				$oBar->setElement($oFilter);
			}

			$oGui->setBar($oBar);
		}

		if(
			!$bInner || 
			$sType === 'notice'
		) {

			$oBar = $oGui->createBar();

			if($sType === 'notice') {
				$oDialog = Ext_TC_Communication_Message_Notice_Gui2_Data::getDialog($oGui);
				$oIcon = $oBar->createNewIcon($oGui->t('Neuer Eintrag'), $oDialog, $oGui->t('Neuer Eintrag'));
				$oBar->setElement($oIcon);
			} elseif($sType === 'message') {
				$oIcon = $oBar->createDeleteIcon($oGui->t('Löschen'), $oGui->t('Löschen'));
				$oIcon->access = array('core_communication', 'delete');
				$oBar->setElement($oIcon);

				if (
					!$bInner &&
					!empty(static::getAllocationHandler())
				) {
					$oIcon = $oBar->createIcon(Ext_TC_Util::getIcon('allocate'), 'request', $oGui->t('Zuweisen'));
					$oIcon->action = 'messageAllocations';
					$oIcon->label = $oGui->t('Zuweisen');
					//$oIcon->dbl_click_element = 1;
					$oIcon->access = array('core_communication', 'allocate');
					$oBar->setElement($oIcon);
				}

			} elseif($sType === 'spool') {

				$oIcon = $oBar->createDeleteIcon($oGui->t('Löschen'), $oGui->t('Löschen'));
				$oIcon->access = array('core_admin_mail_spool', 'delete');
				$oBar->setElement($oIcon);

				$oIcon = $oBar->createIcon(Ext_TC_Util::getIcon('paperplane'), 'request', $oGui->t('Versenden'));
				$oIcon->action = 'messageSend';
				$oIcon->label = $oGui->t('Versenden');
				//$oIcon->dbl_click_element = 1;
				$oIcon->access = array('core_admin_mail_spool', 'send');
				$oBar->setElement($oIcon);
			}

			$oGui->setBar($oBar);
		}

		if(in_array($sType, ['message', 'spool'])) {
			$oBar = $oGui->createBar();

			$oFilter = $oBar->createPagination(false, true);
			$oBar->setElement($oFilter);

			if($sType === 'message') {
				$oLabelgroup = $oBar->createLabelGroup($oGui->t('Weitere Aktionen'));
				$oBar->setElement($oLabelgroup);

				$oIcon = $oBar->createIcon(Ext_TC_Util::getIcon('refresh'), 'request', $oGui->t('E-Mail-Konten synchronisieren'));
				$oIcon->access = array('core_communication', 'sync');
				$oIcon->action = 'sync';
				$oIcon->active = 1;
				$oIcon->label = $oGui->t('E-Mail-Konten synchronisieren');
				$oBar->setElement($oIcon);
				$oBar->createCSVExportWithLabel();
				$oLoading = $oBar->createLoadingIndicator();
				$oBar->setElement($oLoading);
			}

			$oGui->setBar($oBar);

			$oColumn					= new Ext_Gui2_Head();
			$oColumn->db_column			= 'status';
			$oColumn->mouseover_title	= $oGui->t('Status');
			$oColumn->title	= $oGui->t(' ');
			$oColumn->width				= 50;
			$oColumn->format			= new Ext_TC_Communication_Gui2_Format_StatusIcons;
			$oColumn->sortable			= 0;
			$oGui->setColumn($oColumn);

		}

		$oColumn					= new Ext_Gui2_Head();
		$oColumn->db_column			= 'recipient';
		$oColumn->db_alias			= '';
		$oColumn->title				= $oGui->t('Empfänger');
		$oColumn->width				= Ext_TC_Util::getTableColumnWidth('person_name');
		$oColumn->format			= new Ext_TC_Communication_Gui2_Format_Addresses('to', false, true);
		$oColumn->sortable			= 0;
		$oColumn->css_overflow		= 'ellipsis';
		$oGui->setColumn($oColumn);

		$oColumn					= new Ext_Gui2_Head();
		$oColumn->db_column			= 'sender';
		$oColumn->db_alias			= '';
		$oColumn->title				= $oGui->t('Absender');
		$oColumn->width				= Ext_TC_Util::getTableColumnWidth('person_name');
		$oColumn->format			= new Ext_TC_Communication_Gui2_Format_Addresses('from', false, true);
		$oColumn->sortable			= 0;
		$oGui->setColumn($oColumn);

		$oColumn					= new Ext_Gui2_Head();
		$oColumn->db_column			= 'date';
		$oColumn->db_alias			= 'tc_cm';
		$oColumn->title				= $oGui->t('Datum');
		$oColumn->width				= Ext_TC_Util::getTableColumnWidth('date_time');
		$oColumn->format			= Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date_Time');
		$oGui->setColumn($oColumn);

		$oColumn					= new Ext_Gui2_Head();
		$oColumn->db_column			= 'status';
		$oColumn->db_alias			= 'tc_cm';
		$oColumn->title				= $oGui->t('Status');
		$oColumn->width				= Ext_TC_Util::getTableColumnWidth('icon');
		$oColumn->format			= new \Ext_TC_Communication_Gui2_Format_Status($oGui->getLanguageObject());
		$oGui->setColumn($oColumn);

		$oColumn					= new Ext_Gui2_Head();
		$oColumn->db_column			= 'type';
		$oColumn->db_alias			= 'tc_cm';
		$oColumn->title				= $oGui->t('Art');
		$oColumn->format			= new Ext_TC_Communication_Gui2_Format_Type();
		$oGui->setColumn($oColumn);

		$oColumn					= new Ext_Gui2_Head();
		$oColumn->db_column			= 'subject';
		$oColumn->db_alias			= 'subjects';
		$oColumn->title				= $oGui->t('Betreff');
		$oColumn->width				= Ext_TC_Util::getTableColumnWidth('long_description');
		$oColumn->width_resize		= true;
		$oColumn->format			= new \Gui2\Format\EscapedString('htmlall');
		$oGui->setColumn($oColumn);
		
		$oColumn					= new Ext_Gui2_Head();
		$oColumn->db_column			= 'creator_id';
		$oColumn->db_alias			= '';
		$oColumn->title				= $oGui->t('Benutzer');
		$oColumn->width				= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->format			= new Ext_Gui2_View_Format_UserName(true);
		$oColumn->sortable			= 0;
		$oGui->setColumn($oColumn);
		
		self::setLegend($oGui, $sType);

		return $oGui;
		
	}
	
	public static function getPage($bInner = false, $oParentGui = null, $aAccess = array(), $sType = 'message') {

		$oGui = static::createGui($bInner, $oParentGui, $aAccess, $sType);

		$oPage = static::buildPage($oGui);

		return $oPage;
	}
	
	/**
	 * Baut eine übergeben GUI in eine Page ein
	 * @param Ext_Gui2 $oGui
	 * @return \Ext_TC_Communication_Gui2_Page 
	 */
	public static function buildPage(Ext_Gui2 $oGui) {
		
		// Auf jeden Fall die JS Ableitung verwenden für Preview
		$oGui->class_js = 'CommunicationGui';

		/** @var self $oData */
		$oData = $oGui->getDataObject();
		
		$oPage = new Ext_TC_Communication_Gui2_Page();

		$oPage->setGui($oGui);

		$aBlockData = array(
			'title' => $oGui->t('Detailansicht'), 
			'html' => $oData->getPreviewContainer(),
			'hash' => 'preview_'.$oGui->hash
		);
		$oPage->setElement($aBlockData);
		
		return $oPage;
		
	}
	
	public static function setLegend($oGui, $sType)
	{
	
		$oCategory = new Ext_TC_Communication_Category();
		$aCategories = $oCategory->getArrayList();

		$oLegend = new Ext_Gui2_Bar_Legend($oGui);
		$oLegend->position = 'bottom';

		$bAdd = false;
		if(!empty($aCategories)) {

			$oLegend->addTitle($oGui->t('Kategorien'));

			foreach((array)$aCategories as $aCategory) {
				$oLegend->addInfo($aCategory['name'], $aCategory['code']);
			}

			$bAdd = true;
		}

		if ($sType === 'message') {

			$oLegend->addTitle($oGui->t('Mail-Spool'));
			$oLegend->addInfo($oGui->t('Nicht versendet'), \Ext_TC_Util::getColor('inactive'), true);

			$bAdd = true;
		}

		if ($bAdd) {
			$oGui->setBar($oLegend);
		}
	}

	protected function requestMessageAllocations($aVars) {

		$oDialog = $this->_oGui->createDialog($this->_oGui->t('Aktion auswählen'), $this->_oGui->t('Aktion auswählen'));
		$oDialog->sDialogIDTag = 'ALLOCATE_';
		$oDialog->save_button = false;

		$aAllocationConfigs = static::getAllocationHandler();
		$oMessage = $this->_getWDBasicObject($this->request->input('id', []));

		$oH3 = new Ext_Gui2_Html_H4();
		$oH3->setElement($this->t('Bitte wählen Sie eine gewünschte Aktion aus.'));
		$oDialog->setElement($oH3);

		foreach ($aAllocationConfigs as $sType => $aConfig) {

			if (!app()->make($aConfig['class'])->isValid($oMessage)) {
				continue;
			}

			$oDiv = $oDialog->create('div');
			$oDiv->class = 'designDiv allocationAction';
			$oDiv->style = 'cursor: pointer;';
			$oDiv->setDataAttribute('handler', $sType);
			$oDiv->setElement($aConfig['label']);
			$oDialog->setElement($oDiv);
		}

		$aTransfer['action'] = 'openDialog';
		$aTransfer['data'] = $oDialog->getDataObject()->getHtml($this->request->input('action'), $this->request->input('id', []), $this->request->input('additional'));
		$aTransfer['data']['action'] = 'allocate';

		return $aTransfer;
	}

	/**
	 * @param $aVars
	 * @return array
	 */
	protected function saveMessageAllocation($aVars): array {

		$aAllocationConfigs = static::getAllocationHandler();
		$sType = $this->request->input('type');
		$aSelectedIds = $this->request->input('id', []);

		if ($sType === null || !isset($aAllocationConfigs[$sType])) {
			throw new \RuntimeException(sprintf('Unknown message allocation type "%s"', $sType));
		}

		$oMessage = $this->_getWDBasicObject($aSelectedIds);

		$oHandler = app()->make($aAllocationConfigs[$sType]['class']);

		$aTransfer = [];

		if (in_array(\Tc\Traits\Communication\Allocation\WithDialog::class, class_uses($oHandler))) {

			$oDialog = $this->_oGui->createDialog($aAllocationConfigs[$sType]['label'], $aAllocationConfigs[$sType]['label']);

			// Spezifische Felder der Aktion einbauen
			$oHandler->prepareDialog($this->_oGui, $oDialog, $oMessage);

			$oDialog->save_button = false;
			$oDialog->sDialogIDTag = 'ALLOCATIONDIALOG_';
			$oDialog->disableInfoIcons();

			$this->aIconData['messageAllocationOptionDialog']['dialog_data'] = $oDialog;

			$aTransfer['action'] = 'openDialog';
			$aTransfer['data'] = $oDialog->getDataObject()->getHtml($this->request->input('action'), $aSelectedIds, $sType);
			$aTransfer['data']['task'] = 'save';
			$aTransfer['data']['action'] = 'messageAllocationOptionDialog'; // siehe saveMessageAllocationOptionDialog()
			// Aktuellen Dialog schließen und neuen Dialog öffnen
			$aTransfer['data']['force_new_dialog'] = true;
			$aTransfer['data']['old_id'] = 'ALLOCATE_'.implode('_', $aSelectedIds);
			// TODO über $oDialog->aButtons geht es nicht
			$aTransfer['data']['buttons'] = [
				[
					'label'			=> L10N::t('Speichern'),
					'task'			=> 'saveDialog',
					'action'		=> $this->request->input('action'),
					'additional'	=> $sType
				]
			];

		} else {

			$mSuccess = $oHandler->save($this->_oGui, $oMessage, $this->request);

			if (is_array($mSuccess)) {
				$aTransfer['action'] = 'saveDialogCallback';
				$aTransfer['error'] = array_merge([$this->t('Fehler beim Speichern')], $mSuccess);
			} else {
				$aTransfer['action'] = 'closeDialogAndReloadTable';
				$aTransfer['error'] = [];
				$aTransfer['data']['id'] = 'ALLOCATE_'.implode('_', $aSelectedIds);
			}

		}

		return $aTransfer;

	}

	/**
	 * @param $aVars
	 * @return array
	 */
	protected function saveMessageAllocationOptionDialog($aVars): array {

		$aAllocationConfigs = static::getAllocationHandler();
		$sType = $this->request->input('additional');
		$aSelectedIds = $this->request->input('id', []);

		if ($sType === null || !isset($aAllocationConfigs[$sType])) {
			throw new \RuntimeException(sprintf('Unknown message allocation type "%s"', $sType));
		}

		$oMessage = $this->_getWDBasicObject($aSelectedIds);

		$oHandler = app()->make($aAllocationConfigs[$sType]['class']);

		if (!$oHandler->isValid($oMessage)) {
			return [
				'action' => 'saveDialogCallback',
				'error' => [$this->t('Die gewählte Aktion kann nicht ausgeführt werden.')]
			];
		}

		$mSuccess = $oHandler->save($this->_oGui, $oMessage, $this->request);

		if (is_array($mSuccess)) {
			$aTransfer['action'] = 'saveDialogCallback';
			$aTransfer['error'] = array_merge([$this->t('Fehler beim Speichern')], $mSuccess);
		} else {
			$aTransfer['error'] = [];
			$aTransfer['action'] = 'closeDialogAndReloadTable';
			$aTransfer['data']['id'] = 'ALLOCATIONDIALOG_'.implode('_', $aSelectedIds);
		}

		return $aTransfer;
	}

	protected function requestMessageSend($aVars) {

		$selectedIds = (array)$aVars['id'] ?? [];

		$messages = Ext_TC_Communication_Message::query()
			->whereIn('id', $selectedIds)
			->get();

		[$sent, $failed] = \Communication\Facades\MailSpool::for($messages)->run();

		$messageTypes = Factory::executeStatic(Ext_TC_Communication_Gui2_Data::class, 'getTypes');

		$messageTitle = function ($message) use ($messageTypes) {
			return sprintf(
				'%s "%s" (%s)',
				$messageTypes[$message->type] ?? $message->type,
				$message->subject,
				$message->getFormattedContacts('to', false)
			);
		};

		$accordion = new \Ext_Gui2_Dialog_Accordion('mail_spool');

		foreach ($failed as [$message, $errors]) {
			$element = $accordion->createElement('<i class="fa fa-minus-circle fa-colored"></i> '.$messageTitle($message), ['close' => false]);
			$element->setContent('<div class="designDiv" style="overflow: auto;"><pre>'.print_r($errors, true).'</pre></div>');
			$accordion->addElement($element);
		}

		foreach ($sent as $message) {
			$element = $accordion->createElement('<i class="fa fa-check"></i> '.$messageTitle($message), ['close' => true]);
			$element->setContent('<div style="padding: 5px;">'.$this->t('Die Nachricht wurde erfolgreich abgeschickt.').'</div>');;
			$accordion->addElement($element);
		}

		$transfer = [];
		$transfer['action'] = 'openDialog';
		$transfer['load_table']= 1;

		$transfer['data']['id']	= 'ID_'.implode('_', $selectedIds);
		$transfer['data']['title'] = $this->t('Abgearbeitete Nachrichten');
		$transfer['data']['width'] = 900;
		$transfer['data']['html'] = $accordion->generateHtml();

		return $transfer;
	}

	public function getTranslations($sL10NDescription) {
		$aTranslations = parent::getTranslations($sL10NDescription);
		$aTranslations['preview_no_subject'] = $this->t('Kein Betreff');
		return $aTranslations;
	}

	public static function getTypes() {
		return array(
			'email' => Ext_TC_Communication::t('E-Mail'),
			'sms' => Ext_TC_Communication::t('SMS'),
			'app' => Ext_TC_Communication::t('App'),
			'notice' => Ext_TC_Communication::t('Notiz')
		);
	}

	/**
	 * Zuweisungsmöglichkeiten einer Nachricht definieren
	 *
	 * 'enquiry' => [
	 *		'label' => \Ext_TC_Communication::t('In Anfrage umwandeln'),
	 * 		'class' => CreateEnquiry::class
	 *	],
	 *
	 * @return array
	 */
	public static function getAllocationHandler(): array {
		return [];
	}

}

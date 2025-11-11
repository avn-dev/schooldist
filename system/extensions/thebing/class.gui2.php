<?php

class Ext_Thebing_Gui2 extends Ext_TC_Gui2 {

	protected $_aJsFiles			= array();
	protected $_aCssFiles			= array();

	protected $_aDocumentPositions	= array();
	protected $_sDocumentPositionsLanguage = null;
	protected $_bDocumentPositionsInitialized = false;
	protected $documentPositionsCompanyId = null;
	
	protected $_oDefaultColumn		= null;

	public function  __construct($sHash = '', $sDataClass = 'Ext_Thebing_Gui2_Data', $sViewClass = '') {

		parent::__construct($sHash, $sDataClass, $sViewClass);
		
		$this->calendar_format = new Ext_Thebing_Gui2_Format_Date();

	}

	/**
	 * START: Methoden für das Speichern von Unterpositionen in Rechnungen
	 */

	public function setDocumentPositionsCompany($companyId) {
		$this->documentPositionsCompanyId = $companyId;
	}
	
	public function getDocumentPositionsCompany() {
		return $this->documentPositionsCompanyId;
	}
	
	public function setDocumentPositionsInitialized($bInitialized) {
		$this->_bDocumentPositionsInitialized = $bInitialized;
	}

	public function getDocumentPositionsInitialized() {
		return $this->_bDocumentPositionsInitialized;
	}
	
	public function setDocumentPositionsLanguage($sLanguage) {
		$this->_sDocumentPositionsLanguage = $sLanguage;
	}

	public function getDocumentPositionsLanguage() {
		return $this->_sDocumentPositionsLanguage;
	}
	
	public function resetDocumentPositions() {
		$this->_aDocumentPositions = array();
		$this->documentPositionsCompanyId = null;
		$this->_bDocumentPositionsInitialized = false;
		$this->setOption('document_positions_negated', false);
	}

	public function getDocumentPositions() {

		$this->sortPositions();
		
		return $this->_aDocumentPositions;

	}

	/**
	 * Alle Items negieren
	 *
	 * Das wurde damals in der Document_Positions gemacht, wurde aber nicht in den Cache geschrieben.
	 * Das sorgte dann fortlaufend für Probleme beim Negieren!
	 */
	public function negateDocumentPositions() {
		// hier wurde jeder Betrag mit abs erst mal positiv gesetzt und danach * -1 genommen, das ist natürlich falsch
		// bei einer Gutschrift für eine Differenzrechnung (#3945), falls dieser blöde bNegat Flag jedoch woanders noch benutzt
		// wird & ich durch diesen Fix was anderes kaputt gemacht hab, dann bitte meinen Fall auch berücksichtigen....
		// » Wodurch dann 2 Fehler von #4841 entstanden sind.
		foreach($this->_aDocumentPositions as &$aItems) {
			foreach($aItems as &$aItem) {
				$aItem['amount'] *= -1;
				$aItem['amount_provision'] *= -1;
				$aItem['amount_net'] *= -1;
			}
		}
	}

	public function getDocumentPosition($mKey) {
		return $this->_aDocumentPositions[$mKey];
	}

//	public function setDocumentPositions($aPositions) {
//		$this->_aDocumentPositions = $aPositions;
//	}

	public function setDocumentPosition($mKey, $aPosition) {
		$this->_aDocumentPositions[$mKey] = $aPosition;
	}

	public function addDocumentPositionItem($mKey, $aPosition) {
		$this->_aDocumentPositions[$mKey][] = $aPosition;
	}

	public function deleteDocumentPosition($mKey){
		unset($this->_aDocumentPositions[$mKey]);
	}

	public function deleteDocumentPositionItem($mKey, $mSubKey)
	{
		if(
			isset($this->_aDocumentPositions[$mKey]) &&
			isset($this->_aDocumentPositions[$mKey][$mSubKey])
		){
			unset($this->_aDocumentPositions[$mKey][$mSubKey]);
			
			if(
				empty($this->_aDocumentPositions[$mKey])
			){
				$this->deleteDocumentPosition($mKey);
			}
		}
	}

	public function sortPositions(){

		// Wenn Position Wert vorhanden, Sortierung vornehmen
		$aFirstPosition = reset($this->_aDocumentPositions);
		if(
			!empty($aFirstPosition) &&
			array_key_exists('position', reset($aFirstPosition))
		) {
			uasort($this->_aDocumentPositions, array($this, 'sortPositionsFunc'));
		}

	}

	public function sortPositionsFunc($aPosition1, $aPosition2) {

		if($aPosition1[0]['position'] == $aPosition2[0]['position']) {
			return 0;
		} elseif($aPosition1[0]['position'] < $aPosition2[0]['position']) {
			return -1;
		} else {
			return 1;
		}
		
	}

	/**
	 * ENDE: Methoden für das Speichern von Unterpositionen in Rechnungen
	 */
	
	public function display($aOptionalData = array(), $bNoJavaScript=false){

		if(
			!empty($aOptionalData['js']) &&
			!is_array($aOptionalData['js'])
		) {
			$aOptionalData['js'] = array();
		}

		$aOptionalData['js'][] = '/admin/extensions/thebing/js/communication.js';
		$aOptionalData['js'][] = '/admin/extensions/thebing/js/document.js';
		
		$aOptionalData['js']	= array_merge($aOptionalData['js'], $this->_aJsFiles);
		
		$aOptionalData['js']	= array_merge(array('/admin/extensions/tc/gui2/gui2.js'), $aOptionalData['js']);

		// Da das hier JEDES Mal beim Öffnen eines Dialogs gebaut wird, läuft irgendwann der Speicher über
		$aOptionalData['js'] = array_unique($aOptionalData['js']);

		if(
			!empty($aOptionalData['css']) &&
			!is_array($aOptionalData['css'])
		) {
			$aOptionalData['css'] = array();
		}

		$aOptionalData['css'][]	= '/assets/ts/css/gui2.css';
		$aOptionalData['css']	= array_merge($aOptionalData['css'], $this->_aCssFiles);

		$aOptionalData['css'] = array_unique($aOptionalData['css']);

		//wenn das neue Design akzeptiert wird, dann ändern in parent::display :)
		Ext_Gui2::display($aOptionalData, $bNoJavaScript);

	}

	/*
	 * Fügt die GUI Standard-Spalten Hinzu
	 */
	public function addDefaultColumns($mFormat = null, $mWidth = null) {

		$oDefaultColumn = $this->getDefaultColumn();

		$aColumns = (array)$oDefaultColumn->getColumns();

		foreach($aColumns as $sField => $oColumn) {
			// Würde eigentlich in Parent passieren, aber die Methode ruft parent ja nicht auf…
			$this->setDefaultColumnGroup($oColumn);
			$this->setColumn($oColumn);
		}
	}

	/**
	 * Objekt um die getDefaultColumns zu konfigurieren
	 * @return Ext_TC_Gui2_DefaultColumn
	 */
	public function getDefaultColumn() {

		if(
			is_object($this->_oDefaultColumn) &&
			$this->_oDefaultColumn instanceof Ext_TC_Gui2_DefaultColumn
		) {
			$oDefaultColumn = $this->_oDefaultColumn;
		} else {
			$oDefaultColumn = new Ext_Thebing_Gui2_DefaultColumn($this->query_id_alias);
		}

		return $oDefaultColumn;
	}

	public function setDefaultColumn(Ext_TC_Gui2_DefaultColumn $oDefaultColumn)
	{
		$this->_oDefaultColumn = $oDefaultColumn;
	}

	public function getSimplePdfIcon($sDocType = 'additional_document'){

		$sImg = Ext_Thebing_Util::getIcon('pdf');
		$sTask = 'openDialog';
		$sTitle = $this->t('PDF erstellen');

		$oIcon = new Ext_Gui2_Bar_Icon($sImg, $sTask, $sTitle);
		$oIcon->label = $sTitle;
		$oIcon->action = 'additional_document';
		$oIcon->additional = '';

		return $oIcon;
	}

	public function addAdditionalDocumentsOptions(Ext_Thebing_Inquiry_Document_Additional $oInquiryAdditionalDocuments){

		if($oInquiryAdditionalDocuments->allow_multiple){
			$this->multiple_selection = 1;
		}

		$mRowIconClass = $oInquiryAdditionalDocuments->icon_status_active;

		if($mRowIconClass) {
			if(is_object($mRowIconClass)){
				$this->row_icon_status_active = $mRowIconClass;
			}elseif(is_string($mRowIconClass)){
				$this->row_icon_status_active = new $mRowIconClass();
			}
		} else {
			$this->row_icon_status_active = new Ext_Thebing_Gui2_Icon_Inbox();
		}

		$iBarPosition=$oInquiryAdditionalDocuments->icons_bar_position;
		if(!$iBarPosition){
			
			// Bar mit den anderen Icons finden
			$bFound = false;
			foreach($this->_aBar as $iPosition => $oBar) {

				$aElements = $oBar->getElements();
				// alle Elemente laufen und "new"-Icon suchen
				foreach ($aElements as $oElement) {
					if(
						$oElement instanceof Ext_Gui2_Bar_Icon &&
						$oElement->action == 'new'
					) {
						$iBarPosition = $iPosition;
						$bFound = true;
						break 2;
					}
				}
			}
			
			if(!$bFound) {
				$iBarPosition = 1;
			}
		}
		$bIconsFirst = $oInquiryAdditionalDocuments->icons_at_first_pos;

		if(isset($this->_aBar[$iBarPosition])){
			
			if(!$bIconsFirst){
				$oBar = $this->_aBar[$iBarPosition];
			}else{
				$oBar = $this->createBar();
			}

			$sAccessDocumentEdit = $oInquiryAdditionalDocuments->access_document_edit;
			$sAccessDocumentOpen = $oInquiryAdditionalDocuments->access_document_open;
			
			$bAccessDocumentEdit = true;
			$bAccessDocumentOpen = true;
			if($sAccessDocumentEdit !== null) {
				$bAccessDocumentEdit = Ext_Thebing_Access::hasRight($sAccessDocumentEdit);
			}
			if($sAccessDocumentOpen !== null) {
				$bAccessDocumentOpen = Ext_Thebing_Access::hasRight($sAccessDocumentOpen);
			}
			
			$sTemplateType = $oInquiryAdditionalDocuments->use_template_type;

			if(
				$oInquiryAdditionalDocuments->add_label_group &&
				(
					$bAccessDocumentEdit ||
					$bAccessDocumentOpen
				)
			){
				$oLabelGroup	= $oBar->createLabelGroup($this->t('Dokumente'));
				$oBar->setElement($oLabelGroup);
			}

			if($sTemplateType) { 
				$sRequestData = '&template_type='.$sTemplateType;
				// Individuelle Data-Klasse für den Dokumenten-Dialog
				$sDataClass = $oInquiryAdditionalDocuments->data_class;
				if(!empty($sDataClass)) {
					$sRequestData .= '&data_class='.$sDataClass;
				}
			}

			if($bAccessDocumentEdit) {
				$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('page_edit'), 'openDialog', $this->t('Dokumente editieren'));
				$oIcon->action = 'additional_document';
				$oIcon->active = 0;
				if($sTemplateType){ 
					$oIcon->request_data = $sRequestData.'&bNoDocumentId=1';
				}
				$oBar ->setElement($oIcon);
			}

			if($bAccessDocumentOpen) {
				$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('pdf'), 'request', $this->t('Dokument PDF öffnen'));
				$oIcon->info_text = 1;
				$oIcon->request_data = '&action=openDocumentPdf';
				if($sTemplateType){ 
					$oIcon->request_data .= $sRequestData;
				}
				$oIcon->active = 0;
				$oIcon->action = 'openDocumentPdf';
				$oBar ->setElement($oIcon);
			}

			if($bIconsFirst) {

				$oCurrentBar	= $this->_aBar[$iBarPosition];

				$aElementsAdded		= (array)$oBar->getElements();
				$aElementsBefore	= (array)$oCurrentBar->getElements();
				$aElementsAll		= array_merge($aElementsAdded,$aElementsBefore);

				$oNewBar			= $this->createBar();
				foreach($aElementsAll as $mElement){
					$oNewBar->setElement($mElement);
				}

				$this->_aBar[$iBarPosition] = $oNewBar;

			}

		}

		if($oInquiryAdditionalDocuments->add_language_filter){

			$iBarPosition=$oInquiryAdditionalDocuments->filter_bar_position;
			if(!$iBarPosition){
				$iBarPosition = 0;
			}

			if(isset($this->_aBar[$iBarPosition])){
				$oBar = $this->_aBar[$iBarPosition];

				$aCorrespondenceLanguages	= Ext_Thebing_Data::getCorrespondenceLanguages(true);
				asort($aCorrespondenceLanguages);
				
				$oFilter				 = $oBar->createFilter('select');

				if($oInquiryAdditionalDocuments->filter_label_mode == 'inner') {
					$aCorrespondenceLanguages = Ext_Gui2_Util::addLabelItem($aCorrespondenceLanguages, $this->t('Korrespondenzsprache'));
				} else {
					//$oFilter->label			 = $this->t('Korrespondenzsprache');
					$aCorrespondenceLanguages = Ext_Gui2_Util::addLabelItem($aCorrespondenceLanguages, Ext_Thebing_L10N::getEmptySelectLabel('correspondence_language'));
				}

				$oFilter->id			 = 'language_filter';
				$oFilter->value			 = '';
				$oFilter->db_column		 = 'corresponding_language';
				$oFilter->db_alias		 = '';
				$oFilter->select_options = $aCorrespondenceLanguages;

				$oBar ->setElement($oFilter);

				if($oInquiryAdditionalDocuments->corresponding_language_column) {
					$oColumn = $this->createColumn();
					$oColumn->db_column = 'corresponding_language';
					$oColumn->title = $this->t('Korrespondenzsprache');
					$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('name');
					$oColumn->width_resize = false;
					$oColumn->small = true;
					$oColumn->group = $oInquiryAdditionalDocuments->column_group_corresponding_language;
					$oColumn->format = new \Ext_Thebing_Gui2_Format_CorrespondingLanguage();
					$oColumn->default = false;
					$this->setColumn($oColumn);
				}

			}
		}

		if($oInquiryAdditionalDocuments->include_js_files){
			$this->addJs('gui2/util.js');
			$this->addJs('gui2/payment.js');
			$this->addJs('gui2/studentlists.js');
		}
		
		$this->class_js = $oInquiryAdditionalDocuments->class_js;

	}

	public function addCss($sCss, $sMainFolder = 'thebing'){
		$this->_aCssFiles[] = '/admin/extensions/' .$sMainFolder. '/'.$sCss;
	}

	public function addJs($sJs, $sMainFolder = 'thebing'){
		$this->_aJsFiles[] = '/admin/extensions/' .$sMainFolder. '/'.$sJs;
	}

	public function getValidityGui($sParentType, $sItemType, $sItemTitle)
	{
		$oValidity = new Ext_Thebing_Validity($this, $sParentType, $sItemType);
		$oValidity->setItemTitle($sItemTitle);
		return $oValidity->getValidityGui();
	}

	/**
	 * Gruppiere die einzelnen Unterpositionen immer nach Beschreibung & Rabatt
	 * Diese Funktion wird nur für die Gruppenrechnungen benötigt, ist aber kompatibel mit
	 * den Einzelrechnungen
	 * @param int $iPositionKey 
	 */
	public function groupPositionsByDescriptionAndDiscount($iPositionKey)
	{
		$aSubPositions		= $this->getDocumentPosition($iPositionKey);

		foreach($aSubPositions as $iSubKey => $aSubPosition)
		{
			$fAmountDiscount	= (float)$aSubPosition['amount_discount'];
			
			//ersten position_key finden, wo beschreibung & rabatt übereinstimmt
			//dabei wird von jeder position_key die erste subposition verglichen
			$iKeyByDescription	= $this->_findFirstPositionKeyByDescriptionAndDiscount($aSubPosition['description'], $fAmountDiscount, $iPositionKey);

			if(
				//wenn key nicht gefunden oder key stimmt mit der jetzigen nicht überein
				$iKeyByDescription === false ||
				$iPositionKey != $iKeyByDescription
			){
				//key nicht gefunden
				if(
					$iKeyByDescription === false
				){ 
					//generiere eine neue position nummer
					$iKeyByDescription = (int)$this->getNewPositionKeyNumber();
					//key nirgendswo gefunden, neue dokument position erstellen ** splitten
					$this->setDocumentPosition($iKeyByDescription, array());
				}

				//position key auch in der subposition aktualisieren, wird beim
				//html generieren benötigt 
				$aSubPosition['position_key'] = $iKeyByDescription;
				
				//den instance cache aktualisieren, entweder in eine neue position verschieben
				//oder in ein vorhandenes
				$this->addDocumentPositionItem($iKeyByDescription, $aSubPosition);

				//aus der jetzigen position entfernen
				$this->deleteDocumentPositionItem($iPositionKey, $iSubKey);
			}
		}

	}
	
	/**
	 * Die erste übereinstimmende position finden, zu der Beschreibung & Rabatt passt
	 * @param string $sDescription
	 * @param float $fAmountDiscount
	 * @param int $iPositionKeySelf
	 * @return mixed | false wenn nichts gefunden oder int wenn gefunden
	 */
	protected function _findFirstPositionKeyByDescriptionAndDiscount($sDescription, $fAmountDiscount, $iPositionKeySelf)
	{
		$aPositionKeys		= array();
		
		//Positionen immer aus der Instance laden, falls innerhalb der Schleife
		//Aktualisierungen vorgenommen werden
		
		$aPositionsInstance = (array)$this->getDocumentPositions();
		
		//key zum vergleichen bilden
		$sSelfKey			= $sDescription . '_' . $fAmountDiscount;

		foreach($aPositionsInstance as $iPositionKey => $aSubPositions)
		{
			//nur die erste Unterposition vergleichen, sonst würde man immer was finden
			$aFirstSubPosition	= reset($aSubPositions);
			
			$fAmountDiscount	= (float)$aFirstSubPosition['amount_discount'];

			//key für die jetzige unterposition
			$sPositionKey		= $aFirstSubPosition['description'] . '_' . $fAmountDiscount;
			
			//wenn gefunden merke dir den position_key
			if(
				$sPositionKey == $sSelfKey
			){
				$aPositionKeys[$iPositionKey] = $iPositionKey;
			}
		}
		
		if(
			count($aPositionKeys) > 1
		){
			//wenn mehrfach gefunden, dann hat eine andere Position bereits 
			//die gleiche Beschreibung& den gleichen Rabatt, eigenen Positionkey unsetten,
			//damit die andere position nummer zurück gegeben wird, damit die Funktion
			//wieder diese Unterpositionen vereinen kann
			unset($aPositionKeys[$iPositionKeySelf]);
		}
		
		if(
			!empty($aPositionKeys)
		){
			return reset($aPositionKeys);
		}else{
			return false;
		}
		
	}
	
	/**
	 * neue Position Key Nummer generieren
	 * @return int 
	 */
	public function getNewPositionKeyNumber()
	{
		$aKeys		= array_keys((array)$this->_aDocumentPositions);
		$iMaxKey	= max($aKeys);
		$iNewKey	= $iMaxKey + 1;
		
		return $iNewKey;
	}

	/**
	 * prüft, ob Filtersets gesetzt werden sollen
	 * 
	 * @global array $user_data
	 * @param array $aUserGroups
	 * @return boolean
	 */
	protected function _checkDesignFilterBarAccess(array $aUserGroups) {
		
		$oAccess = Access_Backend::getInstance();

        $bAccess = false;

		if($oAccess) {

            $aUserData = $oAccess->getUserData();

            if($aUserData['client']){
                $oAccess	= Ext_Thebing_Access::getInstance();

                if(!Ext_Thebing_System::isAllSchools()) {
                    // Aktuelle Schule nehmen
                    $oSchool = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
                } else {
                    // #5358 - In der All-Schools-Ansicht wurde immer die erste Schule genommen..wenn man für
                    // diese aber kein Recht hatte, wurden die Filter nicht angezeigt
                    $oSchool = Ext_Thebing_Client::getFirstSchoolWithAccess();
                }

                if($oSchool) {
                    // Prüfen, ob die Filter für die Benutzergruppe der Schule freigeschaltet ist
                    $iGroup		= (int)$oAccess->getGroupOfSchool($oSchool->id);
                    $bAccess	= in_array($iGroup, $aUserGroups);
                }
            } else {
                $bAccess = parent::_checkDesignFilterBarAccess($aUserGroups);
            }
        }
		
		return $bAccess;
	}

	/**
	 * @inheritdoc
	 */
	public static function manipulateDefaultConfig(array &$aConfig) {

		parent::manipulateDefaultConfig($aConfig);

		$aConfig['class']['gui'] = 'Ext_Thebing_Gui2';
		$aConfig['class']['data'] = 'Ext_Thebing_Gui2_Data';
		$aConfig['class']['date_format'] = 'Ext_Thebing_Gui2_Format_Date';

		foreach($aConfig['default_columns'] as &$aColumn) {
			if(
				isset($aColumn['data']) &&
				(
					$aColumn['data'] == 'created' ||
					$aColumn['data'] == 'changed'
				)
			) {
				$aColumn['format'] = 'Ext_Thebing_Gui2_Format_Date_DateTime';
			}
		}

	}

	/**
	 * @inheritdoc
	 */
	public static function getIndexEntityMapping() {
		return [
			Ext_TS_Inquiry::class => 'ts_inquiry',
			Ext_TS_Enquiry::class => 'ts_enquiry',
			Ext_Thebing_Inquiry_Document::class => 'ts_document',
			Ext_Thebing_Inquiry_Group::class => 'ts_inquiry_group'
		];
	}
	
	static public function manipulateApiRequest(\MVC_Request $request) {
		
		if(!$request->has('school_id')) {
			return;
		}

		$oSession = Core\Handler\SessionHandler::getInstance();
		$oSession->set('sid', $request->input('school_id'));
		
	}
}

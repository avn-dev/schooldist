<?php

/**
 * Class Ext_Thebing_Gui2_Comments
 */
class Ext_Thebing_Gui2_Comments {

	/**
	 * @var string
	 */
	protected $_sHashName;

	/**
	 * @var string
	 */
	protected $_sWDBasicClass;

	/**
	 * @var string
	 */
	protected $_sUploadRight;

	/**
	 * @var string
	 */
	protected $_sUploadPath;

	/**
	 * @var string
	 */
	protected $_sDataClass;

	/**
	 * @var string
	 */
	protected $_sContactSelectionClass;

	/**
	 * @var string
	 */
	protected $sUsedFrom = '';

	/**
	 * @param string $sHashName
	 * @param string $sWDBasicClass
	 * @param string $sUsedFrom
	 */
	public function __construct($sHashName, $sWDBasicClass, $sUsedFrom = '') {
		$this->_sHashName = $sHashName;
		$this->_sWDBasicClass = $sWDBasicClass;
		$this->sUsedFrom = $sUsedFrom;
	}

	/**
	 * @param string $sDataClass
	 * @return void
	 */
	public function setGui2DataClass($sDataClass) {
		$this->_sDataClass = $sDataClass;
	}

	/**
	 * @param string $sRight
	 * @return void
	 */
	public function setUploadRight($sRight) {
		$this->_sUploadRight = $sRight;
	}

	/**
	 * @param string $sPath
	 * @return void
	 */
	public function setUploadPath($sPath) {
		$this->_sUploadPath = $sPath;
	}

	/**
	 * @param string $sContactSelectionClass
	 * @return void
	 */
	public function setContactSelectionClass($sContactSelectionClass) {
		$this->_sContactSelectionClass = $sContactSelectionClass;
	}

	/**
	 * Gibt die Gui für die Notizen der Agentur zurück
	 *
	 * @return Ext_Thebing_Gui2
	 */
	public function get() {

		$oGuiComments = new Ext_Thebing_Gui2(md5($this->_sHashName), $this->_sDataClass);
		$oGuiComments->gui_description 	= 'Thebing » Kommentare';

		$oSubject = Ext_Thebing_Marketing_Subject::getInstance();
        $aSubjectsSearch = $oSubject->getList(Ext_Thebing_Client::getInstance()->id, true);
		$aSubjectsSearch =  \Ext_Gui2_Util::addLabelItem($aSubjectsSearch, $oGuiComments->t('Betreff'));

		$oActivity = Ext_Thebing_Marketing_Activity::getInstance();
		$aActivities = $oActivity->getList(Ext_Thebing_Client::getInstance()->id, true);
		$aActivities =  \Ext_Gui2_Util::addLabelItem($aActivities, $oGuiComments->t('Aktivität'));

		$oEntity = new $this->_sWDBasicClass();
		$sTableAlias = $oEntity->getTableAlias();

		$oGuiComments->setWDBasic($this->_sWDBasicClass);
		$oGuiComments->setTableData('where', array(''.$sTableAlias.'.active' => 1));
		$oGuiComments->setTableData('limit', 30);
		$oGuiComments->setTableData('orderby', array(''.$sTableAlias.'.created' => 'DESC'));

		// Listenoptionen
		$oGuiComments->gui_title			= $oGuiComments->t('Kommentare');
		$oGuiComments->column_sortable		= 1;
		$oGuiComments->row_sortable			= 0;
		$oGuiComments->multiple_selection	= 0;
		$oGuiComments->query_id_column		= 'id';
		$oGuiComments->query_id_alias		= $sTableAlias;

		$oGuiComments->row_style = new \TsCompany\Gui2\Style\Comments();

		$oDialogComments = $oGuiComments->createDialog($oGuiComments->t('Kommentar "{title}" editieren'), $oGuiComments->t('Kommentar anlegen'));

		$oDialogComments->setElement($oDialogComments->createRow($oGuiComments->t('Titel'), 'input', array(
				'db_alias'			=> $sTableAlias,
				'db_column'			=> 'title',
				'required'			=> 1,
		)));

		$oDialogComments->setElement($oDialogComments->createRow($oGuiComments->t('Betreff'), 'select', array(
				'db_alias'			=> $sTableAlias,
				'db_column'			=> 'subject_id',
				'required'			=> 0,
				'select_options'	=> \Util::addEmptyItem($aSubjectsSearch ),
		)));

		$oDialogComments->setElement($oDialogComments->createRow($oGuiComments->t('Aktivität'), 'select', array(
				'db_alias'			=> $sTableAlias,
				'db_column'			=> 'activity_id',
				'required'			=> 0,
				'select_options'	=> \Util::addEmptyItem($aActivities),
		)));

		$oDialogComments->setElement($oDialogComments->createRow($oGuiComments->t('Text'), 'textarea', array(
				'db_alias'			=> $sTableAlias,
				'db_column'			=> 'text',
				'required'			=> 1,
				'style'				=> 'height: 150px;'
		)));

		if($this->_sContactSelectionClass !== null) {
			$oDialogComments->setElement($oDialogComments->createRow($oGuiComments->t('Kontakt'), 'select', array(
					'db_alias'			=> $sTableAlias,
					'db_column'			=> 'company_contact_id',
					'required'			=> 0,
					'selection'			=> new $this->_sContactSelectionClass(),
			)));
		}

        $oUpload = new Ext_Gui2_Dialog_Upload(
            $oGuiComments,
            $oGuiComments->t('Dokumente'),
            $oDialogComments,
            'documents',
            $sTableAlias,
            $this->_sUploadPath
        );
        $oUpload->multiple = 1;
        $oUpload->bAddColumnData2Filename = 0;
        $oDialogComments->setElement($oUpload);

		if(in_array($this->sUsedFrom, ['company', 'agency'])) {
			$oDialogComments->setElement($oDialogComments->create('h4')->setElement($oGuiComments->t('Nachhaken')));

			$oDialogComments->setElement($oDialogComments->createRow(L10N::t('Nachhaken'), 'calendar', [
				'db_alias' => $sTableAlias,
				'db_column' => 'follow_up',
				'format' => new Ext_Thebing_Gui2_Format_Date(),
			]));
		}

		// Toggle
		$oBar = $oGuiComments->createBar();
		$oBar->width = '100%';

		// Suche
		$oFilter = $oBar->createFilter();
		$oFilter->db_column = array('id', 'title', 'text', 'firstname', 'lastname');
		$oFilter->db_alias = array($sTableAlias, $sTableAlias, $sTableAlias, 'ts_ac', 'ts_ac');
		$oFilter->db_operator = 'LIKE';
		$oFilter->id = 'search';
		$oFilter->placeholder = $oGuiComments->t('Suche').'…';
		$oBar->setElement($oFilter);

		$oBar->setElement($oBar->createSeperator());

		// subject_filter
		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'subject_filter';
		$oFilter->value	= '';
		$oFilter->db_column	= 'subject_id';
		$oFilter->select_options = $aSubjectsSearch;
		$oBar->setElement($oFilter);

		// activity_filter
		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'activity_filter';
		$oFilter->value	= '';
		$oFilter->db_column = 'activity_id';
		$oFilter->select_options	= $aActivities;
		$oBar->setElement($oFilter);

		if(in_array($this->sUsedFrom, ['company', 'agency'])) {
			// followup_filter
			$aOptions = self::getFollowupOptions();
			$aOptions = Util::addEmptyItem($aOptions, '-- '.$oGuiComments->t('Nachhaken').' --');

			$oFilter = $oBar->createFilter('select');
			$oFilter->value = '';
			$oFilter->db_column = 'follow_up';
			$oFilter->select_options = $aOptions;
			$oFilter->filter_query = [
				'follow_up_due' => "`kaco`.`follow_up` != '0000-00-00' AND `kaco`.`follow_up` < NOW() ",
				'follow_up_entered' => "`kaco`.`follow_up` != '0000-00-00'",
			];
			$oBar->setElement($oFilter);
		}

		$oGuiComments->setBar($oBar);

		// Buttons
		$oBar = $oGuiComments->createBar();
		$oBar->width = '100%';
		/*$oLabelGroup = $oBar->createLabelGroup($oGuiComments->t('Aktionen'));
		$oBar->setElement($oLabelGroup);*/

		// Das Recht muss in eine Variable ausgelagert werden, da die Agenturliste ebenfalls die Lehrerverwaltung
		// auf diese Klasse zugreift bzw. sie verwendet. Vorher wurde nur das Agenturrecht geprüft! Das hat in der
		// in der Lehrerverwaltung Probleme verursacht, wenn man das Recht nicht hat.
		if($this->sUsedFrom === 'teacher') {
			$sAccessRight = 'thebing_tuition_resource_teachers_crm';
		} else {
			$sAccessRight = $this->_sUploadRight;
		}

		if(Ext_Thebing_Access::hasRight($sAccessRight)) {

			$oIcon = $oBar->createNewIcon($oGuiComments->t('Neuer Eintrag'), $oDialogComments, $oGuiComments->t('Neuer Eintrag'));
			$oBar->setElement($oIcon);
			$oIcon = $oBar->createEditIcon($oGuiComments->t('Editieren'), $oDialogComments, $oGuiComments->t('Editieren'));
			$oBar->setElement($oIcon);
			$oIcon = $oBar->createDeleteIcon($oGuiComments->t('Löschen'), $oGuiComments->t('Löschen'));
			$oBar->setElement($oIcon);

		} else {

			$oDialogComments->bReadOnly = true;

			$oIcon = $oBar->createIcon(Ext_TC_Util::getIcon('info'), 'openDialog', $oGuiComments->t('Anzeigen'));
			$oIcon->action = 'edit';
			$oIcon->label = $oGuiComments->t('Anzeigen');
			$oIcon->dialog_data = $oDialogComments;
			$oBar ->setElement($oIcon);	

		}

		$oGuiComments->setBar($oBar);

		// Paginator
		$oBar = $oGuiComments->createBar();
		$oBar->width = '100%';
		$oBar->position	= 'top';
		$oPagination = $oBar->createPagination();
		$oBar->setElement($oPagination);

		if(Ext_Thebing_Access::hasRight("thebing_marketing_agencies_notes_edit")) {
			// CSV
			$oLabelGroup = $oBar->createLabelGroup($oGuiComments->t('Export'));
			$oBar->setElement($oLabelGroup);

			$oIcon = $oBar->createCSVExport($oGuiComments->t('Export CSV'));
			$oIcon->label = $oGuiComments->t('CSV');
			$oBar->setElement($oIcon);

			$oIcon = $oBar->createExcelExport();
			$oBar->setElement($oIcon);
		}

		$oLoading = $oBar->createLoadingIndicator();
		$oBar->setElement($oLoading);
		$oGuiComments->setBar($oBar);

		$oColumn = $oGuiComments->createColumn();
		$oColumn->db_column 	= 'upload';
		$oColumn->select_column = 'documents';
		$oColumn->db_alias 		= '';
		$oColumn->title 		= $oGuiComments->t('Datei');
		$oColumn->width 		= 50;
		$oColumn->width_resize 	= false;
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Pdf('documents', $this->_sUploadPath);
		$oColumn->sortable 		= 0;
		$oGuiComments->setColumn($oColumn);

		$oColumn				= $oGuiComments->createColumn();
		$oColumn->db_column		= 'title';
		$oColumn->db_alias		= $sTableAlias;
		$oColumn->title			= $oGuiComments->t('Titel');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= true;
		$oGuiComments->setColumn($oColumn);

		$oColumn				= $oGuiComments->createColumn();
		$oColumn->db_column		= 'text';
		$oColumn->db_alias		= $sTableAlias;
		$oColumn->title			= $oGuiComments->t('Text');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= true;
		$oColumn->css_overflow	= true;
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Nl2br();
		$oGuiComments->setColumn($oColumn);

		if($this->_sContactSelectionClass !== null) {
			$oColumn				= $oGuiComments->createColumn();
			$oColumn->db_column		= 'company_contact_id';
			$oColumn->db_alias		= $sTableAlias;
			$oColumn->title			= $oGuiComments->t('Ansprechpartner');
			$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('person_name');
			$oColumn->width_resize	= false;
			$oColumn->format		= new Ext_Thebing_Gui2_Format_Name();
			$oGuiComments->setColumn($oColumn);
		}

		$oColumn				= $oGuiComments->createColumn();
		$oColumn->db_column		= 'subject';
		$oColumn->db_alias		= '';
		$oColumn->title			= $oGuiComments->t('Betreff');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= false;
		$oGuiComments->setColumn($oColumn);

		$oColumn				= $oGuiComments->createColumn();
		$oColumn->db_column		= 'activity';
		$oColumn->db_alias		= '';
		$oColumn->title			= $oGuiComments->t('Aktivität');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= false;
		$oGuiComments->setColumn($oColumn);

		if(in_array($this->sUsedFrom, ['company', 'agency'])) {
			$oColumn = $oGuiComments->createColumn();
			$oColumn->db_column = 'follow_up';
			$oColumn->db_alias = $sTableAlias;
			$oColumn->title = $oGuiComments->t('Nachhaken');
			$oColumn->format = new Ext_Thebing_Gui2_Format_Date();
			$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('date');
			$oGuiComments->setColumn($oColumn);
		}

		$oDefaultColumn = $oGuiComments->getDefaultColumn();
		$oDefaultColumn->getSystemUsersById();
		$oDefaultColumn->setAliasForAll($sTableAlias);
		$oGuiComments->setDefaultColumn($oDefaultColumn);

		if(in_array($this->sUsedFrom, ['company', 'agency'])) {
			$oLegendBar = $oGuiComments->createBar('legend');
			$oLegendBar->addTitle($oGuiComments->t('Legende'));
			$oLegendBar->addInfo($oGuiComments->t('Nachhaken'), Ext_Thebing_Util::getColor('red'), false);
			$oGuiComments->setBar($oLegendBar);
		}

		$oGuiComments->addDefaultColumns();

		return $oGuiComments;
	}

	/**
	 * Gibt die Optionen für den Nachhake-Filter zurück
	 *
	 * @return array
	 */
	public static function getFollowupOptions() {
		return [
			'follow_up_due' => L10N::t('Nachhaken fällig'),
			'follow_up_entered' => L10N::t('Nachhaken eingetragen'),
		];
	}
	
}

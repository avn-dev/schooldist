<?php

/**
 * 
 */
class GUI_Ajax_Table {

	/** @var int shows current Table Num */
	private $_iTableIndex = 1;
	
	/** @var string standard JS path */
	protected $sJsPath = '/admin/extensions/gui/gui/ajax/table.js.php';
	
	/** @var array The Config Data Array */
	protected $aConfigData = array();
	/** @var array The Header(Column) Data Array */
	protected $aHeaderData = array();
	/** @var array The Icon Data Array */
	protected $aIconData = array();
	/** @var array The Ajax Data Array */
	protected $aAjaxData = array();
	/** @var array The Ajax Data Array */
	protected $aLayoutData = array();
	/** @var array The EditDialog Data Array */
	protected $aEditDialogData = array();
	/** @var array The EditDialog Data Array */
	protected $aQueryData = array();
	/** @var array The aDialogData Data Array */
	protected $aDialogData = array();
	protected $sEditId = 'id';
	protected $aEditSetting = array();
	/** @var string The Hash String */
	protected $sHash = "";
	/** @var string The Ajax Parameter String */
	protected $sAjaxParam = "";
	/** @var flex header Data Array */
	protected $aHeaderDataOrig = array();
	/** @var string SQL order direction */
	protected $sOrder = 'ASC';
	protected $sOrderField = 'id';

	protected $sHtmlPath = '';
	
	/** @var Show Left Frame */
	public $iShowLeftFrame = 1;
	
	/**
	 * @var array result of split_sql()
	 */
	protected $aSqlString = array();

	protected $iPaginationTotal = 0;
	protected $iPaginationOffset = 0;
	protected $iPaginationEnd = 0;
	protected $iPaginationShow = 0;

	static $sSeparator = ';';

	public function convertDateToTimestamp($sDate, $bMySQLFormat=false) {
			return strtotimestamp($sDate, $bMySQLFormat);		
	}
	public function convertTimestampToDate($sDate){
			return strftime('%x', $sDate);		
	}
	public function convertTimestampToDateTime($sDate){
			return strftime('%x %X', $sDate);		
	}
	public function convertTimestampToTime($sDate){
			return strftime('%X', $sDate);		
	}

	public $sRandString = "rand_0";

	/**
	 * Set the Config Array Global
	 * Checkt the Array Data
	 * Gernarte the Hash
	 * and defined the Session Data Array
	 */
	public function __construct($aConfigData = null,$sHash = ""){

		$this->sHash = $sHash;

		// set table index number ///////////////////
		// Erforderlich für Flex II und muss bei mehr als 1 Tabelle/Seite manuell gesetzt werden!!
		if ( 
		     isset($aConfigData['layout_data']['tableIndex']) && 
		    !empty($aConfigData['layout_data']['tableIndex'])  && 
		    ( (int)$aConfigData['layout_data']['tableIndex'] > 0 ) 
		) {
		    $this->setTableIndex( (int)$aConfigData['layout_data']['tableIndex'] );
		} else {
		    $this->setTableIndex( 1 );
		}
		// increase table index Num
		//self::$iTable++;
		///////////////////////////////////////////////
		
		if($sHash == ""){
			$this->_generateHash();
			
			// save Column Data for flexible columns
			$this->saveColumnData($aConfigData);
			// Temp Array needet in this fkt 4 "flexibleList"
			$aHeaderDataFlex = $aConfigData['header_data'];

			$aConfigData['header_data_org'] = $aConfigData['header_data'];

			if($aConfigData['layout_data']['flexible'] == 1) {
				foreach($aHeaderDataFlex as $iKey => $aColumn){
					// online check Colums that are NOT fix!!
					if( $aColumn['flexible'] !== 0 ){

						// check if column is visible
						$bCheck = $this->checkColumnData( $aColumn ,false);

						if(!$bCheck){
							// unset unvisible Columns
							unset($aHeaderDataFlex[$iKey]);
						}
					}
				}
			}

			// Sort Temp Header Array
			$aConfigData['header_data'] = $this->sortHeaderFlexible($aHeaderDataFlex);

		} elseif($sHash != "" && $aConfigData == null) {
			$aConfigData = $_SESSION['gui']['ajax_table'][$this->sHash];
		}
		$this->sAjaxParam .= "+'&hash=".$this->sHash."'";

		$this->sAjaxParam .= $aConfigData['ajax_data']['ajax_param'];


		$this->aIconData = $aConfigData['icon_data'];
		$this->aAjaxData = $aConfigData['ajax_data'];
		$this->aLayoutData = $aConfigData['layout_data'];
		$this->aQueryData = $aConfigData['query_data'];
		$this->aHeaderData = $aConfigData['header_data'];
		$this->aHeaderDataOrig = $aConfigData['header_data_org'];
		$this->aEditData = $aConfigData['edit_data'];
		$this->aDialogData = $aConfigData['dialog_data'];
		$this->sRandString = $aConfigData['random'];
		$this->sEditId = $aConfigData['edit_id'];
		$this->aEditSetting = $aConfigData['edit_settings'];
		$this->sHtmlPath = $aConfigData['html_path'];
		$this->_check();

		$this->_defineConfig();

		$this->aSqlString = $this->split_sql();
		$_SESSION['gui']['ajax_table'][$this->sHash] = $this->aConfigData;
	}

	public function export($sTitle = '', $sType = 'CSV'){

		$aArray = $this->getTableListData();
				
		$aExport = array();
		$i = 1;
		
		foreach((array)$this->aHeaderData as $aHeader){
			$aExport[0][] = $aHeader['value'];
		}
		
		foreach((array)$aArray['data'] as $iKey => $aData){
			$ii = 1;
			foreach($this->aHeaderData as $aHeader){
				$aExport[$i][] = $aData[$ii];
				$ii++;
			}
			$i++;
		}

		if($sType == 'CSV'){
			self::exportCSV($sTitle, $aExport);
		} else {
			self::exportXLS($sTitle, $aExport);
		}

	}
		
	public static function exportXLS($sName, &$aExport, $aSpecials=array()) {

		while(ob_get_level() > 0) {
			ob_end_clean();
		}

		if(empty($sName)) {
			$sName = 'Export';
		}
		
		require_once 'Spreadsheet/Excel/Writer.php';

		$oWorkbook = new Spreadsheet_Excel_Writer();
		$oWorkbook->send(\Util::getCleanFileName($sName).'.xls');

		$oWorksheet =& $oWorkbook->addWorksheet("Data");
		
		$oWorksheet->setColumn(0, count($aExport[0])-1, 20);
		
		$oIntFormat =& $oWorkbook->addFormat();
		$oIntFormat->setNumFormat('#');
		
		$oFloatFormat =& $oWorkbook->addFormat();
		$oFloatFormat->setNumFormat('0.00');
		$oFloatFormat->setHAlign('right');		

		$oFormatTitle =& $oWorkbook->addFormat();
		$oFormatTitle->setBold();	
		
		$oIntHighlightRow =& $oWorkbook->addFormat();
		$oIntHighlightRow->setFgColor('red');
		$oIntHighlightRow->setNumFormat('#');
		
		$oFloatHighlightRow =& $oWorkbook->addFormat();
		$oFloatHighlightRow->setFgColor('red');
		$oFloatHighlightRow->setNumFormat('0.00');
		$oFloatHighlightRow->setHAlign('right');

		$oHighlightRow =& $oWorkbook->addFormat();
		$oHighlightRow->setFgColor('red');
		
		$iRow = 0;
		foreach((array)$aExport as $aLine) {
			$oFormat = false;
			$bHighlight = false;

			if($iRow == 0) {
				$oFormat = $oFormatTitle;
			} elseif(array_key_exists('highlight_empty', $aSpecials)) {

				foreach((array)$aSpecials['highlight_empty'] as $iCol) {
					if(empty($aLine[$iCol])) {
						$bHighlight = true;
					}
				}

			}

			$iCol = 0;
			foreach((array)$aLine as $mValue) {

				$oFormat = false;
				
				if(is_float($mValue)) {
					$oFormat = $oFloatFormat;
					$oWorksheet->write($iRow, $iCol, $mValue, $oFormat);
				} elseif(is_int($mValue)) {
					$oFormat = $oIntFormat;
					$oWorksheet->write($iRow, $iCol, $mValue, $oFormat);
				} elseif(is_numeric($mValue)) {
					$oWorksheet->write($iRow, $iCol, $mValue, $oFormat);
				} else {
					$oWorksheet->write($iRow, $iCol, $mValue, $oFormat);					
				}

				$iCol++;
			}
			$iRow++;
		}

		$oWorkbook->close();

		die();
		
	}
	
	public static function exportCSV($sTitle = 'export',$aExport = array()){
		
		while(ob_get_level() > 0) {
			ob_end_clean();
		}
		
		if(empty($sTitle)) {
			$sTitle = 'Export';
		}
		
		header('Content-Disposition: inline; filename="'.\Util::getCleanFileName($sTitle).'.csv"');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Type: text/x-comma-separated-values');

		foreach((array)$aExport as $aLine) {
			$sLine = "";
			foreach((array)$aLine as $mValue) {
				
				if(is_numeric($mValue)) {
					$sLine .= '' . strip_tags($mValue) . ''.self::$sSeparator;
				} else {
					$mValue = iconv('UTF-8', 'cp1252', $mValue);
					$sLine .= '"' . strip_tags($mValue) . '"'.self::$sSeparator;
				}
			}
			$sLine = substr($sLine, 0, (strlen(self::$sSeparator) * -1));
			$sLine .= "\n";
			echo $sLine;
		}
		
		die();
		
	}
	
	/**
	 * gernerate an new Hash for the Session
	 */
	protected function _generateHash(){
		
		$this->sHash = \Util::generateRandomString(16);
		
	}
	
	/**
	 * start Checking Header,Ajax and Icon Data Array
	 */
	protected function _check(){

		$this->_checkRand();
		$this->_checkLayoutData();
		$this->_checkAjaxData();
		$this->_checkIconData();
		$this->_checkQueryData();
		$this->_checkHeaderData();
		$this->_checkHeaderDataOrig();
		$this->_checkEditData();
		$this->_checkDialogData();
		$this->_checkEditId();
	}
	
	/**
	 * Marge the new Config Array
	 */
	protected function _defineConfig(){

		$this->aConfigData['random'] = $this->sRandString;
		$this->aConfigData['icon_data'] = $this->aIconData;
		$this->aConfigData['ajax_data'] = $this->aAjaxData;
		$this->aConfigData['layout_data'] = $this->aLayoutData;
		$this->aConfigData['query_data'] = $this->aQueryData;
		$this->aConfigData['header_data'] = $this->aHeaderData;
		$this->aConfigData['header_data_org'] = $this->aHeaderDataOrig;

		//$this->createHeaderData();
//		$this->aConfigData['edit_data'] = $this->getEditHeaderData();
		$this->aConfigData['edit_data'] = $this->aEditData;
		$this->aConfigData['dialog_data'] = $this->aDialogData;
		$this->aConfigData['edit_id'] = $this->sEditId;
		$this->aConfigData['edit_settings'] = $this->aEditSetting;
		$this->aConfigData['html_path'] = $this->sHtmlPath;
		if(!isset($this->aConfigData['html_path']) || empty($this->aConfigData['html_path'])){
			$this->aConfigData['html_path'] = $_SERVER['SCRIPT_NAME'];
		}
		
	}
	
	protected function _checkEditId() {
		if(empty($this->sEditId)){
			$this->sEditId = "id";
		}
	}
	
	protected function _checkRand(){
		
		if($this->sRandString == "rand_0" || $this->sRandString == NULL){
			$this->sRandString = "rand_".rand(1,100);
		}
	}
	
	
		/**
	 * Check the Config Array for the Query Options
	 */
	protected function _checkDialogData(){
		if(!$this->aDialogData['height']){
			$this->aDialogData['height'] = '300';
		}
		if(!$this->aDialogData['width']){
			$this->aDialogData['width'] = '500';
		}
		if(!$this->aDialogData['row_style']){
			$this->aDialogData['row_style'] = 'background-color:#EEEEEE; margin:5px;padding-left:5px;padding-right:5px;';
		}
		if(!$this->aDialogData['input_style']){
			$this->aDialogData['input_style'] = 'padding: 4px 4px 2px 4px;';
		}
		if(!$this->aDialogData['label_style']){
			$this->aDialogData['label_style'] = 'padding: 4px 0px; width: 100px;';
		}
		
		if(!isset($this->aDialogData['save_button'])){
			$this->aDialogData['save_button'] = 1;
		}
		
	}
	
	/**
	 * Check the Config Array for the Query Options
	 */
	protected function _checkQueryData(){
			
	
		// Define the SQL String
		if(!$this->aQueryData[0]){
			$this->aQueryData[0] = "SELECT `id` ,`name` FROM #table ";
		}
		// Define the SQL Array
		if(!$this->aQueryData[1]){
			$this->aQueryData[1] = array('table'=>'test_wielath');
		}
		// Define the Column of the ID
		if(!$this->aQueryData['filter']['id']['column']){
			$this->aQueryData['filter']['id']['column'] = "id";
		}
		// Define the ID Table Alias
		if(!$this->aQueryData['filter']['id']['alias']){
			$this->aQueryData['filter']['id']['alias'] = "";
		}
		// Define the FROM Column field
		if(!$this->aQueryData['filter']['from']['column']){
			$this->aQueryData['filter']['from']['column'] = "changed";
		}
		// Define the TO Column field
		if(!$this->aQueryData['filter']['to']['column']){
			$this->aQueryData['filter']['to']['column'] = "changed";
		}
		// Define the SEARCH Column field
		if(!$this->aQueryData['filter']['search']['column']){
			$this->aQueryData['filter']['search']['column'] = "name";
		}
	}

	/**
	 * Check the Config Array for the Query Options
	 */
	protected function _checkHeaderData(){
		if(empty($this->aHeaderData)){
			
			// Column in the DB 
			$this->aHeaderData[0]['column'] = 'id';
			// Alias of the Table in the DB
			$this->aHeaderData[0]['dbalias'] = '';
			// Display Value in the Header of the Table
			$this->aHeaderData[0]['value'] = '#';
			// Style of the Td
			$this->aHeaderData[0]['style'] = '';
			$this->aHeaderData[0]['sortColumn'] = array('id');
			$this->aHeaderData[0]['sortable'] = 1;
			
			
			$this->aHeaderData[1]['column'] = 'name';
			$this->aHeaderData[1]['dbalias'] = '';
			$this->aHeaderData[1]['value'] = 'Name';
			$this->aHeaderData[1]['style'] = '';
			$this->aHeaderData[1]['sortColumn'] = array('name');
			$this->aHeaderData[1]['sortable'] = 1;
			$this->aHeaderData[1]['flexible'] = 1;
			
		} else {

			foreach($this->aHeaderData as $key => $aValue){
				
				// default: all colums are flexible
				if($this->aHeaderData[$key]['flexible'] != 0 || !isset($this->aHeaderData[$key]['flexible'])){
					$this->aHeaderData[$key]['flexible'] = 1;
				}
				
				if(empty($this->aHeaderData[$key]['sortable']) || $this->aLayoutData['sortable'] == 1){
					$this->aHeaderData[$key]['sortable'] = 0;
				}
				
				if(!$this->aHeaderData[$key]['sortColumn']){
					if($aValue['dbalias'] != ''){
						$this->aHeaderData[$key]['sortColumn'] = array('`' . $aValue['dbalias'] . '`.`' . $aValue['column'] . '`');
					}else{
						$this->aHeaderData[$key]['sortColumn'] = array('`' . $aValue['column'] . '`');
					}
				} elseif($this->aLayoutData['sortable'] != 1){
					$this->aHeaderData[$key]['sortable'] = 1;
					$this->aHeaderData[$key]['sortColumn'] = (array)$this->aHeaderData[$key]['sortColumn'];
				}

			}

		}

	}
	protected function _checkHeaderDataOrig(){
		if(empty($this->aHeaderDataOrig)){
			
			// Column in the DB 
			$this->aHeaderDataOrig[0]['column'] = 'id';
			// Alias of the Table in the DB
			$this->aHeaderDataOrig[0]['dbalias'] = '';
			// Display Value in the Header of the Table
			$this->aHeaderDataOrig[0]['value'] = '#';
			// Style of the Td
			$this->aHeaderDataOrig[0]['style'] = '';
			$this->aHeaderDataOrig[0]['sortColumn'] = array('id');
			$this->aHeaderDataOrig[0]['sortable'] = 1;
			
			
			$this->aHeaderDataOrig[1]['column'] = 'name';
			$this->aHeaderDataOrig[1]['dbalias'] = '';
			$this->aHeaderDataOrig[1]['value'] = 'Name';
			$this->aHeaderDataOrig[1]['style'] = '';
			$this->aHeaderDataOrig[1]['sortColumn'] = array('name');
			$this->aHeaderDataOrig[1]['sortable'] = 1;
			$this->aHeaderDataOrig[1]['flexible'] = 1;
			
		} else {

			foreach($this->aHeaderDataOrig as $key => $aValue){
				
				// default: all colums are flexible
				if($this->aHeaderDataOrig[$key]['flexible'] != 0 || !isset($this->aHeaderDataOrig[$key]['flexible'])){
					$this->aHeaderDataOrig[$key]['flexible'] = 1;
				}
				
				if(empty($this->aHeaderDataOrig[$key]['sortable']) || $this->aLayoutData['sortable'] == 1){
					$this->aHeaderDataOrig[$key]['sortable'] = 0;
				}
				
				if(!$this->aHeaderDataOrig[$key]['sortColumn']){
					if($aValue['dbalias'] != ''){
						$this->aHeaderDataOrig[$key]['sortColumn'] = array('`' . $aValue['dbalias'] . '`.`' . $aValue['column'] . '`');
					}else{
						$this->aHeaderDataOrig[$key]['sortColumn'] = array('`' . $aValue['column'] . '`');
					}
				} elseif($this->aLayoutData['sortable'] != 1){
					$this->aHeaderDataOrig[$key]['sortable'] = 1;
					$this->aHeaderDataOrig[$key]['sortColumn'] = (array)$this->aHeaderDataOrig[$key]['sortColumn'];
				}

			}

		}

	}
	/**
	 * Check the Config Array for the Edit Options
	 */
	protected function _checkEditData(){
		if(!$this->aEditData){

			// Name of the Column in the Database
			$this->aEditData[1]['column'] = 'name';
			// Alias of the Table from the Column in the DB
			$this->aEditData[1]['dbalias'] = '';
			// Display Value in the Edit Dialog
			$this->aEditData[1]['value'] = 'Name';
			// Style of the Input
			$this->aEditData[1]['style'] = 'width:300px;';
			// Typ of Input
			$this->aEditData[1]['type'] = 'textarea';
			// Data array ( Optional for Select )
			$this->aEditData[1]['data_array'] = array('value1'=>'Test 1','value 2'=>'Test 2');
			
		} else {

			foreach($this->aEditData as $key => $aValue){
				if(!$this->aEditData[$key]['style']){
					$this->aEditData[$key]['style'] = '';
				}
				if(!$this->aEditData[$key]['type']){
					$this->aEditData[$key]['type'] = 'input';
				}
			}

		}
	}
	/**
	 * Check the Config Array for the Ajax Options
	 */
	protected function _checkAjaxData(){

		$this->aAjaxData['url'] = "'/admin/extensions/gui/gui/ajax/table.ajax.php'";
		
		// File for Toolbar Data
		if(!$this->aAjaxData['otherTableRandomString']){
			$this->aAjaxData['otherTableRandomString'] = "";
		}
		// File for Toolbar Data
		if(!$this->aAjaxData['class']){
			$this->aAjaxData['class'] = get_class($this);
		}
		// File for Toolbar Data
		if(!$this->aAjaxData['toolbar_url']){
			$this->aAjaxData['toolbar_url'] = "'/admin/extensions/gui/gui/ajax/table.ajax.php'";
		}
		if(!$this->aAjaxData['sort_url']){
			$this->aAjaxData['sort_url'] = "'/admin/extensions/gui/gui/ajax/table.ajax.php'";
		}
		if(!$this->aAjaxData['sort_param']){
			$this->aAjaxData['sort_param'] = "'task=save_sort'".$this->sAjaxParam."";
		}
		// Parametr for Toolbar File
		if(!$this->aAjaxData['toolbar_param']){
			$this->aAjaxData['toolbar_param'] = "'task=check_toolbar&row_id='+encodeURIComponent(intRowId)".$this->sAjaxParam;
		}
		// Method for Zoolbar File
		if(!$this->aAjaxData['toolbar_method']){
			$this->aAjaxData['toolbar_method'] = "post";
		}
		// File for Table Data
		if(!$this->aAjaxData['table_url']){
			$this->aAjaxData['table_url'] = "'/admin/extensions/gui/gui/ajax/table.ajax.php'";
		}
		// Parametr for Table File
		if(!$this->aAjaxData['table_param']){
			$this->aAjaxData['table_param'] = "'task=get_rows'".$this->sAjaxParam;
		}
		// Method for Table File
		if(!$this->aAjaxData['table_method']){
			$this->aAjaxData['table_method'] = "post";
		}
		// File for Edit Data
		if(!$this->aAjaxData['edit_url']){
			$this->aAjaxData['edit_url'] = "'/admin/extensions/gui/gui/ajax/table.ajax.php'";
		}
		// Parametr for Edit File
		if(!$this->aAjaxData['edit_param']){
			$this->aAjaxData['edit_param'] = "'task=edit_row'".$this->sAjaxParam;
		}
		// Method for Edit File
		if(!$this->aAjaxData['edit_method']){
			$this->aAjaxData['edit_method'] = "post";
		}
		// File for Save Data
		if(!$this->aAjaxData['save_url']){
			$this->aAjaxData['save_url'] = "'/admin/extensions/gui/gui/ajax/table.ajax.php'";
		}
		// Parametr for Save File
		if(!$this->aAjaxData['save_param']){
			$this->aAjaxData['save_param'] = "'task=save_row'".$this->sAjaxParam;
		}
		// Method for Save File
		if(!$this->aAjaxData['save_method']){
			$this->aAjaxData['save_method'] = "post";
		}
		// File for Delete Data
		if(!$this->aAjaxData['delete_url']){
			$this->aAjaxData['delete_url'] = "'/admin/extensions/gui/gui/ajax/table.ajax.php'";
		}
		// Parametr for Delete File
		if(!$this->aAjaxData['delete_param']){
			$this->aAjaxData['delete_param'] = "'task=delete_row'".$this->sAjaxParam;
		}
		// Method for Delete File
		if(!$this->aAjaxData['delete_method']){
			$this->aAjaxData['delete_method'] = "post";
		}
		// File for Delete Data
		if(!$this->aAjaxData['export_csv_url']){
			$this->aAjaxData['export_csv_url'] = "'/admin/extensions/gui/gui/ajax/table.ajax.php'";
		}
		// Parametr for Delete File
		if(!$this->aAjaxData['export_csv_param']){
			$this->aAjaxData['export_csv_param'] = "'task=export_csv'".$this->sAjaxParam;
		}
		// File for Delete Data
		if(!$this->aAjaxData['export_xls_url']){
			$this->aAjaxData['export_xls_url'] = "'/admin/extensions/gui/gui/ajax/table.ajax.php'";
		}
		// Parametr for Delete File
		if(!$this->aAjaxData['export_xls_param']){
			$this->aAjaxData['export_xls_param'] = "'task=export_xls'".$this->sAjaxParam;
		}
	}
		
	/**
	 * Check the Config Array for Layout
	 */
	protected function _checkLayoutData(){
		// Table/List Layout
		if(!$this->aLayoutData['list']){
			$this->aLayoutData['list']['show'] = 1;
		}
		
		if(!$this->aLayoutData['table_height'] || $this->aLayoutData['table_height'] == 'auto'){
			$this->aLayoutData['table_height'] = 'auto';
		}
		else if
		(strpos($this->aLayoutData['table_height'], 'px') === false && strpos($this->aLayoutData['table_height'], '%') === false )
		{
			$this->aLayoutData['table_height'] = $this->aLayoutData['table_height'].'px';
		}
		
		// ICON Toolbar Layout
		if(!isset($this->aLayoutData['icons']['show'])){
			$this->aLayoutData['icons']['show'] = 1;
		}

		// TODO: edit by sk@linara-ag.com
		// if "style wish" - divToolbars be together.. Need to update js height of Table if set!!!
		if(!$this->aLayoutData['view']){
			$this->aLayoutData['view'] = 0;
		}
		
		if(!$this->aLayoutData['manual_height']){
			$this->aLayoutData['manual_height'] = 0;
		}
		
		if(!$this->aLayoutData['sortable']){
			$this->aLayoutData['sortable'] = 0;
		}
		if(!$this->aLayoutData['sortable_column']){
			$this->aLayoutData['sortable_column'] = 'position';
		}
		// Filter Layout
		if(!$this->aLayoutData['filter']['title']){
			$this->aLayoutData['filter']['title'] = L10N::t('Filter').': ';
		}
		if(!$this->aLayoutData['filter']['show']){
			$this->aLayoutData['filter']['show'] = 0;
		}
		if(!isset($this->aLayoutData['headline']['show'])) {
			$this->aLayoutData['headline']['show'] = 1;
		}
		// Filter Layout - SEARCH
		if(!$this->aLayoutData['filter']['search']['show']){
			$this->aLayoutData['filter']['search']['show'] = 0;
		}
		if(!$this->aLayoutData['filter']['search']['title']){
			$this->aLayoutData['filter']['search']['title'] = L10N::t('Search:');
		}
		if(!$this->aLayoutData['filter']['search']['html'] && $this->aLayoutData['filter']['search']['show'] == 1){
			
			$arrConfig['onKeyUp']	= $this->sRandString."_prepareLoadTableList(this);";
			$arrConfig['name']		= $this->sRandString."_filter_search";
			$arrConfig['id'] 		= $this->sRandString."_filter_search";
			$arrConfig['value']		= $_SESSION['filter_search'];
			$objSelect = new GUI_FormText($arrConfig);
			
			$strDocumentSearch = $objSelect->generateHTML();
			
			$sHtml = '	<div class="divToolbarFormItem">
						'.$this->aLayoutData['filter']['search']['title'].' '.$strDocumentSearch.'
						</div>';
			$this->aLayoutData['filter']['search']['html'] = $sHtml;
			if($this->aLayoutData['filter']['search']['show'] == 1){
				$this->aLayoutData['filter']['show'] = 1;
				$this->sAjaxParam .="+'&filter_search='+encodeURIComponent($('".$this->sRandString."_filter_search').value)";
			}

		}
		// Filter Layout - FROM
		if(!$this->aLayoutData['filter']['from']['show']){
			$this->aLayoutData['filter']['from']['show'] = 0;
		}
		if(!$this->aLayoutData['filter']['from']['title']){
			$this->aLayoutData['filter']['from']['title'] = L10N::t('From');
		}
		if(!$this->aLayoutData['filter']['from']['html'] && $this->aLayoutData['filter']['from']['show'] == 1){
			
			$arrConfig = array();
			$arrConfig['onKeyUp']	= $this->sRandString."_prepareLoadTableList(this);";
			$arrConfig['style']	= "width: 75px;";
			
			$arrConfig['name']	= $this->sRandString."_filter_from";
			$arrConfig['id'] 	= $this->sRandString."_filter_from";
			$arrConfig['value']	= $_SESSION['filter_from'];
			$objSelect = new GUI_FormText($arrConfig);
			
			$strDocumentDateFrom = $objSelect->generateHTML();
			
			$sHtml = '<div class="divToolbarFormItem">';
			$sHtml .= $this->aLayoutData['filter']['from']['title'].': '.$strDocumentDateFrom;
			$sHtml .= '</div>';
			
			// Kalender
			$sHtml .= '<div class="divToolbarFormItem">';
			$sHtml .= '<img id="' . $this->sRandString . '_img_filter_from" class="" src="/admin/media/calendar.png" />';
			$sHtml .= '</div>';
			
			$this->aLayoutData['filter']['from']['html'] = $sHtml;
			if($this->aLayoutData['filter']['from']['show'] == 1){
				$this->aLayoutData['filter']['show'] = 1;
				$this->sAjaxParam .="+'&filter_from='+encodeURIComponent($('".$this->sRandString."_filter_from').value)";
			}
		}
		// Filter Layout - TO
		if(!$this->aLayoutData['filter']['to']['show']){
			$this->aLayoutData['filter']['to']['show'] = 0;
		}
		if(!$this->aLayoutData['filter']['to']['title']){
			$this->aLayoutData['filter']['to']['title'] = L10N::t('To');
		}
		if(!$this->aLayoutData['filter']['to']['html']  && $this->aLayoutData['filter']['to']['show'] == 1){
			
			$arrConfig = array();
			$arrConfig['onKeyUp']	= $this->sRandString."_prepareLoadTableList(this);";
			$arrConfig['style']	= "width: 75px;";
			
			$arrConfig['name']	= $this->sRandString."_filter_to";
			$arrConfig['id'] 	= $this->sRandString."_filter_to";
			if(!$_SESSION['filter_to']){
				$sDay = date("d", time())+1;
				$sMonth = date("m", time());
				$sYear = date("Y", time());
				$iTime = mktime(0, 0, 0, $sMonth, $sDay, $sYear);
				$_SESSION['filter_to'] = strftime("%x", $iTime);
			}
			$arrConfig['value']	= $_SESSION['filter_to'];
			$objSelect = new GUI_FormText($arrConfig);
			
			$strDocumentDateTo = $objSelect->generateHTML();
			
			$sHtml = '	<div class="divToolbarFormItem">
						'.$this->aLayoutData['filter']['to']['title'].': '.$strDocumentDateTo.'
						</div>';
			
			// Kalender
			$sHtml .= '<div class="divToolbarFormItem">';
			$sHtml .= '<img id="' . $this->sRandString . '_img_filter_to" class="" src="/admin/media/calendar.png" />';
			$sHtml .= '</div>';
			
			$this->aLayoutData['filter']['to']['html'] = $sHtml;
			if($this->aLayoutData['filter']['to']['show'] == 1){
				$this->aLayoutData['filter']['show'] = 1;
				$this->sAjaxParam .="+'&filter_to='+encodeURIComponent($('".$this->sRandString."_filter_to').value)";
			}
		}
		// Filter - ADDITINAL FIRST
		if(!$this->aLayoutData['filter']['additional_start']['show']){
			$this->aLayoutData['filter']['additional_start']['show'] = 0;
		}
		if(!$this->aLayoutData['filter']['additional_start']['html']){
			$this->aLayoutData['filter']['additional_start']['html'] = "";
		}
		// Filter - ADDITINAL LAST
		if(!$this->aLayoutData['filter']['additional_end']['show']){
			$this->aLayoutData['filter']['additional_end']['show'] = 0;
		}
		if(!$this->aLayoutData['filter']['additional_end']['html']){
			$this->aLayoutData['filter']['additional_end']['html'] = "";
		}
		
		// Toggle Left Frame
		if(isset($this->aLayoutData['switchable_default']) && $this->aLayoutData['switchable_default'] == 0){
			// show default
			$this->iShowLeftFrame = 0;
		}else{
			$this->iShowLeftFrame = 1;
		}

	}

	/**
	 * Check the Config Array for IconEntries
	 */
	protected function _checkIconData(){
		
		if(
			count((array)$this->aIconData) <= 0 && 
			$this->aLayoutData['icons']['show'] == 1
		) {
			
			// Label of the Icon(Optional)
			$this->aIconData[0]['label'] = "Anlegen:";
			// Action of the Icon(Important!)
			$this->aIconData[0]['action'] = "new";
			// Function of the Icon
			$this->aIconData[0]['function'] = "prepareAddDialog();";
			// IMG of the Icon
			$this->aIconData[0]['icon'] = "page_new.gif";
			// Alt Tag of the Icon
			$this->aIconData[0]['alt'] = "anlegen";
			// Title Tag of the Icon
			$this->aIconData[0]['title'] = "anlegen";	
			// Seperator of the Icon
			$this->aIconData[0]['separator'] = "::";
			// Active(Display full/transparent) of the Icon
			$this->aIconData[0]['active'] = "1";
					
			$this->aIconData[1]['label'] = "Bearbeitung:";
			$this->aIconData[1]['action'] = "edit";
			$this->aIconData[1]['function'] = "prepareEditDialog(intRowId);";
			$this->aIconData[1]['icon'] = "/admin/media/pencil.png";
			$this->aIconData[1]['alt'] = "bearbeiten";
			$this->aIconData[1]['title'] = "bearbeiten";	
			$this->aIconData[1]['separator'] = "";
			$this->aIconData[1]['active'] = "0";
			
			$this->aIconData[2]['label'] = "";
			$this->aIconData[2]['action'] = "delete";
			$this->aIconData[2]['function'] = "deleteRow(intRowId);";
			$this->aIconData[2]['icon'] = "/admin/media/cross.png";
			$this->aIconData[2]['alt'] = "löschen";
			$this->aIconData[2]['title'] = "löschen";	
			$this->aIconData[2]['separator'] = "";
			$this->aIconData[2]['active'] = "0";
		}
		if ($this->aLayoutData['export']['csv'] == 1){
			
			$aTemp['label'] 	= "CSV ";
			$aTemp['action'] 	= "export_csv";
			$aTemp['function'] 	= "export_csv();";
			$aTemp['icon'] 		= "/admin/media/table_go.png";
			$aTemp['alt'] 		= L10N::t('Export als CSV');
			$aTemp['title'] 	= L10N::t('Export als CSV');	
			$aTemp['separator'] = "";
			$aTemp['active'] 	= "1";
			
			$this->aIconData[] = $aTemp;
			$this->aLayoutData['icons']['show'] = 1;
		}
		if ($this->aLayoutData['export']['xls'] == 1){
			
			$aTemp['label'] 	= "XLS ";
			$aTemp['action'] 	= "export_xls";
			$aTemp['function'] 	= "export_xls();";
			$aTemp['icon'] 		= "/admin/media/table_go.png";
			$aTemp['alt'] 		= L10N::t('Export als XLS');
			$aTemp['title'] 	= L10N::t('Export als XLS');	
			$aTemp['separator'] = "";
			$aTemp['active'] 	= "1";
			
			$this->aIconData[] = $aTemp;
			$this->aLayoutData['icons']['show'] = 1;
		}

	}
	
	/**
	 * Split a SQL String into all Parts
	 * @param string SQL String
	 */
	protected function split_sql($sString = ""){

		$sSql = $this->aConfigData['query_data'][0];
		if($sString != ""){
			$sSql = $sString;
		}

		// SQL String in seine Bestandteile zerteilen
		$sSql = trim($sSql);

		$aParts = array('SELECT', 'FROM', 'WHERE', 'GROUP BY', 'ORDER BY', 'LIMIT');
		$aSqlParts = array();

		$iPos = 0;
		$iStart = 0;
		// Alle parts des SQL Strings durchlaufen
		for($iPart=0; $iPart < count($aParts);$iPart++) {
		
			if($iNextPart) {
				$iPart = $iNextPart;
			}

			// solange suchen bis der part auserhalb einer klammer gefunden wird
			$iRun=0;
			do {
		
				$iNextPart = $iPart;
				
				// sucht den nächsten vorkommenden part
				do {
					$iNextPart++;
					$iPos = strpos($sSql, $aParts[$iNextPart], $iPos);
					$iRun++;
				} while(isset($aParts[$iNextPart]) && $iPos === false);
		
				// wenn kein part mehr vorhanden, ende vom string
				if($iPos === false) {
					$sSelect = substr($sSql, $iStart);
				} else {
					$sSelect = substr($sSql, $iStart, $iPos-$iStart);
				}

				// öfnende und schliessende klammern zählen
				$iOpen = substr_count($sSelect, '(');
				$iClose = substr_count($sSelect, ')');
				
				$iPos++;
				$iRun++;
		
			} while($iRun < 100 && $iOpen != $iClose);
		
			$iStart += strlen($sSelect);
		
			// ersten part abschneiden
			$sSelect = substr($sSelect, strlen($aParts[$iPart]));
			
			$aSqlParts[$aParts[$iPart]] = $sSelect;
		
		}

		$aSqlString['select'] = $aSqlParts['SELECT'];
		$aSqlString['from'] = $aSqlParts['FROM'];
		$aSqlString['where'] = $aSqlParts['WHERE'];
		$aSqlString['groupby'] = $aSqlParts['GROUP BY'];
		$aSqlString['orderby'] = $aSqlParts['ORDER BY'];

		if(substr_count($aSqlString['orderby'], ' DESC') >= 1){
			$this->sOrder = 'DESC';
		} else {
			$this->sOrder = 'ASC';
		}

		$sOrderBy = $aSqlString['orderby'];
		$sOrderBy = str_replace(array('DESC','ASC','`'),'',$sOrderBy);
		$sOrderBy = trim($sOrderBy);

		$aTemp = explode(',' , $sOrderBy);
		$sOrderBy = $aTemp[0];
		
		if(strpos($sOrderBy,'.')!== false){
			$aTemp = explode('.',$sOrderBy);
			$sOrderBy = $aTemp[1];
		}
	
		$this->sOrderField = $sOrderBy;
		
		$aSqlString['limit'] = $aSqlParts['LIMIT'];

		$aSqlString['table'] = $this->aConfigData['query_data'][1]['table'];

		return $aSqlString;

	}
	
	/**
	 * get the List of the DB Entries
	 * @return array MYSQL RESULT
	 */
	protected function _getTableList(){
		global $_VARS;

		$aSqlString = $this->aSqlString;
		$aSql = $this->getSqlArray();
		
		$sWhereAddon = "";
		$sWhereTemp = " WHERE ";
		
		if(is_array($this->aQueryData['filter']['additional']) && !empty($this->aQueryData['filter']['additional'])){

			foreach((array)$this->aQueryData['filter']['additional'] as $iFilter=>$aAdditional) {
				if(!empty($_VARS[$aAdditional['variable']])) {
					$sFrom_Column_Alias = "";
					if($aAdditional['alias'] != ""){
						$sFrom_Column_Alias = "`".$aAdditional['alias']."`.";
					}
					if(empty($aAdditional['operator'])) {
						$aAdditional['operator'] = '=';
					}
					$sWhereAddon .= $sWhereTemp." ".$sFrom_Column_Alias."#field_".$iFilter." ".$aAdditional['operator']." :value_".$iFilter."";
					$sWhereTemp = " AND ";
					$aSql['field_'.$iFilter] = $aAdditional['column'];
					$aSql['value_'.$iFilter] = $_VARS[$aAdditional['variable']];
				}
			}

		}
		// Falls der Filter From gesetzt ist
		if($_VARS['filter_from'] != ""){
			$sFrom_Column_Alias = "";
			if($this->aQueryData['filter']['from']['alias'] != ""){
				$sFrom_Column_Alias = "`".$this->aQueryData['filter']['from']['alias']."`.";
			}
			$sWhereAddon .= $sWhereTemp." ".$sFrom_Column_Alias."#from_column >= :from";
			$sWhereTemp = " AND ";
			$aSql['from_column'] = $this->aQueryData['filter']['from']['column'];
			$aSql['from'] = date("YmdHis", $this->convertDateToTimestamp($_VARS['filter_from']));
		}
		// Falls der Filter To gesetzt ist
		if($_VARS['filter_to'] != ""){
			$sTo_Column_Alias = "";
			if($this->aQueryData['filter']['to']['alias'] != ""){
				$sTo_Column_Alias = "`".$this->aQueryData['filter']['to']['alias']."`.";
			}
			$sWhereAddon .= $sWhereTemp." ".$sTo_Column_Alias."#to_column <= :to";
			$sWhereTemp = " AND ";
			$aSql['to_column'] = $this->aQueryData['filter']['to']['column'];
			$iTo = $this->convertDateToTimestamp($_VARS['filter_to']);
			$iTo = strtotime("+1 day", $iTo) - 1;
			$aSql['to'] = date("YmdHis", $iTo);
		}
		// Falls Das Suchfeld gesetzt ist
		if($_VARS['filter_search'] != ""){
			$sSearch_Column_Alias = "";

			$aTmpFields = @explode(',', $this->aQueryData['filter']['search']['column']);
			$aTmpAlias = @explode(',', $this->aQueryData['filter']['search']['alias']);

			//if($this->aQueryData['filter']['search']['alias'] != ""){
			//	$sSearch_Column_Alias .= "`".$this->aQueryData['filter']['search']['alias']."`.";
			//}

			$sWhereAddon .= $sWhereTemp." ( ";
			$sLastSearchColumnAlias = '';
			foreach((array)$aTmpFields as $iKey => $sValue)
			{
				
				$sSearch_Column_Alias = trim($aTmpAlias[$iKey]);
				if($sSearch_Column_Alias == ""){
					$sSearch_Column_Alias = $sLastSearchColumnAlias;
				} else {
					$sLastSearchColumnAlias = $sSearch_Column_Alias;
				}
				
				$sSearch_Column_Alias = '`'.$sSearch_Column_Alias.'`.';
				
				$sValue = trim($sValue);

				$sWhereAddon .= $sSearch_Column_Alias."#search_column".$iKey." LIKE :search".$iKey."";
				if(isset($aTmpFields[$iKey+1]))
				{
					$sWhereAddon .= " OR ";
				}

				$aSql['search_column'.$iKey] = $sValue;
				$aSql['search'.$iKey] = "%".$_VARS['filter_search']."%";
			}

			$sWhereAddon .= " ) ";

			$sWhereTemp = " AND ";
		}
		// Userspezifisches WHERE
		if($aSqlString['where'] != ""){
			$sWhereAddon .= $sWhereTemp." ".$aSqlString['where'];
			$sWhereTemp = " AND ";
		}
		$sId_Column_Alias = "";
		if($this->aQueryData['filter']['id']['alias'] != ""){
			$sId_Column_Alias = "`".$this->aQueryData['filter']['id']['alias']."`.";
		}
		if(substr_count($aSqlString['select'],"*") <= 0){
			$sSelectNew = " ".$sId_Column_Alias."`".$this->aQueryData['filter']['id']['column']."`,".$aSqlString['select'];
		} else {
			$sSelectNew = $aSqlString['select'];
		}
		if($aSqlString['groupby']){
			$aSqlString['groupby'] = " GROUP BY ".$aSqlString['groupby'];
		}

		if($aSqlString['orderby']){
			$aSqlString['orderby'] = " ORDER BY ".$aSqlString['orderby'];

		}
		if($this->aConfigData['layout_data']['sortable'] == 1){
			$aSqlString['orderby'] = " ORDER BY `position`";
		}
		
		// Order By String
		if($_VARS['sOrderString'] != ''){
			
			$sOrderString = $_VARS['sOrderString'];
			$aOrderString = explode(',',$sOrderString);
			
			$aSqlString['orderby'] = " ORDER BY ";
			
			foreach((array)$aOrderString as $iKey => $sFieldString){
				$sFieldString = trim($sFieldString);
				$aFieldData = explode('.',$sFieldString);
				if(count($aFieldData)>1){
					$aSqlString['orderby'] .= '#alias_'.$iKey.'.'.'#orderColumn_'.$iKey . ' ' . $_VARS['sOrder'] . ' ';
					$aSql['alias_'.$iKey] = str_replace('`','',$aFieldData[0]);
					$aSql['orderColumn_'.$iKey] = str_replace('`','',$aFieldData[1]);
				} else {
					$aSqlString['orderby'] .= '#orderColumn_'.$iKey . ' ' . $_VARS['sOrder'] . ' ';
					$aSql['orderColumn_'.$iKey] = str_replace('`','',$aFieldData[0]);
				}

				if($iKey != (count($aOrderString) - 1 ) ){
					$aSqlString['orderby'] .= ', ';
				}
				
			}
			
			$aSql['orderString'] = $_VARS['sOrderString'];
			$aSql['order'] = $_VARS['sOrder'];
		}

		$iOffset = $_VARS['offset'];
		if($iOffset <= 0){
			$iOffset = 0;
		}
		if($aSqlString['limit']){
			$iEnd = (int)$aSqlString['limit'];
			$aSqlString['limit'] = " LIMIT ".$iOffset.",".$aSqlString['limit'];
		} else {
			$aSqlString['limit'] = " LIMIT ".$iOffset.",20";
			$iEnd = '20';
		}
		$sSql_new = "SELECT SQL_CALC_FOUND_ROWS  ".$sSelectNew." FROM ".$aSqlString['from']." ".$sWhereAddon." ".$aSqlString['groupby']." ".$aSqlString['orderby']." ".$aSqlString['limit'];
		$aResult = DB::getPreparedQueryData($sSql_new, $aSql);

 		$aCount = DB::getQueryData('SELECT FOUND_ROWS() as `count`');
		$this->iPaginationTotal 	= $aCount[0]['count'];
		$this->iPaginationOffset 	= (int)$iOffset;
		$this->iPaginationShow 		= (int)$iEnd;

		if(($iOffset + $iEnd) > $aCount[0]['count']){
			$iEnd = $aCount[0]['count'] - $iOffset;
		}

		$this->iPaginationEnd = $iOffset + $iEnd;

		if($this->iPaginationEnd < $this->iPaginationOffset) {
		  	$_VARS['offset'] = 0;
		   	return $this->_getTableList();
		}

		return $aResult;
	}
	
	/**
	 * Load Admin Header with Ajax Table JS/CSS Files
	 * @param	string	Additional js/css
	 * @return 	Admin_Html::loadAdminFooter()
	 */
	public function getAdminHeader($mAdditional= '', $bXHtml = false){
		global $_VARS;

		$arrOptions['additional'] .= '<script language="JavaScript" type="text/javascript" src="' . $this->sJsPath . '?hash='.$this->sHash.'"></script>';
		$arrOptions['additional'] .= '<link rel="stylesheet" href="/admin/extensions/gui/ajax/table.css" />';
				
		if(is_array($mAdditional)){
			
			$sAdditional = $arrOptions['additional'];
			
			$arrOptions = $mAdditional;
			
			$arrOptions['additional'] = $sAdditional.$arrOptions['additional'];

		} else {
			$arrOptions['additional'] .= $mAdditional;
		}
		
		// Wenn keine Kalendar includes manuel gestzt sind dann nehme die standard Daten //////////
		if(!strpos($arrOptions['additional'], 'calendar.js')){
			$arrOptions['additional'] .= '<script type="text/javascript" src="/admin/js/calendar/calendar.js"></script>';
		}
		
		if(!strpos($arrOptions['additional'], 'calendar.css')){
			$arrOptions['additional'] .= '<link type="text/css" rel="stylesheet" href="/admin/js/calendar/calendar.css" />';
		}
		// linken frame switchen nur wenn nicht explizit festgelegt ist //////////////////////////////
		if(!array_key_exists('left_frame', $arrOptions)) {

			if(!isset($_SESSION['gui']['ajax_table'][$sHash]['left_frame'])) {
				$_SESSION['gui']['ajax_table'][$sHash]['left_frame'] = $this->iShowLeftFrame;
			}
	
			if(isset($_VARS['left_frame'])) {
				$_SESSION['gui']['ajax_table'][$sHash]['left_frame'] = (int)$_VARS['left_frame'];
			}
	
			$arrOptions['left_frame'] = $_SESSION['gui']['ajax_table'][$sHash]['left_frame'];
			
		}

		Admin_Html::loadAdminHeader($arrOptions, $frameset=0, $strBody="", $charset="UTF-8", $bSubmitStatus=0, $bXHtml);
		
	}
	
	/**
	 * Retun the Config Array
	 * @return 	array	Config Array
	 */
	public function getConfigArray(){
		return $this->aConfigData;
	}
	
	/**
	 * Generate the HTML Code
	 * @param	string	Title of the Table
	 * @return 	print HTML Code
	 */
	public function generateHTML($sTitle = 'List', $bSubHeader = false){
		global $_VARS;

		$this->aLayoutData['title'] = $sTitle;

		// Switch Icon 
		$sHtmlSwitch = '';
		$iHtmlSwitch = true;
		if($this->aLayoutData['switchable'] == 1){
			$sHtmlSwitch .= '<div style="float: left; width: 20px; margin: 5px 0px; cursor: pointer;">';
				if((int) $_SESSION['gui']['ajax_table'][$sHash]['left_frame'] == 1){
					$sHtmlSwitch .= '<img src="/admin/media/application_side_contract.png" onclick="' . $this->sRandString . '_toggleSide(0);" alt="' . L10N::t('toggle Frame') . '" />';
				}else{
					$sHtmlSwitch .= '<img src="/admin/media/application_side_expand.png" onclick="' . $this->sRandString . '_toggleSide(1);" alt="' . L10N::t('toggle Frame') . '" />';
				}
			$sHtmlSwitch .= '</div>';
		}
		
		// style for floated view
		$sDivHeaderStyle = '';
		$sDivToolbarStyle = '';
		if($this->aLayoutData['view'] == 1)
		{
			$sDivToolbarStyle 	= 'style="float:left; border:0px; border-right:1px solid #CCCCCC; clear:right;';
			if($bSubHeader){
				$sDivToolbarStyle .= 'height:26px;';
			}
			$sDivToolbarStyle .= '"';
			$sDivHeaderStyle 	= 'height:52px;'; 
		} else {
			if($bSubHeader){
				$sDivToolbarStyle .= 'style="height:26px;"';
			}
		}
		
?>

<section class="content-header" id="gui_list_headline">
<?
	if($this->aLayoutData['headline']['show'] == 1) {
		if(!$bSubHeader){
			
			?> 
				<h1><?=$sTitle?></h1>
			<?
			
		}
	}
?>
</section>

<section class="content" id="gui_list_content">
<div class="box box-default color-palette-box">
			  <div class="box-body">
	<div class="divHeader" style='height:auto; margin-bottom:5px; padding: 0; <?=$sDivHeaderStyle?>'>
<?
	if($this->aLayoutData['headline']['show'] == 1) {
		if($bSubHeader){
			?> 
				<div class="infoBoxTabs">
					<div class='infoBoxTabsHead'>
						<ul id="infoBoxTabDetails" class="infoBoxTabsNav">
					    	<li class="" style="border-right:0px;"><a href="#tab_details"><?=$sTitle?></a></li>
					    </ul>
					</div>
				</div>
			<?
		}
	}
?>
		<div class="divCleaner"></div>
<?
		

		if($this->aLayoutData['filter']['show'] == 1){
?>
		<div class="divToolbar" <?=$sDivToolbarStyle?>>
<?
			if($iHtmlSwitch){
				echo $sHtmlSwitch;
			}
?>
			<div class="divToolbarLabel"><?=$this->aLayoutData['filter']['title']?></div>
			<?=$this->aLayoutData['filter']['additional_start']['html']?>
			<?=$this->aLayoutData['filter']['search']['html']?>
			<?=$this->aLayoutData['filter']['from']['html']?>
			<?=$this->aLayoutData['filter']['to']['html']?>
			<?=$this->aLayoutData['filter']['additional_end']['html']?>
			<div class="divToolbarIcon">
				<img src="/admin/media/spacer.gif" height="16" width="1" alt="" />
				<?
				if($this->aLayoutData['icons']['show'] == 0){	
				?>
					<img id="<?=$this->sRandString?>_toolbar_loading" src="/admin/media/indicator.gif" alt="" />
				<?
				}
				?>
			</div>
			<div class="divCleaner"></div>
		</div>
<?
	if($this->aLayoutData['view'] == 0)
	{
?>
		<div class="divCleaner"></div>
<?
	}
?>
		

<?
			$iHtmlSwitch = false;
		}
		if($this->aLayoutData['icons']['show'] == 1){
?>
		<div class="divToolbar divTopLine" <?=$sDivToolbarStyle?>>
<?			
			if($iHtmlSwitch){
				echo $sHtmlSwitch;
			}
?>
			<?=$this->aLayoutData['icons']['additional_start']['html']?>
<?
			foreach($this->aIconData as $aIcon){
				
				if($aIcon['label'] != ""){
?>
					<div class="divToolbarLabel"><?=$aIcon['label']?></div>
<?
				}
?>
			<div class="divToolbarIcon" id="<?=$this->sRandString?>_toolbar_<?=$aIcon['action']?>">
				<img src="<?=$aIcon['icon']?>" onclick="<?=$this->sRandString?>_executeAction(0, '<?=$aIcon['action']?>');" alt="<?=$aIcon['alt']?>" title="<?=$aIcon['title']?>" />
			</div>
<?
				if($aIcon['separator'] != ""){
?>
					<div class="divToolbarSeparator">::</div>
<?
				}
			}

?>

			<div class="divToolbarIcon">
				<img id="<?=$this->sRandString?>_toolbar_loading" src="/admin/media/indicator.gif" alt="" />
			</div>
			<div class="divCleaner"></div>
		</div>
<?
	if($this->aLayoutData['view'] == 0)
	{
?>
		<div class="divCleaner"></div>
<?
	}
?>
<?
		$iHtmlSwitch = false;
		} 
		if ($this->aLayoutData['pagination']['show'] == 1){
			if(!$bSubHeader){
				
			}
?>
		<div class="divToolbar divTopLine" <?=$sDivToolbarStyle?>>
						
		     <div id="<?=$this->sRandString?>_result_count">
		     
				<div class="divToolbarIcon">
					<img onclick="<?=$this->sRandString?>_pagination_first();return false;" src="/admin/media/control_start.png" alt="" />
				</div>
				<div class="divToolbarIcon">
					<img onclick="<?=$this->sRandString?>_pagination_back();return false;" src="/admin/media/control_rewind.png" alt="" />
				</div>
				<div class="divToolbarIcon">
					<img onclick="<?=$this->sRandString?>_pagination_next();return false;" src="/admin/media/control_fastforward.png" alt="" />
				</div>
				<div class="divToolbarIcon">
					<img onclick="<?=$this->sRandString?>_pagination_last();return false;" src="/admin/media/control_end.png" alt="" />
				</div>
				<div class="divToolbarSeparator">::</div>
				<div class="divToolbarFormItem"><?=L10N::t('Einträge')?>: <span id="<?=$this->sRandString?>_pagination_offset"><?=$this->iPaginationOffset?></span> <?=L10N::t('bis')?> <span id="<?=$this->sRandString?>_pagination_end"><?=$this->iPaginationEnd?></span> <?=L10N::t('von')?> <span id="<?=$this->sRandString?>_pagination_total"><?=$this->iPaginationTotal?></span></div>

		     </div>

		     <div class="divCleaner"></div>

		</div>
		<div class="divCleaner"></div>
<?	
		}
		if(!$bSubHeader){
?>
		<div style="width: 100%; border-top: 1px solid #ccc;  height:0px; ">&nbsp;</div>
<?
		} else {
?>
		<div style="width: 100%; border-top: 1px solid #ccc;  height:0px; ">&nbsp;</div>
<?
		}
?>
	</div>
<?
	if($this->aLayoutData['list']['show'] == 1){


?>
	<table id="<?=$this->sRandString?>_tableArticles" cellpadding="0" cellspacing="0" border="0" width="100%" class="table sortable scroll guiTableBody" style="width:100%;table-layout:fixed;">
		<colgroup>
<?
			foreach($this->aHeaderData as $aHeader){
?>
			<col style="<?=$aHeader['style']?>" />
<?
			}
?>
		</colgroup>
		<thead>
			<tr>
<?
				foreach($this->aHeaderData as $aHeader){
					if($aHeader['sortable'] == 1){
						$sOrderString = implode(', ' , $aHeader['sortColumn']);
						$sOrderString = htmlspecialchars($sOrderString);
?>
						<th style="cursor:pointer;" title="<?=L10N::t('Sortieren nach')?> <?=strip_tags($aHeader['value'])?>" 
<?
						// Flexible List. Editable if Config Array entry AND User has Right
						if($this->aLayoutData['flexible'] == 1 && $this->getEditListFlexRight()){
?>	
							oncontextmenu="<?=$this->sRandString?>_edit_flexible_list('<?=$this->getTableIndex()?>'); return false;" 			
<?
						}
						// Order Field
						if($this->sOrderField == $aHeader['column']){

							if($this->sOrder == 'DESC'){
?>
								class="sortdesc"
<?
							} else {
?>
								class="sortasc"
<?
							}
						}
?>
 						onclick="<?=$this->sRandString?>_table_sort('<?=$sOrderString?>', '<?=$this->sOrder?>',this);" ><?=$aHeader['value']?></th>
<?						
					}else{
?>
						<th 
<?	
						// Flexible List. Editable if Config Array entry AND User has Right
						if($this->aLayoutData['flexible'] == 1 && $this->getEditListFlexRight()){
?>	
							oncontextmenu="<?=$this->sRandString?>_edit_flexible_list('<?=$this->getTableIndex()?>'); return false;" 		
<?
						}						
?>					
						
						><?=$aHeader['value']?></th>
<?						
					}
				}
?>
			</tr>
		</thead>
		<tbody id="tbl_tables_<?=$this->sRandString?>"></tbody>
	</table>
	<script language="JavaScript" type="text/javascript">

		Event.observe(window, 'load', function() {
<?
	if(!empty($this->aHeaderData)){
?>	
			<?=$this->sRandString?>_init(true, <?=(int)$this->iShowLeftFrame?>);
<? 
	} else {
?>
			<?=$this->sRandString?>_init(false, <?=(int)$this->iShowLeftFrame?>);
<? 
	} 
?>
		});

		Event.observe(window, 'resize', <?=$this->sRandString?>_checkTableListHeight);

		document.body.style.overflow = "hidden";
	</script>
	<script language="JavaScript" type="text/javascript"> 
		// From-From Filter
		Calendar.prepare({
			dateField      : '<?=$this->sRandString?>_filter_from',
			triggerElement : '<?=$this->sRandString?>_img_filter_from'
		});
		
		// From-To Filter
		Calendar.prepare({
			dateField      : '<?=$this->sRandString?>_filter_to',
			triggerElement : '<?=$this->sRandString?>_img_filter_to'
		});
		
		// set additional calendar closing handler, eg.: reload table list...
		Calendar.defaultCloseHandler = function(oCalendar){
            var sResult = '';
			aResult = oCalendar.dateField.id.split('_');
			sRand = aResult[0] + '_' + aResult[1];

          	oCalendar.hide();
          	if ( 
          		(oCalendar.dateField.id == sRand + '_filter_to') ||
          		(oCalendar.dateField.id == sRand + '_filter_from') 
          	) {
              	// Hier wird Eval benötigt da wir auch mehrere Tabellen auf einer Seite haben und sonst die Callback überschreiben wird
              	var sCode = "if (typeof " + sRand + "_loadTableList == 'function') {" + sRand + "_loadTableList(); }";
              	eval(sCode);
              	
          	}
        }
	</script>
<?
		}
	?>
	</section>
		<?
	}

//	protected function createHeaderData(){
//		$aSqlString = $this->split_sql();
//		$sSelects = $aSqlString['select'];
//		$sSelectString ="SELECT ".$sSelects." FROM";
//		preg_match_all('/((\`?([a-z])*\`?\.)?\`?([a-z]*)\`? *?(as)? *?\`?([a-z]*)\`? *(,|FROM))/i',$sSelectString,$aSelectParts);
//
//		foreach($aSelectParts[0] as $key => $aValue){
//			$aBack[$key]['alias'] = $aSelectParts[3][$key];
//			$aBack[$key]['column'] = $aSelectParts[4][$key];
//			$aBack[$key]['value'] = $aSelectParts[6][$key];
//		}
//		$this->aHeaderData = $aBack;
//		$this->aConfigData['header_data'] = $aBack;
//		
//	}

	/**
	 * Build the Data Array for the Ajax Table List
	 * @return array Data Array with DB Results
	 */
	public function getTableListData() {

		$aResult = $this->_getTableList();

		$aTableData = array();
		foreach((array)$aResult as $key => $aColumn){

			$aTableData['icon'][(string)$aColumn['id']][0] = 'new';
			$aTableData['icon'][(string)$aColumn['id']][1] = 'edit';
			$aTableData['icon'][(string)$aColumn['id']][2] = 'delete';
			$aTableData['icon'][(string)$aColumn['id']][3] = 'export_csv';
			$aTableData['icon'][(string)$aColumn['id']][4] = 'export_xls';

			$aTableData['data'][$key][0] = $aColumn['id'];
			$i = 1;

			foreach($this->aHeaderData as $aHead){

				if($aHead['type'] == "date" ){
					if(!$aHead['format']) {
						$aHead['format'] = "%x";
					}
					$aTableData['data'][$key][$i] = strftime($aHead['format'], (int)$aColumn[$aHead['column']]);
					if($aColumn[$aHead['column']] <= 0) {
						$aTableData['data'][$key][$i] = " --- ";
					}
				} elseif($aHead['type'] == "date_time" ) {
					if(!$aHead['format']) {
						$aHead['format'] = "%x %X";
					}
					$aTableData['data'][$key][$i] = strftime($aHead['format'], (int)$aColumn[$aHead['column']]);
					if($aColumn[$aHead['column']] <= 0){
						$aTableData['data'][$key][$i] = " --- ";
					}
				} elseif($aHead['type'] == "image" ) {
						$sPath = str_replace( $_SERVER["DOCUMENT_ROOT"], '', $this->aConfigData['edit_settings']['upload_path']);
	
						if(
							!empty($aHead['image_builder_set']) &&
							@is_file($_SERVER["DOCUMENT_ROOT"] . $sPath . $aColumn[$aHead['column']])
						){
							$sPath = '/image.php?s='.  $aHead['image_builder_set']  .'&c[0]='.$sPath;
						}
						
						$aTableData['data'][$key][$i] =  '<img src="' . $sPath . $aColumn[$aHead['column']] . '" alt="" />';

				} elseif($aHead['type'] == "function" ) {

					$aTableData['data'][$key][$i] = call_user_func($aHead['function'], $aColumn[$aHead['column']]);

				} else {

					$aTableData['data'][$key][$i] = $aColumn[$aHead['column']];

				}
				
//				// sk@linara-ag.com :: added string escape, to protect xss attacks
//				$aTableData['data'][$key][$i] = htmlspecialchars($aTableData['data'][$key][$i]);
				
				$i++;
			}

		}

		$aTableData['pagination']['offset'] = (int)$this->iPaginationOffset;
		$aTableData['pagination']['end'] 	= (int)$this->iPaginationEnd;
		$aTableData['pagination']['total'] 	= (int)$this->iPaginationTotal;
		$aTableData['pagination']['show'] 	= (int)$this->iPaginationShow;

		return $aTableData;

	}
	
	public function getSqlArray() {
		global $_VARS;
		
		$aSql = $this->aQueryData[1];

		/**
		 * if more than one table and row if master table clicked
		 */
		foreach((array)$aSql as $sKey=>$sValue) {
			if($sValue == 'master_selected_row') {
				$aSql[$sKey] = $_VARS['master_selected_row'];
			}
		}
		
		return $aSql;
	}
	
	/**
	 * Get the Data for the Edit Dialog
	 * @param 	int 	RowId
	 * @return 	array 	DB Result
	 */
	public function getEditData($id){

		$aSqlString = $this->aSqlString;
		$aSql = $this->getSqlArray();

		$sId_Column_Alias = "";
		if($this->aQueryData['filter']['id']['alias'] != ""){
			$sId_Column_Alias = "`".$this->aQueryData['filter']['id']['alias']."`.";
		}

		$sWhereAddon = "";
		$sWhereTemp = " WHERE ";
		// Userspezifisches WHERE
		if($aSqlString['where'] != ""){
			$sWhereAddon .= $sWhereTemp." ".$aSqlString['where'];
			$sWhereTemp = " AND ";
		}
		$sWhereAddon .= $sWhereTemp." ".$sId_Column_Alias."`".$this->aQueryData['filter']['id']['column']."` = :id";
		$sWhereTemp = " AND ";
		$aSql['id'] = $id;
		$sSelectNew = "";
		
		$aEditData = $this->aEditData;
		$i=1;
		
		// Query bauen
		foreach((array)$aEditData as $aEdit) {

			if($aEdit['type'] == "h1" || $aEdit['type'] == "code" || $aEdit['type'] == "h2" || $aEdit['type'] == "h3" || $aEdit['type'] == "text" || $aEdit['type'] == "tab" ){
				
				if($i == count($aEditData) && strrpos($sSelectNew,' , ') == strlen($sSelectNew)-3){
					$sSelectNew = substr($sSelectNew,0,strlen($sSelectNew)-3);
				}
				$i++;
				continue;
			}

			if($aEdit['type'] == "multi") {
				$aFields = $aEdit['fields'];
			} else {
				$aFields = array($aEdit);
			}

			foreach($aFields as $aField) {

				$sAliasAddon = "";
				if($aField['alias'] != ""){
					$sAliasAddon = "`".$aField['alias']."`.";
				}
				if(
					$aField['type'] == "date" || 
					$aField['type'] == "date_time" || 
					$aField['type'] == "calendar"
				){
					$sSelectNew.= " ".$sAliasAddon."`".$aField['column']."` as `".$aField['column']."`";
				} else {
					$sSelectNew.= " ".$sAliasAddon."`".$aField['column']."` ";
				}
				if($i < count($aEditData)){
					$sSelectNew.= " , ";
				}
				$i++;
			}
		}
		if($aSqlString['groupby']){
			$aSqlString['groupby'] = " GROUP BY ".$aSqlString['groupby'];
		}
		if($aSqlString['orderby']){
			$aSqlString['orderby'] = " ORDER BY ".$aSqlString['orderby'];
		}
		if($aSqlString['limit']){
			$aSqlString['limit'] = " LIMIT ".$aSqlString['limit'];
		}
		$sSql_new = "SELECT ".$sSelectNew." FROM ".$aSqlString['from']." ".$sWhereAddon." ".$aSqlString['groupby']." ".$aSqlString['orderby']." ".$aSqlString['limit'];

		$aResult = DB::getPreparedQueryData($sSql_new, $aSql);
			
		foreach((array)$aEditData as $aEdit) {

			if($aEdit['type'] == "multi") {
				$aFields = $aEdit['fields'];
			} else {
				$aFields = array($aEdit);
			}

			foreach($aFields as $aField) {

				if(
					$aField['type'] == "date" || 
					$aField['type'] == "date_time" || 
					$aField['type'] == "calendar"
				) {
					try {
						$oDate = new WDDate($aResult[0][$aField['column']], WDDate::DB_TIMESTAMP);
						$aResult[0][$aField['column']] = $oDate->get(WDDate::TIMESTAMP);
					} catch(Exception $eWDDate) {
						$aResult[0][$aField['column']] = false;
					}
				}

				if($aField['type'] == "date" || $aField['type'] == "calendar") {

					if($aResult[0][$aField['column']] == 0) {
						$aResult[0][$aField['column']] = "";
					} else {
						$aResult[0][$aField['column']] = $this->convertTimestampToDate($aResult[0][$aField['column']]);
					}
				}
				if($aField['type'] == "date_time") {
					if($aResult[0][$aField['column']] == 0) {
						$aResult[0][$aField['column']] = "";
					} else {
						$aResult[0][$aField['column']] = $this->convertTimestampToDateTime($aResult[0][$aField['column']]);
					}
				}
				if($aField['type'] == "time") {
					if($aResult[0][$aField['column']] == 0) {
						$aResult[0][$aField['column']] = "";
					} else {
						$aResult[0][$aField['column']] = $aResult[0][$aField['column']];
					}
				}

				if(
					isset($aField['default_value']) &&
					empty($aResult[0][$aField['column']])
				) {
					$aResult[0][$aField['column']] = $aField['default_value'];
				}

			}

		}
		$aBack = $aResult[0];

		return $aBack;
	}

	/**
	 * Save the Row Data
	 * @require $_VARS
	 */
	public function saveRowData() {
		global $_VARS, $system_data;

		$sDebugVar = $_VARS['debugvar'];
		if(!empty($sDebugVar)){
		 	global $$sDebugVar;
		}

		$sSetAddon = "";
		$aSqlString = $this->aSqlString;
		$aSql = $this->getSqlArray();
		$aUploads = array();
		
		$aEditData = $this->aEditData;
		$i=1;
		foreach((array)$aEditData as $aEdit){
			if($aEdit['type'] == "h1" || $aEdit['type'] == "code" || $aEdit['type'] == "h2" || $aEdit['type'] == "h3" || $aEdit['type'] == "text" || $aEdit['type'] == "tab" ){
				$i++;
				continue;
			}
			
			
			if($aEdit['type'] == "multi") {
				$aFields = $aEdit['fields'];
			} else {
				$aFields = array($aEdit);
			}

			foreach($aFields as $aField) {

				$sAliasAddon = "";
				if($aField['alias'] != ""){
					$sAliasAddon = "`".$aField['alias']."`.";
				}

				if(is_array($_VARS['save'][$aField['column']])){
					$_VARS['save'][$aField['column']] = json_encode($_VARS['save'][$aField['column']]);
				}

				if(
					$aField['type'] == "upload" &&
					!empty($_VARS['save'][$aField['column']])
				) {
					$aUploads[] = $aField['column'];
				}

				if($aField['type'] == "date" || $aField['type'] == "date_time" || $aField['type'] == 'calendar'){
					$sTempDateTime = $this->convertDateToTimestamp($_VARS['save'][$aField['column']], true);
					$sSetAddon.= " ".$sAliasAddon."`".$aField['column']."` = :placeholder_".$i." ";
					$aSql["placeholder_".$i] = $sTempDateTime;
				} else {
					$sSetAddon.= " ".$sAliasAddon."`".$aField['column']."` = :placeholder_".$i." ";
					$aSql["placeholder_".$i] = trim($_VARS['save'][$aField['column']]);
				}

				if($i < count($aEditData)){
					$sSetAddon.= " , ";
				}
				$i++;
			}
		}
		
		// letztes komma entfernen
		$sSetAddon = rtrim($sSetAddon, ', ');
		
		$sWhereTemp = " WHERE ";
		
		// Userspezifisches WHERE
		if($aSqlString['where'] != ""){
			$sWhereAddon .= $sWhereTemp." ".$aSqlString['where'];
			$sWhereTemp = " AND ";
		}
		
		$s_Column_Alias = "";

		$sWhereAddon .= $sWhereTemp." ".$sId_Column_Alias."#id_column = :id";
		
		$aSql['id_column'] = $this->aQueryData['filter']['id']['column'];

		if($_VARS[$this->sEditId] <= 0) {
			$sSql = "INSERT INTO ".$aSqlString['table']." SET ".$sSetAddon;
			$aResult = DB::getPreparedQueryData($sSql, $aSql);
			$_VARS[$this->sEditId] = DB::fetchInsertID();

			// if upload, rename file and save new name
			foreach((array)$aUploads as $sUpload) {
				$sFile = $_VARS['save'][$sUpload];
				$sNewFile = preg_replace('/^0_/', $_VARS[$this->sEditId].'_', $sFile);
				rename($this->aEditSetting['upload_path'].$sFile, $this->aEditSetting['upload_path'].$sNewFile);
				chmod($this->aEditSetting['upload_path'].$sNewFile, $system_data['chmod_mode_file']);
				$sSql = "UPDATE ".$aSqlString['table']." SET #upload_field = :upload_value ".$sWhereAddon;
				$aUploadSql = $aSql;
				$aUploadSql['id'] = $_VARS[$this->sEditId];
				$aUploadSql['upload_field'] = $sUpload;
				$aUploadSql['upload_value'] = $sNewFile;
				DB::executePreparedQuery($sSql, $aUploadSql);
			}

		} else {
			$sSql = "UPDATE ".$aSqlString['table']." SET ".$sSetAddon." ".$sWhereAddon;
			$aSql['id'] = $_VARS[$this->sEditId];
			$aResult = DB::getPreparedQueryData($sSql, $aSql);
		}

		$aBack[0] = $_VARS[$this->sEditId];
		return $aBack;

	}

	/**
	 * Delete one Row
	 * @param 	int 	RowId
	 */
	public function deleteRow($intRowId){
		
		$sSetAddon = "";
		$aSqlString = $this->aSqlString;
		$aSql = $this->getSqlArray();
		$sWhereTemp = " WHERE ";
		
		// Userspezifisches WHERE
		if($aSqlString['where'] != ""){
			$sWhereAddon .= $sWhereTemp." ".$aSqlString['where'];
			$sWhereTemp = " AND ";
		}
		
		$sId_Column_Alias = "";

		$sWhereAddon .= $sWhereTemp." ".$sId_Column_Alias."#id_column = :id";
		
		$aSql['id_column'] = $this->aQueryData['filter']['id']['column'];
		$sSql = "DELETE FROM ".$aSqlString['table']." ".$sWhereAddon;
		$aSql['id'] = $intRowId;
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
	}
	
	public function getAdditionalHeader(){
		return 	'<script language="JavaScript" type="text/javascript" src="/admin/extensions/gui/gui/ajax/table.js.php?hash='.$this->sHash.'"></script>';
	}
	public function getHash(){
		return $this->sHash;
	}

	public function saveSort($aArray){

		$aSqlString = $this->aSqlString;
		$aSql = $this->aConfigData['query_data'][1];
		$iPosition = 1;
		
		foreach((array)$aArray as $key => $aValue){
			
			if(! is_array($aValue)){
				continue;
			}
			foreach((array)$aValue as $iId){
				$sSql = "UPDATE ".$aSqlString['from']." SET #position_ = :position WHERE `id` = :id";
				$aSql['id'] = $iId;
				$aSql['position'] = $iPosition;
				$aSql['position_'] = $this->aLayoutData['sortable_column'];
				DB::executePreparedQuery($sSql,$aSql);
				$iPosition++;
			}
			break;
		}
	}
	
	public function switchAjaxRequests(&$_VARS){
		global $system_data;
		
		if((int)$_VARS['debugmode'] == 1){
			__pout(debug_backtrace());
			__pout($_VARS);
		}
		
		$sHash = $_VARS['hash'];

		$aConfigArray = $_SESSION['gui']['ajax_table'][$sHash];
	/* TODO example Code 4 New GUI Extension, please do not delete that comment
	// <span onclick="' . $this->sRandString . '_displayBox(\'test\');">test</span>
		if(isset($_VARS['task']) && $_VARS['task'] == 'test'){
			$aTransfer = array();
			$aTransfer['html'] = '<p>Hallo</p>';
			$aTransfer['headerBox'] = L10N::t('Titel');
			$aTransfer['errorsBox'][] = L10N::t("Fehler");
			$aTransfer['success'] = 0;
			$aTransfer['html_button'] = '<input id="save" class="btn" type="button" value="' . L10N::t("Speichern") . '" style="opacity: 1; float: right; margin: 5px"/>';
			
			echo json_encode($aTransfer);
		}
	*/
		/**
		 * GUI file upload
		 */
		foreach((array)$aConfigArray['edit_data'] as $aField) {
			
			if($aField['type'] == "upload") {
		
				if(is_file($_FILES['save']['tmp_name'][$aField['column']])) {
			
					$sFilename = $_VARS[$aConfigArray['edit_id']]."_".\Util::getCleanFileName($_FILES['save']['name'][$aField['column']]);
				
					$bSuccess = move_uploaded_file($_FILES['save']['tmp_name'][$aField['column']], $aConfigArray['edit_settings']['upload_path'].$sFilename);

					if($bSuccess) {

						chmod($aConfigArray['edit_settings']['upload_path'].$sFilename, $system_data['chmod_mode_file']);

						echo '<span style="color: green;">'.L10N::t('Upload erfolgreich!').'</span>';
						$_SESSION['gui']['ajax_table'][$sHash]['edit_settings']['uploads'][$_VARS[$aConfigArray['edit_id']]][$aField['column']] = $sFilename;

					} else {
				
						echo '<span style="color: red;">'.L10N::t('Upload fehlgeschlagen!').'</span>';
		
					}		

				}
		
			}
		
		}
		/**
		 * 
		 * GUI Export CSV
		 * 
		 */
		
		if($_VARS['task'] == 'export_csv'){
		
			$this->export($this->aLayoutData['title'], 'CSV');
			
		}
		
		if($_VARS['task'] == 'export_xls'){
		
			$this->export($this->aLayoutData['title'], 'XLS');
			
		}
		
		if($_VARS['task'] == 'get_rows' && $aConfigArray['query_data'][0] != "" && $aConfigArray['query_data'][1] != ""){
			
			echo json_encode($this->getTableListData());
		}
		
		if($_VARS['task'] == 'edit_row'){

			echo json_encode($this->getEditData($_VARS['row_id']));
			
		}
		if($_VARS['task'] == 'delete_row'){
			
			$this->deleteRow($_VARS['row_id']);
			
		}
		if($_VARS['task'] == 'save_row'){
		
			$aData = $this->getEditData($_VARS[$aConfigArray['edit_id']]);

			foreach((array)$aConfigArray['edit_data'] as $aField) {

				if($aField['type'] == "upload") {
					$sFile =& $_SESSION['gui']['ajax_table'][$sHash]['edit_settings']['uploads'][$_VARS[$aConfigArray['edit_id']]][$aField['column']];
					if(!empty($sFile)) {
						$_VARS['save'][$aField['column']] = $sFile;
						unset($sFile);
					} else {
						$_VARS['save'][$aField['column']] = $aData[$aField['column']];
					}
				}
		
			}

			echo json_encode($this->saveRowData());
			
		}
		if($_VARS['task'] == 'save_sort'){
		
			$this->saveSort($_VARS);
			
		}
		
		/// Flex Requests ////////////////////////////////////////////
		if($_VARS['task'] == 'getTableFlexHtml'){
		
			$this->getTableFlexHtml($_VARS);
			
		}
		if($_VARS['task'] == 'saveFlexList'){
		
			$this->saveFlexList($_VARS);	
		}
		if($_VARS['task'] == 'saveFlexListOrder'){

			$this->saveFlexListOrder($_VARS);	
		}
		/////////////////////////////////////////////////////////////
		$sDebugVar = $_VARS['debugvar'];
		if(!empty($sDebugVar)){
		 	global $$sDebugVar;
			__pout($$sDebugVar);
		}
		
		if((int)$_VARS['debugmode'] == 1){
			__pout(Util::getQueryHistory());
		}

		$oPersister = WDBasic_Persister::getInstance();
		$oPersister->save();

	}
	
	public function saveFlexList($_VARS) {

		$aTransfer['message'] = array();

		// get first entry
		$sSql = "
				SELECT
					*
				FROM
					`system_gui_lists`
				WHERE
					`id` = :id
					";
		$aSql = array('id'=>key($_VARS['save']['column_activ']));
		$aCheck = DB::getQueryRow($sSql, $aSql);

		// check if all colums are inactive
		$iNumInactive = 0;
		foreach((array) $_VARS['save']['column_activ'] as $iId => $iValue) {
			if($iValue == 0) {
				$iNumInactive++;
			}
		}

		// Fix Columns
		$iFixColumns = 0;
		foreach($this->aHeaderDataOrig as $aHeader){

			$this->insertNewColumnData($aCheck['path'], $aHeader['column'], $aHeader['dbalias'], 0);

			if(isset($aHeader['flexible']) && $aHeader['flexible'] == 0) {
				$iFixColumns++;
			}

		}
		
		if(
			$iNumInactive < count((array)$_VARS['save']['column_activ']) || 
			$iFixColumns > 0 
		) {

			foreach((array)$_VARS['save']['column_activ'] as $iId=>$iValue) {
	
				$sSql = "
						UPDATE 
							`system_gui_lists` 
						SET 
							`visible` = :visible 
						WHERE 
							`id` = :id
						";
				$aSql = array(
						'visible' => $iValue,
						'id' => $iId
						);
				DB::executePreparedQuery($sSql,$aSql);

			}

			$aTransfer['headerBox'] = L10N::t('Erfolgreich gespeichert!');
			$aTransfer['success'] = 1;

		} else {

			$aTransfer['headerBox'] = L10N::t('Eingabefehler');
			$aTransfer['errorsBox'][] = L10N::t("Es können nicht alle Spalten ausgeblendet werden");
			$aTransfer['success'] = 0;

		}

		echo json_encode($aTransfer);

	}
	
	/**
	 * saves Flexible Liste Drag&Drop handler
	 * @param $_VARS
	 * @return unknown_type
	 */
	public function saveFlexListOrder($_VARS){

		foreach((array)$_VARS['flexible_list'] as $iNum => $iId) {

			$sSql = 'UPDATE `system_gui_lists` SET `position` = :position WHERE `id` = :id';
			$aSql = array(
					'position' =>(int)$iNum,
					'id' =>(int)$iId
			);
		
			DB::getPreparedQueryData($sSql,$aSql);
		}

	}
	
	public function getTableFlexHtml($_VARS){

		$iBoxHeight = $this->aDialogData['height'];
		$iBoxHeight -= 56;
		
		// get All Colums
		$aData = $this->getColumnListData($_VARS['table'], $_SESSION['gui']['ajax_table'][$_VARS['hash']]['html_path']);
				
		$sHtml = '<form method="post" id="form_flex_list" action="">';
		$sHtml .= '<div id="divFlexBox" class="flexList" style="height: ' . $iBoxHeight . 'px; overflow: auto;">';
		$sHtml .= '<h1>' . L10N::t("Spaltenreihenfolge") . '</h1>';
		$sHtml .= '<ul id="flexible_list" class="drag_drop_list">';

		foreach((array) $aData as $i => $aColumn) {
			// to find correct translation name
			foreach((array) $this->aHeaderDataOrig as $j => $aHeaderData){
				if(
					$aHeaderData['column'] == $aColumn['db_column'] &&
					$aHeaderData['dbalias'] == $aColumn['db_alias']
				) {

					$sHtml .= '<li id="item_' . $aColumn['id'] . '" class="row_edit2">';
					//Checkbox if Column == acitive only shown on NON mandatory fields
					if($aHeaderData['flexible'] != 0){
						
						$sHtml .= '<input name="save[column_activ][' . $aColumn['id'] . ']" type="hidden" value="0"/>';
						$sCheck = ($aColumn['visible'] == 1) ? 'checked="checked"':'';
						$sHtml .= '<input name="save[column_activ][' . $aColumn['id'] . ']" style="float: right;" type="checkbox" value="1" ' . $sCheck . '/>';
					}
					if($aHeaderData['flexible'] != 0){
						$sHtml .= '<span style="width: 120px; float: right;">' . L10N::t("Spalte anzeigen:") . '</span>';
					}
					$sHtml .= '<span style="width: 250px; float: left;">' . strip_tags($aHeaderData['value'], '<img>' ) . '</span>';
					$sHtml .= '</li>';
					break;

				}

			}

		}
		$sHtml .= '</ul>';
		$sHtml .= '</div>';

		$sHtml .= '<div class="row_btn">';
		$sHtml .= '<input id="save" class="btn" type="button" onclick="' . $this->sRandString . '_saveFlexList();" value="' . L10N::t("Speichern") . '" style="opacity: 1; float: right; margin: 5px"/>';
		$sHtml .= '</div>';
		$sHtml .= '</form>';

		echo $sHtml;

	}
	
	public function saveColumnData($aConfig) {
		global $user_data, $objWebDynamics;

		$iUserId = $user_data['id'];
		// Set Hock to change user ID
		\System::wd()->executeHook('ajax_gui_flexiblelist_setid', $iUserId);

		//check if info should be updated
		$aData = $this->getColumnListData($this->getTableIndex(), $_SERVER['SCRIPT_NAME']);

		// Compare DB Date with Config Data - decide to update
		if(empty($aData)) {
			// new Insert
			$sDBAction = 'insert_all';
		} else {
			// insert New header_data delete old
			$sDBAction = 'insert_new'; 
		}

		if($sDBAction == 'insert_all') {

			foreach((array) $aConfig['header_data'] as $i => $aHeaderData) {
				// dbalias maybe not set
				$sDBAlias = (isset($aHeaderData['dbalias'])) ? $aHeaderData['dbalias'] : '' ;

				// Default Visible Value
				if(isset($aHeaderData['flexible_show']) && $aHeaderData['flexible_show'] == 0){
					$iVisable = 0;
				} else {
					$iVisable = 1;
				}

				$this->insertNewColumnData($_SERVER['SCRIPT_NAME'], $aHeaderData['column'], $sDBAlias, $iVisable);
				
			}
			
		} elseif($sDBAction == 'insert_new') {

			// insert new
			foreach((array) $aConfig['header_data'] as $j => $aHeaderData) {
				// dbalias maybe not set
				$sDBAlias = (isset($aHeaderData['dbalias'])) ? $aHeaderData['dbalias'] : '' ;

				// Default Visible Value
				if(isset($aHeaderData['flexible_show']) && $aHeaderData['flexible_show'] == 0){
					$iVisable = 0;
				} else {
					$iVisable = 1;
				}

				//check if header Data in DB
				$bCheck = $this->checkColumnData($aHeaderData, true);

				if(!$bCheck) {
					// insert new header Data in DB
					$this->insertNewColumnData($_SERVER['SCRIPT_NAME'], $aHeaderData['column'], $sDBAlias, $iVisable);
				}

			}

			// Delete if not more aktiv
			foreach((array) $aData as $i => $aDBData){ 
				//DB Data
				$iFound = false;
				foreach((array) $aConfig['header_data'] as $j => $aHeaderData){
					// dbalias maybe not set
					$sDBAlias = (isset($aHeaderData['dbalias'])) ? $aHeaderData['dbalias'] : '' ;
					
					// Header Data
					if(
						$aDBData['path'] == $_SERVER['SCRIPT_NAME'] &&
						$aDBData['db_column'] == $aHeaderData['column'] &&
						$aDBData['table'] == $this->getTableIndex() &&
						$aDBData['db_alias'] == $sDBAlias
					) {
						$iFound = true;
						break;
					}
					
				}
				
				if(!$iFound){
					//Delete from DB
					$sSql = 'DELETE 
								FROM 
									`system_gui_lists` 
								WHERE 
									`path` = :path  AND 
									`table` =  :table AND 
									`db_column` = :column AND
									`db_alias` = :alias
							';
					$aSql = array(
								'path' => $_SERVER['SCRIPT_NAME'],
								'table' => $this->getTableIndex(),
								'column' => $aDBData['db_column'],
								'alias' => $aDBData['db_alias']
							);
					DB::getPreparedQueryData($sSql,$aSql);
				}
			}
		}
	}
	
	/**
	 * Function checks if Column should be shown in table
	 * @param $aHeaderData
	 * @param $iCheckAll
	 * @return unknown_type
	 */
	public function checkColumnData($aHeaderData, $iCheckAll){
		global $user_data, $objWebDynamics;
		$iUserId = $user_data['id'];
		
		// Set Hock to change user ID
		\System::wd()->executeHook('ajax_gui_flexiblelist_setid', $iUserId);

		$sSql = 'SELECT 
					* 
				FROM 
					`system_gui_lists` 
				WHERE 
					`path` = :path AND 
					`user_id` = :userID AND 
					`db_column` = :db_column AND 
					`table` = :table AND 
					`db_alias` = :db_alias';
		
		if(!$iCheckAll){
			$sSql .= " AND `visible` = 1";
		}
		
		
		$aSql = array(
					'path' => $_SERVER['SCRIPT_NAME'],
					'userID' => $iUserId,
					'db_column' => (string)$aHeaderData['column'],
					'table' => $this->getTableIndex(),
					'db_alias' => (string)$aHeaderData['dbalias']
		);
		$aData = DB::getPreparedQueryData($sSql,$aSql);


		if(empty($aData)){
			$bCheck = false;
		}else{
			$bCheck = true;
		}

		return $bCheck;
		
	}

	/**
	 * insert new Column Data 4 Flex List
	 * 
	 * @param $sPath
	 * @param $sColumn
	 * @param $sAlias
	 * @param $iVisible
	 * @return unknown_type
	 */
	public function insertNewColumnData($sPath, $sColumn, $sAlias, $iVisible) {
		global $user_data, $objWebDynamics;
		
		$iUserId = $user_data['id'];

		// Set Hock to change user ID
		\System::wd()->executeHook('ajax_gui_flexiblelist_setid', $iUserId);

		## START Prüfen ob Spalte nicht schon existiert
		$sSql = "SELECT
						*
					FROM
						`system_gui_lists`
					WHERE
						`path` = :path AND
						`db_column` = :column AND
						`user_id` = :user_id AND
						`table` = :table
					";
		$aSql['path'] = $sPath;
		$aSql['column'] = $sColumn;
		$aSql['user_id'] = $iUserId;
		$aSql['table'] = $this->getTableIndex();

		$aCheckData = DB::getPreparedQueryData($sSql, $aSql);

		// doppelte entfernen
		if(count($aCheckData) > 1) {

			$iDelete = count($aCheckData) - 1;

			$sSql = "DELETE FROM
							`system_gui_lists`
						WHERE
							`path` = :path AND
							`db_column` = :column AND
							`user_id` = :user_id AND
							`table` = :table
						LIMIT " . $iDelete;
			DB::executePreparedQuery($sSql, $aSql);

		}

		## ENDE

		if(count($aCheckData) == 0) {
			// Get max positon
			$sSql = 'SELECT
						MAX(`position`) num
					FROM
						`system_gui_lists`
					WHERE
						`path` = :path AND
						`table` = :table AND
						`user_id` = :userID';

			$aSql = array(
						'path' => $_SERVER['SCRIPT_NAME'],
						'table' => $this->getTableIndex(),
						'userID' => $iUserId
			);
			$aData = DB::getPreparedQueryData($sSql,$aSql);

			if ( $aData[0]['num'] == NULL ){
				 $iNewNum = 0;
			} else{
				 $iNewNum = $aData[0]['num'] + 1;
			}

			$aSql = array(
					'user_id' => $iUserId,
					'path' => $sPath,
					'db_column' => $sColumn,
					'db_alias' => $sAlias,
					'visible' => $iVisible,
					'table' => $this->getTableIndex(),
					'position' => $iNewNum
			);

			DB::insertData('system_gui_lists',$aSql);
			
		}
	}
	
	/**
	 * get column Data 4 Flex List
	 * 
	 * @param $iTable
	 * @param $spath
	 * @return unknown_type
	 */
	public function getColumnListData($iTable, $spath) {

		global $user_data, $objWebDynamics, $_VARS;

		$iUserId = $user_data['id'];

		$sDebugText  = $_SERVER['HTTP_HOST']."\n\n";
		$sDebugText .= print_r($_VARS,1)."\n\n";
		$sDebugText .= "UserId Before Hook: ".$iUserId;
		$sDebugText .= "\n\n";

		// Set Hock to change user ID
		\System::wd()->executeHook('ajax_gui_flexiblelist_setid', $iUserId);

		$sDebugText .= "UserId After Hook: ".$iUserId;
		$sDebugText .= "\n\n";

		$sSql = "
            SELECT 
                `sgl`.*,
                MAX(`sgl`.`id`) `id`
            FROM 
                `system_gui_lists` `sgl`
            WHERE 
                `sgl`.`path` = :path AND 
                `sgl`.`table` = :table AND
                `sgl`.`user_id` = :userID
            GROUP BY
                `sgl`.`user_id`,
                `sgl`.`path`,
                `sgl`.`db_column`,
                `sgl`.`db_alias`,
                `sgl`.`table`
            ORDER BY 
                `sgl`.`position`
        ";

		$aSql = array(
            'path' => $spath,
            'table' => (int)$iTable,
            'userID' => (int)$iUserId
        );

        $sErrorEmail = System::d('error_email');
		if (
            !is_numeric($iUserId) && 
            !empty($sErrorEmail)
        ) {
			$sDebugText .= print_r($user_data, 1)."\n\n";
            Util::handleErrorMessage($sDebugText);
		}

		return DB::getPreparedQueryData($sSql, $aSql);

	}
	
	/**
	 * Sort Data in right Order (Flex List)
	 * 
	 * @param $aHeader
	 * @return unknown_type
	 */
	public function sortHeaderFlexible($aHeader){

		$aSortedHeader = array();
		$aData = $this->getColumnListData( $this->getTableIndex(), $_SERVER['SCRIPT_NAME']);

		foreach ((array) $aHeader as $i => $aHeaderData) {

			// dbalias maybe not set
			$sDBAlias = (isset($aHeaderData['dbalias'])) ? $aHeaderData['dbalias'] : '' ;

			foreach ((array) $aData as $j => $aDataColumn){
				if (
                    $aHeaderData['column'] == $aDataColumn['db_column'] &&
					$sDBAlias == $aDataColumn['db_alias']
				) {
					$aSortedHeader[$aDataColumn['position']] = $aHeaderData;
					break;
				}
			}
		}

		ksort($aSortedHeader);
		return $aSortedHeader;

	}

	public function getEditListFlexRight() {
		return true;	
	}

	public function setGUIjsPath($sPath) {
		$this->sJsPath = $sPath; 
	}

	public function setTableIndex( $iIndex ) {
		$this->_iTableIndex = (int)$iIndex; 
	}

	public function getTableIndex() {
		return (int)$this->_iTableIndex; 
	}

}
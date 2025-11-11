<?php

class Ext_Gui2_Dialog_Upload extends Ext_Gui2_Dialog_Basic implements Ext_Gui2_Html_Interface {

	/**
	 * @var string
	 */
	public $sTitle = '';

	/**
	 * @var string
	 */
	public $db_alias = '';

	/**
	 * @var string
	 */
	public $db_column = 'upload';

	/**
	 * @var int
	 */
	public $multiple = 0;
	
	/**
	 * @var Ext_Gui2_Dialog
	 */
	public $oDialog;

	/**
	 * @var string
	 */
	public $sUploadPath = '/storage/atg2/uploads/';

	/**
	 * @var int
	 */
	public $bAddColumnData2Filename = 1;

	/**
	 * @var int
	 */
	public $bAddId2Filename = 1;

	/**
	 * @var int
	 */
	public $bDeleteOldFile = 1;

	/**
	 * @var int
	 */
	public $bShowSaveMessage = 1;

	/**
	 * @var string
	 */
	public $sClass = 'GUIDialogRow';

	/**
	 * @var string
	 */
	public $sStyle = '';

	/**
	 * @var bool
	 */
	public $bNoPathCheck = 0;

	/**
	 * @var bool
	 */
	public $bRenderOnlyUploadField = false;

	/**
	 * no_cache-Parameter für StorageController an vorhandenen Upload anhängen
	 *
	 * @var bool
	 */
	public $bNoCache = false;

	/**
	 * @var null
	 */
	public $oPostProcess = null;

	private $aOptions = [];

	/**
	 * @param $oGui
	 * @param string $sTitle
	 * @param $oDialog
	 * @param string $sDbColumn
	 * @param string $sDbAlias
	 * @param string $sUploadPath
	 * @param bool $bReadOnly
	 * @param array $aOptions
	 */
	public function  __construct(Ext_Gui2 $oGui, $sTitle, &$oDialog, $sDbColumn, $sDbAlias = '', $sUploadPath = '/storage/atg2/uploads/', $bReadOnly = false, $aOptions = array()) {

		// Sicherstellen, dass der Pfad mit Slash endet
		if(substr($sUploadPath, -1) != '/') {
			$sUploadPath .= '/';
		}

		$this->sTitle = $sTitle;
		$this->bReadOnly = $bReadOnly;
		$this->db_alias	= $sDbAlias;
		$this->db_column = $sDbColumn;
		$this->oDialog = $oDialog;
		$this->sUploadPath	= $sUploadPath;

		if(isset($aOptions['class'])) {
			$this->sClass = $this->sClass . ' ' . $aOptions['class'];
		}

		if(isset($aOptions['style'])) {
			$this->sStyle = $this->sStyle . ' ' . $aOptions['style'];
		}

		unset($aOptions['class'], $aOptions['style']);

		$this->aOptions = $aOptions;

	}

	/**
	 * @param bool $bReadOnly
	 * @return string
	 */
	public function generateHTML($bReadOnly=false) {

		$this->aElements = array();

		$aRowSettings = [
			'db_column'	=> $this->db_column,
			'db_alias' => $this->db_alias,
			'readonly' => $this->bReadOnly,
			'class' => $this->sClass,
			'style' => $this->sStyle,
			'upload_path' => $this->sUploadPath,
			'upload' => 1,
			'multiple' => $this->multiple,
			'add_column_data_filename' => $this->bAddColumnData2Filename,
			'add_id_filename' => $this->bAddId2Filename,
			'delete_old_file' => $this->bDeleteOldFile,
			'no_path_check' => $this->bNoPathCheck,
			'show_save_message' => $this->bShowSaveMessage,
			'no_cache' => $this->bNoCache,
			'post_process' => $this->oPostProcess,
			'info_icon' => false // erstmal keine Info-Icons erlauben
		];

		$aRowSettings = array_merge($aRowSettings, $this->aOptions);

		$oRow = $this->oDialog->createRow($this->sTitle, 'upload', $aRowSettings);

		if($this->bRenderOnlyUploadField) {
			$aRowElements = $oRow->getElements();
			$aFieldElements = $aRowElements[1]->getElements();
			$oRow = $aFieldElements[0];
		}

		$this->setElement($oRow);
		
		$sHtml = parent::generateHTML();

		return $sHtml;
	}

	function __get($sKey) {
		return $this->$sKey;
	}

	function __set($sKey, $mValue) {
		$this->$sKey = $mValue;
	}

}

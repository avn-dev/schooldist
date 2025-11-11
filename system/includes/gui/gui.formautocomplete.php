<?php


/*
 * -- webDynamics GUI --
 * Björn Goetschke <bg@plan-i.de>
 *
 * copyright by plan-i GmbH
 *
 * Include from: /system/includes/gui/gui.php
 * The list of dependencies is available in that file.
 *
 * 
 */


class GUI_FormAutocomplete implements GUI_Element {

	protected $_sName		= '';
	protected $_sID			= '';
	protected $_sCss		= 'txt';
	protected $_sStyle		= '';
	protected $_sOnClick	= '';
	protected $_aSelected	= array();
	protected $_aOptions	= array();
	protected $_sHTML		= null;
	protected $_sCode		= "";
	protected $_sDisplay	= "";
	protected $_sValue		= "";

	public function __construct(array $aConfig = array()) {
		foreach ($aConfig as $sCurrentIndex => $mixedCurrentValue) {
			switch ($sCurrentIndex) {
				case 'name':
					$this->_sName = (string)trim($mixedCurrentValue);
					break;
				case 'id':
					$this->_sID = (string)trim($mixedCurrentValue);
					break;
				case 'value':
				case 'selected':
					if (is_array($mixedCurrentValue)) {
						foreach ($mixedCurrentValue as $sCurrentValue) {
							$this->_aSelected[] = (string)trim($sCurrentValue);
						}
					} else {
						$this->_aSelected[] = (string)trim($mixedCurrentValue);
					}
					break;
				case 'css':
					$this->_sCss = (string)trim($mixedCurrentValue);
					break;
				case 'style':
					$this->_sStyle = (string)trim($mixedCurrentValue);
					break;
				case 'onChange':
					$this->_sOnChange = (string)trim($mixedCurrentValue);
					break;
				case 'appendCss':
					$this->_sCss = (string)trim($this->_sCss.' '.trim($mixedCurrentValue));
					break;
				case 'appendStyle':
					$this->_sStyle = (string)trim($this->_sStyle.' '.trim($mixedCurrentValue));
					break;
				case 'options':
					$this->_aOptions = $mixedCurrentValue;
					break;
				default:
					throw new Exception('Unprocessed configuration value "'.$sCurrentIndex.'".');
			}
		}
		
		$this->_sCode = \Util::generateRandomString(8, array('no_numbers'=>1));

		$_SESSION['gui_autocomplete'][$this->_sCode] = $this->_aOptions;

		if($this->_aSelected[0]) {
			if($this->_aOptions['mode'] == 'customdata') {

				$this->_aOptions['customdata']['where'] = array();
				$this->_aOptions['customdata']['where'][] = array(
							'mode'     => 'AND',
							'table'    => $this->_aOptions['table'],
							'field'    => $this->_aOptions['key'],
							'cond'     => 'EQ',
							'cond_arg' => $this->_aSelected[0]
					);

				$arrData = CustomData_Dao::fetchTableData($this->_aOptions['table'], $this->_aOptions['customdata']);
		
				$this->_sDisplay	= $arrData[0][$this->_aOptions['display']];
				$this->_sValue		= $arrData[0][$this->_aOptions['key']];		

			} else {

				$strSql = "
							SELECT
								*
							FROM
								#strTable
							WHERE
								#strKey = :mixValue
							LIMIT 1
							";
				$arrSql = array();
				$arrSql['strTable']	= $this->_aOptions['table'];
				$arrSql['strKey']	= $this->_aOptions['key'];
				$arrSql['mixValue']	= $this->_aSelected[0];
				$arrData = DB::getPreparedQueryData($strSql, $arrSql);
		
				$this->_sDisplay	= $arrData[0][$this->_aOptions['display']];
				$this->_sValue		= $arrData[0][$this->_aOptions['key']];

			}

		}
		
	}

	public function generateHTML() {
		if ($this->_sHTML === null) {
			$objSmarty = new GUI_SmartyWrapper();
			$objSmarty->assign('sName', $this->_sName);
			$objSmarty->assign('sID', $this->_sID);
			$objSmarty->assign('sCss', $this->_sCss);
			$objSmarty->assign('sStyle', $this->_sStyle);
			$objSmarty->assign('sOnClick', $this->_sOnClick);
			$objSmarty->assign('aSelected', $this->_aSelected);
			$objSmarty->assign('aOptions', $this->_aOptions);
			$objSmarty->assign('sCode', $this->_sCode);
			$objSmarty->assign('sValue', $this->_sValue);
			$objSmarty->assign('sDisplay', $this->_sDisplay);
			$objSmarty->assign('strItemTemplate', $this->_aOptions['template']);
			$this->_sHTML = $objSmarty->parseTemplate('gui.formautocomplete.tpl');
		}
		return $this->_sHTML;
	}

}

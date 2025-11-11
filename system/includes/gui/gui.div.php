<?php


/*
 * -- webDynamics GUI --
 * Björn Goetschke <bg@plan-i.de>
 *
 * copyright by plan-i GmbH
 *
 * Include from: /system/includes/gui/gui.php
 * The list of dependencies is available in that file.
 */


/**
 * Class that represents a html div element.
 */
class GUI_Div extends GUI_ElementList {


	/**
	 * HTML attribute: name
	 *
	 * @var string
	 */
	protected $_sName = '';


	/**
	 * HTML attribute: id
	 *
	 * @var string
	 */
	protected $_sID = '';


	/**
	 * HTML attribute: class
	 *
	 * @var string
	 */
	protected $_sCss = '';


	/**
	 * HTML attribute: style
	 *
	 * @var string
	 */
	protected $_sStyle = '';


	/**
	 * Constructor.
	 *
	 * The following configuration options are accepted:
	 * - (string) name
	 * - (string) id
	 * - (string) css
	 * - (string) style
	 * - (string) appendCss
	 * - (string) appendStyle
	 *
	 * @param array $aConfig
	 * @return void
	 */
	public function __construct(array $aConfig = array()) {

		// call parent consturctor
		parent::__construct();

		// process configuration options
		foreach ($aConfig as $sCurrentIndex => $mixedCurrentValue) {
			switch ($sCurrentIndex) {
				case 'name':
					$this->_sName = trim((string)$mixedCurrentValue);
					break;
				case 'id':
					$this->_sID = trim((string)$mixedCurrentValue);
					break;
				case 'css':
					$this->_sCss = trim((string)$mixedCurrentValue);
					break;
				case 'style':
					$this->_sStyle = trim((string)$mixedCurrentValue);
					break;
				case 'appendCss':
					$this->_sCss = trim($this->_sCss.' '.trim((string)$mixedCurrentValue));
					break;
				case 'appendStyle':
					$this->_sStyle = trim($this->_sStyle. ' '.trim((string)$mixedCurrentValue));
					break;
				default:
					throw new Exception('Unprocessed configuration value "'.$sCurrentIndex.'".');
			}
		}

	}


	/**
	 * Generate HTML output.
	 *
	 * @return string
	 */
	public function generateHTML() {

		// generate the output if required
		if ($this->_sHTML === null) {
			$sInnerHMTL = parent::generateHTML();
			$objSmarty = new GUI_SmartyWrapper();
			$objSmarty->assign('sName', $this->_sName);
			$objSmarty->assign('sID', $this->_sID);
			$objSmarty->assign('sCss', $this->_sCss);
			$objSmarty->assign('sStyle', $this->_sStyle);
			$objSmarty->assign('sInnerHTML', $sInnerHMTL);
			$this->_sHTML = $objSmarty->parseTemplate('gui.div.tpl');
		}

		// return the cached output
		return $this->_sHTML;

	}


}

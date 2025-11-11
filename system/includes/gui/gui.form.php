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
 * Class that represents a html form element.
 */
class GUI_Form extends GUI_ElementList {


	/**
	 * HTML attribute: action
	 *
	 * @var string
	 */
	protected $_sAction = null;


	/**
	 * HTML attribute: method
	 *
	 * @var string
	 */
	protected $_sMethod = null;


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
	 * HTML attribute: enctype
	 *
	 * @var string
	 */
	protected $_sEnctype = '';


	/**
	 * HTML attribute: target
	 *
	 * @var string
	 */
	protected $_sTarget = '';
	
	protected $_sOnReset 	= '';
	protected $_sOnSubmit 	= '';

	/**
	 * Constructor.
	 *
	 * The following configuration options are accepted:
	 * - (string) action
	 * - (string) method
	 * - (string) name
	 * - (string) id
	 * - (string) css
	 * - (string) style
	 * - (string) appendCss
	 * - (string) appendStyle
	 * - (string) enctype
	 * - (string) target
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
				case 'action':
					$this->_sAction = trim((string)$mixedCurrentValue);
					break;
				case 'method':
					$this->_sMethod = trim((string)$mixedCurrentValue);
					break;
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
					$this->_sStyle = trim($this->_sStyle.' '.trim((string)$mixedCurrentValue));
					break;
				case 'enctype':
					$this->_sEnctype = trim((string)$mixedCurrentValue);
					break;
				case 'onReset':
					$this->_sOnReset = trim((string)$mixedCurrentValue);
					break;
				case 'onSubmit':
					$this->_sOnSubmit = trim((string)$mixedCurrentValue);
					break;
				case 'target':
					$this->_sTarget = trim((string)$mixedCurrentValue);
					break;
				default:
					throw new Exception('Unprocessed configuration value "'.$sCurrentIndex.'".');
			}
		}

		// set the default action if no action is specified
		if ($this->_sAction === null) {
			$this->_sAction = $_SERVER['PHP_SELF'];
		}

		// set the default method if no method is specified
		if ($this->_sMethod === null) {
			$this->_sMethod = 'post';
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
			$objSmarty->assign('sAction', $this->_sAction);
			$objSmarty->assign('sMethod', $this->_sMethod);
			$objSmarty->assign('sName', $this->_sName);
			$objSmarty->assign('sID', $this->_sID);
			$objSmarty->assign('sCss', $this->_sCss);
			$objSmarty->assign('sStyle', $this->_sStyle);
			$objSmarty->assign('sEnctype', $this->_sEnctype);
			$objSmarty->assign('sTarget', $this->_sTarget);
			$objSmarty->assign('sOnSubmit', $this->_sOnSubmit);
			$objSmarty->assign('sOnReset', $this->_sOnReset);
			$objSmarty->assign('sInnerHTML', $sInnerHMTL);
			$this->_sHTML = $objSmarty->parseTemplate('gui.form.tpl');
		}

		// return the cached output
		return $this->_sHTML;

	}


}

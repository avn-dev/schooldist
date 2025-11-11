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
 * GUI element that will be displayed as a HTML link.
 */
class GUI_Link implements GUI_Element {


	/**
	 * The url the link points to.
	 *
	 * @var string
	 */
	protected $_sLinkURL = '';


	/**
	 * The link url must be escaped before it is passed to
	 * the template.
	 *
	 * @var boolean
	 */
	protected $_bEscapeLinkURL = true;


	/**
	 * The text of the link.
	 *
	 * @var string
	 */
	protected $_sLinkText = '';


	/**
	 * The link text must be escaped before it is passed to
	 * the template.
	 *
	 * @var boolean
	 */
	protected $_bEscapeLinkText = true;


	/**
	 * The value that will be used as HTML attribute "name".
	 *
	 * @var string
	 */
	protected $_sName = '';


	/**
	 * The value that will be used as HTML attribute "id".
	 *
	 * @var string
	 */
	protected $_sID = '';


	/**
	 * The value that will be used as HTML attribute "class".
	 *
	 * @var string
	 */
	protected $_sCss = '';


	/**
	 * The value that will be used as HTML attribute "style".
	 *
	 * @var string
	 */
	protected $_sStyle = '';


	/**
	 * The value that will be used as HTML attribute "onclick".
	 *
	 * @var string
	 */
	protected $_sOnClick = '';
	
	/**
	 * The value that will be used as Eventhandler attribute "ondblclick".
	 *
	 * @var string
	 */
	protected $_sOnDblClick  	= '';
	
	/**
	 * The value that will be used as Eventhandler attribute "onfocus".
	 *
	 * @var string
	 */
	protected $_sOnFocus  		= ''; 
	
	/**
	 * The value that will be used as Eventhandler attribute "onblur".
	 *
	 * @var string
	 */
	protected $_sOnBlur  		= '';
	
	/**
	 * The value that will be used as Eventhandler attribute "onkeydown".
	 *
	 * @var string
	 */
	protected $_sOnKeyDown  	= '';
	
	/**
	 * The value that will be used as Eventhandler attribute "onkeypress".
	 *
	 * @var string
	 */
	protected $_sOnKeyPress  	= ''; 
	
	/**
	 * The value that will be used as Eventhandler attribute "onkeyup".
	 *
	 * @var string
	 */
	protected $_sOnKeyUp  		= '';
	
	/**
	 * The value that will be used as Eventhandler attribute "onmousedown".
	 *
	 * @var string
	 */
	protected $_sOnMouseDown	= '';
	
	/**
	 * The value that will be used as Eventhandler attribute "onmouseup".
	 *
	 * @var string
	 */
	protected $_sOnMouseUp		= '';
	
	/**
	 * The value that will be used as Eventhandler attribute "onmousemove".
	 *
	 * @var string
	 */
	protected $_sOnMouseMove	= '';
	
	/**
	 * The value that will be used as Eventhandler attribute "onmouseout".
	 *
	 * @var string
	 */
	protected $_sOnMouseOut		= '';
	
	/**
	 * The value that will be used as Eventhandler attribute "onmouseover".
	 *
	 * @var string
	 */ 
	protected $_sOnMouseOver	= '';
	
	/**
	 * The cached HTML output.
	 *
	 * Will be null if no cached data is available.
	 *
	 * @var string
	 */
	protected $_sHTML = null;


	/**
	 * Constructor.
	 *
	 * @param array $aConfig
	 * @return void
	 */
	public function __construct(array $aConfig = array()) {

		// initialize dummy variable
		$bDummyVariable = false;

		// link url
		if (array_key_exists('url', $aConfig)) {
			$this->_sLinkURL = $this->_convertInputValue($aConfig['url'], $this->_bEscapeLinkURL, true, false);
		}

		// link text
		if (array_key_exists('text', $aConfig)) {
			$this->_sLinkText = $this->_convertInputValue($aConfig['text'], $this->_bEscapeLinkText, true, true);
		}

		// html attribute: name
		if (array_key_exists('name', $aConfig)) {
			$this->_sName = $this->_convertInputValue($aConfig['name'], $bDummyVariable, false, false);
		}

		// html attribute: id
		if (array_key_exists('id', $aConfig)) {
			$this->_sID = $this->_convertInputValue($aConfig['id'], $bDummyVariable, false, false);
		}

		// html attribute: class
		if (array_key_exists('css', $aConfig)) {
			$this->_sCss = $this->_convertInputValue($aConfig['css'], $bDummyVariable, false, false);
		}
		if (array_key_exists('appendCss', $aConfig)) {
			$this->_sCss .= $this->_convertInputValue($aConfig['appendCss'], $bDummyVariable, false, false);
		}

		// html attribute: style
		if (array_key_exists('style', $aConfig)) {
			$this->_sStyle = $this->_convertInputValue($aConfig['style'], $bDummyVariable, false, false);
		}
		if (array_key_exists('appendStyle', $aConfig)) {
			$this->_sStyle .= $this->_convertInputValue($aConfig['appendStyle'], $bDummyVariable, false, false);
		}

		// html attribute: eventhandler
		if (array_key_exists('onClick', $aConfig)) {
			$this->_sOnClick = $this->_convertInputValue($aConfig['onClick'], $bDummyVariable, false, false);
		}
		
		if (array_key_exists('onDblClick', $aConfig)) {
			$this->_sOnDblClick = $this->_convertInputValue($aConfig['onDblClick'], $bDummyVariable, false, false);
		}
		
		if (array_key_exists('onFocus', $aConfig)) {
			$this->_sOnFocus = $this->_convertInputValue($aConfig['onFocus'], $bDummyVariable, false, false);
		}
		
		if (array_key_exists('onBlur', $aConfig)) {
			$this->_sOnBlur = $this->_convertInputValue($aConfig['onBlur'], $bDummyVariable, false, false);
		}
		
		if (array_key_exists('onKeyDown', $aConfig)) {
		    $this->_sOnKeyDown = $this->_convertInputValue($aConfig['onKeyDown'], $bDummyVariable, false, false);
		}
		
		if (array_key_exists('onKeyPress', $aConfig)) {
		    $this->_sOnKeyPress = $this->_convertInputValue($aConfig['onKeyPress'], $bDummyVariable, false, false);
		}
		
		if (array_key_exists('onKeyUp', $aConfig)) {
			$this->_sOnKeyUp = $this->_convertInputValue($aConfig['onKeyUp'], $bDummyVariable, false, false);
		}
		
		if (array_key_exists('onMouseDown', $aConfig)) {
			$this->_sOnMouseDown = $this->_convertInputValue($aConfig['onMouseDown'], $bDummyVariable, false, false);
		}
		
		if (array_key_exists('onMouseUp', $aConfig)) {
			$this->_sOnMouseUp = $this->_convertInputValue($aConfig['onMouseUp'], $bDummyVariable, false, false);
		}
		
		if (array_key_exists('onMouseMove', $aConfig)) {
			$this->_sOnMouseMove = $this->_convertInputValue($aConfig['onMouseMove'], $bDummyVariable, false, false);
		}
		
		if (array_key_exists('onMouseOut', $aConfig)) {
			$this->_sOnMouseOut = $this->_convertInputValue($aConfig['onMouseOut'], $bDummyVariable, false, false);
		}
		    
		if (array_key_exists('onMouseOver',$aConfig)){
			$this->_sOnMouseOver = $this->_convertInputValue($aConfig['onMouseOver'], $bDummyVariable, false, false);
		}
	}


	/**
	 * Get the url of the link.
	 *
	 * @return string|GUI_EscapedString
	 */
	public function getURL() {
		if ($this->_bEscapeLinkURL != true) {
			return new GUI_EscapedString($this->_sLinkURL);
		}
		return $this->_sLinkURL;
	}


	/**
	 * Set the url of the link.
	 *
	 * @param mixed $mNewURL
	 * @return void
	 */
	public function setURL($mNewURL) {
		$this->_sLinkURL = $this->_convertInputValue($mNewURL, $this->_bEscapeLinkURL, true, false);
		$this->_sHTML = null;
	}


	/**
	 * Get the text of the link.
	 *
	 * @return string|GUI_EscapedString
	 */
	public function getText() {
		if ($this->_bEscapeLinkText != true) {
			return new GUI_EscapedString($this->_sLinkText);
		}
		return $this->_sLinkText;
	}


	/**
	 * Set the text of the link.
	 *
	 * @param mixed $mNewText
	 * @return void
	 */
	public function setText($mNewText) {
		$this->_sLinkText = $this->_convertInputValue($mNewText, $this->_bEscapeLinkText, true, true);
		$this->_sHTML = null;
	}


	/**
	 * Generate the HTML output.
	 *
	 * @return string
	 */
	public function generateHTML() {

		// generate the HTML output if required
		if ($this->_sHTML === null) {
			$objSmarty = new GUI_SmartyWrapper();
			$objSmarty->assign('sLinkURL', $this->_sLinkURL);
			$objSmarty->assign('bEscapeLinkURL', $this->_bEscapeLinkURL);
			$objSmarty->assign('sLinkText', $this->_sLinkText);
			$objSmarty->assign('bEscapeLinkText', $this->_bEscapeLinkText);
			$objSmarty->assign('sName', $this->_sName);
			$objSmarty->assign('sID', $this->_sID);
			$objSmarty->assign('sCss', $this->_sCss);
			$objSmarty->assign('sStyle', $this->_sStyle);
			$objSmarty->assign('sOnClick', $this->_sOnClick);
			$objSmarty->assign('sOnDblClick', $this->_sOnDblClick);
			$objSmarty->assign('sOnFocus', $this->_sOnFocus);
			$objSmarty->assign('sOnBlur', $this->_sOnBlur);
			$objSmarty->assign('sOnKeyDown', $this->_sOnKeyDown);
			$objSmarty->assign('sOnKeyPress', $this->_sOnKeyPress);
			$objSmarty->assign('sOnKeyUp', $this->_sOnKeyUp);
			$objSmarty->assign('sOnMouseDown', $this->_sOnMouseDown);
			$objSmarty->assign('sOnMouseUp', $this->_sOnMouseUp);
			$objSmarty->assign('sOnMouseMove', $this->_sOnMouseMove);
			$objSmarty->assign('sOnMouseOut', $this->_sOnMouseOut);
			$objSmarty->assign('sOnMouseOver', $this->_sOnMouseOver);
			$this->_sHTML = $objSmarty->parseTemplate('gui.link.tpl');
		}

		// return the HMTL output
		return $this->_sHTML;

	}


	/**
	 * Convert the specified input value.
	 *
	 * @param mixed $mValue
	 * @param boolean $bValueMustBeEscaped
	 * @param boolean $bAllowGUIElements
	 * @param boolean $bAllowAllGUIElements
	 * @return string
	 */
	protected function _convertInputValue($mValue, &$bValueMustBeEscaped, $bAllowGUIElements = false, $bAllowAllGUIElements = false) {

		// convert the passed arguments
		$bValueMustBeEscaped  = false;
		$bAllowGUIElements    = (bool)$bAllowGUIElements;
		$bAllowAllGUIElements = (bool)$bAllowAllGUIElements;

		// the value can be of type GUI_Element
		if ($bAllowGUIElements == true && $bAllowAllGUIElements == true) {
			if ($mValue instanceof GUI_Element) {
				return trim((string)$mValue->generateHTML());
			}
		}

		// the value can be of type GUI_EscapedString
		elseif ($bAllowGUIElements == true) {
			if ($mValue instanceof GUI_EscapedString) {
				return trim((string)$mValue->generateHTML());
			}
		}

		// convert the value to string
		$bValueMustBeEscaped = true;
		return trim((string)$mValue);

	}


}

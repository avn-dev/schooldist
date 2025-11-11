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
 * Class that generates a tab box.
 */
class GUI_TabBox extends GUI_ElementList {


	/**
	 * The cached HTML output.
	 *
	 * @var string
	 */
	protected $_sHTML = null;


	/**
	 * The list of tabs that will be displayed.
	 *
	 * Each entry will have the following format:
	 * <sName> => array(
	 *     'text' => <mixedText>,
	 *     'active' => <bActive|null>
	 * )
	 *
	 * @var array
	 */
	protected $_aTabList = array(); //


	/**
	 * The name of the argument that specifies the active tab.
	 *
	 * @var string
	 */
	protected $_sTabArgname = 'tab_active';


	/**
	 * The url that will be loaded on click on a tab.
	 *
	 * @var string
	 */
	protected $_sTabLink = null;


	/**
	 * The specified environment that will be used.
	 *
	 * @var array
	 */
	protected $_aEnvironment = array();


	/**
	 * Do not wrap the titles of the tab box.
	 *
	 * @var boolean
	 */
	protected $_bNoTitleWrap = false;


	/**
	 * Constructor.
	 *
	 * @param array $aConfig
	 * @return void
	 */
	public function __construct(array $aConfig) {

		// call parent consturctor
		parent::__construct();

		// argument: tabLink (must be specified)
		if (array_key_exists('tabLink', $aConfig)) {
			$this->_sTabLink = (string)$aConfig['tabLink'];
		}

		// argument: tabArgname
		if (array_key_exists('tabArgname', $aConfig)) {
			$this->_sTabArgname = (string)$aConfig['tabArgname'];
		}

		// argument: environment
		if (array_key_exists('environment', $aConfig)) {
			$this->_aEnvironment = (array)$aConfig['environment'];
		}

		// argument: noTitleWrap
		if (array_key_exists('noTitleWrap', $aConfig)) {
			$this->_bNoTitleWrap = (bool)$aConfig['noTitleWrap'];
		}

		// argument: tabList
		if (array_key_exists('tabList', $aConfig)) {
			foreach ((array)$aConfig['tabList'] as $sTabName => $aTabData) {

				// convert the tab name to string
				$sTabName = (string)$sTabName;

				// each tab must have a unique name
				if (array_key_exists($sTabName, $this->_aTabList)) {
					throw new Exception('Duplicate tab name "'.$sTabName.'".');
				}

				// the tab data must be an array
				if (!is_array($aTabData)) {
					throw new Exception('Tab data for tab "'.$sTabName.'" is not an array.');
				}

				// initialize tab config
				$aTabConfig = array(
					'text'    => new GUI_EscapedString('&nbsp;'),
					'active'  => null,
					'default' => false
				);

				// tab config: text
				if (array_key_exists('text', $aTabData)) {
					$aTabConfig['text'] = (string)$aTabData['text'];
				}

				// tab config: active
				if (array_key_exists('active', $aTabData)) {
					$aTabConfig['active'] = ($aTabData['active'] === true) ? true : false;
				}

				// tab config: default
				if (array_key_exists('default', $aTabData)) {
					$aTabConfig['default'] = ($aTabData['default'] === true) ? true : false;
				}

				// tab config: spacer
				if (array_key_exists('spacer', $aTabData)) {
					$aTabConfig['spacer'] = ($aTabData['spacer'] === true) ? true : false;
				}

				// append the current tab to the tab list
				$this->_aTabList[$sTabName] = $aTabConfig;

			}
		}

		// there must be at least one tab
		if (count($this->_aTabList) < 1) {
			throw new Exception('At least one tab must be specified.');
		}

		// generate an automatic tab link if iti s not specified
		if ($this->_sTabLink === null) {
			$this->_generateAutomaticTabLink();
		}

	}


	/**
	 * Generate and return the HTML output.
	 *
	 * @return string
	 */
	public function generateHTML() {

		// generate the HTML output is required
		if ($this->_sHTML === null) {

			// determine the selected tab
			$sSelectedTab = null;
			if (array_key_exists($this->_sTabArgname, $this->_aEnvironment)) {
				$sSelectedTab = (string)$this->_aEnvironment[$this->_sTabArgname];
			}

			// initialize table config
			$aTableConfig = array(
				'appendStyle' => 'margin-bottom: 10px;',
				'showth'      => false,
				'cols'        => array()
			);
			$aRowConfig   = array(
				'cols' => array()
			);

			// add all tabs to the table config
			foreach ($this->_aTabList as $sTabName => $aTabData) {

				// generate the tab link for the current tab entry
				$sTabLink = $this->_sTabLink;
				$sTabLink = str_replace('{tab_argname}', $this->_sTabArgname, $sTabLink);
				$sTabLink = str_replace('{tab}', $sTabName, $sTabLink);

				// initialize row config
				$aTableConfig['cols'][] = '';
				$bForceTh = false;
				if ($sSelectedTab == $sTabName) {
					$bForceTh = true;
				}
				if ($aTabData['active'] !== null) {
					$bForceTh = $aTabData['active'];
				}
				if ($sSelectedTab == null && $aTabData['default'] == true) {
					$bForceTh = true;
				}

				// generate the link object for the current tab
				$oLink = new GUI_Link(array('url' => $sTabLink, 'text' => $aTabData['text']));

				// put the link object into a span object if required
				if ($this->_bNoTitleWrap === true) {
					$oSpan = new GUI_Span(array('style' => 'white-space: nowrap;'));
					$oSpan->appendElement($oLink);
					$oLink = $oSpan;
				}

				if($aTabData['spacer'] == true) {
					$oLink = new GUI_EscapedString('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
				}

				$aRowConfigBuffer = array(
					'forceTh' => $bForceTh,
					'text'    => $oLink
				);

				// finalize row config
				$aRowConfig['cols'][] = $aRowConfigBuffer;

			}

			// add an empty col to the table config
			$aTableConfig['cols'][] = array(
				'style' => 'width: 99%',
				'text'  => ''
			);
			$aRowConfig['cols'][] = new GUI_EscapedString('&nbsp;');

			// generate the table object
			$oTable = new GUI_Table($aTableConfig);
			$oTable->appendRow($aRowConfig);
			$oContent = new GUI_Div();
			$oContent->appendElement(new GUI_EscapedString(parent::generateHTML()));
			$oElementList = new GUI_ElementList();
			$oElementList->appendElement($oTable);
			$oElementList->appendElement($oContent);

			// cache the HTML output
			$this->_sHTML = $oElementList->generateHTML();

		}

		// return the HTML output
		return $this->_sHTML;

	}


	/**
	 * Generate an automatic tab link.
	 *
	 * This method will be called from the constructor if required.
	 *
	 * @return void
	 */
	protected function _generateAutomaticTabLink() {

		// initialize the tab link
		$sTabLink = $_SERVER['PHP_SELF'].'?';

		// process environment
		foreach ((array)$this->_aEnvironment as $sCurrentIndex => $sCurrentValue) {

			// convert arguments
			$sCurrentIndex = (string)$sCurrentIndex;
			$sCurrentValue = (string)$sCurrentValue;

			// ignore the specified tab argument name
			if ($sCurrentIndex == $this->_sTabArgname) {
				continue;
			}

			// append the current argument to the tab link
			$sTabLink .= $sCurrentIndex.'='.$sCurrentValue.'&';

		}
		$sTabLink .= '{tab_argname}={tab}&';

		// store the generated tab link		
		$this->_sTabLink = $sTabLink;

	}


}

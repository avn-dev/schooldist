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
 * List of gui elements.
 */
class GUI_ElementList implements GUI_Element {


	/**
	 * Array of all elements that were appended to the list.
	 *
	 * The array contains string values.
	 *
	 * @var array
	 */
	protected $_aElements = array();


	/**
	 * The cached html string.
	 *
	 * The value will be null if no cached data is available.
	 *
	 * @var string
	 */
	protected $_sHTML = null;


	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// placeholder, maybe something will be to do here later ;)
	}


	/**
	 * Append an element to the list.
	 *
	 * @param GUI_Element|string $mElement
	 * @return void
	 */
	public function appendElement($mElement) {

		// append an gui element object to the list
		if ($mElement instanceof GUI_Element) {
			$this->_aElements[] = (string)$mElement->generateHTML();
		}

		// append a string to the list
		else {
			$this->_aElements[] = (string)\Util::convertHtmlEntities((string)$mElement);
		}

		// reset the cached output
		$this->_sHTML = null;

	}


	/**
	 * Append an element to the list and apply nl2br() to the value.
	 *
	 * @param GUI_Element|string $mElement
	 * @return void
	 */
	public function appendElementWrap($mElement) {

		// append an gui element object to the list
		if ($mElement instanceof GUI_Element) {
			$this->_aElements[] = nl2br((string)$mElement->generateHTML());
		}

		// append a string to the list
		else {
			$this->_aElements[] = nl2br((string)\Util::convertHtmlEntities((string)$mElement));
		}

		// reset the cached output
		$this->_sHTML = null;

	}


	/**
	 * Returns the number of elements in the list.
	 *
	 * @return integer
	 */
	public function getElementCount() {
		return count($this->_aElements);
	}


	/**
	 * Returns whether the list contains elements.
	 *
	 * @return boolean
	 */
	public function containsElements() {
		return (count($this->_aElements) > 0);
	}


	/**
	 * Generate HTML output.
	 *
	 * @return string
	 */
	public function generateHTML() {

		// generate the output if required
		if ($this->_sHTML === null) {
			$this->_sHTML = '';
			foreach ($this->_aElements as $sElement) {
				$this->_sHTML .= $sElement;
			}
		}

		// return the cached output
		return $this->_sHTML;

	}


}

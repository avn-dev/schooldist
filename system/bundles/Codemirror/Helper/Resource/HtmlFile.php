<?php

namespace Codemirror\Helper\Resource;

abstract class HtmlFile {

	/**
	 * The name/path of the file.
	 * @var string
	 */
	protected $_sFileName;

	/**
	 * @param string $sFileName <p>
	 * The name/path of the file.
	 * </p>
	 */
	public function __construct($sFileName) {
		$this->_sFileName = $sFileName;
	}

	/**
	 * Returns the file as a string that you can print at a html page.
	 * @return string <p>
	 * The string.
	 * </p>
	 */
	public function encode(){
	}
}
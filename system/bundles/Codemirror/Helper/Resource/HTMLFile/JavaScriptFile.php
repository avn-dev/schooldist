<?php

namespace Codemirror\Helper\Resource\HTMLFile;

use Codemirror\Helper\Resource\HtmlFile;

class JavaScriptFile extends HtmlFile {

	/* {@inheritdoc} */
	public function encode(){
		$sJavaScript = "<script src=\"$this->_sFileName\"></script>";
		return $sJavaScript;
	}
}
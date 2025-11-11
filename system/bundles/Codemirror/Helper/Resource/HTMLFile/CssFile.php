<?php

namespace Codemirror\Helper\Resource\HTMLFile;

use Codemirror\Helper\Resource\HtmlFile;

class CssFile extends HtmlFile{

	/* {@inheritdoc} */
	public function encode(){
		$sCss = "<link rel=\"stylesheet\" href=\"$this->_sFileName\" \>";
		return $sCss;
	}
}
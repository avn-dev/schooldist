<?php

namespace Gui2\Format;

class EscapedString extends \Ext_Gui2_View_Format_Abstract {

	private $type;

	private $charset;

	public function __construct(string $type = 'html', string $charset = 'UTF-8') {
		$this->type = $type;
		$this->charset = $charset;
	}

	public function format($value, &$column = null, &$data = null) {
		return \Util::getEscapedString($value, $this->type, $this->charset);
	}

}

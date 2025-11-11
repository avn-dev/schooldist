<?php

namespace Tc\Gui2\Format;

class JoinArray extends \Ext_Gui2_View_Format_Abstract {

	public function __construct(private string $seperator = ', ') {}

	public function format($value, &$column = null, &$resultData = null) {

		if (!empty($value)) {
			return implode($this->seperator, $value);
		}

		return $value;
	}
}

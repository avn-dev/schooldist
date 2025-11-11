<?php

namespace Gui2\Element;

abstract class AbstractElement extends \Ext_Gui2_Html_Div {

	abstract protected function generate(): \Ext_Gui2_Html_Abstract;

	/**
	 * @return string
	 */
	public function generateHTML($bReadOnly = false) {
		$oBoxDiv = $this->generate();
		return $oBoxDiv->generateHTML($bReadOnly);
	}

}

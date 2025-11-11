<?php

namespace Gui2\Format;

class StripTags extends \Ext_Gui2_View_Format_Abstract {

	public function format($value, &$column = null, &$data = null) 
	{
		return strip_tags($value);
	}

}
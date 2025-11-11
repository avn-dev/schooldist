<?php

namespace Ts\Helper\Admin;

class Html extends \Admin_Html {
	
	public static function getHeadIncludeFile($tailwind = false) {
		$file = ($tailwind) ? 'tailwind-head' : 'head';
		return 'system/bundles/Ts/Resources/views/'.$file.'.inc.tpl';
	}
	
}
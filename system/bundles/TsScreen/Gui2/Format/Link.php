<?php

namespace TsScreen\Gui2\Format;

class Link extends \Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$link = \Core\Helper\Routing::generateUrl('TsScreen.ts_screens_show', ['sKey' => $aResultData['key']]);
		
		$link = '<a href="'.$link.'" target="_blank">'.$link.'</a>';
		
		return $link;
	}

}

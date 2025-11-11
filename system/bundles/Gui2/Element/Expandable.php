<?php

namespace Gui2\Element;

use Admin\Helper\Welcome\Box;

/**
 * @see Box
 * @deprecated
 */
class Expandable extends \Ext_Gui2_Html_Div {
	
	protected $body;
	
	public function __construct($sLabel, $sType = 'default') {
		
		$this->class = 'box box-'.$sType.' collapsed-box';

		$this->aElements[] = '<div class="box-separator"></div>';

		$header = new \Ext_Gui2_Html_Div;
		$header->class = 'box-header with-border';
		$header->{'data-widget'} = 'collapse';


		$title = new \Ext_Gui2_Html_H3;
		$title->class = 'box-title';
		$title->setElement($sLabel);
		
		$header->setElement($title);
		
		$tools = new \Ext_Gui2_Html_Div();
		$tools->class = 'box-tools pull-right';
		
		$button = new \Ext_Gui2_Html_Button();
		$button->type = 'button';
		//$button->class = 'btn btn-gray btn-xs btn-box-tool';
		
		$icon = new \Ext_Gui2_Html_I();
		$icon->class = 'fa fa-plus';
		
		$button->setElement($icon);
		
		$tools->setElement($button);

		$header->setElement($tools);
		
		// Direkt setzen, da setElement überschrieben ist!
		$this->aElements[] = $header;
		
		$this->body = new \Ext_Gui2_Html_Div();
		$this->body->class = 'box-body';
		
		$this->aElements[] = $this->body;
		
	}
	
	public function activateAutoExpand() {
		$this->class = 'box-autoexpand';
		return $this;
	}

	public function withoutBodyPadding() {
		$this->body->class .= ' no-padding';
		return $this;
	}

	public function setElement($mElement) {
		
		$this->body->setElement($mElement);
		
	}

}

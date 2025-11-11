<?php

namespace TsScreen\Service\Elements;

class Editor extends AbstractElement {
	
	public function prepare() {
		
		$this->assign('html', $this->schedule->html);
		
	}
	
}

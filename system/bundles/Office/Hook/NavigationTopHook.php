<?php

namespace Office\Hook;

class NavigationTopHook extends \Core\Service\Hook\AbstractHook {
	
	public function run(array &$mixInput) {
		
		$mixInput[] = [
			'name' => 'office',
			'right' => 'office',
			'title' => 'Office',
			'icon' => 'fa-building',				
			'extension' => 1,	
			'load_content' => 1,
			'url' => '/admin/extensions/office.html',
			'key' => 'office'
		];

	}
	
}

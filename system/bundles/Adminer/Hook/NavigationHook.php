<?php

namespace Adminer\Hook;

class NavigationHook extends \Core\Service\Hook\AbstractHook {

	public function run(array &$mixInput) {

		$mixInput[] = [
			'name' => 'adminer',
			'right' => 'adminer',
			'title' => \L10N::t('Datenbank'),
			'icon' => 'fa-database',				
			'extension' => 1,	
			'type' => 'url',
			'url' => '/admin/adminer'
		];

	}

}
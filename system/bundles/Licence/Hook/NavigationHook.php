<?php

namespace Licence\Hook;

class NavigationHook extends \Core\Service\Hook\AbstractHook {
	
	public function run(array &$mixInput) {
	
		if($mixInput['name'] == 'welcome') {

			$oRoutingHelper = new \Core\Service\RoutingService();

			/*
			 * Array with office navigation items 
			 */
			$arrWelcomeNavigationChilds = $mixInput['childs'];

			$arrWelcomeNavigationChilds[] = [
				\L10N::t('Rechnungen', 'Framework'),
				$oRoutingHelper->generateUrl('Licence.billing_list'),
				0,
				"licence_invoices",
				'',
				'licence.invoices'
			];

			$mixInput['childs'] = $arrWelcomeNavigationChilds;

		}
		
	}
	
}

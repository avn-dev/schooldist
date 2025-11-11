<?php

namespace TsWizard\Hook;

class NavigationHook extends \Core\Service\Hook\AbstractHook
{
	public function run(array &$mixInput)
	{
		if($mixInput['name'] == 'welcome') {
			$mixInput['childs'][] = [
				\L10N::t('Setup-Wizard', 'Framework'),
				\Core\Helper\Routing::generateUrl('TsWizard.setup.index'),
				0,
				['ts_wizard_setup', ''],
				null,
				'admin.wizard',
				'url'
			];
		}
	}
	
}

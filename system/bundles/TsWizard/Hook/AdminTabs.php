<?php

namespace TsWizard\Hook;

use Core\Service\Hook\AbstractHook;

class AdminTabs extends AbstractHook
{
	public function run(array &$tabs, bool &$allowSaving)
	{
		// Solange der Wizard nicht abgeschlossen wurde diesen immer als einzigen Tab anzeigen
		if ((int)\System::d('ts_setup_wizard_completed', 0) === 0) {
			$tabs = [
				[
					'key' => 'admin.wizard',
					'type' => 'url',
					'value' => '/admin/wizard',
					'title' => \L10N::t('Setup-Wizard', 'Framework'),
					'active' => true,
					'closable' => false
				]
			];

			$allowSaving = false;
		}
	}
}
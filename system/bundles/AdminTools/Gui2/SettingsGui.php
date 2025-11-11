<?php

namespace AdminTools\Gui2;

class SettingsGui extends \Ext_TC_Config_Gui2 {

	public function executeGuiCreatedHook() {

		parent::executeGuiCreatedHook();

		/** @var \Ext_TS_Config $config */
		$config = $this->getWDBasic();

		$settings = [];
		foreach ($config->getInternalSettings() as $setting) {
			$settings[$setting['key']] = [
				'description' => $setting['label'],
				'type' => $setting['type'],
				'form_text' => $setting['form_text']
			];
		}

		$this->setConfigurations($settings);

	}

}

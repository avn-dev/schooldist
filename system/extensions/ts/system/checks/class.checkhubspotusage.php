<?php

use TsHubspot\Service\SetupHubspot;

/**
 * Prüft ob es auf der Installation die Hubspot App gibt und ob die Verbindung funktioniert (und Agentur oder das andere)
 */
class Ext_TS_System_Checks_CheckHubspotUsage extends GlobalChecks
{
	public function getTitle()
	{
		return 'Hubspot Usage';
	}

	public function getDescription()
	{
		return 'Checks usage of Hubspot';
	}

	public function executeCheck()
	{
		if(\TcExternalApps\Service\AppService::hasApp(\TsHubspot\Handler\ExternalApp::APP_NAME)) {

			$mailContent = sprintf('Die Hubspot-App ist auf der Installation "%s" installiert.', \System::d('domain'));

			try {
				$mail = new \WDMail();
				$mail->subject = 'Hubspot Usage';
				$mail->text = $mailContent;
				$mail->send(['m.priebe@fidelo.com']);
			} catch (\Throwable $e) {
				return false;
			}
		}

		return true;
	}
}
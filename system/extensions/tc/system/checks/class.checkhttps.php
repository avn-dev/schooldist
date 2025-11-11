<?php
/**
 * Dieser Check prüft, ob der aktuelle Server HTTPS unterstützt
 * 	und prüft die Integrität der HTTPS-Einstellungen.
 *
 * Redmine Ticket #2445
 *
 * @since 30.10.2012
 * @author DG <dg@plan-i.de>
 */
class Ext_TC_System_Checks_CheckHTTPS extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'HTTPS Check';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Check if HTTPS is available on this server. If so, the system will use HTTPS in future.';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck() {

		$aMessages = array();
		$bHTTPSEnabled = false;

		if(
			!empty($_SERVER['HTTPS']) &&
			$_SERVER['HTTPS'] === 'on'
		) {
			System::s('admin_https', 1);
			$aMessages[] = 'HTTPS was already activated.';
			$bHTTPSEnabled = true;
		} else {
			$aMessages[] = 'Try to activate HTTPS...';
			$sDomain = System::d('domain');
			$sDomain = str_replace('http://', 'https://', $sDomain);
			$sUrl = $sDomain.'/admin/index.html';
			$bSecure = Util::checkUrl($sUrl);

			if($bSecure) {
				System::s('domain', $sDomain);
				System::s('admin_https', 1);
				$aMessages[] = 'HTTPs is activated.';
				$bHTTPSEnabled = true;
			} else {
				System::s('admin_https', 0);
				$aMessages[] = 'HTTPS is not available.';
			}
		}

		if($bHTTPSEnabled) {
			$aMessages[] = 'HTTPS is activated';
		} else {
			$aMessages[] = 'HTTPS is not activated';
		}

		Ext_TC_Util::reportMessage('(TC) Update: HTTPS Check - '.end($aMessages).' ('.$_SERVER['HTTP_HOST'].')', $aMessages);

		__pout($aMessages);

		return true;

	}

}
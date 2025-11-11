<?php

class Updates_Requirements_Elasticsearch5 extends Requirement {

	const MIN_ELASTICSEARCH_VERSION = '5.6';
	const MAX_ELASTICSEARCH_VERSION = '6';

	/**
	 * @return bool
	 */
	public function checkSystemRequirements() {

		$sHost = 'localhost';
		$iPort = 9205;
		$sTransport = 'http';

		$this->checkSystemElementsEntry();

		$bCheck = $this->checkConnection($sHost, $iPort, $sTransport);

		if(!$bCheck) {
			return false;
		}

		System::s('elasticsearch_host', $sHost);
		System::s('elasticsearch_port', $iPort);
		System::s('elasticsearch_transport', $sTransport);

		return true;

	}

	/**
	 * Verbindung zu Elasticsearch prüfen, inklusive Versionsabfrage
	 *
	 * @param string $sHost
	 * @param int $iPort
	 * @param string $sTransport
	 * @return bool
	 */
	protected function checkConnection($sHost, $iPort, $sTransport) {

		$sUrl = $sTransport.'://'.$sHost.':'.$iPort;

		$rCurl = curl_init();
		curl_setopt($rCurl, CURLOPT_URL, $sUrl);
		curl_setopt($rCurl, CURLOPT_RETURNTRANSFER, true);
		$sJson = curl_exec($rCurl);

		$aData = json_decode($sJson, true);

		if(!empty($aData['version']['number'])) {

			$sVersion = $aData['version']['number'];

			if(
				version_compare($sVersion, self::MIN_ELASTICSEARCH_VERSION, '>=') &&
				version_compare($sVersion, self::MAX_ELASTICSEARCH_VERSION, '<')
			) {
				return true;
			} else {
				$this->_aErrors[] = 'Elasticsearch version '.$sVersion.' of host doesn\'t match required version range from >='.self::MIN_ELASTICSEARCH_VERSION.' to <'.self::MAX_ELASTICSEARCH_VERSION.' ('.$sUrl.')';
				return false;
			}

		} else {
			$this->_aErrors[] = 'No connection to Elasticsearch host '.$sTransport.'://'.$sHost.':'.$iPort;
			return false;
		}

	}

	/**
	 * Eintrag in die system_elements hinzufügen, denn sonst würde Composer das Bundle übersehen
	 */
	protected function checkSystemElementsEntry() {

		$mCheck = DB::getQueryOne("SELECT * FROM `system_elements` WHERE `file` = 'elasticaadapter'");
		if(!$mCheck) {
			DB::executeQuery("
				INSERT INTO `system_elements` (
					`title`,
					`element`,
					`file`,
					`version`,
					`administrable`,
					`active`
				) VALUES (
					'ElasticaAdapter',
					'modul',
					'elasticaadapter',
					0.01,
					0,
					1
				);
			");
		}

	}

}

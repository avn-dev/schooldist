<?php

namespace Tc\Controller;

class UpdateController extends \MVC_Abstract_Controller {

	// Zugriffschutz geht über Lizenz
	protected $_sAccessRight = null;

	public function database() {

		header('Content-Type: text/html; charset=utf-8'); 

		if(
			!\Ext_TC_Util::isTestSystem() &&
			!\Ext_TC_Util::isDevSystem()
		) {

			$bValid = \Ext_TC_Licence::checkLicenceExist((string)$this->_oRequest->get('licence'));

			// TODO: Entfernen sobald Lizenzverwaltung zentral auf CORE
			if(class_exists('Ext_Thebing_Access_Licence')) {
				if(strpos($_SERVER['HTTP_HOST'], 'test.school.fidelo.com') === false) {
					$bValidOld = \Ext_Thebing_Access_Licence::checkValid((string)$this->_oRequest->get('licence'));
				} else {
					$bValidOld = true;
				}
			}

			if(
				$bValid === false &&
				$bValidOld === false
			) {
				exit();
			}

		}

		if($this->_oRequest->get('task') == 'getStructure') {

			$aCreateStrings = \Ext_TC_Update::getDatabaseStructure();
			echo implode(';', $aCreateStrings);

		}

		if($this->_oRequest->get('task') == 'getCreateTable') {

			$sCreateString = \Ext_TC_Update::getCreateTable($this->_oRequest->get('table'));
			echo $sCreateString;

		}

		if($this->_oRequest->get('task') == 'describe') {

			$aResult = \Ext_TC_Update::getDescribeTable($this->_oRequest->get('table'));
			echo json_encode($aResult);

		}

		if($this->_oRequest->get('task') == 'getInsert') {

			$aResult = \Ext_TC_Update::getInsertTableData($this->_oRequest->get('table'));
			echo json_encode($aResult);

		}

		if($this->_oRequest->get('task') == 'getFiltersets') {

			$aData = array();

			$oFilterset = new \Ext_TC_Gui2_Filterset();
			$aSets = $oFilterset->getObjectList();

			foreach($aSets as $oFilterset)
			{
				$aData[$oFilterset->id] = array(
					'data' => $oFilterset->getArray(),
					'bars' => array(),
				);

				$aBars = $oFilterset->getJoinedObjectChilds('bars');

				foreach($aBars as $oBar)
				{
					$aData[$oFilterset->id]['bars'][$oBar->id] = array(
						'data' => $oBar->getArray(),
						'elements' => array(),
					);

					$aElements = $oBar->getJoinedObjectChilds('elements');

					foreach($aElements as $oElement)
					{
						$aData[$oFilterset->id]['bars'][$oBar->id]['elements'][$oElement->id]['data'] = $oElement->getArray();

						$aData[$oFilterset->id]['bars'][$oBar->id]['elements'][$oElement->id]['basedon'] = $oElement->basedon;

						$aData[$oFilterset->id]['bars'][$oBar->id]['elements'][$oElement->id]['timefilter_from_count'] = $oElement->timefilter_from_count;

						$aData[$oFilterset->id]['bars'][$oBar->id]['elements'][$oElement->id]['timefilter_until_count'] = $oElement->timefilter_until_count;

						$aData[$oFilterset->id]['bars'][$oBar->id]['elements'][$oElement->id]['timefilter_from_type'] = $oElement->timefilter_from_type;

						$aData[$oFilterset->id]['bars'][$oBar->id]['elements'][$oElement->id]['timefilter_until_type'] = $oElement->timefilter_until_type;

						$aLangs = $oElement->i18n;

						foreach($aLangs as $aLang)
						{
							$aData[$oFilterset->id]['bars'][$oBar->id]['elements'][$oElement->id]['i18n'][$aLang['language_iso']] = $aLang['label'];
						}

					}
				}
			}


			echo json_encode($aData);
		}

		die();
		
	}

}
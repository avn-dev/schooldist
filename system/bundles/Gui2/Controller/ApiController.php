<?php

namespace Gui2\Controller;

// TODO MVC_Abstract_Controller wegwerfen
class ApiController extends \MVC_Abstract_Controller
{
	use \Core\Traits\MVCControllerToken;

	protected $sTokenApplication = 'gui2_api';

	protected $_sAccessRight = null;

	public function request(string $version, string $hash, \MVC_Request $oRequest)
	{
		
		if(!$this->checkToken()) {
			$this->_setErrorCode('e0001', 401, $_SERVER['REMOTE_ADDR']);
		}

        // TODO sollten wir nicht eher das Limit von 1000 runtersetzen und/oder eine Pagination ermöglichen?
        ini_set('memory_limit', '1G');

		// Das ist für Fidelo School, damit hier die Schul-ID bei einem API-Request in die Session gesetzt werden kann.
		\Factory::executeStatic('Ext_Gui2', 'manipulateApiRequest', [$oRequest]);
		
		$filters = $oRequest->input('filter');
		$set = $oRequest->get('set', '');

		$listData = \DB::getQueryRow('SELECT * FROM gui2_lists WHERE `hash` = :hash', ['hash' => $hash]);

		if(strpos($listData['origin'], '/') === false) {
			
			$_SERVER['REQUEST_URI'] = '/gui2/page/'.$listData['origin'];
			
			if(!empty($listData['description'])) {
				\Ext_TC_System_Navigation::addIndexEntry($_SERVER['REQUEST_URI'], $listData['title'], $listData['description']);
			}

			$ignoreSets = true;
			if(!empty($set)) {
				$ignoreSets = false;
			}
			
			$factory = new \Ext_Gui2_Factory($listData['origin'], false, $ignoreSets, true, true);
			$gui = $factory->createGui($set);
			$gui->setRequest($oRequest);

			// Maximal 1000 Einträge ausgeben
			$gui->setTableData('limit', 1000);

			$ids = array_filter($oRequest->input('ids', []));

			// Alle Spalten
			$gui->column_flexibility = false;
			$data = $gui->getTableData($filters, [], $ids, 'api', false);

			$entries = [];
			foreach($data['body'] as $row) {
				$entry = [];
				foreach ($row['items'] as $col) {
					$key = !empty($col['db_alias']) ? $col['db_alias'] . '.' . $col['db_column'] : $col['db_column'];
					if (version_compare($version, "1.1", '>=')) {
						$entry[$key] = $col['text'];
						if (
							!isset($entry[$key . '_original']) &&
							$col['original'] != $col['text']
						) {
							$entry[$key . '_original'] = $col['original'];
						}
					} else {
						$entry[$key] = $col['original'];
					}
				}
				$entries[$row['id']] = $entry;
			}

			$this->set('hits', count($entries));
			$this->set('entries', $entries);

		}
	}
}
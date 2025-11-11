<?php

namespace AdminTools\Http\Controller;

use AdminTools\Helper\Util;
use Illuminate\Http\Request;
use Inertia\Inertia;
use ElasticaAdapter\Adapter\Client as ElasticaClient;
use ElasticaAdapter\Facade\Elastica;

class AdminToolsController extends \Illuminate\Routing\Controller {

	public function index(Request $request) {

		$debugMode = \System::d('debugmode');
		$debugIp = \Util::isDebugIP();
		$version = \System::d('version');
		$server = gethostname();
		$licence = \System::d('license');

		$actions = $indexes = [];

		if (class_exists('Ext_TC_System_Tools')) {
			$toolsService = \Ext_TC_System_Tools::getToolsService();
			$actions = array_map(function (array $actions) {
				asort($actions);
				return $actions;
			}, $toolsService->getIdActions());

			$indexes = [...$toolsService->getIndexes(), 'all' => 'ALL'];
		}

		return Inertia::render('Dashboard', compact('licence', 'version', 'server', 'debugMode', 'debugIp', 'actions', 'indexes'));
	}

	public function settings()
	{
		return view('settings', ['fluidContainer' => true]);
	}

	public function elasticsearch(Request $request)
    {
        $aElasticSearchConfig = ElasticaClient::getConnectionConfig();
        $oClient = new ElasticaClient();

        $oStatus = $oClient->getStatus();
        $aIndicesData = $oStatus->getData();

        $aIndexes = \Ext_TC_System_Tools::getToolsService()->getIndexes();
        $aPossibleIndices = [];
        foreach (array_keys($aIndexes) as $sIndex) {
            $sIndexName = Elastica::buildIndexName($sIndex);
            $aPossibleIndices[$sIndexName] = $sIndex;
        }
        $aPossibleIndices = \Ext_TC_Util::addEmptyItem($aPossibleIndices);

        $aIndicesTypes = [];
        $aStats = [];
        $aStack = \Ext_Gui2_Index_Stack::getFromDB();
        $aTypesPerIndex = [];

        foreach ($aIndicesData['indices'] ?? [] as $sIndexName => $aIndex) {
            if (!array_key_exists($sIndexName, $aPossibleIndices)) {
                continue;
            }

            $oIndex = $oClient->getIndex($sIndexName);
            $oStats = $oIndex->getStats();
            $aMapping = $oIndex->getMapping();
            $aStats[$sIndexName] = $oStats->getData();

            $aTypes = array_keys($aMapping);
            $aIndicesTypes[$sIndexName] = ['' => ''] + array_combine($aTypes, $aTypes);
            $aTypesPerIndex[$sIndexName] = implode(', ', $aTypes) ?: '';

            $stackCount[$sIndexName] = count($aStack[$aPossibleIndices[$sIndexName]] ?? []);
        }

        $index = $request->input('index', '');
        $search = $request->input('search', '');
        $showMapping = $request->input('show_mapping', false);

        $oResultSet = null;
        $aMapping = [];
        if ($index) {
            $oIndex = $oClient->getIndex($index);
            $oQuery = new \Elastica\Query();
            if ($search) {
                $aQuery = [
                    'query' => [
                        'query_string' => [
                            'query' => $search,
                        ]
                    ]
                ];
                $oQuery->setRawQuery($aQuery);
            }
            $oQuery->setStoredFields(['*']);

            $oResultSet = $oIndex->search($oQuery, []);

            $fullMapping = $oIndex->getMapping();
            $aMapping = $fullMapping;
        }

        return view('elasticsearch', [
            'fluidContainer' => true,
            'aIndicesData' => $aIndicesData,
            'aPossibleIndices' => $aPossibleIndices,
            'aIndicesTypes' => $aIndicesTypes,
            'aStats' => $aStats,
            'aTypesPerIndex' => $aTypesPerIndex,
            'stackCount' => $stackCount,
            'index' => $index,
            'search' => $search,
            'showMapping' => $showMapping,
            'oResultSet' => $oResultSet,
            'aMapping' => $aMapping,
        ]);
    }

	public function legacyTools()
	{
		return view('legacytools', ['fluidContainer' => true]);
	}

	public function supportSessions()
	{
		return view('support_sessions', ['fluidContainer' => true]);
	}

	public function colors()
	{
		return view('colors', ['fluidContainer' => true]);
	}

	public function toggleDebugMode(Request $request)
	{
		$newValue = \System::d('debugmode') ? 0 : 2;

		Util::setDebugMode($request->ip(), $newValue);

		\Log::getLogger()->info('Admin tools action', ['action' => 'debug-mode', 'new' => $newValue]);

		return response()->json(['success' => true, 'value' => $newValue]);
	}

	public function toggleDebugIp(Request $request)
	{
		if (\Util::isDebugIP()) {
			Util::removeDebugIP($request->ip());
		} else {
			Util::setDebugIP($request->ip());
		}

		\Log::getLogger()->info('Admin tools action', ['action' => 'debug-ip', 'new' => \Util::isDebugIP()]);

		return response()->json(['success' => true, 'value' => \Util::isDebugIP()]);
	}

	public function buttonAction(Request $request)
	{
		if (!$request->has('button')) {
			return response('Bad request', 400);
		}

		\Log::getLogger()->info('Admin tools action', ['action' => $request->input('button'), 'request' => $request->all()]);

		try {
			Util::handleButton($request->input('button'));
			$message = \L10N::t('Aktion erfolgreich ausgeführt', 'Admin tools');
			$success = true;
		} catch (\Throwable $e) {
			$message = \L10N::t('Es ist ein Fehler aufgetreten', 'Admin tools');
			$success = false;

			\Log::getLogger()->error('Admin tools action failed', ['action' => $request->input('button'), 'message' => $e->getMessage()]);
		}

		return response()->json([
			'success' => $success,
			'messages' => [
				['type' => (!$success) ? 'error' : 'success', 'message' => $message],
			]
		]);
	}

	public function action(Request $request)
	{
		\Log::getLogger()->info('Admin tools action', ['action' => $request->input('type'), 'request' => $request->all()]);

		try {
			switch ($request->input('type')) {
				case 'action':
					Util::handleAction($request->input('action'), $request->input('value'));
					break;
				case 'index':
					Util::handleIndex($request->input('action'), $request->input('value'));
					break;
				default:
					throw new \Exception('Unknown action type');
			}

			$message = \L10N::t('Aktion erfolgreich ausgeführt', 'Admin tools');
			$success = true;
		} catch (\Throwable $e) {
			$message = \L10N::t('Es ist ein Fehler aufgetreten', 'Admin tools');
			$success = false;

			\Log::getLogger()->error('Admin tools action failed', ['action' => $request->input('type'), 'message' => $e->getMessage()]);
		}

		return response()->json([
			'success' => $success,
			'messages' => [
				['type' => (!$success) ? 'error' : 'success', 'message' => $message],
			]
		]);
	}
}

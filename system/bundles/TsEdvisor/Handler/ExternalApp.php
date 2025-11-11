<?php

namespace TsEdvisor\Handler;

use TcExternalApps\Interfaces\ExternalApp as TcExternalApp;
use TsEdvisor\Exceptions\ApiException;
use TsEdvisor\Service\Api;

class ExternalApp extends TcExternalApp
{
	const APP_NAME = 'edvisor';

	const CONFIG_API_KEY = 'edvisor.api_key';

	const CONFIG_INBOX = 'edvisor.inbox';

	public function getTitle(): string
	{
		return \L10N::t('Edvisor');
	}

	public function getDescription(): string
	{
		return \L10N::t('Edvisor - Beschreibung');
	}

	public function getIcon()
	{
		return 'fas fa-link';
	}

	public function getCategory(): string
	{
		return \Ts\Hook\ExternalAppCategories::CRM;
	}

	public function getContent(): ?string
	{
		$session = \Core\Handler\SessionHandler::getInstance();
		$apiKey = \System::d(self::CONFIG_API_KEY);

		try {
			if (!empty($apiKey)) {
				$api = Api::default();
				$edvisorSchools = collect($api->getSchoolCompanySchools())
					->mapWithKeys(fn(array $school) => [$school['schoolId'] => $school['name']])
					->toArray();
			}
		} catch (\Throwable) {
			$session->getFlashBag()->add('error', \L10N::t('Die Schulen konnten nicht abgerufen werden. Ist der API-Key korrekt?', TcExternalApp::L10N_PATH));
		}

		$schools = collect(\Ext_Thebing_Client::getSchoolList(false, 0, true));
		$schoolMapping = $schools->mapWithKeys(fn(\Ext_Thebing_School $s) => [$s->id => $s->getMeta('edvisor_id')]);

		$smarty = new \SmartyWrapper();
		$smarty->setTranslationPath(TcExternalApp::L10N_PATH);

		$smarty->assign('session', $session);
		$smarty->assign('appKey', self::APP_NAME);
		$smarty->assign('apiKey', $apiKey);
		$smarty->assign('inbox', \System::d(self::CONFIG_INBOX));
		$smarty->assign('inboxes', \Util::addEmptyItem(\Ext_Thebing_Client::getFirstClient()->getInboxList('use_id')));
		$smarty->assign('schools', $schools);
		$smarty->assign('edvisorSchools', $edvisorSchools ?? []);
		$smarty->assign('schoolMapping', $schoolMapping);

		return $smarty->fetch('@TsEdvisor/external_app.tpl');
	}

	public function saveSettings(\Core\Handler\SessionHandler $session, \MVC_Request $request)
	{
		\System::s(self::CONFIG_API_KEY, $request->input('api_key'));
		\System::s(self::CONFIG_INBOX, $request->input('inbox'));

		$ids = [];
		foreach ($request->input('school_mapping', []) as $schoolId => $edvisorSchoolId) {
			$school = \Ext_Thebing_School::getInstance($schoolId);
			if (in_array($edvisorSchoolId, $ids)) $edvisorSchoolId = null; // Jede Schule nur einmal zuweisen
			empty($edvisorSchoolId) ? $school->unsetMeta('edvisor_id') : $school->setMeta('edvisor_id', $edvisorSchoolId);
			$ids[] = $edvisorSchoolId;
			$school->save();
		}

		if ($request->input('submit') === 'connect') {
			try {
				Api::default()->createWebhook();
			} catch (ApiException $e) {
				if (preg_match('/The URL.*has already been associated with a webhook for this School/i', $e->getMessage())) {
					$session->getFlashBag()->add('error', \L10N::t('Der Webhook wurde bereits angelegt.', TcExternalApp::L10N_PATH));
				} else {
					throw $e;
				}
			}
		}
	}
}
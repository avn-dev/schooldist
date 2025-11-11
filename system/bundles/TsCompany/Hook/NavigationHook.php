<?php

namespace TsCompany\Hook;

use Core\Helper\Routing;
use TcExternalApps\Service\AppService;
use TsCompany\Handler\CoOpApp;

class NavigationHook extends \Core\Service\Hook\AbstractHook {

	public function run(array &$mixInput) {

		if (!AppService::hasApp(CoOpApp::APP_NAME)) {
			return;
		}

		// Menüpunkte ergänzen. Annahme: Zu diesem Zeitpunkt wurden noch keine Rechte geprüft und die Menüpunkte, an
		// denen man sich hier orientiert, sind auf jeden Fall noch vorhanden.

		if($mixInput['name'] == 'ac_tuition') {

			$childs = [];
			foreach($mixInput['childs'] as $child) {

				if ($child[5] === 'thebing_tuition_resources') {
					$childs[] = [
						\L10N::t('Jobzuweisungen', 'Fidelo » Co-Op » Students'),
						Routing::generateUrl('TsCompany.co_op.gui2.students'),
						0,
						['ts_coop_students', ''],
						'ts_coop_students',
						'tuition_coop_students'
					];
				}

				$childs[] = $child;
			}

			$mixInput['childs'] = $childs;

		} else if($mixInput['name'] == 'ac_marketing') {

			$childs = [];
			foreach($mixInput['childs'] as $child) {

				if ($child[5] === 'thebing_marketing_sponsoring') {
					$childs[] = [
						\L10N::t('Firmen', 'Fidelo » Marketing » Firmen'),
						'',
						0,
						['ts_marketing_companies', ''],
						null,
						'marketing_companies'
					];
					$childs[] = [
						\L10N::t('Firmen', 'Fidelo » Marketing » Firmen'),
						Routing::generateUrl('TsCompany.companies.gui2.companies'),
						1,
						['ts_marketing_companies', ''],
						null,
						'marketing_companies_list'
					];
					$childs[] = [
						\L10N::t('Branchen', 'Fidelo » Marketing » Firmen » Branchen'),
						Routing::generateUrl('TsCompany.companies.gui2.industries'),
						1,
						['ts_marketing_industries', ''],
						null,
						'marketing_companies_industries'
					];
					$childs[] = [
						\L10N::t('Arbeitsangebote', 'Fidelo » Marketing » Firmen » Arbeitsangebote'),
						Routing::generateUrl('TsCompany.companies.gui2.job_opportunities'),
						1,
						['ts_marketing_companies_job_opportunities', ''],
						null,
						'marketing_companies_job_opportunities'
					];
				}

				$childs[] = $child;
			}


			$mixInput['childs'] = $childs;

		}

	}

}

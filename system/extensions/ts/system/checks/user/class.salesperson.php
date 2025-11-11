<?php

class Ext_TS_System_Checks_User_Salesperson extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Salesperson update';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '';
		return $sDescription;
	}

	public function executeCheck() {

		$oCategoryRepository = \Tc\Entity\Employee\Category::getRepository();
		$oTestCategory = $oCategoryRepository->findOneBy([]);

		// Sobald es eine Kategorie gibt, muss der Check nicht mehr ausgeführt werden..
		if(!empty($oTestCategory)) {
			return true;
		}

		$oUserRepository = \Ext_Thebing_User::getRepository();

		$aUsers = $oUserRepository->findAll();

		$oEmployeeUserCategory = \Tc\Entity\Employee\Category::getInstance();

		$oEmployeeUserCategory->name = \L10N::t('Benutzer');
		$oEmployeeUserCategory->functions = ['user'];

		$oEmployeeUserCategory->save();

		foreach($aUsers as $oUser) {

			if($oUser->status == 1) {

				$aUserCategories = [$oEmployeeUserCategory->id];

				if ($oUser->ts_is_sales_person == 1) {

					if (!isset($oEmployeeSalespersonCategory)) {

						$oEmployeeSalespersonCategory = \Tc\Entity\SystemTypeMapping::getInstance();
						$oEmployeeSalespersonCategory->name = \L10N::t('Vertriebsmitarbeiter');
						$oEmployeeSalespersonCategory->system_types = ['salesperson'];
						$oEmployeeSalespersonCategory->save();

					}

					$aUserCategories[] = $oEmployeeSalespersonCategory->id;
				}

				$oUser->system_types = $aUserCategories;
				$oUser->save();
			}

		}

		return true;
	}

}

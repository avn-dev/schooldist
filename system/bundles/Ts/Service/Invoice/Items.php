<?php

namespace Ts\Service\Invoice;

class Items
{
	
	private \Ext_Thebing_School $school;
	private array $companySettings;

	public function __construct(\Ext_Thebing_School $school)
	{
		$this->school = $school;
		$this->companySettings = $this->buildCompanySettings();
	}
	
	public function getCompanySettings(): array
	{
		return $this->companySettings;
	}

	/**
	 * Scheint nirgends benutzt zu werden
	 *
	 * @param \Ext_Thebing_Client_Inbox $inbox
	 * @param \TsAccounting\Entity\Company $company
	 * @return array
	 */
	public function getServiceTypesForCompany(\Ext_Thebing_Client_Inbox $inbox, \TsAccounting\Entity\Company $company): array
	{
		$servicesTypes = $this->companySettings[$this->school->id][$inbox->id];
		
		return array_keys(array_filter($servicesTypes, function ($categoryIdToCompanyIdArray, $company) {
			return in_array($company->id, $categoryIdToCompanyIdArray);
		}));

	}
	
	/**
	 * @todo Cachen
	 * @return array
	 */
	private function buildCompanySettings(): array
	{
		$companies = \TsAccounting\Entity\Company::query()->get();
		
		$companySettings = [];
		
		$serviceTypesSelection = new \TsAccounting\Gui2\Selection\Company\ServiceTypes();
		$companyGuiFactory = new \Ext_Gui2_Factory('ts_company');
		$companyGui = $companyGuiFactory->createGui();
		$serviceTypesSelection->setGui($companyGui);
		$serviceTypesOptions = $serviceTypesSelection->getOptions([], [], $company);
		
		$client = new \Ext_Thebing_Client;
		$inboxOptios = $client->getInboxList('use_id');

		/** @var \TsAccounting\Entity\Company $company */
		foreach ($companies as $company) {
			$combinations = $company->getCombinations();
			
			foreach ($combinations as $combination) {
				
				foreach ($combination->schools as $schoolId) {
					$services = $combination->services;
					if (empty($services)) {
						$services = array_keys($serviceTypesOptions);
					}
					
					foreach ($services as $service) {
						$inboxes = $combination->inboxes;
						if (empty($inboxes)) {
							$inboxes = array_keys($inboxOptios);
						}
						
						foreach ($inboxes as $inbox) {

							if (
								$service === 'course' &&
								$company->courses_by_category
							) {
								foreach ($combination->course_categories as $courseCategoryId) {
									$companySettings[$schoolId][$inbox][$service][$courseCategoryId] = $company->id;
								}
							} else {
								$companySettings[$schoolId][$inbox][$service][0] = $company->id;
							}
						}
					}
				}
			}
		}
		return $companySettings;
	}
	
	public function splitItemsByCompany(\Ext_TS_Inquiry $inquiry, array $items): array
	{
		$companyItems = [];
		$inboxId = $inquiry->getInbox()->id;
		$schoolId = $inquiry->getSchool()->id;

		foreach ($items as $item) {

			$itemKey = $item['item_key'] ?? $item['additional_info']['item_key'] ?? \Util::generateRandomString(16);
			// Die Kategorie Id wird zur Zeit nur für den service "course" gesetzt. Ansonsten auf 0 setzen.
			$categoryId = $item['category_id'] ?? $item['additional_info']['category_id'] ?? 0;
			$serviceType = !empty($this->companySettings[$schoolId][$inboxId][$item['type']]) ? $item['type'] : 'others';
			$companyItems[
				$this->companySettings[$schoolId][$inboxId][$serviceType][$categoryId] ??
				$this->companySettings[$schoolId][$inboxId][$serviceType][0] ??
				''
			][$itemKey] = $item;

		}

		return $companyItems;
	}
		
}

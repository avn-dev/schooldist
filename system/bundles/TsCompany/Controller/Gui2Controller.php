<?php

namespace TsCompany\Controller;

use TsCompany\Factory\Gui2Factory;
use TsCompany\Entity;
use TsCompany\Gui2\Dialog;

class Gui2Controller extends \Ext_Gui2_Page_Controller {

	protected $_sInterface = 'backend';

	public function agencies() {

		$gui2Factory = (new Gui2Factory(Entity\AbstractCompany::TYPE_AGENCY, 'agency', Dialog\Agency::class))
			->withContacts(Dialog\Contact\Agency::class, 'TsCompany_agency_contacts')
			->withComments('agencies/comments/', 'thebing_marketing_agencies_notes_edit')
			->withUploads('agencies/documents/', 'thebing_marketing_agencies_uploads_edit')
			->withStudents('agency_id', 'TsCompany_agencies_students');

		// Manuelle Gutschriften

		$parentGui = $gui2Factory->getGui();
		$factory = new \Ext_Gui2_Factory('TsAccounting_manual_creditnotes');
		$oGuiCN = $factory->createGui('agency', $parentGui);

		$gui2Factory->addGui($oGuiCN, ['foreign_key' => 'agency_id', 'parent_primary_key' => 'id', 'reload' => true]);

		$gui2Factory->display();die();

	}

	public function companies() {

		$gui2Factory = (new Gui2Factory(Entity\AbstractCompany::TYPE_COMPANY, 'company', Dialog\Company::class))
			->withContacts(Dialog\Contact\Company::class)
			->withComments('companies/comments/', 'ts_marketing_companies_notes')
			->withUploads('companies/documents/', 'ts_marketing_companies_uploads')
			->withStudents('company_id');
		;

		$gui2Factory->display();die();

	}

	public function industries() {

		$config = [
			'parent' => 'TsCompany_industries',
			'childs' => [
				[
					'path' => 'TsCompany_industries',
					'set' => 'sub_categories',
					'parent_gui' => [
						'foreign_key' => 'parent_id',
						'parent_primary_key' => 'id',
						'reload' => true
					]
				],
			]
		];

		$page = $this->displayPage($config);
		$page->display();die();
	}

	public function jobOpportunities() {

		$config = [
			'parent' => 'TsCompany_job_opportunities',
			'childs' => [
				[
					'path' => 'TsCompany_job_opportunities/student_allocations',
					'set' => 'job_opportunities',
					'parent_gui' => [
						'foreign_key' => 'job_opportunity_id',
						'parent_primary_key' => 'id',
						'reload' => false
					]
				],
			]
		];

		$page = $this->displayPage($config);
		$page->display();die();
	}

	/*public function jobRequirements() {

		$config = [
			'parent' => 'TsCompany_industries',
			'childs' => [
				[
					'path' => 'TsCompany_sub_industries',
					'parent_gui' => [
						'foreign_key' => 'parent_id',
						'parent_primary_key' => 'id',
						'reload' => true
					]
				],
			]
		];

		$page = $this->displayPage($config);
		$page->display();die();
	}*/

	public function journeyEmployments() {

		$config = [
			'parent' => 'TsCompany_journey_employments',
			'childs' => [
				[
					'path' => 'TsCompany_job_opportunities/student_allocations',
					'set' => 'journey_employments',
					'parent_gui' => [
						'foreign_key' => ['inquiry_course_id', 'program_service_id'],
						'parent_primary_key' => ['inquiry_journey_course_id', 'program_service_id'],
						'reload' => true
					]
				],
			]
		];

		$page = $this->displayPage($config);
		$page->display();die();

	}

}

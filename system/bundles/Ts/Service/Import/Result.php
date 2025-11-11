<?php

namespace Ts\Service\Import;

use Core\Handler\SessionHandler as Session;
use Tc\Exception\Import\ImportRowException;
use Tc\Service\Import\ErrorPointer;

class Result extends AbstractImport
{
	protected $sEntity = \Ext_Thebing_Placementtests_Results::class;
	
	/**
	 * @var \Ext_Thebing_Placementtests_Results_Gui2
	 */
	protected \Ext_Thebing_Placementtests_Results_Gui2 $gui2Data;

	public function setFlexFieldData(array $flexFieldData): void
	{
		$this->aFlexFields = $flexFieldData;
	}

	/**
	 * @param \Ext_Thebing_Placementtests_Results_Gui2 $data
	 * @return void
	 */
	public function setGui2Data(\Ext_Thebing_Placementtests_Results_Gui2 $data): void
	{
		$this->gui2Data = $data;
		$this->aFields = $this->getFields();
	}
	
	public function getFields(): array
	{
		$courseLanguages = \Ext_Thebing_Tuition_LevelGroup::getSelectOptions();
		/**
		 * Mapping
		 */
		$fields = [];
		$fields[] = ['field'=> 'Name', 'target' => 'name'];
		$fields[] = ['field'=> 'E-Mail', 'target' => 'email'];
		$fields[] = ['field'=> 'Kurssprache', 'target' => 'courselanguage_id', 'special' => 'array', 'additional' => array_flip($courseLanguages), 'mandatory' => true];
		$fields[] = ['field'=> 'Buchung-ID', 'target' => 'inquiry_id'];
		$fields[] = ['field'=> 'Score', 'target' => 'score'];
		$fields[] = ['field'=> 'Ergebnisdatum', 'target' => 'placementtest_result_date', 'special' => 'date', 'additional' => $this->sExcelDateFormat];
		
		return $fields;
	}
		
	protected function getBackupTables(): array
	{
		$tables = [
			'ts_placementtests_results'
		];
	
		return $tables;
	}

	/**
	 * Anhand Importdaten zugehörige Buchung finden
	 * @param array $item
	 * @return ?int
	 * @throws \Exception
	 */
	protected function findMatchingInquiryId(array $item): ?int
	{
		$contactId = null;
		$contactByEmail = collect();
		$contactByName = collect();

		// Treffer anhand E-Mail ermitteln
		if (!empty($item['email'])) {
			$contactByEmail = \Ext_TC_Contact::query()
				->select('tc_c.*')
				->join('tc_contacts_to_emailaddresses', function ($join) {
					$join
						->on('tc_contacts_to_emailaddresses.contact_id', '=', 'tc_c.id');
				})
				->join('tc_emailaddresses', function ($join) use ($item) {
					$join
						->on('tc_contacts_to_emailaddresses.emailaddress_id', '=', 'tc_emailaddresses.id')
						->where('tc_emailaddresses.email', '=', $item['email']);
				})
				->join('ts_inquiries_to_contacts', function ($join) use ($item) {
					$join
						->on('ts_inquiries_to_contacts.contact_id', '=', 'tc_c.id')
						->where('ts_inquiries_to_contacts.type', '=', 'traveller');
				})
				->groupBy('id')
				->get();
		}

		// Treffer anhand Namen ermitteln
		if (
			$contactByEmail->count() != 1 &&
			!empty($item['name'])
		) {
			$nameParts = explode(' ', $item['name']);
			if (
				!empty($nameParts[0]) &&
				!empty($nameParts[1])
			) {
				$contactByName = \Ext_TC_Contact::query()
					->select('tc_c.*')
					->join('ts_inquiries_to_contacts', function ($join) use ($item) {
						$join
							->on('ts_inquiries_to_contacts.contact_id', '=', 'tc_c.id')
							->where('ts_inquiries_to_contacts.type', '=', 'traveller');
					})
					->where('tc_c.firstname', '=', $nameParts[0])
					->where('tc_c.lastname', '=', $nameParts[1])
					->groupBy('id')
					->get();
			}
		}

		// Prüfen, ob eindeutige contactId ermittelt werden kann
		if ($contactByEmail->count() == 1) { // Exakt ein Treffer anhand E-Mail, eindeutig
			$contactId = $contactByEmail
				->first()
				->id;
		} elseif ($contactByName->count() > 0) { // Name vorhanden, versuchen den richtigen Kontakt zu finden
			if ($contactByEmail->count()) { // Mehrere E-Mailtreffer, gemeinsame E-Mail finden
				$contactIds = array_intersect(
					$contactByName
						->pluck('id')
						->toArray(),
					$contactByEmail
						->pluck('id')
						->toArray()
				);
				if (count($contactIds) == 1) { // Eine gemeinsame Kontakt id, ist eindeutig
					$contactId = $contactIds[0];
				}
			} elseif ($contactByName->count() == 1) { // Keine E-Mail gegeben, nur ein Kontakt anhand Namen, eindeutig
				$contactId = $contactByName
					->first()
					->id;
			}
		}

		if ($contactId) {
			// Buchungen zum Kontakt
			$traveller = \Ext_TS_Inquiry_Contact_Traveller::getInstance($contactId);
			if ($traveller) {
				$inquiry = $traveller->getLatestInquiry();
				// Prüfen ob es Kurse mit der courselanguage gibt
				foreach ($inquiry->getCourses() as $course) {
					if ($course->courselanguage_id == $item['courselanguage_id']) {
						return $inquiry->getId();
					}
				}
			}
		}
		return null;
	}

	/**
	 * Bearbeitet und speichert manuell zugewiesene Einträge
	 * @return array
	 */
	public function executeUnmatched(): array
	{
		$importClass = \Factory::getClassName(\Ext_TC_Import::class);

		$this->sImportKey = \Util::getCleanFilename(get_class($this)).'_'.date('YmdHis');

		$this->oImport = new $importClass($this->sImportKey);
		$this->oImport->activateSave();
		$importClass::$oDb = \DB::getDefaultConnection();
		$importClass::setAutoIncrementReset(false);

		$this->aReport = [
			'insert' => 0,
			'update' => 0,
			'error' => 0
		];

		$importClass::prepareImport($this->getBackupTables(), $this->sImportKey, false);

		\DB::begin(__METHOD__);

		try {
			$count = 0;
			foreach ($this->aItems as $rowId => $item) {

				if (empty(array_filter($item))) {
					continue;
				}
				$count++;
				$entityId = $this->processItem($item, $rowId);

				if (
					!empty($entityId) &&
					!empty($this->aFlexFields['Main'])
				) {
					$entity = $this->sEntity::getInstance($entityId);
					$this->oImport->saveFlexValues($this->aFlexFields['Main'], $item, $entityId, $entity->getEntityFlexType());
				}

				if ($count % 100 === 0) {
					\WDBasic::clearInstances($this->sEntity);
				}

			}

			\DB::commit(__METHOD__);

		} catch (\Throwable $e) {

			$this->aReport['terminated'] = true;
			\DB::rollback(__METHOD__);

		}

		return $this->aReport;
	}
		
	protected function processItem(array &$aItem, int $iItem, array $aAdditionalWorksheetData = null): ?int
	{
		try {
			$data = [];
			\Ext_Thebing_Import::processItems($this->aFields, $aItem, $data);
			$this->checkArraySplitFields($aItem, $data);
			$data['inquiry_id'] = empty($data['inquiry_id']) ? $this->findMatchingInquiryId($data) : $data['inquiry_id'];
			if ($data['inquiry_id']) {
				return $this->saveEntry($data);
			} else {
				// Kein Match gefunden, speichere für 2ten Durchgang des Imports
				$session = Session::getInstance();
				$unmatchedResultImportItems = $session->get('ts_placementtest_import_unmatched_items');
				if (empty($unmatchedResultImportItems)) {
					$unmatchedResultImportItems = [
						'items' => [],
						'flexData' => $this->aFlexFields,
						'settings' => $this->aSettings
					];
				}
				$unmatchedResultImportItems['items'][$iItem] = $aItem;
				$session->set('ts_placementtest_import_unmatched_items', $unmatchedResultImportItems);
			}
		} catch (ImportRowException $e) {

			$pointer = ($e instanceof ImportRowException && $e->hasPointer()) ? $e->getPointer() : new ErrorPointer(null, $iItem);

			$this->aErrors[$iItem] = [
				['message'=>$e->getMessage(), 'pointer' => $pointer]
			];

			$this->aReport['error']++;

			if (empty($this->aSettings['skip_errors'])) {
				throw new \Exception('Terminate import');
			}
		}
		return null;
	}

	/**
	 * Speichert den Import Eintrag. inquiry_id und courselanguage_d werden in $data vorausgesetzt.
	 * @param $data
	 * @return int
	 * @throws \Exception
	 */
	private function saveEntry($data): int
	{
		// Zur Sicherheit erst Inquiry Id prüfen
		try {
			$inquiry = \Ext_TS_Inquiry::getInstance($data['inquiry_id']);
		} catch (\Exception $e) {
			throw new ImportRowException('Inquiry Id '.$data['inquiry_id'].' not found.');
		}

		// Prüfen ob Objekt existiert
		$placementtestResult = \Ext_Thebing_Placementtests_Results::getResultByInquiryAndCourseLanguage($data['inquiry_id'], $data['courselanguage_id']);

		if (empty($placementtestResult->id)) {
			$placementtestResult = \Ext_Thebing_Placementtests_Results::getInstance();
			$placementtestResult->level_id = 0;
			$report = 'insert';
		} else {
			$report = 'update';
		}

		foreach ($data as $field => $value) {
			if (!in_array($field, ['name', 'email'])) {
				$placementtestResult->$field = $value;
			}
		}

		try {
			$placementtestResult->save();
			if (
				!empty($this->aSettings['add_email']) &&
				!empty($data['email'])
			) {
				$customer = $inquiry->getCustomer();
				if (empty($customer->email)) {
					$customer->email = $data['email'];
					$customer->save();
				}
			}
		} catch (\Exception $e) {
			throw new ImportRowException($e->getMessage());
		}

		$this->aReport[$report]++;

		return $placementtestResult->getId();
	}
}
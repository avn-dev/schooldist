<?php

class Ext_TS_System_Checks_Enquiry_MigrateToInquiry extends GlobalChecks {

	/**
	 * @var Ext_TS_System_Checks_Inquiry_Journey_TransferMode
	 */
	private $transferMigration;

	/**
	 * @var array
	 */
	private $enquiry;

	/**
	 * @var array
	 */
	private $productlines;

	/**
	 * @var array
	 */
	private $flexFieldsEnquiry;

	/**
	 * @var array
	 */
	private $flexFieldsGroup;

	/**
	 * @var array
	 */
	private $serviceMappings = [];

	public function getTitle() {
		return 'Enquiry Migration';
	}

	public function getDescription() {
		return 'Migrate enquiry structure to booking structure to allow a lot of improvements for the enquiry section (CRM) in near future.';
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '4G'); // Util::getBacktrace?

		$converted = (int)DB::getQueryOne(" SELECT COUNT(*) FROM ts_enquiries WHERE inquiry_id IS NOT NULL ");
		if ($converted > 0) {
			return true;
		}

		$this->backupTables();

		// Tabellen einmalig leeren, da diese 2012 durch die Buchungsumstellung einmal gefüllt wurden und dann nie wieder
		// Jetzt werden hier bei den Enquiry-Journeys die zugewiesenen Traveller gespeichert (Multiselect)
		DB::executeQuery(" TRUNCATE TABLE ts_inquiries_journeys_courses_to_travellers ");
		DB::executeQuery(" TRUNCATE TABLE ts_inquiries_journeys_accommodations_to_travellers ");
		DB::executeQuery(" TRUNCATE TABLE ts_inquiries_journeys_transfers_to_travellers ");
		DB::executeQuery(" TRUNCATE TABLE ts_inquiries_journeys_insurances_to_travellers ");

		$this->transferMigration = new Ext_TS_System_Checks_Inquiry_Journey_TransferMode();

		DB::begin(__CLASS__);

		$schoolIds = DB::getQueryCol(" SELECT id FROM customer_db_2 WHERE active = 1 ");

		$this->productlines = (array)DB::getQueryPairs(" SELECT school_id, productline_id FROM ts_productlines_schools ");

		$this->flexFieldsEnquiry = (array)DB::getQueryCol(" SELECT tc_fsf.id FROM tc_flex_sections_fields tc_fsf INNER JOIN tc_flex_sections tc_fs ON tc_fs.id = tc_fsf.section_id WHERE tc_fs.type IN ('enquiries_enquiries') ");
		$this->flexFieldsGroup = (array)DB::getQueryCol(" SELECT tc_fsf.id FROM tc_flex_sections_fields tc_fsf INNER JOIN tc_flex_sections tc_fs ON tc_fs.id = tc_fsf.section_id WHERE tc_fs.type IN ('enquiries_groups', 'groups_enquiries_bookings') ");

		$time = microtime(true);

		$enquiries = DB::getQueryRows("
			SELECT
				ts_e.*,
				ts_etc.contact_id,
				ts_g.id group_id,
				GROUP_CONCAT(DISTINCT ts_eti.inquiry_id) converted_to_inquiry,
				ts_eti2.inquiry_id allocate_to_inquiry,
				MIN(ts_i.created) converted
			FROM
				ts_enquiries ts_e LEFT JOIN
				ts_enquiries_to_contacts ts_etc ON
					ts_etc.enquiry_id = ts_e.id AND
					ts_etc.type = 'traveller' LEFT JOIN
				ts_enquiries_to_groups ts_etg ON
					ts_etg.enquiry_id = ts_e.id LEFT JOIN
				ts_groups ts_g ON
					ts_g.id = ts_etg.group_id AND
					ts_g.active = 1 LEFT JOIN
				/* Bei einer Gruppe stehen in ts_enquiries_to_inquiries alle verknüpften Buchungen */
				ts_enquiries_to_inquiries ts_eti ON
					ts_eti.enquiry_id = ts_e.id LEFT JOIN
				ts_inquiries ts_i ON
					ts_i.id = ts_eti.inquiry_id LEFT JOIN
				/* Bei einem einzelnen Schüler ist ts_enquiries_to_inquiries immer 1-1 und bei nochmals umgewandelten immer die letzte Inquiry-ID  */
				(
					ts_enquiries_to_inquiries ts_eti2 INNER JOIN
					ts_inquiries ts_i2 INNER JOIN
					ts_inquiries_to_contacts ts_itc
				) ON
					ts_etg.group_id IS NULL AND
					ts_eti2.enquiry_id = ts_e.id AND
					ts_i2.id = ts_eti2.inquiry_id AND
					ts_i2.group_id = 0 AND
					ts_i2.active = 1 AND
					ts_itc.inquiry_id = ts_i2.id AND
					ts_itc.type = 'traveller' AND
					/* Kontakt muss identisch sein */
					ts_itc.contact_id = ts_etc.contact_id 
			WHERE
				ts_e.school_id IN (:school_ids) AND
				ts_e.active = 1
			GROUP BY
				ts_e.id
		", [
			'school_ids' => $schoolIds
		]);

		foreach ($enquiries as $enquiry) {

			if (empty($enquiry['contact_id'])) {
				// Das sollte eigentlich nicht vorkommen, kommt aber doch vor. Wurde vlt. der Kontakt in dieser Kontaktliste gelöscht?
				$this->logError('Enquiry '.$enquiry['id'].' has no contact, SKIPPING');
				continue;
			}

			$this->enquiry = $enquiry;
			$this->serviceMappings = [];

			$this->migrateEnquiry();

			$this->logInfo(sprintf('Migrated enquiry %d to inquiry %d', $this->enquiry['id'],  $this->enquiry['inquiry_id']));

		}

		$this->migrateFlex();

		$this->logInfo(sprintf('Migration time: %F seconds', microtime(true) - $time));

		// Muss sowieso alles über die Checks aktualisiert werden wegen Typ Buchung usw.
//		Ext_Gui2_Index_Stack::save();

		DB::commit(__CLASS__);

		$this->logInfo('DB transaction committed');

		// Index löschen
		$indexName = Ext_Gui2_Index_Generator::createIndexName('ts_enquiry');
		$index = new \ElasticaAdapter\Adapter\Index($indexName);
		$index->delete(true);

		return true;

	}

	private function backupTables() {

		$time = microtime(true);

		Util::backupTable('ts_inquiries');
		Util::backupTable('ts_inquiries_journeys');
		Util::backupTable('ts_inquiries_journeys_courses');
		Util::backupTable('ts_inquiries_journeys_courses_to_travellers');
		Util::backupTable('ts_inquiries_journeys_accommodations');
		Util::backupTable('ts_inquiries_journeys_accommodations_to_travellers');
		Util::backupTable('ts_inquiries_journeys_transfers');
		Util::backupTable('ts_inquiries_journeys_transfers_to_travellers');
		Util::backupTable('ts_inquiries_journeys_insurances');
		Util::backupTable('ts_inquiries_journeys_insurances_to_travellers');
		Util::backupTable('kolumbus_inquiries_documents');
		Util::backupTable('kolumbus_inquiries_documents_versions_items');
		Util::backupTable('tc_flex_sections_fields');
		Util::backupTable('tc_flex_sections_fields_values');
		Util::backupTable('tc_communication_messages_relations');

		$this->logInfo(sprintf('Table backups took %F seconds', microtime(true) - $time));

	}

	private function migrateEnquiry() {

		$this->enquiry['comment'] = [];

		$groupId = 0;
		if (!empty($this->enquiry['group_id'])) {
			$groupId = $this->migrateGroup();
		} else {
			$this->enquiry['contact_ids'] = [$this->enquiry['contact_id']];
		}

		if ($this->enquiry['allocate_to_inquiry']) {

			$this->enquiry['inquiry_id'] = $this->enquiry['allocate_to_inquiry'];

			DB::executePreparedQuery("
				UPDATE
					ts_inquiries
				SET
					type = :type,
					converted = :converted,
					follow_up = :follow_up,
					created = IF(:created < :converted, :created, created),
					changed = changed
				WHERE
					id = :inquiry_id
			", [
				'inquiry_id' => $this->enquiry['inquiry_id'],
				'converted' => $this->enquiry['converted'],
				'type' => Ext_TS_Inquiry::TYPE_ENQUIRY | Ext_TS_Inquiry::TYPE_BOOKING,
				'follow_up' => $this->enquiry['follow_up'],
				'created' => $this->enquiry['created']
			]);

			$this->logInfo(sprintf('Changed inquiry %d to type enquiry + inquiry, for enquiry %d', $this->enquiry['inquiry_id'], $this->enquiry['id']));

		} else {

			$this->enquiry['inquiry_id'] = DB::insertData('ts_inquiries', [
				'type' => Ext_TS_Inquiry::TYPE_ENQUIRY,
				'anonymized' => $this->enquiry['anonymized'],
				'creator_id' => $this->enquiry['creator_id'],
				'editor_id' => $this->enquiry['editor_id'],
				'group_id' => $groupId,
				'inbox' => Ext_Thebing_Client_Inbox::getInstance($this->enquiry['inbox'])->short,
				'agency_id' => $this->enquiry['agency_id'],
				'follow_up' => $this->enquiry['follow_up'] !== '0000-00-00' ? $this->enquiry['follow_up'] : null,
				'converted' => $this->enquiry['converted'],
				'referer_id' => $this->enquiry['referer_id'],
				'profession' => $this->enquiry['profession'],
				'social_security_number' => $this->enquiry['social_security_number'],
				'agency_contact_id' => $this->enquiry['agency_contact_id'],
				'payment_method' => $this->enquiry['payment_method'],
				'payment_method_comment' => $this->enquiry['payment_method_comment'],
				'currency_id' => $this->enquiry['currency_id'],
				'promotion' => $this->enquiry['promotion_code'], // Voucher ID
				'sales_person_id' => $this->enquiry['sales_person_id'],
				'status_id' => $this->enquiry['status_id'],
				'frontend_log_id' => $this->enquiry['frontend_log_id'],
				'created' => $this->enquiry['created'],
				'changed' => $this->enquiry['changed']
			]);

			// ts_enquiries_to_contacts hat analog zu dieser Tabelle funktioniert und bei Gruppen auch immer nur einen Kontakt gehabt
			DB::insertData('ts_inquiries_to_contacts', [
				'inquiry_id' => $this->enquiry['inquiry_id'],
				'contact_id' => $this->enquiry['contact_id'],
				'type' => 'traveller'
			]);

			// Gruppenbuchungen sind weiterhin über die Zwischentabelle mit der Anfrage verknüpft
			if (!empty($this->enquiry['converted_to_inquiry'])) {
				\Illuminate\Support\Str
					::of($this->enquiry['converted_to_inquiry'])
					->explode(',')
					->each(function ($id) {
						DB::executePreparedQuery("UPDATE ts_inquiries SET converted = :converted, changed = changed WHERE id = :id", [
							'converted' => $this->enquiry['converted'],
							'id' => $id
						]);
						DB::insertData('ts_inquiries_to_inquiries', [
							'parent_id' => $this->enquiry['inquiry_id'],
							'child_id' => $id
						]);
					});
			}

			$this->logInfo(sprintf('New inquiry %d from enquiry %d (no allocation to inquiry possible)', $this->enquiry['inquiry_id'], $this->enquiry['id']));

		}

		$this->migrateCombinationsAndOffers();

		// Kommentarfelder übernehmen (Preferred course categories etc.)
		DB::executePreparedQuery("
			INSERT IGNORE INTO
				wdbasic_attributes
				(entity, entity_id, `key`, `value`)
			SELECT
				'ts_inquiries',
				:inquiry_id,
				CONCAT('enquiry_', `key`),
				`value`
			FROM
				wdbasic_attributes
			WHERE
				entity = 'ts_enquiries' AND
				entity_id = :id
		", $this->enquiry);

		// Alle Zusatzdokumente der Buchung zuweisen
		// Diese Tabelle enthält alle Zusatzdokumente und Angebote, aber Angebote werden bei den Offers dem Journey zugewiesen
		DB::executePreparedQuery("
			UPDATE
				kolumbus_inquiries_documents kid INNER JOIN
				ts_enquiries_to_documents ts_etd ON
					ts_etd.document_id = kid.id
			SET
				kid.entity = '".Ext_TS_Inquiry::class."',
				kid.entity_id = :inquiry_id
			WHERE
				kid.type = 'additional_document' AND
				ts_etd.enquiry_id = :id
		", $this->enquiry);

		// Kommunikation 1: Ext_TS_Enquiry
		DB::executePreparedQuery("
			UPDATE IGNORE
				tc_communication_messages_relations
			SET
				relation = '".Ext_TS_Inquiry::class."',
				relation_id = :inquiry_id
			WHERE
				relation = 'Ext_TS_Enquiry' AND
				relation_id = :id
		", $this->enquiry);

		// Kommunikation 2: Ext_TS_Enquiry_Contact_Traveller
		DB::executePreparedQuery("
			UPDATE IGNORE
				tc_communication_messages_relations
			SET
				relation = '".Ext_TS_Inquiry_Contact_Traveller::class."',
				relation_id = :contact_id
			WHERE
				relation = 'Ext_TS_Enquiry_Contact_Traveller' AND
				relation_id = :contact_id
		", $this->enquiry);

		// Flex-Felder OHNE Gruppe
		DB::executePreparedQuery("
			UPDATE
				tc_flex_sections_fields_values
			SET
				item_id = :inquiry_id,
				item_type = ''
			WHERE
				item_id = :enquiry_id AND
				item_type = 'enquiry' AND
				field_id IN (:field_ids)
		", [
			'enquiry_id' => $this->enquiry['id'],
			'inquiry_id' => $this->enquiry['inquiry_id'],
			'field_ids' => $this->flexFieldsEnquiry
		]);

		if (!empty($this->enquiry['comment'])) {
			$newComment = join("\n", array_unique($this->enquiry['comment']));
			$comment = DB::getQueryRow(" SELECT id, value FROM tc_contacts_details WHERE contact_id = :contact_id AND type = 'comment' AND active = 1 ",  $this->enquiry);
			if (empty($comment)) {
				DB::insertData('tc_contacts_details', ['contact_id' => $this->enquiry['contact_id'], 'type' => 'comment', 'value' => $newComment]);
			} elseif ($comment['value'] !== $newComment) { // Der Kommentar wurde immer 1:1 von der Anfrage in die Kombination kopiert
				$comment['value'] = $newComment;
				DB::executePreparedQuery(" UPDATE tc_contacts_details SET value = CONCAT(value, '\n', :value) WHERE id = :id", $comment);
			}
		}

		DB::executePreparedQuery(" UPDATE ts_enquiries SET inquiry_id = :inquiry_id, changed = changed WHERE id = :id ", $this->enquiry);

//		$diff = Carbon\Carbon::parse($this->enquiry['created'])->diff(Carbon\Carbon::now());
//		$priority = 10 + (($diff->y * 12) + $diff->m);
//
//		Ext_Gui2_Index_Stack::add('ts_inquiry', $this->enquiry['inquiry_id'], $priority);
//
//		if ($groupId) {
//			Ext_Gui2_Index_Stack::add('ts_inquiry_group', $groupId, $priority);
//		}

	}

	private function migrateGroup() {

		$group = (array)DB::getQueryRow(" SELECT * FROM ts_groups WHERE id = :group_id ", $this->enquiry);

		if (empty($group)) {
			throw new \RuntimeException('No group found for enquiry '.$this->enquiry['id']);
		}

		$groupId = DB::insertData('kolumbus_groups', [
			'name' => $group['name'],
			'short' => $group['name_short'],
			'currency_id' => $this->enquiry['currency_id'],
			'course_closed' => $group['closed_class'],
			'number' => $group['number'],
			'numberrange_id' => $group['numberrange_id'],
			'created' => $group['created'],
			'changed' => $group['changed']
		]);

		// Traveller sind über ts_groups_to_contacts zugewiesen
		$contactIds = (array)DB::getQueryCol("
			SELECT
				ts_gtc.contact_id
			FROM
				ts_groups_to_contacts ts_gtc INNER JOIN
				tc_contacts tc_c ON
					tc_c.id = ts_gtc.contact_id AND
					tc_c.active = 1
			WHERE
				ts_gtc.group_id = :group_id AND
				ts_gtc.type = 'enquiry'
		", $this->enquiry);

		foreach ($contactIds as $contactId) {

			// Eigentliche Traveller der Gruppe standen in eigener Tabelle
			DB::insertData('ts_groups_to_contacts', [
				'group_id' => $groupId,
				'contact_id' => $contactId,
				'type' => 'inquiry'
			]);

			$data = $this->enquiry;
			$data['group_contact_id'] = $contactId;

			// Traveller-Gruppen-Flags hatten wieder eine eigene Tabelle
			// Das wurde sehr speziell behandelt, d.h. value = 0 wurde immer gelöscht
			// Für Enquiry-Gruppen stehen die Flags jetzt in tc_contacts_details, da ts_journeys_travellers_detail mit am Journey hängt
			DB::executePreparedQuery("
				INSERT INTO
					tc_contacts_details
					(created, creator_id, editor_id, contact_id, type, value)
				SELECT
					:created created,
					:creator_id creator_id,
					:editor_id editor_id,
					contact_id,
					flag type,
					'1' value
				FROM
					ts_groups_contacts_flags
				WHERE
					group_id = :group_id AND
					contact_id = :group_contact_id
			", $data);

		}

		// Flex-Felder enquiries_groups + groups_enquiries_bookings mit item_type = 'enquiry' gehörten immer der Enquiry statt Gruppe
		DB::executePreparedQuery("
			UPDATE
				tc_flex_sections_fields_values
			SET
				item_id = :group_id,
				item_type = ''
			WHERE
				item_id = :enquiry_id AND
				item_type = 'enquiry' AND
				field_id IN (:field_ids)
		", [
			'enquiry_id' => $this->enquiry['id'],
			'group_id' => $groupId,
			'field_ids' => $this->flexFieldsGroup
		]);

		$this->enquiry['contact_ids'] = $contactIds;

		return $groupId;

	}

	private function migrateCombinationsAndOffers() {

		$combinationsWithOffer = [];
		$combinations = collect(DB::getQueryRows(" SELECT * FROM ts_enquiries_combinations WHERE enquiry_id = :id AND active = 1 ", $this->enquiry));

		$this->enquiry['combination_ids'] = $combinations->pluck('id')->toArray();

		// Angebote: Zuweisung der Leistungen zu Kombinationen, Dokument-Zuweisung
		$offers = (array)DB::getQueryRows("
			SELECT
				ts_eo.id,
				ts_eotd.document_id,
				ts_eoti.inquiry_id,
				GROUP_CONCAT(DISTINCT COALESCE(ts_ecc.combination_id, ts_eca.combination_id, ts_ect.combination_id, ts_eci.combination_id)) combination_ids,
				GROUP_CONCAT(DISTINCT CONCAT(ts_ecc.id, '_', ts_eotcc.contact_id)) course_travellers,
				GROUP_CONCAT(DISTINCT CONCAT(ts_eca.id, '_', ts_eotca.contact_id)) accommodation_travellers,
				GROUP_CONCAT(DISTINCT CONCAT(ts_ect.id, '_', ts_eotct.contact_id)) transfer_travellers,
				GROUP_CONCAT(DISTINCT CONCAT(ts_eci.id, '_', ts_eotci.contact_id)) insurance_travellers
			FROM
				ts_enquiries_offers ts_eo LEFT JOIN
				ts_enquiries_offers_to_combinations_courses ts_eotcc ON
					ts_eotcc.offer_id = ts_eo.id LEFT JOIN
				ts_enquiries_combinations_courses ts_ecc ON
					ts_ecc.id = ts_eotcc.combination_course_id AND
					ts_ecc.combination_id IN (:combination_ids) AND
					ts_ecc.active = 1 LEFT JOIN
				ts_enquiries_offers_to_combinations_accommodations ts_eotca ON
					ts_eotca.offer_id = ts_eo.id LEFT JOIN
				ts_enquiries_combinations_accommodations ts_eca ON		
					ts_eca.id = ts_eotca.combination_accommodation_id AND
					ts_eca.combination_id IN (:combination_ids) AND
					ts_eca.active = 1 LEFT JOIN
				ts_enquiries_offers_to_combinations_transfers ts_eotct ON
					ts_eotct.offer_id = ts_eo.id LEFT JOIN
				ts_enquiries_combinations_transfers ts_ect ON	
					ts_ect.id = ts_eotct.combination_transfer_id AND
					ts_ect.combination_id IN (:combination_ids) AND
					ts_ect.active = 1 LEFT JOIN
				ts_enquiries_offers_to_combinations_insurances ts_eotci ON
					ts_eotci.offer_id = ts_eo.id LEFT JOIN
				ts_enquiries_combinations_insurances ts_eci ON
					ts_eci.id = ts_eotci.combination_insurance_id AND
					ts_eci.combination_id IN (:combination_ids) AND
					ts_eci.active = 1 LEFT JOIN
				/* Tabelle wurde immer als 1-1 behandelt */
				ts_enquiries_offers_to_documents ts_eotd ON
					ts_eotd.enquiry_offer_id = ts_eo.id LEFT JOIN
				/* Bei einzelnen Schülern auf immer nur 1-1, egal wie oft umgewandelt */    
				ts_enquiries_offers_to_inquiries ts_eoti ON
					ts_eoti.enquiry_offer_id = ts_eo.id AND
					ts_eoti.inquiry_id = :allocate_to_inquiry
			WHERE
				ts_eo.enquiry_id = :id AND
				ts_eo.active = 1
			GROUP BY
				ts_eo.id
		", $this->enquiry);

		// Angebote können beliebig oft aus Kombinationen erzeugt werden, daher ist jedes Angebot eine Journey
		foreach ($offers as $offer) {

			$combinationIds = \Illuminate\Support\Str::of($offer['combination_ids'])->explode(',');

			if (
				empty($offer['combination_ids']) || // explode((string)null) === ['']
				$combinationIds->isEmpty()
			) {
				// Früher konnte man scheinbar Kombinationen mit Angebot löschen, daher versuchen, der erstbesten Kombination zuzuweisen
				if (!empty($this->enquiry['combination_ids'])) {
					$combinationIds = collect($this->enquiry['combination_ids']);
					$this->logError(sprintf('No combination found for offer %d / enquiry %d but using first found combination', $offer['id'], $this->enquiry['id']), $this->enquiry['combination_ids']);
				} else {
					// Ist natürlich live aufgetreten, daher komplett leere Kombination anlegen und so durchschleifen
					// Vlt. konnte das durch Mehrfachauswahl auftreten und das Angebot wurde dann der erstbesten/letztbesten ID zugeordnet? Die untere GUI ist aber zukünftig bei Mehrfachauswahl gesperrt.
					$newCombinationId = DB::insertData('ts_enquiries_combinations', ['enquiry_id' => $this->enquiry['id']]);
					$combinations->push(DB::getQueryRow(" SELECT * FROM ts_enquiries_combinations WHERE id = :id", ['id' => $newCombinationId]));
					$combinationIds = collect()->push($newCombinationId);
					$this->logError(sprintf('No combination found for offer %d / enquiry %d, created NEW empty combination %d!', $offer['id'], $this->enquiry['id'], $newCombinationId));

//					// TODO Wenn das live auftritt, muss man überlegen, wie man das lösen soll, weil die Anfrage dann wirklich kaputt ist
//					throw new \RuntimeException(sprintf('No combination found for offer %d / enquiry %d', $offer['id'], $this->enquiry['id']));
				}
			}

			if ($combinationIds->count() > 1) {
				$this->logInfo(sprintf('More than one combination id found for offer %d / enquiry %d: %s', $offer['id'], $this->enquiry['id'], $combinationIds->join(', ')));
			}

			// Zuweisungen von Leistungen zu Travellern (ehemaliger Multiselect-Dialog beim Erstellen des Angebots)
			$travellerAllocations = [];
			foreach (['course', 'accommodation', 'transfer', 'insurance'] as $serviceType) {
				$travellerAllocations[$serviceType] = [];
				if (empty($offers[$serviceType.'_travellers'])) {
					continue;
				}

				// course_travellers, accommodation_travellers etc.
				$travellerData = explode(',', $offers[$serviceType.'_travellers']);
				foreach ($travellerData as $travellerData2) {
					[$combinationServiceId, $travellerId] = explode('_', $travellerData2);
					$travellerAllocations[$serviceType][$combinationServiceId][] = $travellerId;
				}
			}

			// Früher konnte man aus mehreren Kombinationen ein Angebot erzeugen, aber das wurde praktisch nie benutzt
			$combination = $combinations->first(function (array $combination) use ($combinationIds) {
				return $combination['id'] == $combinationIds->first();
			});

			if ($combination === null) {
				throw new \RuntimeException('No matching combination found in array: '.$combinationIds->first());
			}

			// Auf Merge prüfen
			$combination['inquiry_id'] = $offer['inquiry_id'];
			$combination['document_id'] = $offer['document_id'];

			$journeyId = $this->migrateCombination($combination, $travellerAllocations);

			$combinationsWithOffer[] = $combination['id'];

			// Dokument auf Journey migrieren
			if (!empty($offer['document_id'])) {

				$this->migrateOfferDocument($journeyId, $offer);

				$this->logInfo(sprintf('Migrated offer document %d to journey %d', $offer['document_id'], $journeyId));

			}

		}

		// Alle übrigen Kombinationen, die kein Angebot haben, als Journey migrieren
		foreach ($combinations as $combination) {

			if (in_array($combination['id'], $combinationsWithOffer)) {
				continue;
			}

			$this->migrateCombination($combination, []);

		}

		// Anfragen ohne Kombinationen benötigen einen Fake-Journey, da es ansonsten keine Schule mehr gibt
		if ($combinations->isEmpty()) {
			DB::insertData('ts_inquiries_journeys', [
				'created' => $this->enquiry['created'],
				'changed' => $this->enquiry['changed'],
				'creator_id' => $this->enquiry['creator_id'],
				'editor_id' => $this->enquiry['editor_id'],
				'inquiry_id' => $this->enquiry['inquiry_id'],
				'school_id' => $this->enquiry['school_id'],
				'productline_id' => $this->productlines[$this->enquiry['school_id']],
				'type' => Ext_TS_Inquiry_Journey::TYPE_DUMMY,
			]);
		}

	}

	private function migrateCombination(array $combination, array $travellerAllocations): int {

		// Versuchen, die Kombination zu mergen (d.h. gar nicht zu übernehmen, sondern das Offer auf den Journey umzuschreiben)
		// Hierfür müssen alle Item-Relationen des Offers zum Journey-Service gematcht werden können
		// Es gibt zwar überall irgendwelche Relationen, aber natürlich keine zwischen Combination-Service und Journey-Service…
		if (
			empty($this->enquiry['group_id']) &&
			!empty($combination['inquiry_id']) &&
			!empty($combination['document_id'])
		) {

			$getItems = function (string $where) use ($combination) {
				$items = collect(DB::getQueryRows("
					SELECT
						kidvi.*
					FROM
						kolumbus_inquiries_documents kid INNER JOIN
						kolumbus_inquiries_documents_versions_items kidvi ON
							kidvi.version_id = kid.latest_version AND
							kidvi.active = 1
					WHERE
						{$where}
					ORDER BY
						kidvi.id
				", $combination));

				return $items->map(function (array $item) {
					$item['additional_info'] = json_decode($item['additional_info'], true);
					return $item;
				});
			};

			$offerItems = $getItems("kid.id = :document_id");
			$invoiceItems = $getItems("kid.entity = 'Ext_TS_Inquiry' AND kid.entity_id = :inquiry_id");

			$serviceMappings = [];
			foreach ($offerItems as $offerItem) {
				// Vergleich mit item_key, da die Werte immer nur kopiert wurden
				$matchItem = $invoiceItems->first(function (array $invoiceItem) use ($offerItem) {
					return \Illuminate\Support\Arr::get($offerItem['additional_info'], 'item_key') !== null &&
						\Illuminate\Support\Arr::get($offerItem['additional_info'], 'item_key') === \Illuminate\Support\Arr::get($invoiceItem['additional_info'], 'item_key');
				});

				// Versicherungen kennen keinen item_key
				if ($matchItem === null) {
					$matchItem = $invoiceItems->first(function (array $invoiceItem) use ($offerItem) {
						return !empty($offerItem['type_object_id']) &&
							$offerItem['type_object_id'] == $invoiceItem['type_object_id'] &&
							$offerItem['index_from'] == $invoiceItem['index_from'] &&
							$offerItem['index_until'] == $invoiceItem['index_until'];
					});
				}

				switch ($offerItem['type']) {
					case 'course':
						$serviceMappings['course'][$offerItem['type_id']] = $matchItem['type_id'] ?? null;
						break;
					case 'accommodation':
					case 'extra_nights':
					case 'extra_weeks':
						$serviceMappings['accommodation'][$offerItem['type_id']] = $matchItem['type_id'] ?? null;
						break;
					case 'transfer':
						if (!empty($offerItem['type_id'])) {
							$serviceMappings['transfer'][$offerItem['type_id']] = $matchItem['type_id'] ?? null;
						}
						if (
							!empty($offerItem['additional_info']['transfer_arrival_id']) &&
							!empty($offerItem['additional_info']['transfer_departure_id'])
						) {
							$serviceMappings['transfer'][$offerItem['additional_info']['transfer_arrival_id']] = $matchItem['additional_info']['transfer_arrival_id'] ?? null;
							$serviceMappings['transfer'][$offerItem['additional_info']['transfer_departure_id']] = $matchItem['additional_info']['transfer_departure_id'] ?? null;
						}
						break;
					case 'insurance':
						$serviceMappings['insurance'][$offerItem['type_id']] = $matchItem['type_id'] ?? null;
						break;
				}
			}

			$anyMissingService = collect($serviceMappings)
				->flatten()
				->some(function ($value) {
					return empty($value);
				});

			if (!$anyMissingService) {

				$this->serviceMappings = $serviceMappings;
				$combination['journey_id'] = (int)DB::getQueryOne(" SELECT id FROM ts_inquiries_journeys WHERE inquiry_id = :inquiry_id AND active = 1 ", $combination);

				DB::executePreparedQuery("
					UPDATE
						ts_inquiries_journeys
					SET
						type = :type,
						created = IF(:created < created, :created, created),
						changed = changed
					WHERE
						id = :id 
				", [
					'id' => $combination['journey_id'],
					'type' => Ext_TS_Inquiry_Journey::TYPE_REQUEST | Ext_TS_Inquiry_Journey::TYPE_BOOKING,
					'created' => $combination['created']
				]);

				$this->logInfo(sprintf('Merging combination %d with journey %d / inquiry %d', $combination['id'], $combination['journey_id'], $combination['inquiry_id']), $serviceMappings);

			} else {

				$this->logError(sprintf('Tried to merge combination %d with inquiry %d but missing services (document %d, enquiry %d)', $combination['id'], $combination['inquiry_id'], $combination['document_id'], $this->enquiry['id']), $serviceMappings);

			}

		}

		// Kombination konnte nicht gemerged werden, neu anlegen
		if (empty($combination['journey_id'])) {

			$combination['journey_id'] = (int)DB::insertData('ts_inquiries_journeys', [
				'created' => $combination['created'],
				'changed' => $combination['changed'],
				'creator_id' => $combination['creator_id'],
				'editor_id' => $combination['editor_id'],
				'inquiry_id' => $this->enquiry['inquiry_id'],
				'school_id' => $this->enquiry['school_id'],
				'productline_id' => $this->productlines[$this->enquiry['school_id']],
				'type' => Ext_TS_Inquiry_Journey::TYPE_REQUEST,
				'transfer_mode' => $this->transferMigration->calculateTransferMode($combination['transfer_mode'])
			]);

			$this->transferMigration->migrateTransferComment($combination['journey_id'], $combination['transfer_comment']);

			foreach (['course', 'accommodation', 'transfer', 'insurance'] as $serviceType) {
				$this->migrateCombinationService($combination, $serviceType, $travellerAllocations[$serviceType] ?? []);
			}

			$this->logInfo(sprintf('Created journey %d from combination %d', $combination['journey_id'], $combination['id']));

		}

		// Kommentar in der Kombination existiert im Journey nicht
		if (!empty($combination['comment'])) {
			$this->enquiry['comment'][] = $combination['comment'];
		}

		DB::updateData('ts_enquiries_combinations', ['journey_id' => $combination['journey_id']], ['id' => $combination['id']]);

		return $combination['journey_id'];

	}

	private function migrateCombinationService(array $combination, string $type, array $serviceAllocations) {

		// Felder sind meistens gleich benannt, aber natürlich nicht immer
		$courseMapping = ['created', 'changed', 'course_id', 'level_id', 'units', 'from', 'until', 'weeks', 'comment'];
		$accommodationMapping = ['created', 'changed', 'accommodation_id', 'roomtype_id', 'meal_id', 'from', 'until', 'weeks', 'comment'];
		$transferMapping = ['created', 'changed', 'transfer_type', 'start', 'end', 'start_type', 'end_type', 'transfer_date', 'transfer_time', 'start_additional', 'end_additional', 'airline', 'flightnumber', 'pickup', 'comment'];
		$insuranceMapping = ['created', 'changed', 'insurance_id', 'weeks', 'from'];

		$mappings = [
			'course' => array_combine($courseMapping, $courseMapping),
			'accommodation' => array_merge(array_combine($accommodationMapping, $accommodationMapping), ['from_time' => 'arrival_time', 'until_time' => 'departure_time']),
			'transfer' => array_combine($transferMapping, $transferMapping),
			'insurance' => array_combine($insuranceMapping, $insuranceMapping),
		];

		if (!isset($mappings[$type])) {
			throw new \RuntimeException('Mapping for journey/combination service missing: '.$type);
		}

		$table = 'ts_enquiries_combinations_'.$type.'s'; // ts_enquiries_combinations_courses etc.
		$table2 = 'ts_inquiries_journeys_'.$type.'s'; // ts_inquiries_journeys_courses etc.
		$table3 = 'ts_inquiries_journeys_'.$type.'s_to_travellers'; // ts_inquiries_journeys_courses_to_travellers etc.

		$services = (array)DB::getQueryRows(" SELECT * FROM $table WHERE combination_id = :id AND active = 1 ", $combination);

		foreach ($services as $service) {

			$values = array_map(function (string $field) use ($service, $type) {
				if (!array_key_exists($field, $service)) {
					throw new \RuntimeException('Field missing in service: '.$field.' '.$type);
				}
				return $service[$field];
			}, $mappings[$type]);

			$values['journey_id'] = $combination['journey_id'];

			$serviceId = DB::insertData($table2, $values);

			// IDs werden benötigt für Item-Relationen
			$this->serviceMappings[$type][$service['id']] = $serviceId;

			if (empty($serviceAllocations[$combination['id']])) {
				// Bei einer Kombination ohne Angebot den Service allen Travellern zuweisen
				$serviceAllocations[$combination['id']] = $this->enquiry['contact_ids'];
			}

			foreach ($serviceAllocations[$combination['id']] as $contactId) {
				DB::insertData($table3, [
					'journey_'.$type.'_id' => $serviceId,
					'contact_id' => $contactId
				]);
			}

		}

	}

	private function migrateOfferDocument(int $journeyId, array $offer) {

		DB::executePreparedQuery("
			UPDATE
				kolumbus_inquiries_documents
			SET
				entity = :entity,
				entity_id = :entitiy_id,
				type = CONCAT('offer_', type), /* Zur besseren Unterscheidung in der Software wurde offer_ als Präfix eingeführt */
				changed = changed
			WHERE
				id = :id	
		", [
			'id' => $offer['document_id'],
			'entity' => Ext_TS_Inquiry_Journey::class,
			'entitiy_id' => $journeyId
		]);

		$items = (array)DB::getQueryRows("
			SELECT
				kidvi.*
			FROM
				kolumbus_inquiries_documents kid INNER JOIN
				kolumbus_inquiries_documents_versions kidv ON
					kidv.document_id = kid.id AND
					kidv.active = 1 INNER JOIN
				kolumbus_inquiries_documents_versions_items kidvi ON
					kidvi.version_id = kidv.id
			WHERE
				kid.id = :document_id
		", [
			'document_id' => $offer['document_id']
		]);

		$lookupMapping = function (array $item, $type, $id) {
			if (empty($id)) {
				return 0;
			}
			if (!$this->serviceMappings[$type][$id]) {
				$this->logError(sprintf('No item relation found for item %d %s (enquiry %d)', $item['id'], $item['type'], $this->enquiry['id']));
				return 0;
			}
			return $this->serviceMappings[$type][$id];
		};

		// Von allen Items müssen die Relations auf die Journey-Services umgeschrieben werden
		// Vgl. Ext_TS_Enquiry_Convert::_adaptItems()
		// Vgl. Ext_Thebing_Inquiry_Document_Version::adaptItems()
		foreach ($items as $item) {

			$data = [];
			$additional = json_decode($item['additional_info'], true);

			switch ($item['type']) {
				case 'course':
					$data['type_id'] = $lookupMapping($item, 'course', $item['type_id']);
					break;
				case 'accommodation':
				case 'extra_nights':
				case 'extra_weeks':
					$data['type_id'] = $lookupMapping($item, 'accommodation', $item['type_id']);
					if (!empty($additional['accommodation_id'])) {
						$additional['accommodation_id'] = $lookupMapping($item, 'accommodation', $additional['accommodation_id']);
						$data['additional_info'] = json_encode($additional);
					}
					break;
				case 'transfer':
					if (!empty($item['type_id'])) {
						// Kein Paketpreis
						$data['type_id'] = $lookupMapping($item, 'transfer', $item['type_id']);
					}
					if (
						!empty($additional['transfer_arrival_id']) ||
						!empty($additional['transfer_departure_id'])
					) {
						// Paketpreis
						$additional['transfer_arrival_id'] = $lookupMapping($item, 'transfer', $additional['transfer_arrival_id']);
						$additional['transfer_departure_id'] = $lookupMapping($item, 'transfer', $additional['transfer_departure_id']);
						$data['additional_info'] = json_encode($additional);
					}
					break;
				case 'insurance':
					$data['type_id'] = $lookupMapping($item, 'insurance', $item['type_id']);
					break;
				case 'additional_course':
					$data['parent_booking_id'] = $lookupMapping($item, 'course', $item['parent_booking_id']);
					break;
				case 'additional_accommodation':
					$data['parent_booking_id'] = $lookupMapping($item, 'accommodation', $item['parent_booking_id']);
					break;
				default:
					continue 2;
			}

			if (!empty($data)) {
				DB::updateData('kolumbus_inquiries_documents_versions_items', $data, ['id' => $item['id']]);
			}
		}

	}

	private function migrateFlex() {

		// item_type wurde entfernt, da diese Section nur noch für eine Entität da ist (kolumbus_groups(
		DB::executePreparedQuery(" UPDATE tc_flex_sections_fields_values SET item_type = '' WHERE item_type = 'booking' AND field_id IN (:field_ids) ", ['field_ids' => $this->flexFieldsGroup]);

		$oldId = DB::getQueryOne(" SELECT id FROM tc_flex_sections WHERE type = 'enquiries_groups' ");
		$newId = DB::getQueryOne(" SELECT id FROM tc_flex_sections WHERE type = 'groups_enquiries_bookings' ");

		// Section enquiries_groups ist jetzt redundant, daher löschen
		DB::executePreparedQuery(" UPDATE tc_flex_sections SET active = 0 WHERE id = :id ", ['id' => $oldId]);

		// Felder von enquiries_groups auf groups_enquiries_bookings umschreiben
		DB::executePreparedQuery(" UPDATE tc_flex_sections_fields SET section_id = :new_id WHERE section_id = :old_id ", ['old_id' => $oldId, 'new_id' => $newId]);

	}

}
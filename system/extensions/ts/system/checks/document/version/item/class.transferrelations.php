<?php

/**
 * Transfer-items: transfer_arrival_id + transfer_departure_id korrigieren
 *
 * Beim Umwandeln einer Anfrage wurde zwar type_id umgewandelt, das betrifft aber nur Transfere ohne Paketpreis oder individuelle.
 * Bei dem Standardfall ist mit den IDs einfach überhaupt nichts passiert.
 */
class Ext_TS_System_Checks_Document_Version_Item_TransferRelations extends GlobalChecks {

	public function getTitle() {
		return 'Fix transfer item relations';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		Util::backupTable('kolumbus_inquiries_documents_versions_items');

		$items = (array)DB::getQueryRows("
			SELECT
				kidvi.id,
				kidvi.additional_info,
			    ts_i.id inquiry_id,
			    ts_eti.enquiry_id
			FROM
				kolumbus_inquiries_documents_versions_items kidvi INNER JOIN
				kolumbus_inquiries_documents_versions kidv ON
					kidv.id = kidvi.version_id AND
					kidv.active = 1 INNER JOIN
				kolumbus_inquiries_documents kid ON
					kid.id = kidv.document_id INNER JOIN
				ts_inquiries ts_i ON
				    kid.entity = 'Ext_TS_Inquiry' AND
					ts_i.id = kid.entity_id AND
				    ts_i.active = 1 LEFT JOIN
				ts_enquiries_to_inquiries ts_eti ON
					ts_eti.inquiry_id = ts_i.id
			WHERE
				kidvi.active = 1 AND
				kidvi.type = 'transfer' AND
				kidvi.type_id = 0 AND
			    kidvi.additional_info != ''
		");

		foreach ($items as $item) {

			$additional = json_decode($item['additional_info'], true);

			if (
				empty($additional['transfer_arrival_id']) &&
				empty($additional['transfer_departure_id'])
			) {
				continue;
			}

			$services = collect(DB::getQueryRows("
				SELECT
					ts_ijt.*
				FROM
					ts_inquiries ts_i INNER JOIN
					ts_inquiries_journeys ts_ij ON
						ts_ij.inquiry_id = ts_i.id AND
						ts_ij.type & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
						ts_ij.active = 1 INNER JOIN	
					ts_inquiries_journeys_transfers ts_ijt ON
						ts_ijt.journey_id = ts_ij.id AND
						ts_ijt.active = 1
				WHERE
					ts_i.id = :inquiry_id
			", $item));

			$ok = true;

			if (!empty($additional['transfer_arrival_id'])) {
				$service = $services->first(function (array $service) {
					return $service['transfer_type'] == Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL;
				});

				if ($service['id'] != $additional['transfer_arrival_id']) {
					$ok = false;
					$additional['transfer_arrival_id'] = (int)$service['id'];
					$this->logInfo(sprintf('Item %d: transfer_arrival_id %d does not belong to inquiry %d, set to %d (enquiry: %d)', $item['id'], $additional['transfer_arrival_id'], $item['inquiry_id'], $service['id'], $item['enquiry_id']));
				}
			}

			if (!empty($additional['transfer_departure_id'])) {
				$service = $services->first(function (array $service) {
					return $service['transfer_type'] == Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE;
				});

				if ($service['id'] != $additional['transfer_departure_id']) {
					$ok = false;
					$additional['transfer_departure_id'] = (int)$service['id'];
					$this->logInfo(sprintf('Item %d: transfer_departure_id %d does not belong to inquiry %d, set to %d (enquiry: %d)', $item['id'], $additional['transfer_departure_id'], $item['inquiry_id'], $service['id'], $item['enquiry_id']));
				}
			}

			if (!$ok) {
				DB::updateData('kolumbus_inquiries_documents_versions_items', ['additional_info' => json_encode($additional)], ['id' => $item['id']]);
			} else {
				$this->logInfo(sprintf('Item %d (inquiry %d) seems to be correct', $item['id'], $item['inquiry_id']));
			}

		}

		return true;

	}

}
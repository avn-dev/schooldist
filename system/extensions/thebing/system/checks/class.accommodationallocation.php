<?php

class Ext_Thebing_System_Checks_AccommodationAllocation extends Ext_Thebing_System_ThebingCheck {

	public function getTitle() {
		$sTitle = 'Accommodation allocation import';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Imports entries to new database structure.';
		return $sDescription;
	}

	public function executeCheck(){
		global $session_data;

		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
	
		Ext_Thebing_Util::backupTable('kolumbus_rooms_allocation');
		Ext_Thebing_Util::backupTable('kolumbus_accommodations_allocations');
		Ext_Thebing_Util::backupTable('kolumbus_accommodation_communication_status');
		Ext_Thebing_Util::backupTable('kolumbus_accommodation_communication_maillog');

		$sSql = "DROP TABLE IF EXISTS `kolumbus_accommodations_allocations`";
		DB::executeQuery($sSql);

		$sSql = "CREATE TABLE IF NOT EXISTS `kolumbus_accommodations_allocations` (
			  `id` int(11) NOT NULL auto_increment,
			  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			  `active` tinyint(1) NOT NULL default '1',
			  `user_id` int(11) NOT NULL,
			  `inquiry_accommodation_id` int(11) NOT NULL,
			  `room_id` int(11) NOT NULL,
			  `from` datetime NOT NULL,
			  `until` datetime NOT NULL,
			  `share_with` int(11) NOT NULL,
			  `accommodation_confirmed` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `accommodation_transfer_confirmed` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `customer_agency_confirmed` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `accommodation_canceled` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `customer_agency_canceled` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `allocation_changed` timestamp NOT NULL default '0000-00-00 00:00:00',
			  PRIMARY KEY  (`id`),
			  KEY `active` (`active`),
			  KEY `user_id` (`user_id`),
			  KEY `inquiry_accommodation_id` (`inquiry_accommodation_id`),
			  KEY `room_id` (`room_id`),
			  KEY `share_with` (`share_with`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
		DB::executeQuery($sSql);

		try {

			$sSql = "
				SELECT
					`ts_i_j_a`.*
				FROM
					`ts_inquiries_journeys_accommodations` `ts_i_j_a` INNER JOIN
					`ts_inquiries_journeys` `ts_i_j` ON
						`ts_i_j`.`id` = `ts_i_j_a`.`journey_id` AND
						`ts_i_j`.`active` = 1
				WHERE
					`active` = 1
				ORDER BY
					`ts_i_j`.`inquiry_id` ASC,
					`ts_i_j_a`.`from` ASC
			";
			
			$aInquiryAccommodations = DB::getQueryRows($sSql, $aSql);

			foreach((array)$aInquiryAccommodations as $aInquiryAccommodation) {

				$aNewAllocations = array();

				$oInquiry = Ext_TS_Inquiry::getInstance($aInquiryAccommodation['inquiry_id']);

				$sCustomerAgencyConfirmed = '0000-00-00 00:00:00';

				// Schauen ob Kunde / Agentur schon bestätigt wurde
				if(
					$oInquiry->accInfoSent ||
					$oInquiry->agencyAccInfoSent
				) {

					if($oInquiry->accInfoSent) {
						$iSendDate = $oInquiry->accInfoSent;
					} else {
						$iSendDate = $oInquiry->agencyAccInfoSent;
					}

					if($iSendDate != 1) {
						$oTimestamp = new WDDate($iSendDate);
						$sCustomerAgencyConfirmed = $this->_checkDbDate($oTimestamp->get(WDDate::DB_TIMESTAMP));
					}

				}

				/**
				 * Fall 1
				 *
				 * Unterkunft zugeordnet, Kunde und Anbieter bestätigt
				 *
				 * Kunde bestätigt $oInquiry->accInfoSent gesetzt (Agentur bestätigt $oInquiry->agencyAccInfoSent)
				 * Anbieter bestätigt kolumbus_accommodation_communication_status, status = 0
				 *
				 * Fall 2
				 *
				 * Zuordnung geändert
				 *
				 * Kunde bestätigt $oInquiry->accInfoSent zurück auf 0
				 * room_allocation Eintrag aktualisiert
				 *
				 * Alten communication_status auf status = 2 gesetzt und neuen Eintrag erstellt mit status = 1
				 *
				 * Fall 3
				 *
				 * Kunde und Anbieter bestätigt
				 *
				 * Kunde bestätigt $oInquiry->accInfoSent gesetzt
				 *
				 * Beide communication_status Einträge auf status = 0
				 *
				 * LIVE0 > kia.id = 11422
				 *
				 */

				$aSql = array('inquiry_accommodation_id'=>$aInquiryAccommodation['id']);

				// Familien, mit denen kommuniziert wurde
				$sSql = "
					SELECT
						*
					FROM
						`kolumbus_accommodation_communication_status`
					WHERE
						`inquiry_acc_id` = :inquiry_accommodation_id AND
						`active` = 1
					ORDER BY
						`changed` ASC
					";
				$aStatus = DB::getQueryRows($sSql, $aSql);

				$aStatusCache = array();
				foreach((array)$aStatus as $aEntry) {
					$aStatusCache[$aEntry['family_id']] = $aEntry;
				}

				// Aktueller Status
				$sSql = "
					SELECT
						*
					FROM
						`kolumbus_rooms_allocation`
					WHERE
						`accommodation_id` = :inquiry_accommodation_id
					ORDER BY
						`changed` ASC
					";
				$aAllocations = DB::getQueryRows($sSql, $aSql);

				// Vorhandene Allocations durchlaufen
				foreach((array)$aAllocations as $aAllocation) {

					$iAccommodationId = 0;

					$sAccommodationConfirmed = '0000-00-00 00:00:00';
					$sAllocationChanged = '0000-00-00 00:00:00';
					$sAccommodationTransferConfirmed = '0000-00-00 00:00:00';

					if($aAllocation['active'] == 0) {
						$aAllocation['room_id'] = 0;
					}

					// Wenn zugeordnet
					if($aAllocation['room_id'] > 0) {
						$iAccommodationId = $this->_getAccommodationId($aAllocation['room_id']);

						// Wenn es dazu einen Status Eintrag gibt
						if(isset($aStatusCache[$iAccommodationId])) {

							// Wenn Info kommuniziert wurde
							if($aStatusCache[$iAccommodationId]['status'] == 0) {
								$sAccommodationConfirmed = $aStatusCache[$iAccommodationId]['changed'];
							}
							// Wenn geändert oder gelöscht
							if($aStatusCache[$iAccommodationId]['status'] >= 2) {
								$sAllocationChanged = $aStatusCache[$iAccommodationId]['changed'];
							}
							// Wenn Transfer kommuniziert wurde
							if($aStatusCache[$iAccommodationId]['status_transfer'] == 0) {
								$sAccommodationTransferConfirmed = $aStatusCache[$iAccommodationId]['changed'];
							}

							$aStatusCache[$iAccommodationId]['exists'] = 1;

						}

					}

					$oFrom = new WDDate($aAllocation['from']);
					$oTo = new WDDate($aAllocation['to']);

					// Uhrzeit auf 00:00:00 setzen
					if($oFrom->get(WDDate::HOUR) != 0) {
						if($oFrom->get(WDDate::HOUR) <= 12) {
							$oFrom->set(0, WDDate::HOUR);
						} else {
							$oFrom->set(0, WDDate::HOUR);
							$oFrom->add(1, WDDate::DAY);
						}
						$oFrom->set(0, WDDate::MINUTE);
						$oFrom->set(0, WDDate::SECOND);
					}

					if($oTo->get(WDDate::HOUR) != 0) {
						if($oTo->get(WDDate::HOUR) <= 12) {
							$oTo->set(0, WDDate::HOUR);
						} else {
							$oTo->set(0, WDDate::HOUR);
							$oTo->add(1, WDDate::DAY);
						}
						$oTo->set(0, WDDate::MINUTE);
						$oTo->set(0, WDDate::SECOND);
					}

					$aNewAllocation = array();
					$aNewAllocation['created'] = $aAllocation['changed'];
					$aNewAllocation['changed'] = $aAllocation['changed'];
					$aNewAllocation['active'] = 1;
					$aNewAllocation['user_id'] = 0;
					$aNewAllocation['inquiry_accommodation_id'] = (int)$aAllocation['accommodation_id'];
					$aNewAllocation['room_id'] = (int)$aAllocation['room_id'];
					$aNewAllocation['from'] = $oFrom->get(WDDate::DB_DATETIME);
					$aNewAllocation['until'] = $oTo->get(WDDate::DB_DATETIME);
					$aNewAllocation['share_with'] = $aAllocation['shareWith'];
					$aNewAllocation['accommodation_confirmed'] = $sAccommodationConfirmed;
					$aNewAllocation['accommodation_transfer_confirmed'] = $sAccommodationTransferConfirmed;
					$aNewAllocation['customer_agency_confirmed'] = $sCustomerAgencyConfirmed;
					$aNewAllocation['accommodation_canceled'] = '0000-00-00 00:00:00';
					$aNewAllocation['customer_agency_canceled'] = '0000-00-00 00:00:00';
					$aNewAllocation['allocation_changed'] = $sAllocationChanged;

					DB::insertData('kolumbus_accommodations_allocations', $aNewAllocation);

				}

				// Alle Status Einträge durchlaufen, die nicht zu einer vorhandenen allocation gehören
				foreach((array)$aStatusCache as $aStatus) {

					if($aStatus['exists'] == 1) {
						continue;
					}

					$iRoomId = $this->_getRoomId($aStatus['family_id']);

					$sAccommodationConfirmed = '0000-00-00 00:00:00';
					$sAllocationChanged = '0000-00-00 00:00:00';
					$sAccommodationTransferConfirmed = '0000-00-00 00:00:00';

					// Wenn Info kommuniziert wurde
					if($aStatus['status'] == 0) {
						$sAccommodationConfirmed = $aStatus['changed'];
					}
					// Wenn geändert oder gelöscht
					if($aStatus['status'] >= 2) {
						$sAllocationChanged = $aStatus['changed'];
					}
					// Wenn Transfer kommuniziert wurde
					if($aStatus['status_transfer'] == 0) {
						$sAccommodationTransferConfirmed = $aStatus['changed'];
					}

					$aNewAllocation = array();
					$aNewAllocation['created'] = $aStatus['created'];
					$aNewAllocation['changed'] = $aStatus['changed'];
					$aNewAllocation['active'] = 0;
					$aNewAllocation['user_id'] = 0;
					$aNewAllocation['inquiry_accommodation_id'] = (int)$aStatus['inquiry_acc_id'];
					$aNewAllocation['room_id'] = (int)$iRoomId;
					$aNewAllocation['from'] = $aInquiryAccommodation['from'];
					$aNewAllocation['until'] = $aInquiryAccommodation['until'];
					$aNewAllocation['share_with'] = '';
					$aNewAllocation['accommodation_confirmed'] = $sAccommodationConfirmed;
					$aNewAllocation['accommodation_transfer_confirmed'] = $sAccommodationTransferConfirmed;
					$aNewAllocation['customer_agency_confirmed'] = $sCustomerAgencyConfirmed;
					$aNewAllocation['accommodation_canceled'] = '0000-00-00 00:00:00';
					$aNewAllocation['customer_agency_canceled'] = '0000-00-00 00:00:00';
					$aNewAllocation['allocation_changed'] = $sAllocationChanged;

					DB::insertData('kolumbus_accommodations_allocations', $aNewAllocation);

				}

			}

			// Mails in neues E-Mail Log schreiben
			$sSql = "
				SELECT
					*
				FROM
					`kolumbus_accommodation_communication_maillog`
				WHERE
					`active` = 1
				ORDER BY
					`changed` ASC
				";
			$aMailLog = DB::getQueryRows($sSql, $aSql);

			foreach((array)$aMailLog as $aLog) {

				$sApplication = 'accommodation_communication_customer_agency';

				if($aLog['parent_type'] == 'accommodation') {
					$sApplication = 'accommodation_communication_provider';
				}

				$aInquiryIds = explode(",", $aLog['parent_ids']);

				foreach((array)$aInquiryIds as $iInquiryId) {

					$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);

					$aRecipients = array();
					$aRecipients['to'] = array($aLog['email']);
					$sAttachments = $this->getAttachments($aLog['attachments']);

					$sSql = "INSERT INTO
									`kolumbus_email_log`
								SET
									`client_id` = :client_id,
									`application` = :application,
									`object` = :object,
									`object_id` = :object_id,
									`user_id` = :user_id,
									`sender_id` = :sender_id,
									`recipients`= :recipients,
									`documents` = :documents,
									`attachments` = :attachments,
									`flags` = :flags,
									`subject` = :subject,
									`content` = :content
								";
					$aSql['client_id']		= (int)$oInquiry->office;
					$aSql['application']	= $sApplication;
					$aSql['object']			= 'Ext_TS_Inquiry';
					$aSql['object_id']		= (int)$iInquiryId;
					$aSql['user_id']		= (int)$aLog['user_id'];
					$aSql['sender_id']		= (int)$aLog['user_id'];
					$aSql['recipients']		= json_encode($aRecipients);
					$aSql['documents']		= '';
					$aSql['attachments']	= $sAttachments;
					$aSql['flags']			= '';
					$aSql['subject']		= $aLog['subject'];
					$aSql['content']		= $aLog['content'];

					DB::executePreparedQuery($sSql,$aSql);

				}

				unset($session_data['queryhistory']);

			}
			$sSql = "DROP TABLE `kolumbus_accommodation_communication_maillog`";
			DB::executeQuery($sSql);
			$sSql = "DROP TABLE `kolumbus_accommodation_communication_status`";
			DB::executeQuery($sSql);
			$sSql = "DROP TABLE `kolumbus_rooms_allocation`";
			DB::executeQuery($sSql);

		} catch(DB_QueryFailedException $e) {
			__pout($e);		
		} catch(Exception $e) {
			__pout($e);
		}

		return true;

	}

	public function getAttachments($sOldAttachments){
		$sAttachments = '';
		$aOldAttachments =  unserialize  ( $sOldAttachments );
		if(empty($aOldAttachments)) {
			return '';
		}
		$aTemp = array();

		foreach((array)$aOldAttachments as $sKey => $sValue){
			$aPath = pathinfo($sValue);

			$aTemp[$sValue] = $aPath['basename'];
		}

		$sAttachments = json_encode($aTemp);
		return $sAttachments;
	}

	protected function _getRoomId($iAccommodationId) {

		$sSql = "
			SELECT
				`id`
			FROM
				`kolumbus_rooms`
			WHERE
				`accommodation_id` = :accommodation_id
			LIMIT 1
			";
		$aSql = array('accommodation_id'=>(int)$iAccommodationId);

		$iRoomId = DB::getQueryOne($sSql, $aSql);

		return $iRoomId;

	}

	protected function _getAccommodationId($iRoomId) {

		$sSql = "
			SELECT
				`accommodation_id`
			FROM
				`kolumbus_rooms`
			WHERE
				`id` = :room_id
			LIMIT 1
			";
		$aSql = array('room_id'=>(int)$iRoomId);

		$iAccommodationId = DB::getQueryOne($sSql, $aSql);

		return $iAccommodationId;

	}

	protected function _checkDbDate($sDate) {
		$oDate = new WDDate($sDate, WDDate::DB_DATETIME);
		if($oDate->get(WDDate::YEAR) < 2000) {
			return '0000-00-00 00:00:00';
		} else {
			return $sDate;
		}
	}

}

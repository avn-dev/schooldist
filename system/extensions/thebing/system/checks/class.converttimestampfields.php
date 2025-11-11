<?php

class Ext_Thebing_System_Checks_ConvertTimestampFields extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Convert timestamp fields';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Changes database table structure to get better control of different timezones.';
		return $sDescription;
	}

	public function executeCheck(){
		global $user_data, $system_data;

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

//		$aTables = DB::listTables();
//
//		$aClientTables = array();
//		foreach((array)$aTables as $sTable) {
//
//			if(
//				strpos($sTable, '__') === 0 ||
//				strpos($sTable, 'kolumbus') !== 0
//			) {
//				continue;
//			}
//
//			$aDescribe = DB::describeTable($sTable);
//
//			foreach((array)$aDescribe as $aField) {
//				$sType = strtolower($aField['DATA_TYPE']);
//				if(
//					$sType == 'timestamp' &&
//					$aField['COLUMN_NAME'] != 'changed' &&
//					$aField['COLUMN_NAME'] != 'created' &&
//					$aField['COLUMN_NAME'] != 'last_changed' &&
//					$aField['COLUMN_NAME'] != 'last_login' &&
//					$aField['COLUMN_NAME'] != 'deleted' &&
//					$aField['COLUMN_NAME'] != 'released' &&
//					$aField['COLUMN_NAME'] != 'received' &&
//					strpos($aField['COLUMN_NAME'], 'changed') === false &&
//					strpos($aField['COLUMN_NAME'], 'confirmed') === false &&
//					strpos($aField['COLUMN_NAME'], 'canceled') === false &&
//					strpos($aField['COLUMN_NAME'], 'updated') === false &&
//					strpos($aField['COLUMN_NAME'], 'sent') === false
//				) {
//					$sTimestampFields[$sTable][$aField['COLUMN_NAME']] = $aField['COLUMN_NAME'];
//				}
//
//			}
//
//		}


		$aTimestampFields = array(
			'kolumbus_accommodation_nightprices_periods' => array(
				'from' => 'from',
				'until' => 'until'
			),

			'kolumbus_accounting_taxes' => array
				(
					'valid_from' => 'valid_from'
				),

			'kolumbus_groups_accommodations' => array
				(
					'from' => 'from',
					'until' => 'until'
				),

			'kolumbus_groups_courses' => array
				(
					'from' => 'from',
					'until' => 'until'
				),

			'kolumbus_inquiries' => array
				(

					'visum_passport_date_of_issue' => 'visum_passport_date_of_issue',
					'visum_passport_due_date' => 'visum_passport_due_date'
			),

			'kolumbus_inquiries_accommodations' => array
				(
					'from' => 'from',
					'until' => 'until'
				),

			'kolumbus_inquiries_courses' => array
				(
					'from' => 'from',
					'until' => 'until'
				),

			'kolumbus_inquiries_holidays' => array
				(
					'from' => 'from',
					'until' => 'until'
				),

			'kolumbus_inquiries_holidays_accommodationsave' => array
				(
					'from' => 'from',
					'until' => 'until'
				),

			'kolumbus_inquiries_holidays_coursesave' => array
				(
					'from' => 'from',
					'until' => 'until'
				),

			'kolumbus_inquiries_holidays_splitting' => array
				(
					'from' => 'from',
					'until' => 'until'
				),

			'kolumbus_specials' => array
				(
					'from' => 'from',
					'to' => 'to'
				)
		);

		foreach((array)$aTimestampFields as $sTable=>$aFields) {
			Ext_Thebing_Util::backupTable($sTable);
			foreach((array)$aFields as $sField) {
				$this->convertField($sTable, $sField);
			}
		}

		/**
		 * delete some old tables
		 */
		$aDeleteTables = array(
			'customer_db_6',
			'customer_db_9',
			'customer_db_14',
			'customer_db_15',
			'customer_db_16',
			'customer_db_17',
			'customer_db_19',
			'customer_db_20',
			'customer_db_25',
			'customer_db_26',
			'customer_db_27',
			'customer_db_28',
			'customer_db_29',
			'customer_db_30',
			'customer_db_31',
			'kolumbus_promotion',
			'kolumbus_accounting_accommodation'
			);
		foreach((array)$aDeleteTables as $sDeleteTable) {
			try {
				Ext_Thebing_Util::backupTable($sDeleteTable);
				$sSql = "DROP TABLE #table";
				$aSql = array('table'=>$sDeleteTable);
				DB::executePreparedQuery($sSql, $aSql);
			} catch(Exception $e) {
			} catch(DB_QueryFailedException $e) {
			}
		}

		return true;

	}

	protected function convertField($sTable, $sField) {

		$sSql = "
			UPDATE
				#table
			SET
				`changed` = `changed`,
				#field = IF(
					HOUR(#field) <= 12,
					DATE_SUB(#field, INTERVAL HOUR(#field) HOUR),
					DATE_ADD(#field, INTERVAL (24-HOUR(#field)) HOUR)
				)
			WHERE
				#field != :zero_timestamp
			";
		$aSql = array(
			'table'=>$sTable,
			'field'=>$sField,
			'zero_timestamp'=>'0000-00-00 00:00:00'
		);
		DB::executePreparedQuery($sSql, $aSql);

		$sSql = "ALTER TABLE #table CHANGE #field #field DATE NOT NULL DEFAULT '0000-00-00'";
		$aSql = array('table'=>$sTable, 'field'=>$sField);
		DB::executePreparedQuery($sSql, $aSql);

	}

}
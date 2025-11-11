<?php

class Ext_TS_System_Checks_Accounting_Bookingstack_ExportedFiles extends GlobalChecks {

	protected $aDirs = ['datev', 'quickbooks', 'sage'];

	public function getTitle() {
		return 'Booking stack maintenance';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		Util::backupTable('ts_booking_stack_histories');

		// /media/secure bzw. storage/-Pfad rauslöschen
		$sSql = "
			SELECT
				*
			FROM
				`ts_booking_stack_histories`
		";

		$aResult = (array)DB::getQueryRows($sSql);
		foreach($aResult as $aRow) {

			$aData = [
				'file_export' => $aRow['file_export'],
				'file_json' => $aRow['file_json'],
				'created' => $aRow['created'] // ON UPDATE […]
			];

			foreach($this->aDirs as $sDir) {
				foreach(['file_export', 'file_json'] as $sField) {
					$aData[$sField] = str_replace('/media/secure/'.$sDir.'/export', 'booking_stack/'.$sDir, $aData[$sField]);
					$aData[$sField] = str_replace('media/secure/'.$sDir.'/export', 'booking_stack/'.$sDir, $aData[$sField]);
					$aData[$sField] = str_replace('/storage/'.$sDir.'/export', 'booking_stack/'.$sDir, $aData[$sField]);
					$aData[$sField] = str_replace('storage/'.$sDir.'/export', 'booking_stack/'.$sDir, $aData[$sField]);
				}
			}

			DB::updateData('ts_booking_stack_histories',$aData, "`id` = ".$aRow['id']);

		}


		$aReplaces = ['/media/secure/', 'media/secure/', '/storage/', 'storage/'];
		foreach($aReplaces as $sReplace) {

			$sSql = "
				UPDATE
					`ts_booking_stack_histories`
				SET
					`file_json` = REPLACE(`file_json`, :search, ''),
					`file_export` = REPLACE(`file_export`, :search, '')
			";

			DB::executePreparedQuery($sSql, ['search' => $sReplace]);

		}

		// Dateien in neues Unterverzeichnis verschieben
		foreach($this->aDirs as $sDir) {

			$sSrcDir = Util::getDocumentRoot().'storage/'.$sDir;
			$sTargetDir = Util::getDocumentRoot().'storage/booking_stack/'.$sDir;

			if(is_dir($sSrcDir)) {

				Util::checkDir($sTargetDir);

				$aFiles = glob($sSrcDir.'/export/*');
				foreach($aFiles as $sFile) {
					if(!rename($sFile, $sTargetDir.'/'.basename($sFile))) {
						throw new RuntimeException('Could not rename "'.$sFile.'"!');
					}
				}

				rmdir($sSrcDir.'/export');
				rmdir($sSrcDir);

			}

		}

		return true;

	}

}
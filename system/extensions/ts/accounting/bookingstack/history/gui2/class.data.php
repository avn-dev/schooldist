<?php

/**
 * Class Ext_TS_Accounting_BookingStack_History_Gui2_Data
 */
class Ext_TS_Accounting_BookingStack_History_Gui2_Data extends Ext_Thebing_Gui2_Data {

	/**
	 * Gibt die Sortierung einer Liste wieder.
	 *
	 * @return array
	 */
	public static function getOrderBy() {
		return ['created' => 'DESC'];
	}

	public function requestAsUrlZipExport($aVars) {

		ini_set('memory_limit', '8G');
		set_time_limit(600);

		$now = new \DateTime;
		
		$zipFileName = 'bookingstack_history_export_'.$now->format('YmdHisv').'.zip';
		
		$zipFile = \Util::getDocumentRoot().'storage/tmp/'.$zipFileName;

		$zip = new \ZipArchive();
		$zip->open($zipFile, \ZIPARCHIVE::CREATE);

		foreach($aVars['id'] as $iHistoryId) {

			$history = \Ext_TS_Accounting_BookingStack_History::getInstance($iHistoryId);

			$user = \Access_Backend::getInstance()->getUser();
			$history->touchDownload($user)->save();

			if(empty($history->file_export)) {
				continue;
			}
			
			$fullPath = \Util::getDocumentRoot().'storage/'.$history->file_export;

			if(!file_exists($fullPath)) {
				continue;
			}

			$fileInfo = pathinfo($fullPath);

			if(!empty($fileInfo['basename'])) {
				$zip->addFile($fullPath, $fileInfo['basename']);
			}

		}
		
		$zip->close();

		header("Content-Type: application/zip");
		header("Content-Disposition: attachment; filename=".$zipFileName);
		header("Content-Length: " . filesize($zipFile));

		readfile($zipFile);
		
		unlink($zipFile);
		die();
	
	}

}
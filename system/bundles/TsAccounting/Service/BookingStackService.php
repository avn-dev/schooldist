<?php

namespace TsAccounting\Service;

class BookingStackService {

	/**
	 * @param \WDBasic $oEntity
	 * @throws \Ext_TS_Accounting_Bookingstack_Generator_Exception
	 */
	public static function outputTestExport(array $entities) {

		\DB::begin(__METHOD__);

		$oFactory = new \Ext_TS_Accounting_Bookingstack_Generator_Factory();
		
		$aStackIds = $aEntityStackIds = [];
		foreach($entities as $entity) {
			$oGenerator = $oFactory->getGenerator($entity, ['no_receipt_text_found']);
			if (!\System::d('ts_accounting_booking_stack_no_warning')) {
				$oGenerator->setTestMode(true);
			}

			try {
				$aEntityStackIds = $oGenerator->createStack(true);
			} catch (\Ext_TS_Accounting_Bookingstack_Generator_Exception $e) {
				if (!$e->isWarning()) {
					__pout($e->getOptionalData());
					throw $e;
				}
			}

			$aStackIds = array_merge($aStackIds, $aEntityStackIds);
		}

		$oCompany = $oGenerator->getCompany();

		/** @var \Ext_TS_Accounting_Bookingstack_Export $oExporter */
		$oExporter = \Factory::getObject(\Ext_TS_Accounting_Bookingstack_Export::class, [$oCompany]);

		if(!empty($aStackIds)) {
			$oExporter->loadDataFromGui($aStackIds);
		}

		$fullPath = $oExporter->export();
		$pathInfo = pathinfo($fullPath);
		
		if (str_ends_with($fullPath, '.zip')) {
			header("Content-Type: application/zip");
			header("Content-Disposition: attachment; filename=\"".$pathInfo['basename']."\";" );
			header("Content-Length: " . filesize($fullPath));
		} else {
			header("Content-Type: text/csv");
			header("Content-Disposition: attachment; filename=\"".$pathInfo['basename']."\";" );
			header("Content-Transfer-Encoding: binary");
		}
		
		echo file_get_contents($fullPath);		
		
		unlink($fullPath);

		// TODO Schlechter Stil, Testmodus benutzen
		// Stack darf nicht dauerhaft gespeichert werden!
		\DB::rollback(__METHOD__);

		die();
	}

	/**
	 * @param array $aSelectedIds
	 * @param \TsAccounting\Entity\Company $oCompany
	 * @param string $sHistoryType
	 * @return string|string[]
	 * @throws \Exception
	 */
    public static function saveHistory(array $aSelectedIds, \TsAccounting\Entity\Company $oCompany, $sHistoryType = 'export') {

        \DB::begin(__METHOD__);

		/** @var \Ext_TS_Accounting_Bookingstack_Export $oExporter */
		$oExporter = \Factory::getObject(\Ext_TS_Accounting_Bookingstack_Export::class, [$oCompany]);

        $oExporter->loadDataFromGui($aSelectedIds);

        $sExportFile = $oExporter->export();

        // TODO: Wofür wird der JSON-Export benötigt?
        $sJsonFile = $oExporter->exportJson($aSelectedIds);

        $sJsonFile = str_replace(\Util::getDocumentRoot(), '', $sJsonFile);
        $sExportFile = str_replace(\Util::getDocumentRoot(). 'storage/', '', $sExportFile);

        $oHistory = new \Ext_TS_Accounting_BookingStack_History();
        $oHistory->file_json = $sJsonFile;
        $oHistory->file_export = $sExportFile;
        $oHistory->type = $sHistoryType;

		if (\Access_Backend::getInstance()->checkValidAccess()) {
			$user = \Access_Backend::getInstance()->getUser();
			$oHistory->touchDownload($user);
		}

        $oHistory->save();

        $aData = array();
        // Dokumente verknüpfen
        foreach ($aSelectedIds as $iBookingStack) {
            $oStack = \Ext_TS_Accounting_BookingStack::getInstance($iBookingStack);
            if ($oStack->document_id > 0) {
                $aData[$oStack->document_id] = array(
                    'document_id' => $oStack->document_id,
                    'history_id' => $oHistory->getId()
                );
            }
            $oStack->delete();
        }

        \DB::insertMany('ts_documents_booking_stack_histories', $aData, true);

        \DB::commit(__METHOD__);

        $sFullExportFile = \Util::getDocumentRoot().'storage/'.$sExportFile;
        $sFullJsonFile = \Util::getDocumentRoot().'storage/'.$sJsonFile;

        \System::wd()->executeHook('ts_bookingstack_export', $sFullExportFile, $sFullJsonFile);

        return $sExportFile;
    }
}

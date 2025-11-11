<?php


class Ext_Thebing_System_Checks_InvoiceCredits extends GlobalChecks
{
	public function executeCheck()
	{
		
		Ext_Thebing_Util::backupTable('kolumbus_inquiries_documents');

		// Alle Brutto Diff "mit" gutschrift auf den neuen Diff Type ändern!
		// is_credit entfernen da dies keine wirkliche "gutschrift" ist!
		$sSql = " UPDATE 
						`kolumbus_inquiries_documents` 
					SET 
						`type` = 'brutto_diff_special', 
						`is_credit` = 0 
					WHERE 
						`type` = 'brutto_diff' AND 
						`is_credit` = 1 ";
		DB::executeQuery($sSql);		
		
		
		return true;
	}

	public function getTitle()
	{
		return 'Import current creditnotes';
	}

	public function getDescription()
	{
		return 'Import current credit notes into the improved invoice functionality.';
	}
}
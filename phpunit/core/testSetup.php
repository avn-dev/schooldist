<?php

/**
 * Dieses Setup Leert alle WDBasic Instanzen
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class coreTestSetup extends PHPUnit_Framework_TestCase
{
	
	protected $backupGlobals = FALSE;

	public function setUp()
    {
		
		ini_set('max_execution_time', 0);
		ini_set('memory_limit', -1);
		ini_set('display_errors', 1);
		date_default_timezone_set('Europe/Berlin');
		error_reporting(E_ALL & ~(E_STRICT|E_NOTICE));
		
		System::s('debugmode', 2);

		global $user_data;
		$user_data = array(
			'id' => 1,
			'name' => 'admin'
		);

		// Tabellen leeren
		$aTruncates = array();
		$aTruncates[] = 'gui2_indexes_stacks';
		$aTruncates[] = 'gui2_index_registry';

		$aTruncates[] = 'tc_communication_messages';
		$aTruncates[] = 'tc_communication_messages_flags';
		$aTruncates[] = 'tc_communication_messages_flags_relations';
		$aTruncates[] = 'tc_communication_messages_addresses';
		
		$aTruncates[] = 'tc_exchangerates_tables';
		
		foreach($aTruncates as $sTable){
			$sTruncate = 'TRUNCATE `'.$sTable.'`';
			DB::executeQuery($sTruncate);
		}		
		//
		
		// Cache leeren
		WDCache::flush(); 
		
    }

    public function tearDown()
    {
		
    }
}
?>
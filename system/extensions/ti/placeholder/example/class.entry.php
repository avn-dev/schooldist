<?php

class Ext_TI_Placeholder_Example_Entry extends Ext_TC_Placeholder_Example_Entry {

	/**
	 * DB-Connection
	 * @var string 
	 */
	protected $_sDbConnectionName = 'tb_core_dev';

	
	/**
	 * The constructor
	 *
	 * @param int : The data ID
	 */
	public function  __construct($iDataID = 0) {
		// DB Connection zu dev.core
		try{
			DB::getConnection('tb_core_dev');
		} catch(Exception $e){
			DB::createConnection('tb_core_dev', 'dev.core.fidelo.com', 'tb_core_dev', 'M8POCKHFDLcLO88v', 'tb_core_dev');
		}
		
		parent::__construct($iDataID);

	}
	
}
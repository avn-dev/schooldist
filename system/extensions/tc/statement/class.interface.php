<?php

/**
 * @author Mehmet Durmaz
 */
interface Ext_TC_Statement_Interface
{
	public function addSelect(mixed $mField): Ext_TC_Statement_Interface;
	
	public function addWhere(string $sField, mixed $mValue, bool|string $sAlias = false): Ext_TC_Statement_Interface;
	
	public function addOrWhere(string $sField, mixed $mValue, bool|string $sAlias = false): Ext_TC_Statement_Interface;
			
	public function addAndWhere(string $sField, mixed $mValue, bool|string $sAlias = false): Ext_TC_Statement_Interface;
	
	/**
	 * Statement-Parts wieder zurücksetzen
	 */
	public function reset();
	
	public function addLimit(int $iLimit, int $iOffset): Ext_TC_Statement_Interface;
	
	public function addOrder(string $sSortField, string $sSortType): Ext_TC_Statement_Interface;
	
	/**
	 * Ergebnisse für das aktuelle Statement bekommen
	 */
	public function getResults();
}
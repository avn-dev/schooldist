<?php

/**
 * @author Mehmet Durmaz
 */
interface Ext_TC_Statement_Result_Interface
{
	/**
	 * Alle Daten zurückgeben
	 */
	public function getAllData();
	
	/**
	 * Wert für ein bestimmtes Feld bekommen
	 */
	public function getValue($sField);
}
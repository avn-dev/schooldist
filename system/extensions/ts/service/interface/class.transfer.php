<?php


interface Ext_TS_Service_Interface_Transfer
{
	public function getName($oCalendarFormat = null, $sView = 1, $sLang = '');
	
	/**
	 * Liefert den Ankunftsort eines Transfers
	 */
	public function getStartLocation();
	
	/**
	 * Liefert den Abreiseort eines Transfers
	 */
	public function getEndLocation();
	
	/**
	 * Liefert die Start Zusatzinformation des Transfers
	 */
	public function getLocationName($sType = 'start', $bTerminals = false, $sLang = '');
	
	/**
	 * Liefert die Ende Zusatzinformation des Transfers
	 */
	public function getTerminalName($sType = 'start', $sLanguage = '');
}
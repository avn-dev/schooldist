<?php

namespace Gui2\DTO;

class InfoIconMessageBag {
	/**
	 * @var \Ext_Gui2 
	 */
	protected $oGui;
	/**
	 * @var \HTMLPurifierWrapper 
	 */
	protected $oPurifier;
	/**
	 * @var string 
	 */
	protected $sDialogSuffix;
	/**
	 * @var string 
	 */
	protected $sLanguage;
	/**
	 * @var array 
	 */
	protected $aTexts = [];
	
	/**
	 * @param \Ext_Gui2 $oGui
	 * @param string $sDialogSuffix
	 * @param string $sLanguage
	 */
	public function __construct(\Ext_Gui2 $oGui, $sDialogSuffix, $sLanguage) {
		$this->oGui = $oGui;
		$this->sDialogSuffix = $sDialogSuffix;
		$this->sLanguage = $sLanguage;
		
		$this->oPurifier = new \HTMLPurifierWrapper;
	}
	
	/**
	 * @param string $sField
	 * @param string $sText
	 * @param bool $bPrivate
	 */
	public function addText($sField, $sText, $bPrivate = false) {
		
		$sRowKey = \Gui2\Service\InfoIcon\Hashing::encode($this->oGui, $this->sDialogSuffix, $sField);
		
		$sText = $this->oPurifier->purify($sText);
		
		$this->aTexts[$sRowKey] = [
			'row_key' => $sRowKey,
			'value' => $sText,
			'private' => (int) $bPrivate
		];
	}
	
	/**
	 * @return array
	 */
	public function toArray() {
		return [
			'language' => $this->sLanguage,
			'info_texts' => array_values($this->aTexts)
		];
	}
}



<?php

namespace TcStatistic\Model\Statistic;

use \TcStatistic\Generator\Statistic\AbstractGenerator;
use \TcStatistic\Model\Table\Cell;

class Column {

	private $sKey;
	private $sTitle;
	private $sFormat;
	private $sBackground;
	public $sCurrency;
	public $bSummable = false;
	public $bFormatNullValue = true;
	public $bKeepLineBreaks = false;
	public $mNullValueReplace = null;
	public $aAdditional = [];

	/**
	 * @param string $sKey
	 * @param string $sTitle
	 * @param string $sFormat
	 */
	public function __construct($sKey, $sTitle, $sFormat=null) {
		$this->sKey = $sKey;
		$this->sTitle = $sTitle;
		$this->sFormat = $sFormat;
	}

	/**
	 * Zelle für Renderer generieren
	 *
	 * @param bool $bHeadCell
	 * @return Cell
	 */
	public function createCell($bHeadCell=false) {
		$oCell = new Cell();

		if($bHeadCell) {
			$oCell->setValue($this->sTitle);

			if(!empty($this->sBackground)) {
				$oCell->setBackground($this->sBackground);
			}
		} else {
			if(!empty($this->sFormat)) {
				$oCell->setFormat($this->sFormat);
			}
		}

		$oCell->setHeading($bHeadCell);
		$oCell->setNullValueFormatting($this->bFormatNullValue, $this->mNullValueReplace);
		$oCell->setKeepLineBreaks($this->bKeepLineBreaks);

		return $oCell;
	}

	/**
	 * Hintergrund der Spalte: Entweder HEX-Code direkt oder Kategorie und Typ
	 *
	 * @param string $sColor
	 * @param string $sType
	 */
	public function setBackground($sColor, $sType='light') {

		if(substr($sColor, 0, 1) === '#') {
			$this->sBackground = $sColor;
		} else {
			$this->sBackground = AbstractGenerator::getColumnColor($sColor, $sType);
		}

	}

	/**
	 * @return string
	 */
	public function getKey() {
		return $this->sKey;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->sTitle;
	}

	/**
	 * @return null|string
	 */
	public function getFormat() {
		return $this->sFormat;
	}

	public function setKeepLineBreaks(bool $bKeepLineBreaks) {
		$this->bKeepLineBreaks = $bKeepLineBreaks;
	}

	/**
	 * @return bool
	 */
	public function shouldKeepLineBreaks(): bool {
		return $this->bKeepLineBreaks;
	}

}

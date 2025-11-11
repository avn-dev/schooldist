<?php

namespace TcStatistic\Model\Table;

class Cell {

	const BORDER_RIGHT = 2;
	const BORDER_BOTTOM = 4;

	private $mValue = null;
	private $bHeading = false;
	private $sFormat = null;
	private $iColspan = 0;
	private $iRowspan = 0;
	private $sBackground = null;
	private $sCurrency = null;
	private $iBorderSettings = 0;
	private $bFormatNullValue = true;
	private $mNullValueReplace = null;
	private $bNoWrap = false;
	private $bKeepLineBreaks = false;
	private $aFontStyle = [];
	private $sComment = null;
	private $sAlignment = null;

	/**
	 * @param mixed $mValue
	 * @param bool|false $bHeading
	 * @param string $sFormat
	 */
	public function __construct($mValue=null, $bHeading=false, $sFormat=null) {
		$this->setValue($mValue);
		$this->setHeading($bHeading);
		$this->setFormat($sFormat);
	}

	/**
	 * @param mixed $mValue
	 */
	public function setValue($mValue) {
		$this->mValue = $mValue;
		return $this;
	}

	/**
	 * @return null|string
	 */
	public function getValue() {
		return $this->mValue;
	}

	/**
	 * @return bool
	 */
	public function hasValue() {
		return $this->mValue !== null;
	}

	/**
	 * @param bool $bHeading
	 */
	public function setHeading($bHeading) {
		$this->bHeading = $bHeading;
	}

	/**
	 * @return bool
	 */
	public function isHeading() {
		return $this->bHeading;
	}

	/**
	 * @param string $sFormat
	 */
	public function setFormat($sFormat) {
		$this->sFormat = $sFormat;
	}

	/**
	 * @return null|string
	 */
	public function getFormat() {
		return $this->sFormat;
	}

	/**
	 * @return bool
	 */
	public function hasFormat() {
		return !empty($this->sFormat);
	}

	/**
	 * @param int $iColspan
	 */
	public function setColspan($iColspan) {
		$this->iColspan = $iColspan;
	}

	/**
	 * @return int
	 */
	public function getColspan() {
		return $this->iColspan;
	}

	/**
	 * @param int $iRowspan
	 */
	public function setRowspan($iRowspan) {
		$this->iRowspan = $iRowspan;
	}

	/**
	 * @return int
	 */
	public function getRowspan() {
		return $this->iRowspan;
	}

	/**
	 * @param string $sBackground
	 */
	public function setBackground($sBackground) {
		$this->sBackground = $sBackground;
	}

	/**
	 * @return null|string
	 */
	public function getBackground() {
		return $this->sBackground;
	}

	/**
	 * @return bool
	 */
	public function hasBackground() {
		return !empty($this->sBackground);
	}

	/**
	 * @param string $sCurrency
	 */
	public function setCurrency($sCurrency) {
		$this->sCurrency = $sCurrency;
	}

	/**
	 * @return null|string
	 */
	public function getCurrency() {
		return $this->sCurrency;
	}

	/**
	 * @return bool
	 */
	public function hasCurrency() {
		return !empty($this->sCurrency);
	}

	/**
	 * NULL formatieren oder leer anzeigen (vor allem relevant für Zahlen)
	 *
	 * @param bool $bValue
	 * @param mixed $mReplace
	 */
	public function setNullValueFormatting($bValue, $mReplace = null)  {
		$this->bFormatNullValue = $bValue;
		$this->mNullValueReplace = $mReplace;
	}

	/**
	 * @return bool
	 */
	public function getNullValueFormatting() {
		return $this->bFormatNullValue;
	}

	/**
	 * @return mixed|null
	 */
	public function getNullValueReplace() {
		return $this->mNullValueReplace;
	}

	/**
	 * @param int $iSettings
	 */
	public function setBorder($iSettings) {
		$this->iBorderSettings = $iSettings;
	}

	/**
	 * @return int
	 */
	public function getBorder() {
		return $this->iBorderSettings;
	}

	/**
	 * HTML: white-space: nowrap
	 *
	 * @param bool $bNoWrap
	 */
	public function setNoWrap($bNoWrap) {
		$this->bNoWrap = $bNoWrap;
	}

	/**
	 * @return bool
	 */
	public function getNoWrap() {
		return $this->bNoWrap;
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

	/**
	 * Font-Style setzen
	 *
	 * @param string $sType
	 * @param bool $mValue
	 */
	public function setFontStyle($sType, $mValue = true) {
		$this->aFontStyle[$sType] = $mValue;
	}

	/**
	 * @return array
	 */
	public function getFontStyle() {
		return $this->aFontStyle;
	}

	/**
	 * @return bool
	 */
	public function hasFontStyle() {
		return !empty($this->aFontStyle);
	}

	/**
	 * @param string $sComment
	 */
	public function setComment($sComment) {
		$this->sComment = $sComment;
	}

	/**
	 * @return string
	 */
	public function getComment() {
		return $this->sComment;
	}

	/**
	 * @see \PhpOffice\PhpSpreadsheet\Style\Alignment
	 * @param string $sAlignment
	 */
	public function setAlignment($sAlignment) {
		$this->sAlignment = $sAlignment;
	}

	/**
	 * @return string|null
	 */
	public function getAlignment() {
		return $this->sAlignment;
	}

}
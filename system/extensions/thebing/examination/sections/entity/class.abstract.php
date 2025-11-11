<?php

abstract class Ext_Thebing_Examination_Sections_Entity_Abstract extends Ext_Thebing_Basic
{
	abstract public function getInput();
	abstract public function getEntityKey();
	protected $_aFormat = array();
	public function addValue(Ext_Gui2_Html_Abstract $oInput, $mValue)
	{
		$mValue = $this->getFormat($mValue);
		$oInput->value = $mValue;
		return $oInput;
	}
	public function __toArray()
	{
		return array(
			'section_id'				=> $this->section_id,
			'examination_version_id'	=> $this->examination_version_id,
			'value'						=> $this->value,
		);
	}
	public function clear()
	{
		$sSql = "
			DELETE FROM
				#table
			WHERE
				`examination_version_id` = :id
		";

		$aSql = array(
			'table' => $this->_sTable,
			'id'	=> $this->examination_version_id,
		);

		DB::executePreparedQuery($sSql, $aSql);
	}

	public function getFormat($mValue)
	{
		return $mValue;
	}

	public function addOptions(Ext_Gui2_Html_Abstract $oInput)
	{
		return $oInput;
	}

	public function getStringValue()
	{
		return $this->value;
	}

	public function setValue($mValue)
	{
		$this->_aData['value'] = $mValue;
	}

	public function setSectionId($iValue)
	{
		$this->_aData['section_id'] = (int)$iValue;
	}

	public function getValueByVersion($iVersionId, $bStringValue = true)
	{
		$sSql = "
			SELECT
				`value`
			FROM
				#table
			WHERE
				`section_id` = :section_id AND
				`examination_version_id` = :version_id
		";

		$aSql = array(
			'table'			=> $this->_sTable,
			'section_id'	=> $this->section_id,
			'version_id'	=> $iVersionId
		);

		$aResult = DB::getQueryOne($sSql, $aSql);
		if(!empty($aResult)) {

			$this->examination_version_id	= $iVersionId;
			$this->value					= $aResult;

			if($bStringValue === true) {
				return $this->getStringValue();
			} else {
				return $this->value;
			}

		}

		return null;
	}
}
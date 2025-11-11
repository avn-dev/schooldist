<?php

/**
 * Check sucht nach Lücken bei Dokumentennummern
 * Dieser Check durchsucht NUR Dokumentennummern des gleichen Formats.
 * 	Sollte das Format des Nummernkreises verändert worden sein, werden die alten Nummern ignoriert!
 */
class Ext_TS_System_Checks_Document_Numberrange_CheckGaps extends GlobalChecks {

	protected $sTmpTable = 'tmp_kid_document_numbers';

	public function getTitle() {
		return 'Check Document Numbers';
	}

	public function getDescription() {
		return 'Check for gaps in number ranges (current format)';
	}

	public function executeCheck() {

		// Alle Dokumenten-Nummernkreise
		$sSql = "
			SELECT
				`id`
			FROM
				`tc_number_ranges`
			WHERE
				`category` = 'document'
		";

		$aNumberranges = DB::getQueryCol($sSql);
		$aNumberrangeGaps = array();

		$this->manageTemporaryTable('drop');
		$this->manageTemporaryTable('create');

		foreach($aNumberranges as $iNumberrangeId) {
			$this->manageTemporaryTable('truncate');
			$aNumberrangeGaps[$iNumberrangeId] = $this->checkNumberrange($iNumberrangeId);
		}

		$this->notify($aNumberrangeGaps);

		$this->manageTemporaryTable('drop');

		return true;
	}

	/**
	 * Support benachrichtigen
	 *
	 * @param array $aNumberrangeGaps
	 */
	protected function notify($aNumberrangeGaps) {
		global $_VARS;
		$aReport = array();

		foreach($aNumberrangeGaps as $iNumberrangeId => $aGaps) {
			$oNumberrange = Ext_Thebing_Inquiry_Document_Numberrange::getInstance($iNumberrangeId);
			$sRow = 'Number Range "'.$oNumberrange->name.'" ('.$iNumberrangeId.')'."\n";

			if(count($aGaps) > 0) {
				foreach($aGaps as $aGap) {
					$iNumbers = $aGap['end'] - $aGap['start'] + 1;
					$sRow .= $this->formatNumber($oNumberrange, $aGap['start']).' - '.$this->formatNumber($oNumberrange, $aGap['end']);
					$sRow .= ' ('.$iNumbers.' numbers missing)'."\n";

					$sRow .= "\tInfo about ".$this->getDocumentInfoByNumber($this->formatNumber($oNumberrange, $aGap['start'] - 1))."\n";
					$sRow .= "\tInfo about ".$this->getDocumentInfoByNumber($this->formatNumber($oNumberrange, $aGap['end'] + 1))."\n";

				}
			} else {
				$sRow .= '-'."\n";
			}

			$aReport[] = $sRow."\n";
		}

		$this->logInfo('Report', $aReport);

		$oSchool = Ext_Thebing_Client::getFirstSchool();
		wdmail('info@thebing.com,thebing_error@p32.de', 'TS Checks Report '.$oSchool->getName().' (Ext_TS_System_Checks_Document_Numberrange_CheckGaps)', join("\n", $aReport)."\n\n".print_r($_VARS, 1));
	}

	/**
	 * Infos über Dokument für Report
	 * @param $sDocumentNumber
	 * @return string
	 */
	protected function getDocumentInfoByNumber($sDocumentNumber) {

		$sSql = "
			SELECT
				`kid`.`created` `document_created`,
				`cdb2`.`ext_1` `school_name`,
				`ts_ij`.`inquiry_id` `inquiry_id`,
				`tc_cn`.`number` `customer_number`
			FROM
				`kolumbus_inquiries_documents` `kid` LEFT JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`inquiry_id` = `kid`.`inquiry_id` LEFT JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ts_ij`.`school_id` LEFT JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`inquiry_id` = `kid`.`inquiry_id` AND
					`ts_itc`.`type` = 'traveller' LEFT JOIN
				`tc_contacts_numbers` `tc_cn` ON
					`tc_cn`.`contact_id` = `ts_itc`.`contact_id`
			WHERE
				`kid`.`document_number` = :document_number
			GROUP BY
				`kid`.`id`
			LIMIT 1
		";

		$aSql = array(
			'document_number' => $sDocumentNumber
		);

		$aResult = DB::getQueryRow($sSql, $aSql);

		$sRow = $sDocumentNumber;
		if(count($aResult) > 0) {
			$sRow .= ': Created: '.$aResult['document_created'].', School: '.$aResult['school_name'].', Customer Number: '.$aResult['customer_number'].', Inquiry ID: '.$aResult['inquiry_id'];
		} else {
			$sRow .= ' (not found)';
		}

		return $sRow;
	}

	/**
	 * Erzeugt eine (temporäre) Tabelle, wo nur die aktuellen Nummern drin stehen:
	 * 	Da jeder Nummernkreis ein anderes Format (und demnach Präfixlängen hat)
	 *
	 * @param string $sAction
	 * @throws UnexpectedValueException
	 */
	protected function manageTemporaryTable($sAction) {
		switch($sAction) {
			case 'drop':
				DB::executeQuery("DROP TABLE IF EXISTS `".$this->sTmpTable."`");
				break;
			case 'create':
				DB::executeQuery("
					CREATE TABLE IF NOT EXISTS `".$this->sTmpTable."` (
						`number` int(11) NOT NULL DEFAULT '0',
						`original_number` TEXT,
						PRIMARY KEY (`number`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;
				");
				break;
			case 'truncate':
				DB::executeQuery("TRUNCATE `".$this->sTmpTable."`");
				break;
			default:
				throw new UnexpectedValueException();
				break;
		}
	}

	/**
	 * Prüft einen Nummernkreis (nur aktuelles Format)
	 *
	 * @param $iNumberrangeId
	 * @return array
	 */
	protected function checkNumberrange($iNumberrangeId) {
		$oNumberrange = Ext_Thebing_Inquiry_Document_Numberrange::getInstance($iNumberrangeId);

		$aPreAndPostfix = $this->getPreAndPostfixOrFormat($oNumberrange);

		$aSql = array(
			'numberrange_id' => $oNumberrange->id,
			'pattern' => $aPreAndPostfix[0].'%'.$aPreAndPostfix[1],
			'prefix_length' => strlen($aPreAndPostfix[0]) + 1,
			'postfix_length' => strlen($aPreAndPostfix[1]),
			'prefix' => $aPreAndPostfix[0],
			'postfix' => $aPreAndPostfix[1]
		);

		// Pure Nummern in die temporäre Tabelle eintragen
		$sSql = "
			REPLACE INTO
				{$this->sTmpTable}
			SELECT
				(
					SUBSTRING(
						SUBSTRING(`document_number`, :prefix_length),
						1,
						(
							LENGTH(
								SUBSTRING(`document_number`, :prefix_length)
							) - :postfix_length
						)
					) + 0
				) `number`,
				`document_number`
			FROM
				`kolumbus_inquiries_documents`
			WHERE
				`numberrange_id` = :numberrange_id AND
				`document_number` LIKE :pattern

		";

		DB::executePreparedQuery($sSql, $aSql);

		// Query um alle Lücken zu finden
		// Das ist schneller mit der temporären Tabelle!
		$sSql = "
			SELECT
				`t1`.`number` + 1 `start`,
				MIN(`t2`.`number`) - 1 `end`
			FROM
				{$this->sTmpTable} `t1`, {$this->sTmpTable} `t2`
			WHERE
				`t1`.`number` < `t2`.`number`
			GROUP BY
				`t1`.`number`
			HAVING
				`start` < MIN(`t2`.`number`)
		";

		$aGaps = DB::getQueryRows($sSql, $aSql);

		return $aGaps;
	}

	/**
	 * Selbiges passiert in der Ext_TC_Numberrange::generateNumber()
	 *
	 * @param Ext_Thebing_Inquiry_Document_Numberrange $oNumberrange
	 * @param bool $bReturnFormat
	 * @return mixed
	 */
	protected function getPreAndPostfixOrFormat($oNumberrange, $bReturnFormat=false) {
		$sFormat = $oNumberrange->format;

		if(strpos($sFormat, '%count') === false) {
			$sFormat .= '%count';
		}

		$sTempFormat = str_replace('%count', '', $sFormat);
		$iTimestamp = time();

		preg_match_all('/(\%[a-zA-Z0-9]*)/', $sTempFormat, $aParts);

		$aTemp = array();
		foreach((array)$aParts[0] as $sPart) {
			$aTemp[$sPart] = strftime($sPart, $iTimestamp);
		}

		foreach((array)$aTemp as $sPlaceholder => $sData) {
			$sFormat = str_replace($sPlaceholder, $sData, $sFormat);
		}

		if($bReturnFormat) {
			return $sFormat;
		} else {
			return explode('%count', $sFormat, 2);
		}
	}

	/**
	 * Liefert Nummer mit kompletten Format zurück
	 *
	 * @param $oNumberrange
	 * @param $iNumber
	 * @return mixed
	 */
	protected function formatNumber($oNumberrange, $iNumber) {
		$iDigits = (int)$oNumberrange->digits;
		$sFormat = $this->getPreAndPostfixOrFormat($oNumberrange, true);

		$sCount = str_pad($iNumber, $iDigits, '0', STR_PAD_LEFT);
		$sNumber = str_replace('%count', $sCount, $sFormat);

		return $sNumber;
	}
}
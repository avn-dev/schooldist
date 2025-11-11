<?php


use Communication\Interfaces\Model\CommunicationSubObject;

class Ext_Thebing_Agency_Payment extends Ext_Thebing_Basic implements \Communication\Interfaces\Model\HasCommunication {

	// Tabellenname
	protected $_sTable = 'kolumbus_accounting_agency_payments';

	protected $_sTableAlias = 'kaap';

	protected $_aFormat = array(
		'school_id' => array(
			'required'=>true,
			'validate'=>'INT_POSITIVE'
		),
		'amount_currency' => [
			'required' => true,
			'validate' => 'INT_POSITIVE'
		]
	);

	/**
	 * Bezahlter Betrag dieser Agenturzahlung (in Währung der Agenturzahlung)
	 *
	 * Die verknüpften Zahlungen (erster Query) beinhalten den benutzten Betrag PLUS normale Creditnotes
	 * (stehen in derselben Tabelle). Da die Zahlungen der benutzten Creditnotes negativ sind,
	 * zieht das SUM() diese schon ab, Da die manuellen Creditnotes aber mal wieder was komplett
	 * Eigenes sind, müssen diese danach manuell abgezogen werden.
	 *
	 * @return float
	 */
	public function getPayedAmount() {

		$aSql = ['id' => $this->id, 'currency' => $this->amount_currency];
		$fPayedWithDocumentCreditnotes = (float)DB::getQueryOne(self::getAmountUsedSql(':id', ':currency'), $aSql);
		$fPayedWithManualCreditnotes = (float)DB::getQueryOne(self::getAmountUsedManualCreditnotesSql(':id'), $aSql);
		$fPayed = $fPayedWithDocumentCreditnotes - $fPayedWithManualCreditnotes;

		return $fPayed;

	}

	/**
	 * Offener Betrag dieser Agenturzahlung (in Währung der Agenturzahlung)
	 *
	 * @return float
	 */
	public function getOpenAmount() {

		$fAmount = (float)$this->amount;
		$fAmountPayed = $this->getPayedAmount();

		return $fAmount - $fAmountPayed;

//		// Bei PHP 7.2.20-2+ubuntu16.04.1+deb.sury.org+1 kam mit normaler PHP-Subtraktion im JS (json_encode) 933.8499999999985 statt 933.85 raus #14333
//		// https://stackoverflow.com/questions/42981409/php7-1-json-encode-float-issue
//		return (float)bcsub($fAmount, $fAmountPayed, 2);

	}

	/**
	 * @param array $aSqlParts
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		parent::manipulateSqlParts($aSqlParts, $sView);

		$aSqlParts['select'] .= ",
			`ka`.`ext_1` `agency_name`,
			`kpm`.`name` `payment_method_name`,
			`kaap`.`amount_currency` `currency_id`,
			(
				".self::getAmountUsedSql("`kaap`.`id`", "`kaap`.`amount_currency`")."
			) `amount_used_with_document_creditnotes`,
			(
				/*
				 * Eigentlich sollte man hier über die verknüpften Dokumente gehen, aber bei Verrechnung gibt
				 * es nur Typ 4 und beim Buchung-Bezahlen gibt es den Typen nicht
				 */
				".self::getAmountUsedSql("`kaap`.`id`", "`kaap`.`amount_currency`", [4, 5])."
			) `amount_used_document_creditnotes`,
			(
				".self::getAmountUsedManualCreditnotesSql("`kaap`.`id`")."
			) `amount_used_manual_creditnotes`
		";

		$aSqlParts['from'] .= " LEFT JOIN
			`ts_companies` `ka` ON
				`ka`.`id` = `kaap`.`agency_id` LEFT JOIN
			`kolumbus_payment_method` AS `kpm` ON
				`kaap`.`method_id` = `kpm`.`id`
		";

		$aSqlParts['groupby'] = "
			`kaap`.`id`
		";

	}

	/**
	 * Query zum Berechnen des bezahlten Betrags dieser Agenturzahlung (wird benötigt für Liste und Methode)
	 *
	 * @see getPayedAmount()
	 *
	 * @param string $sIdColumn
	 * @param string $sCurrencyToColumn
	 * @param array $aPaymentTypeIds
	 * @param string $sPaymentIdColumn
	 * @return string
	 */
	public static function getAmountUsedSql($sIdColumn, $sCurrencyToColumn, $aPaymentTypeIds=[], $sPaymentIdColumn=null) {

		$sWhere = "";
		if(!empty($aPaymentTypeIds)) {
			$sWhere .= " AND `kip_sub`.`type_id` IN ( ".join(',', $aPaymentTypeIds)." ) ";
		}

		if($sPaymentIdColumn) {
			$sWhere .= " AND `kip_sub`.`id` = {$sPaymentIdColumn} ";
		}

		return "
			SELECT
				SUM(
					IF(
						/* Wenn Währungen übereinstimmen: Nichts konvertieren, da das für Kommafehler sorgt */
						`kipi_sub`.`currency_inquiry` = {$sCurrencyToColumn},
						COALESCE(`kipi_sub`.`amount_inquiry`, 0),
						calcAmountByCurrencyFactors(
							COALESCE(`kipi_sub`.`amount_school`, 0),
							`kipi_sub`.`currency_school`,
							`kip_sub`.`date`,
							{$sCurrencyToColumn},
							`kip_sub`.`date`
						)
					)
				)
			FROM
				`kolumbus_inquiries_payments_agencypayments` `kipa_sub` INNER JOIN
				`kolumbus_inquiries_payments` `kip_sub` ON
					`kip_sub`.`id` = `kipa_sub`.`payment_id` AND
					`kip_sub`.`active` = 1 INNER JOIN
				`kolumbus_inquiries_payments_items` `kipi_sub` ON
					`kipi_sub`.`payment_id` = `kip_sub`.`id` AND
					`kipi_sub`.`active` = 1
			WHERE
				`kipa_sub`.`agency_payment_id` = {$sIdColumn}
				{$sWhere}
		";

	}

	/**
	 * Query zum Berechnen des bezahlten Betrags mit manuellen Creditnotes
	 *
	 * @param string $sIdColumn
	 * @param string $sPaymentIdColumn
	 * @return string
	 */
	public static function getAmountUsedManualCreditnotesSql($sIdColumn, $sPaymentIdColumn=null) {

		$sJoin = $sWhere = "";
		if($sPaymentIdColumn) {
			$sJoin = " INNER JOIN
				`kolumbus_inquiries_payments` `sub_kip` ON
					`sub_kip`.`id` = `kamcp_sub`.`payment_id` AND
					`sub_kip`.`active` = 1
			";
			$sWhere = " AND `kamcp_sub`.`payment_id` = {$sPaymentIdColumn} ";
		}

		return "
			SELECT
				COALESCE(SUM(`kamcp_sub`.`amount`), 0)
			FROM
				`kolumbus_agencies_manual_creditnotes_payments` `kamcp_sub`
				{$sJoin}
			WHERE
				`kamcp_sub`.`agency_payment_id` = {$sIdColumn} AND
				`kamcp_sub`.`active` = 1
				{$sWhere}
		";

	}

	/**
	 * @return Ext_Thebing_Agency
	 */
	public function getAgency() {
		return Ext_Thebing_Agency::getInstance($this->agency_id);
	}

	public function getCommunicationDefaultApplication(): string
	{
		return \TsAccounting\Communication\Application\AgencyPayments::class;
	}

	public function getCommunicationLabel(\Tc\Service\LanguageAbstract $l10n): string
	{
		return $l10n->translate('Agenturzahlung');
	}

	public function getCommunicationSubObject(): CommunicationSubObject
	{
		return \Ext_Thebing_School::getInstance($this->school_id);
	}
}

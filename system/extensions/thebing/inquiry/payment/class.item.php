<?php

/**
 * @TODO currency_inquiry + currency_school entfernen (Parent verwenden)
 *
 * @property $id
 * @property $changed
 * @property $created
 * @property $active
 * @property $creator_id
 * @property $payment_id
 * @property $item_id
 * @property $amount_inquiry
 * @property $amount_school
 * @property $currency_inquiry
 * @property $currency_school
 */
class Ext_Thebing_Inquiry_Payment_Item extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'kolumbus_inquiries_payments_items';

	protected $_sTableAlias = 'kipi';

	protected $_aFormat = array(
			'changed' => array(
							'format' => 'TIMESTAMP'
			),
			'created' => array(
							'format' => 'TIMESTAMP'
			),
			'currency_inquiry' => array(
				'validate' => 'INT_POSITIVE',
				'required' => true
			),
			'currency_school' => array(
				'validate' => 'INT_POSITIVE',
				'required' => true
			),
	);

	protected $_aJoinedObjects = [
		'payment' => [
			'class'	=> 'Ext_Thebing_Inquiry_Payment',
			'key' => 'payment_id',
			'bidirectional' => true
		]
	];

	/**
	 * @return Ext_TS_Inquiry
	 */
	public function getInquiry() {

		$iItem = (int)$this->item_id;
		$oItem = Ext_Thebing_Inquiry_Document_Version_Item::getInstance($iItem);

		$oVersion = $oItem->getVersion();

		$oDocument = $oVersion->getDocument();

//		$iInquiry = $oDocument->inquiry_id;
//		$oInquiry = Ext_TS_Inquiry::getInstance($iInquiry);

		return $oDocument->getInquiry();
	}

	/**
	 * @return Ext_Thebing_Inquiry_Payment
	 */
	public function getPayment() {
		return Ext_Thebing_Inquiry_Payment::getInstance($this->payment_id);
	}

	/**
	 * Ableitung für Index-Aktualisierung
	 * @param bool $bLog 
	 */
	public function save($bLog = true) {
		
		parent::save($bLog);

		// Relation zwischen Dokument und Zahlung aktualisieren
		$this->updateDocumentRelation();

	}

	/**
	 * @return Ext_Thebing_Inquiry_Document_Version_Item
	 */
	public function getVersionItem() {
		$iVersionItemId		= (int)$this->item_id;
		$oVersionItem		= Ext_Thebing_Inquiry_Document_Version_Item::getInstance($iVersionItemId);

		return $oVersionItem;
	}

	/**
	 * @inheritdoc
	 */
	public function validate($bThrowExceptions = false) {
		$mSucccess = parent::validate($bThrowExceptions);

		if($mSucccess === true) {
			if(!Ext_Thebing_Inquiry_Payment::checkAmountValuesPlausibility($this->amount_inquiry, $this->amount_school)) {
				return [['kipi.amount_inquiry' => 'CURRENCY_CONVERT_ERROR']];
			}
		}

		return $mSucccess;
	}

	/**
	 * Relation zwischen Dokument und Zahlung aktualisieren
	 *
	 * Das wird aktuell immer pro Item gemacht, da die Ext_Thebing_Payment ein großer Alptraum ist.
	 * Die Relation sollte nur dann da sein, wenn auch ein Item aus dem Dokument bezahlt wurde.
	 */
	protected function updateDocumentRelation() {

		if (!$this->active) {
			return;
		}

		$oItem = $this->getVersionItem();
		$oDocument = $oItem->getDocument();

		if($oDocument->id == 0) {
			// Eventuell rausnehmen, je nachdem, was da für Fehler kommen
			throw new RuntimeException('updateDocumentRelation() with document-ID 0');
		}

		$oPayment = $this->getPayment();
		$oPayment->updateDocumentRelation($oDocument->id);

	}

}
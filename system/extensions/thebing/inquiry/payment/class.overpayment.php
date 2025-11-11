<?php

/**
 * @TODO currency_inquiry + currency_school entfernen (Parent verwenden)
 *
 * @property int $id
 * @property $changed
 * @property $created
 * @property int $active
 * @property int $creator_id
 * @property int $payment_id
 * @property int $inquiry_document_id Nur (noch) relevant für Unterscheidung von normalen Rechnungen und Creditnotes
 * @property string|float $amount_inquiry
 * @property string|float $amount_school
 * @property int $currency_inquiry
 * @property int $currency_school
 */
class Ext_Thebing_Inquiry_Payment_Overpayment extends Ext_Thebing_Basic {

	protected $_sTable = 'kolumbus_inquiries_payments_overpayment';

	protected $_sTableAlias = 'kipo';
	
	protected $_aFormat = array(
		'payment_id' => array(
			'required'	=> true,
			'validate'	=> 'INT_POSITIVE'
		),
		'inquiry_document_id' => array(
//			'required'	=> true,
			'validate'	=> 'INT_POSITIVE'
		),
		'amount_inquiry' => array(
			'required'	=> true,
			'validate'	=> 'FLOAT'
		),
		'amount_school' => array(
			'required'	=> true,
			'validate'	=> 'FLOAT'
		),
		'currency_inquiry' => array(
			'required'	=> true,
			'validate'	=> 'INT_POSITIVE'
		),
		'currency_school' => array(
			'required'	=> true,
			'validate'	=> 'INT_POSITIVE'
		),
	);
	
	protected $_aJoinedObjects = array(
		'payment' => array(
			'class'	=> 'Ext_Thebing_Inquiry_Payment',
			'key' => 'payment_id',
			'bidirectional' => true,
		),
		'inquiry_document' => array(
			'class'	=> 'Ext_Thebing_Inquiry_Document',
			'key'	=> 'inquiry_document_id'
		),
		// TODO Wofür ist das? Das wird nirgends verwendet
		'rebookings' => array(
			'class'	=> 'Ext_Thebing_Inquiry_Payment_Overpayment_Rebooking',
			'key'	=> 'overpayment_id'
		)
	);

	/**
	 * @inheritdoc
	 */
	public function validate($bThrowExceptions = false) {
		$mValidate = parent::validate($bThrowExceptions);

		if($mValidate === true) {
			if(!Ext_Thebing_Inquiry_Payment::checkAmountValuesPlausibility($this->amount_inquiry, $this->amount_school)) {
				return [['kipo.amount_inquiry' => 'CURRENCY_CONVERT_ERROR']];
			}
		}

		return $mValidate;
	}

	public function saveRebooking($iItem) {
		
		$oCurrent = new DateTime();
		
		$oOverpaymentRebooking = new Ext_Thebing_Inquiry_Payment_Overpayment_Rebooking();
		$oOverpaymentRebooking->date = $oCurrent->format('Y-m-d');
		$oOverpaymentRebooking->overpayment_id = $this->id;
		$oOverpaymentRebooking->payment_item_id = (int) $iItem;
		$oOverpaymentRebooking->save();

	}
	
	/**
	 * @return Ext_Thebing_Inquiry_Payment
	 */
	public function getPayment() {
		return $this->getJoinedObject('payment');
	}
	
	/**
	 * @return Ext_Thebing_Inquiry_Document
	 */
	public function getInquiryDocument() {
		return $this->getJoinedObject('inquiry_document');
	}
}
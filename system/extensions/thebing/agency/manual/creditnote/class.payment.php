<?php

/**
 * @property $id
 * @property $changed
 * @property $created
 * @property $active	
 * @property $creator_id
 * @property $user_id		
 * @property $creditnote_id	
 * @property $agency_payment_id
 * @property $amount	
 * @property $amount_school	
 * @property $currency_id	
 * @property $currency_school_id
 * @property $transaction_id
 * @property $payment_id
 * @property $payment_item_id
 */

class Ext_Thebing_Agency_Manual_Creditnote_Payment extends Ext_Thebing_Basic {

	protected $_sTable = 'kolumbus_agencies_manual_creditnotes_payments';
	
	protected $_aFormat = array(
		'currency_id' => array(
			'required'	=> true,
			'validate'	=> 'INT_POSITIVE'
		),
		'currency_school_id' => array(
			'required'	=> true,
			'validate'	=> 'INT_POSITIVE'
		),
	);

}
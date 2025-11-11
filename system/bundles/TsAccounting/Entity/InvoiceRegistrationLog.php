<?php

namespace TsAccounting\Entity;

/**
 * @property $id
 * @property $created
 * @property $changed
 * @property $active
 * @property $creator_id
 * @property $editor_id
 * @property $type
 * @property $document_id
 * @property $version_id
 * @property $document_number
 * @property $document_type
 * @property $operation
 * @property $test
 * @property $verification_url
 * @property $errors
 * @property $rejected
 * @property $body
 * @property $response
 * @property $success
 */
class InvoiceRegistrationLog extends \Ext_Thebing_Basic {

	protected $_sTable = 'ts_accounting_invoice_registration_log';
	protected $_sTableAlias = 'ts_airl';

}
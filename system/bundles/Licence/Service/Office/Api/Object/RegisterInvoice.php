<?php

namespace Licence\Service\Office\Api\Object;

use DateTimeInterface;

/**
 * Registriert eine Rechnung im Office
 *
 */
class RegisterInvoice extends \Licence\Service\Office\Api\AbstractObject
{

	/**
	 * @param DateTimeInterface $created
	 * @param string $hash
	 * @param string $documentNumber
	 */
	public function __construct(private DateTimeInterface $created, private string $hash, private string $documentNumber) {}

	public function getUrl()
	{
		return '/customer/api/invoices/register';
	}

	public function getRequestMethod()
	{
		return 'POST';
	}

	/**
	 * Alle nötigen Request-Parameter setzen
	 *
	 * @param \Licence\Service\Office\Api\Request $oRequest
	 */
	public function prepareRequest(\Licence\Service\Office\Api\Request $oRequest)
	{
		$oRequest->add('created', $this->created->format('Y-m-d H:i:s'));
		$oRequest->add('hash', $this->hash);
		$oRequest->add('document_number', $this->documentNumber);
	}

}
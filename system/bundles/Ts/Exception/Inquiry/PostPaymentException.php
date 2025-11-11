<?php

namespace Ts\Exception\Inquiry;


class PostPaymentException extends \RuntimeException implements \JsonSerializable
{
	public function __construct(
		private \Ext_Thebing_Inquiry_Payment $payment,
		private array $errors
	) {
		parent::__construct('Payment post process failed');
	}

	public function jsonSerialize(): mixed
	{
		return ['payment_id' => $this->payment->id, 'errors' => $this->errors];
	}
}
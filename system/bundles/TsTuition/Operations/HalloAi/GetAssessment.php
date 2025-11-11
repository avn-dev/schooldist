<?php

namespace TsTuition\Operations\HalloAi;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use TcApi\Client\Interfaces\Operation;

class GetAssessment implements Operation
{
	/**
	 * email: string: the email address of the user.
	 * @var ?string $email
	 */
	private ?string $email = null;

	/**
	 * @param ?string $email
	 */
	public function __construct(?string $email = null)
	{
		$this->email = $email;
	}

	/**
	 * @return ?string
	 */
	public function getEmail(): ?string
	{
		return $this->email;
	}

	/**
	 * @param ?string $email
	 * @return GetAssessment
	 */
	public function setEmail(?string $email): GetAssessment
	{
		$this->email = $email;
		return $this;
	}

	/**
	 * Send request
	 * @param PendingRequest $request
	 * @return ?Response
	 */
	public function send(PendingRequest $request): ?Response
	{
		return $request->post('/getAssessment', $this->generateBody());
	}

	/**
	 * Generate request body
	 * @return array
	 */
	private function generateBody(): array
	{
		$body = [];
		if (!is_null($this->getEmail())) $body['email'] = $this->getEmail();
		return $body;
	}

}
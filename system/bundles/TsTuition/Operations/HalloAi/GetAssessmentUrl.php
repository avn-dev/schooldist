<?php

namespace TsTuition\Operations\HalloAi;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use TcApi\Client\Interfaces\Operation;

class GetAssessmentUrl implements Operation
{
	/**
	 * optional firstName: string: the first name of the user.
	 * @var ?string $firstName
	 */
	private ?string $firstName = null;

	/**
	 * optional lastName: string: the last name of the user.
	 * @var ?string $lastName
	 */
	private ?string $lastName = null;

	/**
	 * optional email: string: the email address of the user.
	 * @var ?string $email
	 */
	private ?string $email = null;

	/**
	 * optional userId: string: your UUID of the user (if applicable).
	 * @var ?string $userId
	 */
	private ?string $userId = null;

	/**
	 * optional language: string: the language code of the language to assess (user will get to choose if
	 * not provided).
	 * @var ?string $language
	 */
	private ?string $language = null;

	/**
	 * optional assessmentType: string: the type of assessment to give, from the following options:
	 * speaking, writing, listening, reading, speaking_writing, reading_listening_speaking_writing (defaults to
	 * speaking).
	 * @var ?string $assessmentType
	 */
	private ?string $assessmentType = null;

	/**
	 * optional instructionLanguage: string: the language code of the language for the user-facing
	 * instructions to be in (your company's default (or English if one hasn't been set) if not provided).
	 * Available options are as follows:
	 * Arabic "ar", Bengali "be", English "en", Hindi "hi", Indonesian "id", Japanese "ja", Korean "ko", Portuguese "pt", Spanish "es", Thai "th", Turkish "tr"
	 * @var ?string $instructionLanguage
	 */
	private ?string $instructionLanguage = null;

	/**
	 * optional callbackUrl: string: the full URL of the webhook to be called upon completion of the test.
	 * @var ?string $callbackUrl
	 */
	private ?string $callbackUrl = null;

	/**
	 * optional expirationTimestampInMillis: number: the expiration timestamp in milliseconds since Epoch (defaults to timestamp after 24 hours).
	 * @var ?int $expirationTimestampInMillis
	 */
	private ?int $expirationTimestampInMillis = null;

	/**
	 * optional extendAssessmentUrlExpiration: boolean: whether you want the assessment URL to have a
	 * longer expiration time (defaults to false). Note: If expirationTimestampInMillis is set, this field will have no effect.
	 * @var ?bool $extendAssessmentUrlExpiration
	 */
	private ?bool $extendAssessmentUrlExpiration = null;

	/**
	 * optional forceFullScreen: boolean: whether you want the iframe to require the assessment to be
	 * taken full screen (defaults to false). Note: If you set this to true, you must also allow the fullscreen
	 * permission in your iframe definition (if false, that permission is unnecessary).
	 * @var ?bool $forceFullScreen
	 */
	private ?bool $forceFullScreen = null;

	/**
	 * optional forceSecuritySnapshot: boolean: whether you want us to take snapshots for proctoring
	 * purposes (defaults to false). Note: If you set this to true, you must also allow the camera permission in
	 * your iframe definition (if false, that permission is unnecessary).
	 * @var ?bool $forceSecuritySnapshot
	 */
	private ?bool $forceSecuritySnapshot = null;

	/**
	 * optional automaticallySendEmail: boolean: whether you want us to send the assessment email to the
	 * recipient (defaults to false). Note: If you set this to true, you must also have the email field set.
	 * @var ?bool $automaticallySendEmail
	 */

	private ?bool $automaticallySendEmail = null;

	/**
	 * optional getShareableUrl: boolean: whether you want the assessmentUrl field to be a shareable URL
	 * INSTEAD of being a URL suitable for an iframe (defaults to false).
	 * @var ?bool $getShareableUrl
	 */
	private ?bool $getShareableUrl = null;

	/**
	 * @return ?string
	 */
	public function getFirstName(): ?string
	{
		return $this->firstName;
	}

	/**
	 * @param ?string $firstName
	 * @return GetAssessmentUrl
	 */
	public function setFirstName(?string $firstName): GetAssessmentUrl
	{
		$this->firstName = $firstName;
		return $this;
	}

	/**
	 * @return ?string
	 */
	public function getLastName(): ?string
	{
		return $this->lastName;
	}

	/**
	 * @param ?string $lastName
	 * @return GetAssessmentUrl
	 */
	public function setLastName(?string $lastName): GetAssessmentUrl
	{
		$this->lastName = $lastName;
		return $this;
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
	 * @return GetAssessmentUrl
	 */
	public function setEmail(?string $email): GetAssessmentUrl
	{
		$this->email = $email;
		return $this;
	}

	/**
	 * @return ?string
	 */
	public function getUserId(): ?string
	{
		return $this->userId;
	}

	/**
	 * @param ?string $userId
	 * @return GetAssessmentUrl
	 */
	public function setUserId(?string $userId): GetAssessmentUrl
	{
		$this->userId = $userId;
		return $this;
	}

	/**
	 * @return ?string
	 */
	public function getLanguage(): ?string
	{
		return $this->language;
	}

	/**
	 * @param ?string $language
	 * @return GetAssessmentUrl
	 */
	public function setLanguage(?string $language): GetAssessmentUrl
	{
		$this->language = $language;
		return $this;
	}

	/**
	 * @return ?string
	 */
	public function getAssessmentType(): ?string
	{
		return $this->assessmentType;
	}

	/**
	 * @param ?string $assessmentType
	 * @return GetAssessmentUrl
	 */
	public function setAssessmentType(?string $assessmentType): GetAssessmentUrl
	{
		$this->assessmentType = $assessmentType;
		return $this;
	}

	/**
	 * @return ?string
	 */
	public function getInstructionLanguage(): ?string
	{
		return $this->instructionLanguage;
	}

	/**
	 * @param ?string $instructionLanguage
	 * @return GetAssessmentUrl
	 */
	public function setInstructionLanguage(?string $instructionLanguage): GetAssessmentUrl
	{
		$this->instructionLanguage = $instructionLanguage;
		return $this;
	}

	/**
	 * @return ?string
	 */
	public function getCallbackUrl(): ?string
	{
		return $this->callbackUrl;
	}

	/**
	 * @param ?string $callbackUrl
	 * @return GetAssessmentUrl
	 */
	public function setCallbackUrl(?string $callbackUrl): GetAssessmentUrl
	{
		$this->callbackUrl = $callbackUrl;
		return $this;
	}

	/**
	 * @return ?int
	 */
	public function getExpirationTimestampInMillis(): ?int
	{
		return $this->expirationTimestampInMillis;
	}

	/**
	 * @param ?int $expirationTimestampInMillis
	 * @return GetAssessmentUrl
	 */
	public function setExpirationTimestampInMillis(?int $expirationTimestampInMillis): GetAssessmentUrl
	{
		$this->expirationTimestampInMillis = $expirationTimestampInMillis;
		return $this;
	}

	/**
	 * @return ?bool
	 */
	public function isExtendAssessmentUrlExpiration(): ?bool
	{
		return $this->extendAssessmentUrlExpiration;
	}

	/**
	 * @param ?bool $extendAssessmentUrlExpiration
	 * @return GetAssessmentUrl
	 */
	public function setExtendAssessmentUrlExpiration(?bool $extendAssessmentUrlExpiration): GetAssessmentUrl
	{
		$this->extendAssessmentUrlExpiration = $extendAssessmentUrlExpiration;
		return $this;
	}

	/**
	 * @return ?bool
	 */
	public function isForceFullScreen(): ?bool
	{
		return $this->forceFullScreen;
	}

	/**
	 * @param ?bool $forceFullScreen
	 * @return GetAssessmentUrl
	 */
	public function setForceFullScreen(?bool $forceFullScreen): GetAssessmentUrl
	{
		$this->forceFullScreen = $forceFullScreen;
		return $this;
	}

	/**
	 * @return ?bool
	 */
	public function isForceSecuritySnapshot(): ?bool
	{
		return $this->forceSecuritySnapshot;
	}

	/**
	 * @param ?bool $forceSecuritySnapshot
	 */
	public function setForceSecuritySnapshot(?bool $forceSecuritySnapshot): GetAssessmentUrl
	{
		$this->forceSecuritySnapshot = $forceSecuritySnapshot;
	}

	/**
	 * @return ?bool
	 */
	public function isAutomaticallySendEmail(): ?bool
	{
		return $this->automaticallySendEmail;
	}

	/**
	 * @param ?bool $automaticallySendEmail
	 * @return GetAssessmentUrl
	 */
	public function setAutomaticallySendEmail(?bool $automaticallySendEmail): GetAssessmentUrl
	{
		$this->automaticallySendEmail = $automaticallySendEmail;
		return $this;
	}

	/**
	 * @return ?bool
	 */
	public function isGetShareableUrl(): ?bool
	{
		return $this->getShareableUrl;
	}

	/**
	 * @param ?bool $getShareableUrl
	 * @return GetAssessmentUrl
	 */
	public function setGetShareableUrl(?bool $getShareableUrl): GetAssessmentUrl
	{
		$this->getShareableUrl = $getShareableUrl;
		return $this;
	}

	/**
	 * Send request
	 * @param PendingRequest $request
	 * @return ?Response
	 */
	public function send(PendingRequest $request): ?Response
	{
		return $request->post('/getAssessmentUrl', $this->generateBody());
	}

	/**
	 * Generate request body
	 * @return array
	 */
	private function generateBody(): array
	{
		$body = [];
		if (!is_null($this->getFirstName())) $body['firstName'] = $this->getFirstName();
		if (!is_null($this->getLastName())) $body['lastName'] = $this->getLastName();
		if (!is_null($this->getEmail())) $body['email'] = $this->getEmail();
		if (!is_null($this->getUserId())) $body['userId'] = $this->getUserId();
		if (!is_null($this->getLanguage())) $body['language'] = $this->getLanguage();
		if (!is_null($this->getAssessmentType())) $body['assessmentType'] = $this->getAssessmentType();
		if (!is_null($this->getInstructionLanguage())) $body['instructionLanguage'] = $this->getInstructionLanguage();
		if (!is_null($this->getCallbackUrl())) $body['callbackUrl'] = $this->getCallbackUrl();
		if (!is_null($this->getExpirationTimestampInMillis())) $body['expirationTimestampInMillis'] = $this->getExpirationTimestampInMillis();
		if (!is_null($this->isExtendAssessmentUrlExpiration())) $body['extendAssessmentUrlExpiration'] = $this->isExtendAssessmentUrlExpiration();
		if (!is_null($this->isForceFullScreen())) $body['forceFullScreen'] = $this->isForceFullScreen();
		if (!is_null($this->isForceSecuritySnapshot())) $body['forceSecuritySnapshot'] = $this->isForceSecuritySnapshot();
		if (!is_null($this->isAutomaticallySendEmail())) $body['automaticallySendEmail'] = $this->isAutomaticallySendEmail();
		if (!is_null($this->isGetShareableUrl())) $body['getShareableUrl'] = $this->isGetShareableUrl();
		return $body;
	}

}
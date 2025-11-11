<?php

namespace TsStudentApp;

use Carbon\Carbon;
use Core\Helper\BundleConfig;
use Ext_Thebing_School;
use Ext_TS_Inquiry;
use Ext_TS_Inquiry_Contact_Traveller;
use Illuminate\Support\Arr;
use Tc\Service\Language\Frontend;
use TsStudentApp\Facades\PropertyKey;
use TsStudentApp\Service\LoggingService;
use TsStudentApp\Properties\Property;
use TsStudentApp\Service\Util;

class AppInterface {

	/**
	 * @var BundleConfig
	 */
	private $bundleConfig;
	/**
	 * @var Frontend
	 */
	private $l10N;
	/**
	 * @var Device
	 */
	private $device;
	/**
	 * @var string
	 */
	private $appVersion;
	/**
	 * @var \Ext_TS_Inquiry_Contact_Login
	 */
	private \Ext_TS_Inquiry_Contact_Login $user;
	/**
	 * @var Ext_TS_Inquiry_Contact_Traveller
	 */
	private $student;
	/**
	 * @var Ext_TS_Inquiry
	 */
	private $inquiry;
	/**
	 * @var Ext_Thebing_School
	 */
	private $school;
	/**
	 * @var int
	 */
	private $requestInquiryId;

	/**
	 * Erst ab Version 2.0.6
	 *
	 * @var string|null
	 */
	private $appEnvironment;

	private LoggingService $logger;

	/**
	 * AppInterface constructor.
	 *
	 * @param BundleConfig $bundleConfig
	 * @param string $interfaceLanguage
	 * @param string $appVersion
	 */
	public function __construct(BundleConfig $bundleConfig, string $interfaceLanguage, string $appVersion, string $appEnvironment = null) {

		$l10N = new Frontend($interfaceLanguage);
		$l10N->setContext('Fidelo » StudentApp');

		\System::setInterfaceLanguage($interfaceLanguage);

		$this->bundleConfig = $bundleConfig;
		$this->l10N = $l10N;
		$this->appVersion = $appVersion;
		$this->appEnvironment = $appEnvironment;
		$this->logger = app()->make(LoggingService::class);
	}

	/**
	 * @param Device $device
	 */
	public function setDevice(Device $device): self {
		$this->device = $device;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isRunningNative(): bool {
		return !is_null($this->device);
	}

	/**
	 * @return Device|null
	 */
	public function getDevice(): ?Device {
		return $this->device;
	}

	public function getLoginDevice(): ?\Ext_TS_Inquiry_Contact_Login_Device {
		if ($this->device && $this->user) {
			return $this->device->getLoginDevice($this->user);
		}
		return null;
	}

	/**
	 * Bundle Config
	 *
	 * @param string $key
	 * @param null $default
	 * @return mixed
	 */
	public function config(string $key, $default = null) {
		return $this->bundleConfig->get($key, $default);
	}

	/**
	 * Liefert eine Übersetzung für die in der App ausgewählten Sprache
	 *
	 * @param string $translate
	 * @return string
	 */
	public function t(string $translate): string {
		return $this->l10N->translate($translate);
	}

	/**
	 * Setzt die vom Kunden angefragte Buchungs-ID
	 *
	 * @param int $inquiryId
	 */
	public function setRequestInquiryId(int $inquiryId) {
		$this->requestInquiryId = $inquiryId;
	}

	/**
	 * Ist der Kunde eingeloggt?
	 *
	 * @return bool
	 */
	public function isLoggedIn(): bool {
		return !is_null($this->student);
	}

	/**
	 * App-Version des Kunden
	 *
	 * @return string
	 */
	public function getAppVersion(): string {
		return $this->appVersion;
	}

	/**
	 * @return string|null
	 */
	public function getAppEnvironment(): ?string {
		return $this->appEnvironment;
	}

	public function getBundleConfig(): BundleConfig {
		return $this->bundleConfig;
	}

	/**
	 * Sprache die in der App des Kunden benutzt wird
	 *
	 * Das darf nicht benutzt werden. Überall, wo das Objekt weitergegeben werden kann, muss dieses benutzt werden,
	 * damit Sprachen wie fr_CA nicht wegen alter Fallbacks bei den Backend-Translations aufgerufen werden (Exception).
	 *
	 * @deprecated
	 * @return string
	 */
	public function getLanguage(): string {
		return $this->l10N->getLanguage();
	}

	/**
	 * @return Frontend
	 */
	public function getLanguageObject(): Frontend {
		return $this->l10N;
	}

	/**
	 * Eingeloggter Kunde
	 * @return Ext_TS_Inquiry_Contact_Traveller
	 */
	public function getStudent() {
		return $this->student;
	}

	/**
	 * Buchung der eingeloggten Kunden
	 *
	 * @return Ext_TS_Inquiry
	 */
	public function getInquiry() {
		return $this->inquiry;
	}

	/**
	 * Schule des eingeloggten Kunden
	 *
	 * @return Ext_Thebing_School
	 */
	public function getSchool() {
		return $this->school;
	}

	/**
	 * Liefert die Einstellungen zu einer bestimmten Seite
	 *
	 * @param string $page
	 * @return array|null
	 */
	public function getPage(string $page) {
		return $this->getConfigPages()->get($page);
	}

	/**
	 * Liefert die Seiteneinstellungen aus der config.php
	 * @return \Illuminate\Support\Collection
	 */
	public function getConfigPages() {

		$pages = $this->config('pages', []);
		if (version_compare($this->getAppVersion(), '2.2', '<')) {
			unset($pages['settings']);
		}

		return collect($pages)
			->map(fn ($item, $key) => Arr::prepend($item, $key, 'key'));
	}

	/**
	 * Alle Messenger-Threads aus der config.php
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function getMessengerThreads() {
		return collect($this->config('messenger.threads'));
	}

	/**
	 * @return bool
	 */
	public function needsIntro(): bool {

		// Intro im Web niemals anzeigen (State kann nicht getrackt werden, da kein Device)
		if (!$this->isRunningNative()) {
			return false;
		}

		$device = $this->getDevice();
		$login = $device->getLoginDevice($this->user);
		if ($login && $login->intro_finished) {
			return false;
		}

		return true;
	}

	/**
	 * Login-User der App
	 *
	 * @param \Ext_TS_Inquiry_Contact_Login $user
	 */
	public function setUser(\Ext_TS_Inquiry_Contact_Login $user) {

		$this->user = $user;
		$this->student = \Ext_TS_Inquiry_Contact_Traveller::getInstance((int)$user->contact_id);

		if($this->requestInquiryId !== null) {
			$this->inquiry = $this->student->getInquiryById($this->requestInquiryId);
		}

		if(!$this->inquiry) {
			// Fallback
			$this->inquiry = $this->student->getClosestInquiry();
		}

		$this->student->setInquiry($this->inquiry);

		$this->school = $this->inquiry->getSchool();
	}

	public function getProperty(string $key): Property {

		[$key, $placeholders] = PropertyKey::match($key);

		if (null === $class = $this->bundleConfig->get('properties.'.$key.'.class', null)) {
			throw new \RuntimeException('Unknown property "'.$key.'"');
		}

		$property = app()->make($class);

		if (method_exists($property, 'placeholders')) {
			$property->placeholders((array)$placeholders);
		}

		return $property;
	}

	/**
	 * URL zu einem Bild
	 *
	 * @param string $type
	 * @param string $id
	 * @return string
	 */
	public function image(string $type, string $id) {
		return Util::imageUrl($type, $id);
	}

	/**
	 * URL zu einem Dokument
	 *
	 * @param string $type
	 * @param string $id
	 * @return string
	 */
	public function document(string $type, string $id) {
		return Util::documentUrl($type, $id);
	}

	/**
	 * Formatiert ein Datum anhand den Schuleinstellungen
	 *
	 * @deprecated
	 * @param mixed $date
	 * @param \Ext_Thebing_School $school
	 * @return string
	 */
	public function formatDate($date, \Ext_Thebing_School $school = null) {

		if(is_null($school)) {
			$school = $this->school;
		}

		$dateFormat = new \Ext_Thebing_Gui2_Format_Date('frontend_date_format', $school->getId());
		return $dateFormat->formatByValue($date);
	}

	/**
	 * @link https://carbon.nesbot.com/docs/#available-macro-formats
	 *
	 * @param \DateTime $date
	 * @param string $format
	 * @return string
	 */
	public function formatDate2(\DateTime $date, string $format): string {
		return Carbon::instance($date)
			->locale($this->getLanguageObject()->getLanguage())
			->isoFormat($format);
	}

	/**
	 * E-Mail als mailto:-Link formatieren, wenn E-Mail gültiges Format hat
	 *
	 * @param $value
	 * @return string|null
	 */
	public function formatEmailLink($value): ?string {

		if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
			return null;
		}

		return 'mailto:'.preg_replace('/\s/', '', $value);

	}

	/**
	 * Telefonnummer als tel:-Link formatieren, wenn Nummer gültig ist nach ITU
	 *
	 * @param $value
	 * @return string|null
	 * @throws \Exception
	 */
	public function formatPhoneNumberLink($value): ?string {

		// Die 0 in Klammern ist nach E.123 / DIN 5008 nicht gültig
		$value = str_replace(' (0)', '', $value);

		$oldValidate = new \WDValidate();
		$oldValidate->check = 'PHONE_ITU';
		$oldValidate->value = $value;

		if (!$oldValidate->execute()) {
			return null;
		}

		return 'tel:'.preg_replace('/\s/', '', $value);

	}

	/**
	 * Formatiert ein Datum nach ISO8601
	 *
	 * @param \DateTime $dateTime
	 * @return string
	 */
	public function formatIsoDate(\DateTime $dateTime) {
		return $dateTime->format(\DateTime::ISO8601);
	}

	/**
	 * Formatiert eine Uhrzeit anhand den Schuleinstellungen
	 *
	 * @deprecated
	 * @param $time
	 * @return string
	 */
	public function formatTime($time) {
		$timeFormat = new \Ext_Thebing_Gui2_Format_Time();
		return $timeFormat->format($time);
	}

	public function formatPercent($percent, \Ext_Thebing_School $school = null) {

		if(is_null($school)) {
			$school = $this->school;
		}

		return \Ext_Thebing_Format::Number($percent, null, $school->getId()).'%';
	}

	public function getLoggingService(): LoggingService {
		return $this->logger;
	}

	public function replaceInquiryPlaceholders(string $text): string {

		if (!$this->inquiry) {
			return $text;
		}

		$placeholderObject = $this->inquiry->getPlaceholderObject();

		try {
			$text = $placeholderObject->replace($text);
		} catch (\Throwable $e) {
			$this->logger->getLogger()->error('Inquiry placeholders failed', ['exception' => $e->getMessage(), 'errors' => $placeholderObject->getErrors()]);
		}

		return $text;
	}

}

<?php

namespace TsContactLogin\Combination;

use Carbon\Carbon;
use Core\Handler\CookieHandler;
use Core\Handler\SessionHandler;
use Exception;
use MVC_Request;
use Smarty\Smarty;
use TsContactLogin\Combination\Handler\AddCustomer;
use TsContactLogin\Combination\Handler\Billing;
use TsContactLogin\Combination\Handler\Bookings;
use TsContactLogin\Combination\Handler\Dashboard;
use TsContactLogin\Combination\Handler\Documents;
use TsContactLogin\Combination\Handler\Download;
use TsContactLogin\Combination\Handler\Personal;
use TsContactLogin\Combination\Handler\School;
use TsContactLogin\Combination\Handler\BookClass;

/**
 * Class Contact
 * Contact Portal Base Class
 * Handles Contact Portal Page Requests
 */
class Contact extends \Ext_TS_Frontend_Combination_Login_Abstract {

	/**
	 * Logged in Contact
	 * @var \Ext_TS_Contact|null
	 */
	private \Ext_TS_Contact|null $contact;

	/**
	 * Type of contact that is logged in. 'traveller' or 'booker'
	 * @var string
	 */
	private string $loginType = '';

	/**
	 * Array of all inquiries (as objects) associated with logged in contact
	 * @var array
	 */
	private array $inquiries = [];

	/**
	 * Current inquiry id
	 * @var int|null
	 */
	private int|null $currentInquiryId = null;

	/**
	 * Array of all travellers
	 * @var \Ext_TS_Inquiry_Contact_Traveller[]
	 */
	private array $travellers = [];

	public function __construct(\Ext_TC_Frontend_Combination $combination, Smarty $smarty = null) {
		if (!\TcExternalApps\Service\AppService::hasApp(\TsContactLogin\Handler\ExternalApp::APP_NAME)) {
			throw new \Exception('App ist nicht installiert.');
		}
		parent::__construct($combination, $smarty);
	}

	/**
	 * Get the currently selected inquiry id
	 * @return int
	 */
	public function getCurrentInquiryId(): int {
		return $this->currentInquiryId;
	}

	/**
	 * Get id of the logged in contact
	 * @return int
	 */
	public function getContactId(): int {
		return $this->_aUserData['data']['contact_id'];
	}

	/**
	 * Get logged in contact
	 * @return \Ext_TS_Contact
	 */
	public function getContact(): \Ext_TS_Contact {
		return $this->contact;
	}

	/**
	 * Get the type of logged in contact ('booker', 'traveller')
	 * @return string
	 */
	public function getLoginType(): string {
		return $this->loginType;
	}

	/**
	 * Get all Inquiries
	 * @return \Ext_TS_Inquiry[]
	 */
	public function getInquiries(): array {
		return $this->inquiries;
	}

	/**
	 * Get all active Inquiries
	 * @return \Ext_TS_Inquiry[]
	 */
	public function getActiveInquiries(): array {
		return array_filter($this->inquiries, fn ($inquiry) : bool  => $inquiry->active && !empty($inquiry->service_from));
	}

	/**
	 * Get Ids of all travellers
	 * @return \Ext_TS_Inquiry_Contact_Traveller[]
	 */
	public function getTravellers(): array {
		return $this->travellers;
	}

	/**
	 * Add traveller to list of travellers associated with the login
	 * @param \Ext_TS_Inquiry_Contact_Traveller $traveller
	 * @return void
	 */
	public function addTraveller(\Ext_TS_Inquiry_Contact_Traveller $traveller): void {
		$this->travellers[$traveller->id] = $traveller;
	}

	/**
	 * Get current selected inquiry as object
	 * @return \Ext_TS_Inquiry|null
	 */
	public function getInquiry(): \Ext_TS_Inquiry|null {
		return $this->inquiries[$this->currentInquiryId] ?? null;
	}

	/**
	 * Add inquiry to list of inquiries associated with the login
	 * @param \Ext_TS_Inquiry $inquiry
	 * @return void
	 */
	public function addInquiry(\Ext_TS_Inquiry $inquiry): void {
		$this->inquiries[$inquiry->id] = $inquiry;
	}

	/**
	 * @param string $sNavItem
	 * @return bool
	 */
	public function navItemIsActive(string $sNavItem): bool {
		return $sNavItem === $this->_sTask;
	}

	private function initUserData(): void {

		$inquiries = [];
		$inquiryCookieName = $this->getContactId().'inquiry';

		/**
		 * First check if contact is a booker
		 */
		$this->contact = \Ext_TS_Inquiry_Contact_Booker::getInstance($this->getContactId());

		if ($this->contact) {
			$this->loginType = 'booker';
			/**
			 * Contact is booker, get associated inquiries
			 */
			$inquiries = $this->contact->getInquiries(false, true);
		} else {
			$this->loginType = 'traveller';
			/**
			 * Contact is not booker, must be a traveller
			 */
			$this->contact = \Ext_TS_Inquiry_Contact_Traveller::getInstance($this->getContactId());
			$inquiries = $this->contact?->getInquiries(false, true);
		}

		foreach ($inquiries as $inquiry) {
			$traveller = $inquiry->getCustomer();
			$this->travellers[$traveller->id] = $traveller;
			$this->inquiries[$inquiry->id] = $inquiry;
		}

		/**
		 * Check for current selected inquiry and assign
		 */

		$availableKeys = array_keys($this->inquiries);
		if ($this->getRequest()->exists('student_booking') && in_array((int)$this->getRequest()->get('student_booking'), $availableKeys)) {
			$this->currentInquiryId = (int)$this->getRequest()->get('student_booking');
			CookieHandler::set($inquiryCookieName, $this->currentInquiryId);
		} else if (CookieHandler::is($inquiryCookieName)) {
			$this->currentInquiryId = CookieHandler::get($inquiryCookieName);
		} else {
			$this->currentInquiryId = array_key_first($this->inquiries);
		}

		$this->_assign('currentInquiryId', $this->currentInquiryId);

		$this->_assign('language', $this->_getLanguage());
		$this->_assign('localeService', new \Core\Service\LocaleService());

		$this->_assign('loggedIn', $this->_isLoggedIn());
		$this->_assign('contact', $this->contact);
		$this->_assign('loginType', $this->loginType);
	}

	protected function taskMaster(string $task): void {
		$handlePasswordFunction = "_".$task;
		if ($this->_isLoggedIn()) {
			$this->initUserData();
		}
		match ($task) {
			'newBooking' => new BookClass($this),
			'getFile' => new Download($this),
			'showDocuments' => new Documents($this),
			'showBookingData' => new Bookings($this),
			'showSchoolData' => new School($this),
			'showBillingData' => new Billing($this),
			'addCustomer' => new AddCustomer($this),
			'showPersonalData' => new Personal($this),
			'showIndexData', 'default' => new Dashboard($this),
			'sendPassword', 'requestPassword', 'changePassword', 'executeChangePassword', 'resetPassword' => $this->$handlePasswordFunction(),
			default => null,
		};
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function assign(string $name, mixed $value): void {
		$this->_assign($name, $value);
	}

	/**
	 * Sets value of the task variable
	 * @param string $name
	 * @return void
	 */
	public function setTask(string $name): void {
		$this->_setTask($name);
	}

	/**
	 * Get the language
	 * @return string
	 */
	public function getLanguage(): string {
		return $this->_getLanguage();
	}

	/**
	 * Get the url
	 * @param string $task
	 * @return string
	 */
	public function getUrl(string $task): string {
		return $this->_getUrl($task);
	}

	/**
	 * Get the url
	 * @return MVC_Request
	 */
	public function getRequest(): MVC_Request {
		return $this->_oRequest;
	}

	/**
	 * Get nav items (not used but abstract)
	 * @return array
	 */
	protected function _getNavItems(): array {
		return ['showIndexData', 'showSchoolData', 'showPersonalData', 'showBillingData', 'showBookingData', 'showDocuments'];
	}

	/**
	 * Get base url (not used but abstract)
	 * @return string
	 */
	public function _getBaseUrl(): string {
		return '?';
	}

	/**
	 * Get id of db
	 * @return string
	 */
	protected function _getCustomerDbId(): string {
		return '77';
	}

	/**
	 * Changed traveller to contact, because it can also be booker now
	 * Get email of logged in contact
	 * @param $iLoginId
	 * @return string|null
	 * @throws Exception
	 */
	protected function getLoginEmail($iLoginId): string|null {
		$oContact = \Ext_TS_Contact::getInstance($iLoginId);
		return $oContact->getFirstEmailAddress()->email;
	}

}

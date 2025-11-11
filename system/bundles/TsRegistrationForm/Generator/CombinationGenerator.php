<?php

namespace TsRegistrationForm\Generator;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tc\Service\Language\Frontend as FrontendLanguage;
use TcFrontend\Dto\WidgetPath;
use TcFrontend\Factory\WidgetPathHashedFactory;
use TcFrontend\Interfaces\WidgetCombination;
use TcFrontend\Traits\WidgetCombinationTrait;
use TsRegistrationForm\Factory\BookingByKeyFactory;
use TsRegistrationForm\Factory\ServiceBlockFactory;
use TsRegistrationForm\Handler\SpamShield;
use TsRegistrationForm\Interfaces\RegistrationCombination;

/**
 * @see \TsRegistrationForm\Controller\RegistrationController
 */
class CombinationGenerator extends \Ext_TC_Frontend_Combination_Abstract implements WidgetCombination, RegistrationCombination {

	use WidgetCombinationTrait;

	const FRONTEND_CONTEXT = 'Fidelo » Registration Form';

	/**
	 * @var \Ext_Thebing_School
	 */
	private $school;

	/**
	 * @var \Ext_Thebing_Form
	 */
	private $form;

	/**
	 * @var FrontendLanguage
	 */
	private $language;

	private string $token;

	/**
	 * @var array
	 */
	private array $cacheData;

	private array $schoolData = [];

	/**
	 * @var bool
	 */
	private $cacheGenerating = false;

	/**
	 * @var \Ext_TS_Inquiry
	 */
	private $inquiry;

	private FormBookingGenerator $bookingGenerator;

	private SpamShield $spamShield;

	public function __construct(\Ext_TC_Frontend_Combination $oCombination, \SmartyWrapper $oSmarty = null) {
		parent::__construct($oCombination, $oSmarty);
		$this->bookingGenerator = new FormBookingGenerator($this);
	}

	/**
	 * Prüfen, ob (mögliche) übergeben Sprache im Form existiert, ansonsten Default-Sprache
	 * Prüfen, ob Schule übergeben wurde, ansonsten Default-School von Kombination
	 * Buchung setzen, falls vorhanden (Prozess + Vorlage)
	 */
	public function initCombination(Request $request, string $language = null) {

		// (Unnötige) Session muss deaktiviert werden, da sich ansonsten alle Requests gegenseitig blockieren
		// Die Session wird ohnehin nicht im neuen Form benötigt
		\Core\Handler\SessionHandler::disableSession();

		$this->token = $request->filled('fields.token') ? $request->input('fields.token') : Str::random(40);

		$this->form = \Ext_Thebing_Form::getInstance($this->_oCombination->items_form);

		if (
			empty($language) ||
			!in_array($language, $this->form->languages)
		) {
			$language = $this->form->default_language;
		}

		parent::initCombination($request, $language);

		$this->language = $this->createLanguageObject($language);

		// Wird auch in \TsRegistrationForm\Handler\ParallelProcessing\AbstractTask gesetzt
		$schoolId = $request->input('fields.school');

		if (
			empty($schoolId) ||
			!in_array($schoolId, $this->form->schools)
		) {
			$schoolId = $this->getCombination()->getSchool();
		}

		$this->school = \Ext_Thebing_School::getInstance($schoolId);

		if ($request->has('no_cache')) {
			$this->form->ignore_cache = 1;
		}

		// Buchungsvorlage über Widget-URL direkt (in der Script-URL)
		$inquiry = (new BookingByKeyFactory($this))->make($request->input('fields.booking', $request->input('booking')));
		if ($inquiry !== null) {
			$this->setInquiry($inquiry);
		}

		$this->spamShield = new SpamShield($this, $request);

	}

	/**
	 * Thebing Form Designer (Kombination)
	 *
	 * @return \Ext_Thebing_Form
	 */
	public function getForm(): \Ext_Thebing_Form {
		return $this->form;
	}

	/**
	 * Default-School (Kombination) oder übermittelte (fields.school)
	 *
	 * @return \Ext_Thebing_School
	 */
	public function getSchool(): \Ext_Thebing_School {
		return $this->school;
	}

	/**
	 * @see initCombination()
	 * @inheritdoc
	 */
	public function getLanguage(): FrontendLanguage {
		return $this->language;
	}

	public function getWidgetPaths(): array {

		$paths = [
			// Lokal: https://CUSTOMER.fidelo.com/api/1.0/ts/frontend/registration/
			// Extern: https://proxy.fidelo.com/app/registration-form/1.0/:CUSTOMER/api/
			'api' => new WidgetPath('api/1.0/ts/frontend/registration', '', 'registration-form:api')
		];

		foreach ($this->cacheData['schools'] as $schoolData) {
			foreach ($schoolData['paths'] as $key => $path) {
				if (!$path instanceof WidgetPath) {
					$path = WidgetPath::fromArray($path);
				}
				$paths[$key] = $path;
			}
		}

		return $paths;

	}

	public function getWidgetScripts(): array {

//		// Polyfills von polyfill.io für IE11
//		// Das könnte man zwar auch mit imports von core-js lösen, würde aber das RegForm-JS nur noch weiter aufblähen
//		$polyfills = [
//			'Array.prototype.find',
//			'Array.prototype.findIndex',
//			'Array.prototype.includes',
//			'Object.entries',
//			'Object.fromEntries', // widget.js/parseCookies
//			'Number.isInteger',
//			'String.prototype.endsWith',
//			'String.prototype.startsWith',
//			'String.prototype.padStart',
//			'Promise'
//		];

		return [
//			// Siehe oben
//			'https://polyfill.io/v3/polyfill.min.js?features='.urlencode(join(',', $polyfills)),
//			// Polyfill für Custom Elements für alte Browser https://caniuse.com/#feat=custom-elementsv1
//			'https://cdnjs.cloudflare.com/ajax/libs/document-register-element/1.4.1/document-register-element.js',
			// Lokal: https://CUSTOMER.fidelo.com/assets/ts-registration-form/js/registration.js
			// Extern: https://proxy.fidelo.com/app/registration-form/1.0/:CUSTOMER/js/registration.js
			(new WidgetPathHashedFactory('assets/ts-registration-form', 'js/registration.js', 'registration-form', 'TsRegistrationForm:assets/'))->create()
		];

	}

	public function getWidgetStyles(): array {

		$styles = [];

		if ($this->isUsingBundle() || $this->isUsingIframe()) {
			$styles[] = (new WidgetPathHashedFactory('assets/ts-registration-form', 'css/registration-bootstrap4.css', 'registration-form', 'TsRegistrationForm:assets/'))->create();
		}

		$styles[] = (new WidgetPathHashedFactory('assets/ts-registration-form', 'css/registration.css', 'registration-form', 'TsRegistrationForm:assets/'))->create();

		if ($this->isUsingBundle() || $this->isUsingIframe()) {
			// TODO Über fontawesome-svg-core lösen
			$styles[] = 'https://use.fontawesome.com/releases/v5.13.0/css/fontawesome.css';
			$styles[] = 'https://use.fontawesome.com/releases/v5.13.0/css/solid.css';
		}

		return $styles;

	}

	public function getWidgetData($checkCacheIgnore = false): array {

		$this->checkCache($checkCacheIgnore);

		$data = [
			'settings' => $this->cacheData['settings'],
			'icons' => $this->cacheData['icons'],
			'mutations' => [],
			'actions' => []
		];

		// Diese Felder stehen zwar in schoolData drin, gehen aber nicht ans Frontend
		$excludedData = ['fields', 'fields_client', 'paths', 'activities', 'booking'];

		$schoolData = $this->getSchoolData();

		$widgetData = new Collection();
		$widgetData->put('fields', $schoolData->get('fields_client'));
		$widgetData = $widgetData->merge($schoolData->except($excludedData));

		$data = array_merge($data, $widgetData->all());

		// Form abstürzen lassen, wenn Daten aktualisieren + Leistungen kombiniert werden
		if ($this->form->hasInvalidBlocksForEditPurpose()) {
			throw new \RuntimeException('Currently service blocks only work for new bookings');
		}

		$hasBooking = !empty($this->inquiry);
		$booking = $this->bookingGenerator->mergeBooking($schoolData, $data['settings']);
		$data['actions'][] = array_merge(['handler' => 'setInitialBookingData'], $booking);
		$data['settings']['has_booking'] = $hasBooking;

		if (!$hasBooking && $this->form->purpose !== \Ext_Thebing_Form::PURPOSE_NEW) {
			// Formular initial sperren, da in jedem anderen Fall ein Key benötigt wird (analog zur Fehlermeldung im Form-Designer)
			$data['mutations'][] = ['handler' => 'DISABLE_STATE', 'key' => 'form', 'status' => true];
			$data['actions'][] = ['handler' => 'addNotification', 'key' => 'booking_error', 'type' => 'info', 'message' => $this->form->getTranslation('error_key', $this->getLanguage())];
		} else {
			// Manueller Trigger auf Services für Preise, abhängige Leistungen etc.
			foreach ($booking['services'] as $blockKey => $services) {
				$keyField = data_get($schoolData, 'fields.services.'.$blockKey.'.id');
				$data['actions'][] = ['handler' => 'triggerField', 'field' => ['services', $blockKey, $keyField]];
			}
		}

		if ($this->getForm()->ignore_cache) {
			$data['notifications'] = [
				[
					'key' => 'cache_disabled',
					'type' => 'warning ',
					'message' => 'Warning: Cache is disabled!'
				]
			];
		}

		$this->spamShield->init();

		return $data;

	}

	/**
	 * @see FormDataGenerator::getSettings()
	 *
	 * @return array
	 */
	public function getSettings() {

		$this->checkCache();

		return $this->cacheData['settings'];

	}

	/**
	 * @see FormDataGenerator::generateSchoolData()
	 */
	public function getSchoolData(int $schoolId = null): Collection {

		if ($schoolId === null) {
			$schoolId = $this->getSchool()->id;
		}

		if (isset($this->schoolData[$schoolId])) {
			return $this->schoolData[$schoolId];
		}

		$this->checkCache();

		// TODO schoolData zu einer eigenen Klasse machen, die sich um Transformation des JSONs kümmert
		$this->schoolData[$schoolId] = new Collection($this->cacheData['schools'][$schoolId]);
		$this->schoolData[$schoolId]->transform(function ($data) {
			return new Collection($data);
		});

		if (
			$this->inquiry !== null &&
			$this->getForm()->purpose === \Ext_Thebing_Form::PURPOSE_EDIT
		) {
			$this->bookingGenerator->mergeMissingValues($this->schoolData[$schoolId]);
		}

		foreach ((new ServiceBlockFactory())->makeAll($this, $this->schoolData[$schoolId]) as $serviceBlock) {
			$serviceBlock->generateData();
		}

		return $this->schoolData[$schoolId];

	}

	/**
	 * Buchung setzen mit der das Form vorbefüllt wird
	 *
	 * @param \Ext_TS_Inquiry $inquiry
	 */
	public function setInquiry(\Ext_TS_Inquiry $inquiry) {

		$this->inquiry = $inquiry;

		// Schule komplett austauschen
		// Der Standard in der Buchung ist erst einmal der hier gesetzte, aber eine Buchungsvorlage kann die Schule überschreiben
		$this->school = $inquiry->getJourney()->getSchool();

	}

	/**
	 * Cache prüfen, ggf. generieren, Daten holen/setzen
	 *
	 * @param bool $checkCacheIgnore
	 */
	private function checkCache($checkCacheIgnore = false) {

		if (!empty($this->cacheData)) {
			return;
		}

		// Wenn an irgendeiner Stelle in generateWidgetData() wieder etwas aufgerufen wird, das checkCache() aufruft,
		// 	gibt es eine Endlosschleife, bis ein Memory Overflow kommt
		if ($this->cacheGenerating) {
			throw new \LogicException('Possible infinite loop detected');
		}

		$data = null;

		if (
			!$checkCacheIgnore ||
			!$this->getForm()->ignore_cache
		) {
			$data = $this->oCachingHelper->getFromCache($this->getLanguage()->getLanguage());
		}

		// Die Benutzer wären eh zu blöd dafür, wenn die Kombination manuell das erste mal generiert werden müsste
		// Das läuft in der Agentur anders, weil da die Kombinationen so riesig sind, dass das Frontend abstürzen würde
		if ($data === null) {
			$this->generateCache();
		}

		$this->cacheData = $this->oCachingHelper->getFromCache($this->getLanguage()->getLanguage());

	}

	/**
	 * Cache-Datei pro Sprache; Schulen stehen alle in einer Datei
	 */
	private function generateCache() {

		ini_set('memory_limit', '1G');
		
		// $this in Ruhe lassen
		$self = clone $this;
		$self->cacheGenerating = true;

		foreach ($self->getForm()->languages as $iso) {
			$self->language = $self->createLanguageObject($iso);

			// Einmal global für diese Sprache (Settings)
			$generator = new FormDataGenerator($self);
			$data = $generator->generateWidgetData();

			// Dann für jede Schule generieren
			foreach ($self->form->getSelectedSchools() as $school) {
				$self->school = $school;
				$generator = new FormDataGenerator($self);
				$data['schools'][$school->id] = $generator->generateSchoolData();
			}

			$self->oCachingHelper->writeToCache($iso, $data);
		}

		$self->cacheGenerating = false;

	}

	/**
	 * @inheritdoc
	 */
	protected function executeInitializeData() {

		$this->initCombination(new \Illuminate\Http\Request());
		$this->generateCache();

	}

	/**
	 * @param string $iso
	 * @return FrontendLanguage
	 */
	private function createLanguageObject(string $iso): FrontendLanguage {

		$language = new FrontendLanguage($iso);
		$language->setContext(self::FRONTEND_CONTEXT);
		return $language;

	}

	public function getWidgetPassParams(): array {

		$params = ['param:booking', 'param:prefill'];

		$trackingKey = \System::d('ts_registration_form_tracking_key');
		if ($trackingKey) {
			$params[] = 'cookie:'.$trackingKey;
		}

		return $params;

	}

	public function getInquiry(): ?\Ext_TS_Inquiry {
		return $this->inquiry;
	}

	public function getBookingGenerator(): FormBookingGenerator {
		return $this->bookingGenerator;
	}

	public function getToken(): string {
		return $this->token;
	}

	public function getSpamShield(): SpamShield {
		return $this->spamShield;
	}

}

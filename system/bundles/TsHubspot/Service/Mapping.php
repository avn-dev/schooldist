<?php

namespace TsHubspot\Service;

use Core\Helper\DateTime;
use TsHubspot\Dto\FieldMapping;

class Mapping {

	public $courseFields;
	public $accommodationFields;
	public $inquiryFields;
	public $transferFields;
	public $insuranceFields;
	public $agencyFields;
	public $activityFields;
	public $paymentFields;

	public function __construct() {

		$this->setCourseFields();
		$this->setAccommodationFields();
		$this->setInsuranceFields();
		$this->setTransferFields();
		$this->setInquiryFields();
		$this->setAgencyFields();
//		$this->setActivityFields();
		$this->setPaymentFields();
	}

	public function setCourseFields() {
		$courseFieldsForMapping = self::getCourseFieldsForMapping();

		$fields = [];

		// Standardinformationen setzen
		foreach ($courseFieldsForMapping as $fieldName => $fieldValue) {
			$fields[$fieldName] = FieldMapping::getInstance($fieldName)->setLabel($fieldValue)->setService('courses');
		}

		$fields['category_id']->setGetter(
			fn($course) => $course->getCourse()->getCategory()->getName($course->getJourney()->getSchool()->getLanguage())
		);
		// Unsetten für unten, weil hier jetzt ein Getter schon gesetzt ist.
		unset($courseFieldsForMapping['category_id']);

		$fields['course_id']->setGetter(
			fn($course) => $course->getCourseName()
		);
		unset($courseFieldsForMapping['course_id']);

		$fields['category_course']->setGetter(
			function($course) {
				$category = $course->getCourse()->getCategory()->getName($course->getJourney()->getSchool()->getLanguage());
				$course = $course->getCourseName();
				return $category . ' - ' . $course;
			}
		);
		unset($courseFieldsForMapping['category_course']);

		$fields['courselanguage_id']->setGetter(
			fn($course) => $course->getCourseLanguage()->getName($course->getJourney()->getSchool()->getLanguage())
		);
		unset($courseFieldsForMapping['courselanguage_id']);

		$fields['level_id']->setGetter(
			fn($course) => $course->getLevel()->getName($course->getJourney()->getSchool()->getLanguage())

		);
		unset($courseFieldsForMapping['level_id']);

		$fields['from']->setGetter(
			function($course) {
				return \Ext_Thebing_Format::LocalDate($course->from, $course->getJourney()->getSchool());
			}
		);
		unset($courseFieldsForMapping['from']);

		$fields['until']->setGetter(
			function($course) {
				return \Ext_Thebing_Format::LocalDate($course->until, $course->getJourney()->getSchool());
			}
		);
		unset($courseFieldsForMapping['until']);

		// Bei diesen Feldern kann man es verallgemeinern weil es nur $course->$fieldName ist.
		foreach ($courseFieldsForMapping as $fieldName => $fieldValue) {
			$fields[$fieldName]->setGetter(fn($course) => $course->$fieldName);
		}

		$this->courseFields = $fields;
	}

	public function setAccommodationFields() {
		$accommodationFieldsForMapping = self::getAccommodationFieldsForMapping();

		$fields = [];

		// Standardinformationen setzen
		foreach ($accommodationFieldsForMapping as $fieldName => $fieldValue) {
			$fields[$fieldName] = FieldMapping::getInstance($fieldName)->setLabel($fieldValue)->setService('accommodation');
		}

		$fields['accommodation_id']->setGetter(
			fn($accommodation) => $accommodation->getAccommodationName(false, $accommodation->getJourney()->getSchool()->getLanguage())
		);
		// Unsetten für unten, weil hier jetzt ein Getter schon gesetzt ist.
		unset($accommodationFieldsForMapping['accommodation_id']);

		$fields['roomtype_id']->setGetter(
			fn($accommodation) => $accommodation->getRoomType()->getName($accommodation->getJourney()->getSchool()->getLanguage())
		);
		unset($accommodationFieldsForMapping['roomtype_id']);

		$fields['meal_id']->setGetter(
			fn($accommodation) => $accommodation->getMeal()->getName($accommodation->getJourney()->getSchool()->getLanguage())
		);
		unset($accommodationFieldsForMapping['meal_id']);

		$fields['acc_allergies']->setGetter(
			fn($accommodation) => $accommodation->getJourney()->getInquiry()->getMatchingData()->acc_allergies

		);
		unset($accommodationFieldsForMapping['acc_allergies']);

		$fields['from']->setGetter(
			function($accommodation) {
				return \Ext_Thebing_Format::LocalDate($accommodation->from, $accommodation->getJourney()->getSchool());
			}
		);
		unset($accommodationFieldsForMapping['from']);

		$fields['until']->setGetter(
			function($accommodation) {
				return \Ext_Thebing_Format::LocalDate($accommodation->until, $accommodation->getJourney()->getSchool());
			}
		);
		unset($accommodationFieldsForMapping['until']);

		// Bei diesen Feldern kann man es verallgemeinern weil es nur $accommodation->$fieldName ist.
		foreach ($accommodationFieldsForMapping as $fieldName => $fieldValue) {
			$fields[$fieldName]->setGetter(fn($accommodation) => $accommodation->$fieldName);
		}

		$this->accommodationFields = $fields;
	}

	public function setInsuranceFields() {
		$insuranceFieldsForMapping = self::getInsuranceFieldsForMapping();

		$fields = [];

		// Standardinformationen setzen
		foreach ($insuranceFieldsForMapping as $fieldName => $fieldValue) {
			$fields[$fieldName] = FieldMapping::getInstance($fieldName)->setLabel($fieldValue)->setService('insurance');
		}

		$fields['insurance_id']->setGetter(
			fn($insurance) => $insurance->getInsuranceName($insurance->getJourney()->getSchool()->getLanguage())
		);

		$fields['from']->setGetter(
			function($insurance) {
				return \Ext_Thebing_Format::LocalDate($insurance->from, $insurance->getJourney()->getSchool());
			}
		);

		$fields['until']->setGetter(
			function($insurance) {
				return \Ext_Thebing_Format::LocalDate($insurance->until, $insurance->getJourney()->getSchool());
			}
		);

		$this->insuranceFields = $fields;
	}

	public function setTransferFields() {
		$transferFieldsForMapping = self::getTransferFieldsForMapping();

		$fields = [];

		// Standardinformationen setzen
		foreach ($transferFieldsForMapping as $fieldName => $fieldValue) {
			$fields[$fieldName] = FieldMapping::getInstance($fieldName)->setLabel($fieldValue)->setService('transfer');
		}

		$fields['transfer_type']->setGetter(
			fn($transfer) => \Ext_Thebing_Data::getTransferList(
				$transfer->getJourney()->getInquiry()->getSchool()->getLanguage(),
				true
			)[$transfer->getJourney()->transfer_mode]
		);

		$fields['transfer_date']->setGetter(
			function($transfer) {
				return \Ext_Thebing_Format::LocalDate($transfer->transfer_date, $transfer->getJourney()->getSchool());
			}
		);

		$fields['start']->setGetter(
			function($transfer) {
				$language = $transfer->getJourney()->getInquiry()->getSchool()->getLanguage();
				return $transfer->getStartLocation(new \Tc\Service\Language\Frontend($language));
			}
		);

		$fields['end']->setGetter(
			function($transfer) {
				$language = $transfer->getJourney()->getInquiry()->getSchool()->getLanguage();
				return $transfer->getEndLocation(new \Tc\Service\Language\Frontend($language));
			}
		);

		$fields['comment']->setGetter(
			fn($transfer) => $transfer->comment
		);

		$this->transferFields = $fields;
	}

	public function setInquiryFields() {
		$inquiryFieldsForMapping = self::getInquiryFieldsForMapping();

		$fields = [];

		// Standardinformationen setzen
		foreach ($inquiryFieldsForMapping as $fieldName => $fieldValue) {
			$fields[$fieldName] = FieldMapping::getInstance($fieldName)->setLabel($fieldValue)->setService('inquiry');
		}

		$fields['school_id']->setGetter(
			fn($inquiry) => $inquiry->getSchool()->ext_1
		);

		$fields['lastname']->setGetter(
			fn($inquiry) => $inquiry->getTraveller()->lastname
		);

		$fields['firstname']->setGetter(
			fn($inquiry) => $inquiry->getTraveller()->firstname
		);

		$fields['gender']->setGetter(
			fn($inquiry) => $inquiry->getTraveller()->getFrontendGender($inquiry->getJourney()->getSchool()->getLanguage())
		);

		$fields['birthday']->setGetter(
			function($inquiry) {
				return \Ext_Thebing_Format::LocalDate($inquiry->getTraveller()->birthday, $inquiry->getJourney()->getSchool());
			}
		);

		$fields['nationality']->setGetter(
			fn($inquiry) => $inquiry->getNationality($inquiry->getJourney()->getSchool()->getLanguage())
		);

		$fields['mother_tongue']->setGetter(
			fn($inquiry) => \Ext_Thebing_Util::getLanguageName(
				$inquiry->getTraveller()->language,
				$inquiry->getJourney()->getSchool()->getLanguage()
			)
		);

		$fields['corresponding_language']->setGetter(
			function($inquiry) {
				$oLocaleService = new \Core\Service\LocaleService;
				return $oLocaleService->getInstalledLocales(
					$inquiry->getJourney()->getSchool()->getLanguage()
				)[$inquiry->getTraveller()->corresponding_language] ?? $inquiry->getTraveller()->corresponding_language;
			}
		);

		$fields['address']->setGetter(
			fn($inquiry) => $inquiry->getTraveller()->getAddress()->address
		);

		$fields['address_addon']->setGetter(
			fn($inquiry) => $inquiry->getTraveller()->getAddress()->address_addon
		);

		$fields['zip']->setGetter(
			fn($inquiry) => $inquiry->getTraveller()->getAddress()->zip
		);

		$fields['city']->setGetter(
			fn($inquiry) => $inquiry->getTraveller()->getAddress()->city
		);

		$fields['state']->setGetter(
			fn($inquiry) => $inquiry->getTraveller()->getAddress()->state
		);

		$fields['country']->setGetter(
			function($inquiry) {
				$localeService = new \Core\Service\LocaleService();
				return $localeService->getCountries($inquiry->getJourney()->getSchool()->getLanguage())[$inquiry->getTraveller()->getAddress()->country_iso];
			}
		);

		$fields['phone_private']->setGetter(
			fn($inquiry) => $inquiry->getTraveller()->getFirstPrivatePhoneNumber()
		);

		$fields['phone_office']->setGetter(
			fn($inquiry) => $inquiry->getTraveller()->getFirstOfficePhoneNumber()
		);

		$fields['email']->setGetter(
			fn($inquiry) => $inquiry->getCustomerEmail()
		);

		$fields['agency_id']->setGetter(
			fn($inquiry) => $inquiry->getAgency()?->getName(true) ?? ''
		);

		$fields['agency_contact_id']->setGetter(
			fn($inquiry) => $inquiry->getAgencyContact()?->getName() ?? ''
		);

		$fields['currency_id']->setGetter(
			fn($inquiry) => $inquiry->getCurrency(true)->getName()
		);

		$fields['sales_person_id']->setGetter(
			fn($inquiry) => $inquiry->getSalesPerson()?->getName() ?? ''
		);

		$fields['comment']->setGetter(
			fn($inquiry) => $inquiry->getTraveller()->getComment()
		);

		$fields['confirmed_date']->setGetter(
			function($inquiry) {
				return \Ext_Thebing_Format::LocalDate($inquiry->getBookingConfirmedForIndex(), $inquiry->getJourney()->getSchool());
			}
		);

		$fields['passport_number']->setGetter(
			fn($inquiry) => $inquiry->getVisaData()?->passport_number ?? ''
		);

		$fields['visa_required']->setGetter(
			fn($inquiry) => $inquiry->getVisaData()?->required ?? ''
		);

		$fields['total_amount']->setGetter(
			fn($inquiry) => $inquiry->getTotalAmount()
		);

		$this->inquiryFields = $fields;
	}

	public function setAgencyFields() {
		$agencyFieldsForMapping = self::getAgencyFieldsForMapping();

		$fields = [];

		// Standardinformationen setzen
		foreach ($agencyFieldsForMapping as $fieldName => $fieldValue) {
			$fields[$fieldName] = FieldMapping::getInstance($fieldName)->setLabel($fieldValue)->setService('agency');
		}

		$fields['number']->setGetter(
			fn($agency) => $agency->getNumber()
		);
		// Unsetten für unten, weil hier jetzt ein Getter schon gesetzt ist.
		unset($agencyFieldsForMapping['number']);

		$fields['name']->setGetter(
			fn($agency) => $agency->getName(true)
		);
		// Unsetten für unten, weil hier jetzt ein Getter schon gesetzt ist.
		unset($agencyFieldsForMapping['name']);

		$fields['short']->setGetter(
			fn($agency) => $agency->getName(false)
		);
		// Unsetten für unten, weil hier jetzt ein Getter schon gesetzt ist.
		unset($agencyFieldsForMapping['short']);

		$fields['category_id']->setGetter(
			fn($agency) => $agency->getCategory()->name
		);
		// Unsetten für unten, weil hier jetzt ein Getter schon gesetzt ist.
		unset($agencyFieldsForMapping['category_id']);

		$fields['country']->setGetter(
			function($agency) {
				$localeService = new \Core\Service\LocaleService();
				return $localeService->getCountries($agency->getLanguage())[$agency->ext_6];
			}
		);
		// Unsetten für unten, weil hier jetzt ein Getter schon gesetzt ist.
		unset($agencyFieldsForMapping['country']);

		$fields['corresponding_language']->setGetter(
			function($agency) {
				$oLocaleService = new \Core\Service\LocaleService;
				return $oLocaleService->getInstalledLocales($agency->getLanguage())[$agency->ext_33] ?? $agency->ext_33;
			}
		);
		// Unsetten für unten, weil hier jetzt ein Getter schon gesetzt ist.
		unset($agencyFieldsForMapping['corresponding_language']);

		$fields['currency_id']->setGetter(
			function($agency) {
				return \Ext_Thebing_Currency::getInstance($agency->ext_23)->getName();
			}
		);
		// Unsetten für unten, weil hier jetzt ein Getter schon gesetzt ist.
		unset($agencyFieldsForMapping['currency_id']);

		// Bei diesen Feldern kann man es verallgemeinern weil es nur $agency->$fieldName ist.
		foreach ($agencyFieldsForMapping as $fieldName => $fieldValue) {
			$fields[$fieldName]->setGetter(fn($agency) => $agency->$fieldName);
		}

		$this->agencyFields = $fields;
	}

	/**
	 * Gibt es (erstmal) nicht
	 */
//	public function setActivityFields(): void {
//		$activityFieldsForMapping = self::getActivityFieldsForMapping();
//
//		$fields = [];
//
//		// Standardinformationen setzen
//		foreach ($activityFieldsForMapping as $fieldName => $fieldValue) {
//			$fields[$fieldName] = FieldMapping::getInstance($fieldName)->setLabel($fieldValue)->setService('activity');
//		}
//
//		$fields['activity_id']->setGetter(
//			fn($activity) => $activity->getActivity()->getName($activity->getJourney()->getSchool()->getLanguage())
//		);
//
//		$fields['weeks']->setGetter(
//			fn($activity) => $activity->weeks
//		);
//
//		$fields['from']->setGetter(
//			function($activity) {
//				if (
//					empty($activity->from) ||
//					$activity->from == '0000-00-00'
//				) {
//					return '';
//				} else {
//					$dateTime = new DateTime($activity->from);
//					return $dateTime->format($activity->getJourney()->getSchool()->date_format_long);
//				}
//			}
//		);
//
//		$fields['until']->setGetter(
//			function($activity) {
//				if (
//					empty($activity->until) ||
//					$activity->until == '0000-00-00'
//				) {
//					return '';
//				} else {
//					$dateTime = new DateTime($activity->until);
//					return $dateTime->format($activity->getJourney()->getSchool()->date_format_long);
//				}
//			}
//		);
//
//		$fields['comment']->setGetter(
//			fn($activity) => $activity->comment
//		);
//
//		// Logik später in der validatePropertyAdding() weil das hier nicht wirklich geht.
//		$fields['booked']->setGetter(
//			fn($activity) => true
//		);
//
//		$this->activityFields = $fields;
//	}

	public function setPaymentFields(): void {
		$paymentFieldsForMapping = self::getPaymentFieldsForMapping();

		$fields = [];

		// Standardinformationen setzen
		foreach ($paymentFieldsForMapping as $fieldName => $fieldValue) {
			$fields[$fieldName] = FieldMapping::getInstance($fieldName)->setLabel($fieldValue)->setService('payment');
		}

		$fields['amount']->setGetter(
			function($payment) {
				$format = new \Ext_Thebing_Gui2_Format_Amount();
				return $format->format($payment->amount_inquiry);
			}
		);

		$fields['currency']->setGetter(
			function($payment) {
				$format = new \Ext_Thebing_Gui2_Format_Currency();
				return $format->format($payment->currency_inquiry);
			}
		);

		$fields['method_id']->setGetter(
			fn($payment) => $payment->getMethodName()
		);

		$fields['comment']->setGetter(
			fn($payment) => $payment->comment
		);

		$fields['date']->setGetter(
			function($payment) {
				return \Ext_Thebing_Format::LocalDate($payment->date, $payment->getJourney()->getSchool());
			}
		);


		$this->paymentFields = $fields;
	}

	public function getFieldsByService($service) {
		$variableString = $service.'Fields';
		return $this->$variableString;
	}

	public static function getCourseFieldsForMapping(): array
	{
		return [
			'category_id' => 'Kategorie',
			'course_id' => 'Kurs',
			'category_course' => 'Kategorie - Kurs',
			'courselanguage_id' => 'Kurssprache',
			'level_id' => 'Level',
			'weeks' => 'Wochenanzahl',
			'from' => 'Kursstart',
			'until' => 'Kursende',
			'comment' => 'Kommentar'
		];
	}

	public static function getAccommodationFieldsForMapping(): array
	{
		return [
			'accommodation_id' => 'Unterkunft',
			'roomtype_id' => 'Raumart',
			'meal_id' => 'Verpflegung',
			'weeks' => 'Wochenanzahl',
			'from' => 'Unterkunftsstart',
			'until' => 'Unterkunftsende',
			'comment' => 'Kommentar',
			'acc_allergies' => 'Allergien'
		];
	}

	public static function getInsuranceFieldsForMapping(): array
	{
		return [
			'insurance_id' => 'Kategorie',
			'from' => 'Versicherungsstart',
			'until' => 'Versicherungsende'
		];
	}

	public static function getTransferFieldsForMapping(): array
	{
		return [
			'transfer_type' => 'Transfer',
			'transfer_date' => 'Datum',
			'start' => 'Anreiseort',
			'end' => 'Ankunftsort',
			'comment' => 'Kommentar'
		];
	}

	public static function getInquiryFieldsForMapping(): array
	{
		return [
			'school_id' => 'Schule',
			'lastname' => 'Nachname',
			'firstname' => 'Vorname',
			'gender' => 'Geschlecht',
			'birthday' => 'Geburtsdatum',
			'nationality' => 'Nationalität',
			'mother_tongue' => 'Muttersprache',
			'corresponding_language' => 'Korrespondenzsprache',
			'address' => 'Adresse',
			'address_addon' => 'Adresszusatz',
			'zip' => 'PLZ',
			'city' => 'Stadt',
			'state' => 'Bundesland',
			'country' => 'Land',
			'phone_private' => 'Telefon',
			'phone_office' => 'Telefon Büro',
			'email' => 'E-Mail',
			'agency_id' => 'Agentur',
			'agency_contact_id' => 'Agenturmitarbeiter',
			'currency_id' => 'Währung',
			'sales_person_id' => 'Vertriebsmitarbeiter',
			'comment' => 'Kommentar',
			'confirmed_date' => 'Bestätigt am',
			'passport_number' => 'Passnummer',
			'visa_required' => 'Visum wird benötigt',
			'total_amount' => 'Gesamtbetrag',
		];
	}

	public static function getAgencyFieldsForMapping(): array
	{
		return [
			'number' => 'Nummer',
			'name' => 'Name',
			'short' => 'Abkürzung',
			'category_id' => 'Kategorie',
			'ext_3' => 'Adresse',
			'ext_35' => 'Adresszusatz',
			'ext_4' => 'PLZ',
			'ext_5' => 'Stadt',
			'state' => 'Bundesland',
			'country' => 'Land',
			'comment' => 'Kommentar',
			'corresponding_language' => 'Korrespondenzsprache',
			'founding_year' => 'Gründungsjahr',
			'start_cooperation' => 'Beginn der Zusammenarbeit',
			'staffs' => 'Anzahl der Mitarbeiter',
			'customers' => 'Anzahl der Kunden',
			'currency_id' => 'Währung',
			'ext_24' => 'Steuernummer',
			'vat_number' => 'USt.-ID'
		];
	}

	public static function getActivityFieldsForMapping(): array
	{
		return [
			'activity_id' => 'Aktivität',
			'weeks' => 'Wochen',
			'from' => 'Von',
			'until' => 'Bis',
			'comment' => 'Kommentar',
			'booked' => 'Ist gebucht',
		];
	}

	public static function getPaymentFieldsForMapping(): array
	{
		return [
			'amount' => 'Summe',
			'currency' => 'Währung',
			'method_id' => 'Methode',
			'comment' => 'Bemerkung',
			'date' => 'Quittungsdatum',
		];
	}

}
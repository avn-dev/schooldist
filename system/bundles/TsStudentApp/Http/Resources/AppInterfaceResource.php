<?php

namespace TsStudentApp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use TsStudentApp\AppInterface;
use TsStudentApp\Entity\AppContent;
use TsStudentApp\Facades\PropertyKey;

/**
 * @mixin AppInterface
 */
class AppInterfaceResource extends JsonResource
{
	public function toArray($request)
	{
		$data = [];

		if($this->isLoggedIn()) {

			$disabledPages = (array)$this->getSchool()->getMeta('student_app_disabled_pages');
			// Dürfen nicht in der normalen Navigation erscheinen
			$fixedPages = ['messenger-thread', 'schedule-info'];

			if (empty($intro)) {
				// Aktuell kann man in den Settings nur das Intro zurücksetzen, wenn es kein Intro gibt macht es keinen
				// Sinn die Settings-Page anzuzeigen
				$fixedPages[] = 'settings';
			}

			$pageCollection = $this->getConfigPages()
				->reject(fn(array $page) => in_array($page['key'], $fixedPages) || ($page['deactivatable'] && in_array($page['key'], $disabledPages)))
				->values();

			/*$pageCollection->filter(fn (array $page) => isset($page['watch']))
				->each(fn (array $page) => $this->watch($page['watch']));*/

			$inquiries = collect($this->getStudent()->getInquiries(false, true))
				->filter(fn (\Ext_TS_Inquiry $inquiry) => $inquiry->isActive());

			$data['inquiryId'] = $this->getInquiry()->getId();
			$data['inquiries'] = InquiryResource::collection($inquiries)->toArray($request);
			$data['pages'] = PageResource::collection($pageCollection)->toArray($request);
			$data['properties'] = $this->generatePropertyResources($request)->toArray($request);

			if($this->isRunningNative()) {
				$data['device'] = $this->getDevice()->id;
			}

			// Intro für das Device anzeigen?
			$data['intro'] = $this->needsIntro() ? $this->generateIntroEntries() : [];

			// Farben überschreiben (--ion-color-primary-shade => statusbar)
//			$appColors = new AppColor($this->school);
//			$data['colors'] = $appColors->generateColorsArray();

			// Globale Übersetzungen
			$data['i18n'] = [
				'global.logout' => $this->t('Logout'), // More
				'global.no_data' => $this->t('No data found.'),
				'global.booking' => $this->t('Buchung'), // More
				'messenger.message.new' => $this->t('You received a new message.'),
				'messenger.toast.open' => $this->t('Open'),
				'messenger.toast.okay' => $this->t('Got it!'),
				'welcome.intro.continue' => $this->t('Continue to App'),
				'schedule.event.info.heading' => $this->t('Information'),
				'schedule.event.no_upcoming' => $this->t('No upcoming dates.'),
				'activities.show_details' => $this->t('Show details'),
				'activities.book_now' => $this->t('Book now'),
				'activities.pay_now' => $this->t('Pay now'),
				'activities.no_upcoming' => $this->t('No upcoming activities.'),
				'activities.order.close' => $this->t('Close'),
				'activities.for_free' => $this->t('For free'),
				// @deprecated - "tab." entfernen - muss in der App auch noch angepasst werden
				'tab.activities.show_details' => $this->t('Show details'),
				'tab.activities.book_now' => $this->t('Book now'),
				'tab.activities.pay_now' => $this->t('Pay now'),
				'tab.activities.no_upcoming' => $this->t('No upcoming activities.'),
				'tab.activities.order.close' => $this->t('Close'),
				'tab.activities.for_free' => $this->t('For free'),
			];

			// Native Übersetzungen (/assets/i18n/) müssen in der selben Struktur verschachtelt sein
			/*$nativeI18N = [];
			foreach($nativeI18N as $key => $translation) {
				Arr::set($data['i18n'], $key, $translation);
			}*/

			// TODO Abwärtskompatibilität (< 2.1.0)
			$data['inquiry_id'] = $data['inquiryId'];
		}

		return $data;
	}

	private function generatePropertyResources(Request $request) {

		$config = collect($this->getBundleConfig()->get('properties'));

		$properties = $config
			->filter(fn ($property) => ($property['on_request'] ?? false) === false)
			->keys()
			->map(fn ($key) => $this->getProperty(PropertyKey::generate($key)));

		return PropertyResource::collection($properties);
	}

	private function generateIntroEntries(): array {
		$entries = AppContent::query()
			->onlyValid()
			->where('school_id', $this->getSchool()->getId())
			->where('type', \TsStudentApp\Enums\AppContentType::INTRO)
			->where('released', 1)
			->get();

		$language = $this->getLanguageObject()->getLanguage();

		$intros = [];
		foreach ($entries as $entry) {

			$entry = [
				'heading' => $entry->getI18NName('i18n', 'title', $language),
				// TODO aufsplitten wenn zu lang
				'content' => $this->replaceInquiryPlaceholders($entry->getI18NName('i18n', 'content', $language))
			];

			$intros[] = $entry;
		}

		return $intros;
	}

}
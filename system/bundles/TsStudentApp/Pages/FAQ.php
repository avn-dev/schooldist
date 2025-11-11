<?php

namespace TsStudentApp\Pages;

use TsStudentApp\AppInterface;
use TsStudentApp\Entity\AppContent;

class FAQ extends AbstractPage {

	private $appInterface;

	private $school;

	public function __construct(AppInterface $appInterface, \Ext_Thebing_School $school) {
		$this->appInterface = $appInterface;
		$this->school = $school;
	}

	public function init(): array {
		return [
			'nodes' => $this->getFaqEntries()
		];
	}

	/**
	 * Einträge direkt über Query holen, da Objekte hier keinen Sinn machen (return 1:1)
	 *
	 * @return array
	 */
	private function getFaqEntries(): array {

		$entries = AppContent::query()
			->onlyValid()
			->where('school_id', $this->school->getId())
			->where('type', \TsStudentApp\Enums\AppContentType::FAQ)
			->where('released', 1)
			->get();

		$language = $this->appInterface->getLanguageObject()->getLanguage();

		return $entries->map(fn (AppContent $entry) => [
				'id' => $entry->id,
				'title' => $entry->getI18NName('i18n', 'title', $language),
				'content' => $this->appInterface->replaceInquiryPlaceholders($entry->getI18NName('i18n', 'content', $language))
			])
			->toArray();
	}
}

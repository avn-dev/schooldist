<?php

namespace TsRegistrationForm\Events;

use Core\Enums\AlertLevel;
use Core\Interfaces\HasAlertLevel;
use Core\Traits\WithAlertLevel;
use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Interfaces\Events;
use Ts\Traits\Events\Manageable\WithManageableIndividualCommunication;
use TsFrontend\Interfaces\Events\CombinationEvent;
use TsRegistrationForm\Interfaces\RegistrationCombination;

class PdfCreationFailed implements ManageableEvent, Events\InquiryEvent, CombinationEvent, HasAlertLevel
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableSystemUserCommunication,
		WithManageableIndividualCommunication,
		WithAlertLevel;

	public function __construct(
		private RegistrationCombination $combination,
		private \Ext_TS_Inquiry $inquiry,
		private \Ext_Thebing_Inquiry_Document $document
	) {}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Online-Formular: PDF fehlgeschlagen');
	}

	public function getAlertLevel(): ?AlertLevel
	{
		return AlertLevel::DANGER;
	}

	public function getIcon(): ?string
	{
		return 'fas fa-file-pdf';
	}

	public function getInquiry(): \Ext_TS_Inquiry
	{
		return $this->inquiry;
	}

	public function getSchool(): \Ext_Thebing_School
	{
		return $this->getInquiry()->getSchool();
	}

	public function getDocument(): \Ext_Thebing_Inquiry_Document
	{
		return $this->document;
	}

	public function getCombination(): \Ext_TC_Frontend_Combination
	{
		return $this->combination->getCombination();
	}

	public function getLanguage(): string
	{
		return $this->combination->getLanguage()->getLanguage();
	}

	public static function getPlaceholderObject(self $event = null): ?\Ext_TC_Placeholder_Abstract
	{
		$document = ($event) ? $event->getDocument() : new \Ext_Thebing_Inquiry_Document();
		return $document->getPlaceholderObject();
	}

}

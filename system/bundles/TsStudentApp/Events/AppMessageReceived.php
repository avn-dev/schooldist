<?php

namespace TsStudentApp\Events;

use Core\Interfaces\Events\SystemEvent;
use Core\Interfaces\HasIcon;
use Core\Traits\WithIcon;
use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Interfaces\Events\InquiryEvent;
use Ts\Traits\Events\Manageable\WithManageableIndividualCommunication;
use Ts\Traits\Events\Manageable\WithManageableSchoolCommunication;
use TsStudentApp\Listeners;
use TsStudentApp\Messenger\Thread\AbstractThread;
use TsStudentApp\Notifications\AppMessageNotification;

class AppMessageReceived implements ManageableEvent, InquiryEvent, SystemEvent, HasIcon
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableSystemUserCommunication,
		WithManageableSchoolCommunication,
		WithManageableIndividualCommunication,
		WithIcon;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Schüler-App: Nachricht eingegangen');
	}

	public function __construct(
		private AbstractThread $thread,
		private \Ext_TC_Communication_Message $message
	) {}

	public function getIcon(): ?string
	{
		return 'fas fa-comments';
	}

	public function getThread(): AbstractThread
	{
		return $this->thread;
	}

	public function getInquiry(): \Ext_TS_Inquiry
	{
		return $this->thread->getInquiry();
	}

	public function getSchool(): \Ext_Thebing_School
	{
		return $this->getInquiry()->getSchool();
	}

	public function getReceiver(): \WDBasic
	{
		return $this->thread->getEntity();
	}

	public function getMessage(): \Ext_TC_Communication_Message
	{
		return $this->message;
	}

	public function getNotification($listener, $notification = null) {
		return new AppMessageNotification($this->thread, $this->message);
	}

	public static function manageAppMessageListenerAndConditions()
	{
		self::addManageableCondition(Conditions\Receiver::class);
		self::addManageableListener(Listeners\RedirectAppMessageToReceiver::class);
	}

}
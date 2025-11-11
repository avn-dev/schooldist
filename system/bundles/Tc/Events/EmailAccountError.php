<?php

namespace Tc\Events;

use Core\Interfaces\Events\SystemEvent;
use Core\Notifications\SystemUserNotification;
use Core\Service\HtmlPurifier;
use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;

class EmailAccountError implements ManageableEvent, SystemEvent
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableSystemUserCommunication;

	public function __construct(private \Ext_TC_Communication_EmailAccount $account, private string $message) {}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Probleme mit E-Mail-Konten');
	}

	public function getAccount(): \Ext_TC_Communication_EmailAccount
	{
		return $this->account;
	}

	public function getSystemUserNotification($listener, $notification, $users)
	{
		return [self::buildSystemUserNotification($this->account, $this->message), $users];
	}

	public static function manageListenersAndConditions(): void
	{
		//self::addManageableCondition(NewsType::class);
	}

	public static function buildSystemUserNotification(\Ext_TC_Communication_EmailAccount $account, string $message)
	{
		$purifier = (new HtmlPurifier(HtmlPurifier::SET_FRONTEND));

        $content = sprintf(
            $purifier->purify(\L10N::t('Bei dem E-Mail-Konto <strong>%s</strong> sind folgende Probleme aufgetreten: <br/><br/>%s')),
            $account->email,
            $purifier->purify($message)
        );

        $notification = (new SystemUserNotification($content))
            ->group(\L10N::t('Systembenachrichtigung'));

		return $notification;
	}

}
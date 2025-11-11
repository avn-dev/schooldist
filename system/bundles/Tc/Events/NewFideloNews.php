<?php

namespace Tc\Events;

use Core\Interfaces\Events\SystemEvent;
use Core\Notifications\SystemUserNotification;
use Core\Service\HtmlPurifier;
use Illuminate\Foundation\Events\Dispatchable;
use Tc\Events\Conditions\NewsType;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;

class NewFideloNews implements ManageableEvent, SystemEvent
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableSystemUserCommunication;

	public function __construct(private array $news) {}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Neue Fidelo-News');
	}

	public function getNews(): array
	{
		return $this->news;
	}

	public function getSystemUserNotification($listener, $notification, $users)
	{
		return [self::buildSystemUserNotification($this->news), $users];
	}

	public static function manageListenersAndConditions(): void
	{
		//self::addManageableCondition(NewsType::class);
	}

	public static function buildSystemUserNotification(array $news)
	{
		$purifier = (new HtmlPurifier(HtmlPurifier::SET_TCPDF));

		$title = $purifier->purify($news['title']);
		$content = $purifier->purify($news['content']);

		if ($news['type'] === 'announcement') {
			$notification = new \Core\Notifications\AnnouncementNotification($title, $content);
			if (!empty($news['image'])) {
				$notification->image(strip_tags($news['image']));
			}
		} else if ($news['type'] === 'news' && (int)$news['important']) {
			$notification = new \Core\Notifications\PopupNotification($title, $content);
		} else {
			$notification = (new SystemUserNotification($content))
				->group(\L10N::t('Systembenachrichtigung'));
		}

		return $notification;
	}

}
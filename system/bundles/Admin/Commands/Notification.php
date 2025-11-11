<?php

namespace Admin\Commands;

use Admin\Instance;
use Core\Command\AbstractCommand;
use Core\Enums\AlertLevel;
use Core\Notifications\SystemUserNotification;
use Core\Service\HtmlPurifier;
use Illuminate\Http\Request;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Notification extends AbstractCommand
{
    protected function configure()
	{
        $this->setName("admin:notification")
			->addArgument('user', InputArgument::REQUIRED)
			->addOption('type', null, InputOption::VALUE_OPTIONAL)
			->addOption('count', null, InputOption::VALUE_OPTIONAL)
			->setDescription("Send test notification");
    }

	public function handle()
	{
		/* @var \User $user */
		$user = \User::query()->findOrFail($this->argument('user'));

		$count = $this->option('count') ?? 1;

		if ($count > 10) {
			$count = 10;
		}

		for ($i = 0; $i < $count; $i++) {
			$notification = $this->generateNotification($this->option('type', 'default'));
			$user->notifyNow($notification, ['database']);
		}

		$this->components->info(sprintf('%d Notification(s) sent to user "%s"!', $count, $user->getName()));

		return Command::SUCCESS;
    }

	private function generateNotification(string $type = null)
	{
		$purifier = (new HtmlPurifier(HtmlPurifier::SET_TCPDF));

		$title = $purifier->purify('Fidelo Test Notification');
		$content = $purifier->purify('This is a <b>test notification</b> to see if everything works as expected! Have a <u>great</u> day! <br><br> <a href="https://fidelo.com">fidelo.com</a>');

		if ($type === 'announcement') {
			$images = [
				'https://fidelo.com/storage/public/fidelo/visual_school.png',
				'https://fidelo.com/storage/public/announcements/webinar-featured_fideo.png',
				'https://fidelo.com/storage/public/announcements/layout_1.png',
				'https://fidelo.com/storage/public/announcements/layout_1.png',
				'https://fidelo.com/storage/public/announcements/layout_1.png',
			];
			$notification = new \Core\Notifications\AnnouncementNotification($title, $content);
			$notification->image($images[random_int(0, count($images) - 1)]);
		} else if ($type === 'important') {
			$notification = new \Core\Notifications\PopupNotification($title, $content);
		} else if ($type === 'toast' || $type === 'toast-persist') {
			$levels = AlertLevel::cases();
			$notification = (new \Core\Notifications\ToastrNotification($content, $levels[random_int(0, count($levels) - 1)]));
			if ($type === 'toast-persist') {
				$notification->persist();
			}
		} else {
			$notification = (new SystemUserNotification($content))
				->group(\L10N::t('Systembenachrichtigung'))
				->icon('fa fa-info-circle');
		}

		return $notification;
	}

}
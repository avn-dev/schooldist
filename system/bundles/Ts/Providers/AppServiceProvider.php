<?php

namespace Ts\Providers;

use Admin\Components\BookmarksComponent as CoreBookmarks;
use Admin\Instance;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use Tc\Interfaces\ResourcesFactory;
use Ts\Admin\Components;
use Ts\Admin\Search;
use Ts\Admin\Tenants;

class AppServiceProvider extends ServiceProvider
{
	public function boot()
	{
		$this->app->bind(ResourcesFactory::class, \Ts\Helper\ResourcesFactory::class);

		// Notification Channels
		//Notification::extend('ts-mail', fn ($app) => new Channels\TsMailChannel());
		Notification::extend('mail', fn ($app) => new \Communication\Notifications\Channels\MailChannel());
		Notification::extend('sms', fn ($app) => new \Communication\Notifications\Channels\SmsChannel());
		Notification::extend('app', fn ($app) => new \Communication\Notifications\Channels\AppChannel());
		Notification::extend('notice', fn ($app) => new \Communication\Notifications\Channels\NoticeChannel());

		// Admin
		$this->app->resolving(Instance::class, function (Instance $instance) {
			$instance->booting(function (Instance $instance, \Access_Backend $access) {
				if (!$this->app->runningInConsole()) {
					// TODO anders lösen
					$instance->supportChat('https://update.thebing.com/fidelo_software_chat_v3.js');
					if ($access->hasRight('core_zendesk')) {
						$instance->helpdesk('/zendesk/sso');
					}
				}
				$instance->tenants(Tenants::class);
				$instance->search('ts.inquiry', Search\Traveller::class);
				$instance->component(CoreBookmarks::KEY, Components\BookmarksComponent::class);
				$instance->component('dashboard.news', \Tc\Admin\Components\Dashboard\NewsWidgetComponent::class);
				$instance->component('dashboard.enquiries_inquiries', \TsDashboard\Admin\Components\EnquiriesAndInquiriesComponent::class);
				$instance->component('dashboard.student_nationalities', \TsDashboard\Admin\Components\StudentNationalitiesComponent::class);
				$instance->component(\Communication\Admin\Components\CommunicationComponent::KEY, \Communication\Admin\Components\CommunicationComponent::class);
				$instance->component(\Communication\Admin\Components\CommunicationComponent::ALLOCATE_COMPONENT_KEY, Components\Communication\AllocateMessageComponent::class);
				$instance->component('ts.traveller.{traveller}', Components\TravellerComponent::class);
			});
		});
	}
}

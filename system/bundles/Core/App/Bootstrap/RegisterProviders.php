<?php

namespace Core\App\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\RegisterProviders as LaravelBootstrap;

class RegisterProviders extends LaravelBootstrap
{
	public function bootstrap(Application $app)
	{
		// TODO Workaround für https://github.com/nunomaduro/collision/issues/311
		$app->singleton(\Illuminate\Contracts\Debug\ExceptionHandler::class, \Illuminate\Foundation\Exceptions\Handler::class);

		parent::bootstrap($app);

		/*$providers = Collection::make(\System::wd()->getServiceProviders())
			->partition(function ($provider) {
				// Core-Serviceproviders zuerst einbinden
				return str_starts_with($provider, 'Core\\');
			});

		$serviceProviders = $providers->collapse()->toArray();*/

		// Core-Serviceprovider werden in config/app.php hinterlegt
		$serviceProviders = \System::wd()->getServiceProviders();

		foreach ($serviceProviders as $providerClass) {
			$app->register($providerClass);
		}
	}
}
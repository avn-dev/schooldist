<?php

if(defined('APP_DEBUG') === false) {
	define('APP_DEBUG', false);
}

if(defined('APP_KEY') === false) {
	define('APP_KEY', '');
}

return [

	'debug' => APP_DEBUG,

	'intern' => [
		'emails' => [
			'domains' => ['@fidelo.com', '@thebing.com', '@p32.de', '@plan-i.de']
		]
	],

	/*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

	'key' => APP_KEY,

	'cipher' => 'AES-256-CBC',

	/*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        #Illuminate\Auth\AuthServiceProvider::class,
        #Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        #Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        #Illuminate\Cookie\CookieServiceProvider::class,
        #Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        #Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        #Illuminate\Hashing\HashServiceProvider::class,
        #Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        #Illuminate\Pagination\PaginationServiceProvider::class,
        #Illuminate\Pipeline\PipelineServiceProvider::class,
        #Illuminate\Queue\QueueServiceProvider::class,
        #Illuminate\Redis\RedisServiceProvider::class,
        #Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        #Illuminate\Translation\TranslationServiceProvider::class,
        #Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

		/*
		 * Fidelo Application
		 */
		\Core\Providers\AppServiceProvider::class,
		\Core\Providers\EventServiceProvider::class,
		'\Inertia\ServiceProvider', // Falls die Klasse noch nicht existiert
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [

        'App' => Illuminate\Support\Facades\App::class,
        #'Artisan' => Illuminate\Support\Facades\Artisan::class,
        #'Auth' => Illuminate\Support\Facades\Auth::class,
        #'Blade' => Illuminate\Support\Facades\Blade::class,
        #'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        #'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        #'Cookie' => Illuminate\Support\Facades\Cookie::class,
        #'Crypt' => Illuminate\Support\Facades\Crypt::class,
        #'DB' => Illuminate\Support\Facades\DB::class,
        #'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        #'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        #'Gate' => Illuminate\Support\Facades\Gate::class,
        #'Hash' => Illuminate\Support\Facades\Hash::class,
        #'Lang' => Illuminate\Support\Facades\Lang::class,
        #'Log' => Illuminate\Support\Facades\Log::class,
        #'Mail' => Illuminate\Support\Facades\Mail::class,
        #'Notification' => Illuminate\Support\Facades\Notification::class,
        #'Password' => Illuminate\Support\Facades\Password::class,
        #'Queue' => Illuminate\Support\Facades\Queue::class,
        #'Redirect' => Illuminate\Support\Facades\Redirect::class,
        #'Redis' => Illuminate\Support\Facades\Redis::class,
        #'Request' => Illuminate\Support\Facades\Request::class,
        #'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        #'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        #'Storage' => Illuminate\Support\Facades\Storage::class,
        #'URL' => Illuminate\Support\Facades\URL::class,
        #'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,

    ],

];

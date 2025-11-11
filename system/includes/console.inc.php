<?php

// Minimale Includes

require_once __DIR__."/../../config/config.php";
require_once __DIR__.'/autoload.inc.php';
require_once __DIR__.'/debug.inc.php';

if(!class_exists(\Symfony\Component\Console\Application::class)) {
	// Falls vendor-Ordner fehlt, hier abstürzen, anstatt mit Fatal Error: Class not found
	echo "Symfony console does not exist! Does vendor folder exist? Aborting.\n";
	exit(1);
}

// Systemeinstellungen
$system_data = \System::readConfig();

// Debugmodus prüfen und setzen
\System::setDebugmode();

// System booten
$app = \System::boot('backend');

\System::setInterface('backend');

// Für Hooks
$objWebDynamics = webdynamics::getInstance('backend');

//frontend MVC macht sonst probleme wenn man noch nicht im system eingeloggt bzw. zumindestens auf der startseite war :)
System::setInterfaceLanguage('en');

$objWebDynamics->getIncludes();

// Get DB Objekt
DB::setResultType(MYSQL_ASSOC);
$oDb = DB::getDefaultConnection();

Factory::executeStatic('Util', 'getAndSetTimezone');
Factory::executeStatic('System', 'setLocale');

$app->instance('env', 'production');
// Das Scheduling braucht eine Cache-Klasse um sich das withOverlapping() zu merken. Da wir eine eigene Implementierung
// vom Cache haben muss die Cache-Klasse von Laravel überschrieben werden
//$app->bind(\Illuminate\Contracts\Cache\Factory::class, fn () => new \Core\Console\Scheduling\CacheFactory());
//$app->bind(\Illuminate\Contracts\Debug\ExceptionHandler::class, \Core\Exception\ExceptionHandler::class);



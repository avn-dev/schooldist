<?php

use Illuminate\Support\Facades\Route;

/*
 * @todo Route prüfen
 */
Route::any('/css/{sFile}.css', [Cms\Controller\CssController::class, 'outputFile'])
	->name('cms_css');

Route::get('/assets/cms/{sFile}', [Cms\Controller\ResourceController::class, 'printFile'])
	->name('cms_resources')
	->where('sFile','.+')
;
Route::group(['prefix' => '/admin/cms'], function() {

	Route::any('', [Cms\Controller\AdminController::class, 'page'])
		->name('cms_admin');

	/*
	 * @todo Route prüfen
	 */
	Route::any('/structure/{iSiteId}/{sLanguage}', [Cms\Controller\AdminController::class, 'structure'])
		->name('cms_structure')
		->where('iSiteId','\d+')
		->where('sLanguage','[a-zA-Z-_]{2,}');

	Route::get('/structure/edit', [Cms\Controller\AdminController::class, 'editStructure'])
		->name('cms_structure_edit');

	Route::get('/structure/save', [Cms\Controller\AdminController::class, 'saveStructure'])
		->name('cms_structure_save');

	Route::post('/structure/delete', [Cms\Controller\AdminController::class, 'deleteStructure'])
		->name('cms_structure_delete');

	/*
	 * @todo Route prüfen
	 */
	Route::any('/structure/home/{iPageId}', [Cms\Controller\AdminController::class, 'homeStructure'])
		->name('cms_structure_home');

	/*
	 * @todo Route prüfen
	 */
	Route::any('/publish/{iPageId}/{sLanguage}/{sAction}', [Cms\Controller\AdminController::class, 'publishContent'])
		->name('cms_page_publish')
		->where('sAction','accept|deny');
	/*
	 * @todo Route prüfen
	 */
	Route::any('/page/{iPageId}/{sLanguage}/{sMode}', [Cms\Controller\AdminPageController::class, 'editPage'])
		->name('cms_edit_page')
		->where('sMode','online|live|preview|edit|settings|structure');

	Route::post('/content/save', [Cms\Controller\AdminController::class, 'saveContent'])
		->name('cms_content_save');

	/*
	 * @todo Route prüfen
	 */
	Route::any('/site/{iSiteId}/languages', [Cms\Controller\AdminController::class, 'siteLanguages'])
		->name('cms_site_languages');
});

/*
 * @todo Route prüfen
 */
Route::any('/vendor/maximebf/debugbar/src/DebugBar/Resources/{sFile}', [Cms\Controller\PublicResourseController::class, 'printFile'])
	->name('cms_debugbar')
	->where('sFile','.+');


$dispatch = \Factory::getObject(Cms\Service\Routing::class);

/* @var $dynamicRoutes \Core\Model\DynamicRoute[] */
$dynamicRoutes = call_user_func([$dispatch, 'buildRoutes']);

foreach($dynamicRoutes as $dynamicRoute) {

	$requirements = $dynamicRoute->getRequirements();
	if(!empty($requirements['hosts'])) {

		$hosts = explode('|', $requirements['hosts']);

		foreach($hosts as $host) {
			Route::domain($host)->group(function () use($dynamicRoute) {
				$dynamicRoute->addLaravelRoute();
			});
		}

	} else {
		$dynamicRoute->addLaravelRoute();
	}
	
}

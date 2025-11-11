<?php

namespace TsStudentApp\Http\Controller;

use Core\Helper\BundleConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use TsStudentApp\AppInterface;
use TsStudentApp\Helper\DynamicComponentsResponse;
use TsStudentApp\Http\Resources\AppInterfaceResource;
use TsStudentApp\Http\Resources\PropertyResource;
use TsStudentApp\Pages\AbstractPage;
use TsStudentApp\Service\AccessService;
use TsStudentApp\Service\LoggingService;

class InterfaceController extends \Illuminate\Routing\Controller {

	/**
	 * Init-Request der App. Hier werden die Seiten und globale Informationen der App geladen
	 *
	 * @param AppInterface $appInterface
	 * @return JsonResponse
	 */
	public function init(Request $request, AppInterface $appInterface) {

		return response()
			->json(
				(new AppInterfaceResource($appInterface))->toArray($request)
			);

	}

	/**
	 * Page-Action (init, refresh, etc.)
	 *
	 * @param AppInterface $appInterface
	 * @param AccessService $accessService
	 * @param AbstractPage $pageObject
	 * @param LoggingService $loggingService
	 * @param $page
	 * @param $action
	 * @return mixed
	 */
	public function pageAction(AppInterface $appInterface, AccessService $accessService, AbstractPage $pageObject, LoggingService $loggingService, $page, $action) {

		if(!method_exists($pageObject, $action)) {
			return response('Invalid page action', 400);
		}

		if ($appInterface->isRunningNative()) {
			$loggingService->pageAction($accessService, $page, $action);
		}

		$response = app()->call([$pageObject, $action]);

		if ($response instanceof DynamicComponentsResponse) {
			return $response->toResponse();
		} else if(is_object($response)) {
			return $response;
		}

		$json = [];
		$json['data'] = $response;

		if($action === 'init') {
			$i18n = $pageObject->getTranslations($appInterface);
			if(!empty($i18n)) {
				$json['i18n'] = $i18n;
			}

			$colors = $pageObject->getColors($appInterface);
			if(!empty($colors)) {
				$json['colors'] = $colors;
			}
		}

		return response()
				->json($json);
	}

	/**
	 * @deprecated Ab App 3.0.0
	 */
	public function finishIntro(AppInterface $appInterface) {

		$loginDevice = $appInterface->getLoginDevice();
		if ($loginDevice) {
			$loginDevice->intro_finished = 1;
			$loginDevice->save();
		}

		return response()->json(['success' => true]);

	}

	public function properties(Request $request, AppInterface $appInterface, BundleConfig $bundleConfig) {

		$allPropertyKeys = array_keys($bundleConfig->get('properties'));

		$propertyKeys = array_unique(Arr::wrap($request->input('properties', $allPropertyKeys)));

		$properties = collect($propertyKeys)
			->map(fn (string $property) => $appInterface->getProperty($property))
			->values();

		$json = PropertyResource::collection($properties)->toArray($request);

		return response()
			->json($json);
	}

}

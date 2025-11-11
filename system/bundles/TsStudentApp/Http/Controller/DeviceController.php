<?php

namespace TsStudentApp\Http\Controller;

use Illuminate\Http\Request;
use TsStudentApp\AppInterface;
use TsStudentApp\Service\AccessService;

class DeviceController extends \Illuminate\Routing\Controller {

	public function storeMessagingToken(Request $request, AccessService $accessService, AppInterface $appInterface) {

		if(
			!$appInterface->isRunningNative() ||
			!$request->has('token')
		) {
			return response('Bad request', 400);
		}

		$loginDevice = $appInterface->getDevice()->getLoginDevice($accessService->getUser());

		if(is_null($loginDevice)) {
			return response('Bad request', 400);
		}

		if ($request->filled('token')) {
			$loginDevice->fcm_token = $request->input('token');
		}

		if ($request->filled('apns_token')) {
			$loginDevice->apns_token = $request->input('apns_token');
		}

		// Ab Version 2.1.0
		$loginDevice->push_permission = 1;
		if ($request->has('has_permission')) {
			$loginDevice->push_permission = (int)$request->boolean('has_permission');
		}

		$loginDevice->last_action = time();

		$loginDevice->save();

		return response()->json(['success' => true]);

	}

}

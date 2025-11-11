<?php

namespace TsMews\Controller;

use TsMews\Api;
use TsMews\Api\Operations\GetReservations;
use TsMews\Service\Synchronization;
use Illuminate\Http\Request;

/**
 * https://github.com/MewsSystems/gitbook-connector-api/blob/f66450477bda45692668d7c88bba50d7a2e880e4/webhooks.md
 *
 * Class WebHookController
 * @package TsMews\Controller
 */
class WebHookController extends \Illuminate\Routing\Controller {

	public function action(Request $request) {

		Api::getLogger()->info('Webhook action', ['data' => $request->all()]);
		return response('Ok', 200);

		$data = $request->only(['Entities']);

		$reservationIds = [];
		if(!empty($data) && isset($data['Entities']['ServiceOrders'])) {
			$reservationIds = array_map(function(array $reservation) {
				return $reservation['Id'];
			}, $data['Entities']['ServiceOrders']);
		}

		if(!empty($reservationIds)) {

			$operation = (new GetReservations())->byIds($reservationIds);

			try {

				$search = Api::default()->request($operation);

				$reservations = collect($search->get('Reservations'));
				$customers = collect($search->get('Customers'));

				foreach($reservations as $reservation) {

					$customer = $customers->first(function($customer) use($reservation) {
						return ($customer['Id'] === $reservation['CustomerId']);
					});

					Synchronization::syncMewsReservationToFidelo($reservation, $customer);

				}

			} catch(\Throwable $e) {
				Api::getLogger()->error('Webhook failed', ['message' => $e, 'request' => $data]);
			}

		}

		return response('Ok', 200);
	}

}

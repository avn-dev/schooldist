<?php

namespace OpenBanking\Http\Controllers\ExternalApps;

use Core\Factory\OptionsFactory;
use Core\Helper\Routing;
use Core\Interfaces\Optionable;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Http\Response;
use OpenBanking\OpenBanking;
use OpenBanking\Providers\finAPI\Api\Models\Account;
use OpenBanking\Providers\finAPI\DefaultApi;
use OpenBanking\Providers\finAPI\ExternalApp;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Tc\Interfaces\ResourcesFactory;

class finAPIController extends Controller
{
	public function init(ResourcesFactory $resources)
	{
		$savedAccountIds = ExternalApp::getAccountIds();
		$savedPaymentMethods = ExternalApp::getPaymentMethodsIds();
		$savedExecutionTimes = ExternalApp::getExecutionTimes();

		$accounts = DefaultApi::default()->getAccounts(ExternalApp::getUser())
			->mapToGroups(function (Account $account) use ($savedAccountIds, $savedPaymentMethods) {
				$payload = $account->toArray();
				$payload['payment_method'] = $savedPaymentMethods[$account->getId()] ?? 0;
				$payload['execution_times'] = $savedExecutionTimes[$account->getId()] ?? [];
				$payload['selected'] = in_array($account->getId(), $savedAccountIds);
				return [$account->getBankConnectionId() => $payload];
			});

		return response()
			->json([
				'accounts' => $accounts
					->map(fn ($accounts, $bankConnectionId) => ['id' => $bankConnectionId, 'execution_times' => $savedExecutionTimes[$bankConnectionId] ?? [], 'accounts' => $accounts])
					->values(),
				'payment_methods' => OptionsFactory::buildJs($resources->getPaymentMethods())
					->toArray(),
				'times' => collect(\Ext_TC_Util::getHours())
					->map(fn ($time, $index) => ['value' => $index, 'text' => $time])
					->values()
			]);
	}

	public function webform()
	{
		$check = \Util::generateRandomString(10);

		$webForm = DefaultApi::default()->requestWebForm(
			ExternalApp::getUser(),
			new Uri(Routing::generateUrl('OpenBanking.api.finApi.webform.callback', ['check' => $check]))
		);

		ExternalApp::addOpenWebform($webForm);

		return redirect($webForm->getUrl());
	}

	public function webformCallback(Request $request)
	{
		OpenBanking::logger('finAPI')->info('Webform callback', ['request' => $request->all()]);

		if ($request->exists('webFormId')) {
			ExternalApp::deleteOpenWebform($request->input('webFormId'));
		}

		return response('', Response::HTTP_NO_CONTENT);
	}

	public function toggleAccount(Request $request)
	{
		$saved = ExternalApp::getAccountIds();

		$requestAccountId = (int)$request->input('account_id');
		$requestAccountSelected = (bool)$request->input('selected', false);

		$selected = false;
		if ($requestAccountSelected) {
			$accountIds = DefaultApi::default()->getAccounts(ExternalApp::getUser())->map(fn ($account) => $account->getId());
			if ($accountIds->contains($requestAccountId)) {
				$saved[] = $requestAccountId;
				$selected = true;
			}
		}

		if (!$selected) {
			$saved = array_filter($saved, fn ($accountId) => (int)$accountId !== $requestAccountId);
		}

		ExternalApp::saveAccountIds($saved);

		return response()->json(['success' => true]);
	}

	public function setAccountPaymentMethod(Request $request, ResourcesFactory $resources, int $id)
	{
		$requestPaymentMethodId = $request->input('payment_method', false);

		$saved = ExternalApp::getPaymentMethodsIds();
		$found = false;

		if ($requestPaymentMethodId !== 0) {
			$paymentMethod = $resources->getPaymentMethods()
				->first(fn (Optionable $option) => $option->getOptionValue() == $requestPaymentMethodId);

			if ($paymentMethod instanceof Optionable) {
				$saved[$id] = $paymentMethod->getOptionValue();
				$found = true;
			}
		}

		if (!$found) {
			unset($saved[$id]);
		}

		ExternalApp::savePaymentMethodIds($saved);

		return response()->json(['success' => true]);
	}

	public function setBankConnectionsExecutionTimes(Request $request, int $id)
	{
		$requestTimes = $request->input('execution_times', []);

		$saved = ExternalApp::getExecutionTimes();

		if (!empty($requestTimes)) {
			$times = array_unique(array_map('intval', $requestTimes));
			sort($times);
			$saved[$id] = $times;
		} else {
			unset($saved[$id]);
		}

		ExternalApp::saveExecutionTimes($saved);

		return response()->json(['success' => true]);
	}

	public function deleteAccount(int $id)
	{
		$account = DefaultApi::default()->getAccount(ExternalApp::getUser(), $id);

		if ($account) {
			$success = DefaultApi::default()->deleteAccount(ExternalApp::getUser(), $account);
		} else {
			$success = true;
		}

		if ($success) {
			$accountIds = array_diff(ExternalApp::getAccountIds(), [$id]);
			ExternalApp::saveAccountIds($accountIds);
		}

		return response()
			->json(['success' => $success]);
	}
}
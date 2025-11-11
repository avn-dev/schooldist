<?php

namespace TsStudentApp\Pages;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use TsActivities\Dto\ActivityBlockWeekCombination;
use TsActivities\Dto\BlockEvent;
use TsActivities\Entity\Activity\Block;
use TsActivities\Entity\Activity\BlockTraveller;
use TsActivities\Events\ActivityCancelled;
use TsActivities\Exceptions\InvalidAssignmentException;
use TsActivities\Exceptions\OverbookingException;
use TsActivities\Service\ActivityService;
use TsStudentApp\AppInterface;
use TsStudentApp\Http\Resources\ActivityBlockResource;

class Activities extends AbstractPage {

	private AppInterface $appInterface;

	private ActivityService $activityService;

	private \Ext_TS_Inquiry $inquiry;

	private \Ext_Thebing_School $school;

	public function __construct(AppInterface $appInterface, ActivityService $activityService, \Ext_TS_Inquiry $inquiry, \Ext_Thebing_School $school) {
		$this->appInterface = $appInterface;
		$this->activityService = $activityService;
		$this->inquiry = $inquiry;
		$this->school = $school;
	}

	public function init(Request $request): array {
		$data = $this->refresh($request);
		$data['toast_duration'] = 3000;
		$data['filters_enabled'] = true;
		$data['timeframe'] = [
			'from' => $this->inquiry->getServiceFrom(),
			'until' => $this->inquiry->getServiceUntil()
		];
		return $data;
	}

	/**
	 * Refresh-Methode
	 *
	 * @return array
	 */
	public function refresh(Request $request): array {

		// Gebuchte Aktivitäten
		$bookedBlocks = $this->buildBookedCombinations()->pluck('combination');

		// Gebuchte Aktivitäten: Obere Icons
		$limit = 6;
		$now = Carbon::now();
		$bookedLabel = null;
		$booked = $bookedBlocks
			->sort(function (ActivityBlockWeekCombination $combination) use ($now) {
				return $combination->dates[0]->start > $now ? -1 : 1;
			})
			->take($limit);

		// Label neben Icons: + X (erst ab 2.1.0)
		if ($bookedBlocks->count() > $limit) {
			// Achtung: NARROW NO-BREAK SPACE
			$bookedLabel = sprintf('+ %d', $bookedBlocks->count() - $limit);
		}

		$blocks = collect();
		if (
			($from = $this->inquiry->getServiceFrom(true)) &&
			($until = $this->inquiry->getServiceUntil(true))
		) {
			// Blöcke die gebucht werden können
			$blocks = $this->activityService->searchAvailableBlocksForInquiry($this->inquiry, $from, $until)
				->filter(function (ActivityBlockWeekCombination $combination) {
					/*if (version_compare($this->appInterface->getAppVersion(), '3.0', '<')) {
						// In den vorherigen Versionen wurden nur buchbare Blöcke angezeigt
						return $combination->isBookableInFrontend(Carbon::now());
					}*/
					return $combination->isVisibleInFrontend(Carbon::now()) ||
						$combination->isBookableInFrontend(Carbon::now());
				})
				->merge($bookedBlocks)
				->sort(function (ActivityBlockWeekCombination $combination, ActivityBlockWeekCombination $combination2) {
					return $combination->dates[0]->start > $combination2->dates[0]->start;
				});
		}

		// Apps < 2.1.0: Alle nicht buchbaren Aktivitäten wegfiltern, also gebuchte und volle (der Status existiert noch nicht)
		if (version_compare($this->appInterface->getAppVersion(), '2.1.0', '<')) {
			$blocks = $blocks->filter(fn(ActivityBlockWeekCombination $combination) => in_array(ActivityBlockWeekCombination::STATUS_BOOKABLE, $combination->status));
		}

		// Optionen für Filter
		$selectOptions = $blocks->map(function (ActivityBlockWeekCombination $combination) {
			return ['key' => $combination->activity->id, 'label' => $combination->buildName($this->appInterface->getLanguage())];
		})->unique('key');

		return [
			'booked' => ActivityBlockResource::collection(
				$booked
					->values()
					->filter(fn ($combination) => $combination->getDates()->last()->end >= Carbon::now())
			)->toArray($request),
			'booked_label' => $bookedLabel,
			'select_options' => $selectOptions->values(),
			'activities' => ActivityBlockResource::collection($blocks->values())->toArray($request),
			'date_format' => (new \Ext_Thebing_School_Proxy($this->school))->getDateFormat('date-fns') // TODO Einzige Stelle in App mit Datumsformat innerhalb App
		];
	}

	/**
	 * @TODO Entfernen wenn alle Apps >= 2.1.0. Filtern passiert ab 2.1.0 direkt in der App.
	 *
	 * Aktivitäten filtern
	 *
	 * @param Request $request
	 * @return array
	 */
	public function filterActivities(Request $request): array {

		$activities = collect($this->refresh($request)['activities']);

		$formatDateTime = function($value) {
			if(!is_null($value)) {
				$datetime = Carbon::parse($value);
				if($datetime) {
					return $datetime->setTime(0, 0);
				}
			}
			return null;
		};

		$blockKeys = (array)$request->input('block_keys', []);
		$from = $formatDateTime($request->input('from'));
		$until = $formatDateTime($request->input('until'));
		$freeOfCharge = $request->input('forFree', false);

		$activities = $activities->filter(function(array $activity) use ($blockKeys, $from, $until, $freeOfCharge) {
			$add = true;

			if(!empty($blockKeys)) {
				$add = in_array($activity['block_id'].'_'.$activity['activity_id'], $blockKeys);
			}

			if($add && $freeOfCharge) {
				$add = ($activity['for_free'] === true);
			}

			if(!empty($activity['dates'])) {
				$start = Carbon::parse($activity['dates'][0]['start_iso']);
				if($add && !is_null($from)) {
					$add = ($start >= $from);
				}

				if($add && !is_null($until)) {
					$add = ($start <= $until);
				}
			}

			return $add;
		});

		return [
			'activities' => $activities->values()
		];
	}

	/**
	 * Aktivität buchen
	 */
	public function order(Request $request) {

		if (!$request->has('activity')) {
			return response('Activity missing', 500);
		}

		$requestedActivity = $request->input('activity');

		try {

			// Version < 2.2.0
			if (!isset($requestedActivity['key'])) {
				$key = sprintf('%d_%d_%s', $requestedActivity['activity_id'], $requestedActivity['block_id'], $requestedActivity['week']);
				$requestedActivity['key'] = md5($key);
			}

			/** @var \TsActivities\Dto\ActivityBlockWeekCombination $activityCombination */
			$activityCombination = $this->activityService->searchAvailableBlocksForInquiry($this->inquiry, $this->inquiry->getServiceFrom(true), $this->inquiry->getServiceUntil(true))
				->first(function (\TsActivities\Dto\ActivityBlockWeekCombination $combination) use ($requestedActivity) {
					return (
						$combination->isBookableInFrontend(Carbon::now()) &&
						in_array(\TsActivities\Dto\ActivityBlockWeekCombination::STATUS_BOOKABLE, $combination->status) &&
						$combination->buildKey(true) === $requestedActivity['key']
					);
				});

			if (!$activityCombination) {
				throw new InvalidAssignmentException();
			}

			if (
				$activityCombination->activity->isFreeOfCharge() ||
				!($combination = \Ext_TS_Frontend_Combination::getInstance($this->school->getMeta('student_app_combination_activity_booking')))->exist()
			) {
				$this->activityService->assignBlockToInquiry($this->inquiry, $activityCombination);

				return response([
					'success' => true,
					'message' => $this->appInterface->t('The activity was booked successfully.')
				]);
			}

			$process = new \TsFrontend\Entity\InquiryFormProcess();
			$process->inquiry_id = $this->inquiry->id;
			$process->combination_id = $combination->id;
			$process->valid_until = Carbon::now()->addDay()->toDateString();
			$process->multiple = 0;
			$process->payload = json_encode(['services' => ['activities' => [['activity' => $activityCombination->activity->id, 'additional' => $activityCombination->buildKey()]]]]);
			$process->save();

			return response([
				'success' => true,
				'message' => $this->appInterface->t('Please use the payment link to complete purchase.'),
				'payment_link' => $process->buildUrl($combination->items_url)
			]);

		} catch (OverbookingException $ex) {

			$message = $this->appInterface->t('The activity could not be booked. Unfortunately there is no free space.');

		} catch (InvalidAssignmentException $ex) {
			// Fall sollte nicht auftreten, ansonsten ist die Anzeige in der App falsch
			$message = $this->appInterface->t('The activity could not be booked. The activity is not available.');
		}

		return response()->json([
			'success' => false,
			'message' => $message
		]);
	}

	public function cancel(Request $request) {

		if (!$request->has('activity')) {
			return response('Activity missing', 500);
		}

		$requestedActivity = $request->input('activity');

		// Gebuchte Aktivität
		$bookedBlock = $this->buildBookedCombinations()
			->first(function ($block) use ($requestedActivity) {
				/* @var $combination ActivityBlockWeekCombination */
				$combination = $block['combination'];
				return (
					$combination->block->frontend_release === Block::FRONTEND_BOOKABLE &&
					in_array(\TsActivities\Dto\ActivityBlockWeekCombination::STATUS_CANCELABLE, $combination->status) &&
					$combination->buildKey(true) === $requestedActivity['key']
				);
			});

		if (!$bookedBlock) {
			return response('Activity block missing', 500);
		}

		$this->activityService->removeBlockFromInquiry($this->inquiry, $bookedBlock['combination'], $bookedBlock['journey_activity']);

		return response([
			'success' => true,
			'message' => $this->appInterface->t('The activity was cancelled successfully.')
		]);

	}

	private function buildBookedCombinations():Collection {
		return $this->activityService->getBlocksOfInquiry($this->inquiry)
			->map(function (BlockTraveller $allocation) {
				$activity = $allocation->getActivity();
				$block = $allocation->getBlock();
				$combination = new ActivityBlockWeekCombination($block, $activity, Carbon::parse($allocation->week));
				$combination->status = [ActivityBlockWeekCombination::STATUS_BOOKED];
				if (
					(bool)$this->school->getMeta('student_app_show_activity_block_cancelable', false) &&
					// TODO Das wir evtl. nicht ganz richtig sein weil es nur die Woche ist und nicht die Termine berücksichtigt
					$combination->week > Carbon::now()
				) {
					$combination->status[] = ActivityBlockWeekCombination::STATUS_CANCELABLE;
				}
				$combination->dates = $allocation->generateBlockEvents();
				return ['journey_activity' => $allocation->getJourneyActivity(), 'combination' => $combination];
			});
	}

	public function getTranslations(AppInterface $appInterface): array  {
		return [
			'tab.activities.filter_button' => $appInterface->t('Filter'),
			'tab.activities.filter_error_dates' => $appInterface->t('Invalid timeframe'),
			'tab.activities.filter_from' => ucfirst($appInterface->t('From')),
			'tab.activities.filter_until' => lcfirst($appInterface->t('Until')),
			'tab.activities.filter_timeframe' => $appInterface->t('Timeframe'),
			'tab.activities.filter_activity' => $appInterface->t('Activity'),
			'tab.activities.filter_for_free' => $appInterface->t('Free of charge'),
			'tab.activities.filter_booked' => $appInterface->t('Booked'),
			'tab.activities.filter_not_booked' => $appInterface->t('Not booked'),
			'tab.activities.filter_reset_date_range' => $appInterface->t('Reset date range'),
			'tab.activities.my_activities' => $appInterface->t('My Activities'),
			'tab.activities.available_activities' => $appInterface->t('Available Activities'),
			'tab.activities.filtered' => $appInterface->t('Filtered'),
			'tab.activities.show_details' => $appInterface->t('Show details'),
			'tab.activities.book_now' => $appInterface->t('Book now'),
			'tab.activities.pay_now' => $appInterface->t('Pay now'),
			'tab.activities.cancel_now' => $appInterface->t('Cancel activity'),
			'tab.activities.for_free' => $appInterface->t('For free'),
			'tab.activities.order.close' => $appInterface->t('Close'),
			// Sobald für einen Status eine Übersetzung existiert wird dieser Status auch als Badge angezeigt
			'tab.activities.status.'.ActivityBlockWeekCombination::STATUS_BOOKED => $appInterface->t('Booked'),
			'tab.activities.status.'.ActivityBlockWeekCombination::STATUS_FULL => $appInterface->t('Full'),
			'tab.activities.status.'.ActivityBlockWeekCombination::STATUS_ALMOST_FULL => $appInterface->t('Almost full'),
			'tab.activities.status.'.ActivityBlockWeekCombination::STATUS_VISIBLE => $appInterface->t('Not bookable online'),
		];
	}
}

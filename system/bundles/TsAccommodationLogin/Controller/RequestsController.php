<?php

namespace TsAccommodationLogin\Controller;

use Illuminate\Database\Query\JoinClause;
use Core\Database\WDBasic\Builder;
use TsAccommodationLogin\Events\AccommodationRequestAccepted;
use TsAccommodationLogin\Events\AccommodationRequestRejected;
use TsAccommodationLogin\Handler\ExternalApp;

class RequestsController extends InterfaceController {
	
	public function overview() {
		
		$accommodation = \Ext_Thebing_Accommodation::getInstance($this->_oAccess->id);
		
		$pendingRequests = \TsAccommodation\Entity\Request::query()
			->select('ts_ar.*')
			->join('ts_accommodation_requests_recipients as ts_arr', 'ts_ar.id', '=', 'ts_arr.request_id')
			->leftJoin('ts_accommodation_requests_recipients as ts_arr_check', function (JoinClause $join) {
				$join->on('ts_arr_check.request_id', '=', 'ts_ar.id')
					->whereNotNull('ts_arr_check.accepted');
			})
			->leftJoin('kolumbus_accommodations_allocations as kaal_check', function (JoinClause $join) {
				$join->on('kaal_check.inquiry_accommodation_id', '=', 'ts_ar.inquiry_accommodation_id')
					->where('kaal_check.status', '=', 0);
			})
			->where('ts_arr.accommodation_provider_id', $accommodation->id)
			->where('ts_arr.accepted', null)
			->where('ts_arr.rejected', null)
			->where('ts_arr_check.id', null)
			->where('kaal_check.id', null)
			->orderBy('ts_ar.created', 'DESC')
			->get();
	
		$closedRequests = \TsAccommodation\Entity\Request::query()
			->select('ts_ar.*')
			->join('ts_accommodation_requests_recipients as ts_arr', 'ts_ar.id', '=', 'ts_arr.request_id')
			->leftJoin('ts_accommodation_requests_recipients as ts_arr_check', function (JoinClause $join) {
				$join->on('ts_arr_check.request_id', '=', 'ts_ar.id')
					->whereNotNull('ts_arr_check.accepted');
			})
			->leftJoin('kolumbus_accommodations_allocations as kaal_check', function (JoinClause $join) {
				$join->on('kaal_check.inquiry_accommodation_id', '=', 'ts_ar.inquiry_accommodation_id')
					->where('kaal_check.status', '=', 0);
			})
			->where('ts_arr.accommodation_provider_id', $accommodation->id)
			->where(function (Builder $query) {
				
				$query->whereNotNull('ts_arr.accepted')
				->orWhereNotNull('ts_arr.rejected')
				->orWhereNotNull('ts_arr_check.id')
				->orWhereNotNull('kaal_check.id');
				
			})
			->orderBy('ts_ar.created', 'DESC')
			->limit(10)
			->get();

		$app = new ExternalApp();
	
		$this->set('pendingRequests', $pendingRequests);
		$this->set('closedRequests', $closedRequests);
		$this->set('existingColumns', $app->getDefaultColumnValues());
		
	}
	
	public function requestAvailability($task, $key) {

		$this->set('key', $key);	
		
		$recipient = \TsAccommodation\Entity\Request\Recipient::getRepository()->findOneBy(['key'=>$key]);
		
		if($recipient instanceof \TsAccommodation\Entity\Request\Recipient) {
			
			$accommodationRequest = $recipient->getJoinedObject('request');
			$inquiryAccommodation = $accommodationRequest->getJoinedObject('inquiry_accommodation');
			$provider = $recipient->getJoinedObject('provider');

			if(
				$recipient->accepted !== null || 
				$recipient->rejected !== null
			) {
				$this->oSession->getFlashBag()->add('error', \L10N::t('This accommodation availability request has already been answered!'));
				$this->set('task', false);
				return;
			}
			
			if($task === 'accept') {
				
				$checkAccepted = \TsAccommodation\Entity\Request::query()
					->select('ts_ar.*')
					->join('ts_accommodation_requests_recipients as ts_arr', 'ts_ar.id', '=', 'ts_arr.request_id')
					->where('ts_ar.inquiry_accommodation_id', '=', $accommodationRequest->inquiry_accommodation_id)
					->where('ts_arr.accepted', '!=' , null)
					->get();
				
				if($checkAccepted->count() > 0) {
					$this->oSession->getFlashBag()->add('error', \L10N::t('This accommodation availability request has already been accepted by another accommodation provider!'));
					$this->set('task', false);
					return;
				}
			
				$availableBeds = $this->getAvailableBeds($inquiryAccommodation, $provider);

				$this->set('availableBeds', $availableBeds);
				
			}
			
			$this->set('info', $inquiryAccommodation->getInfo(false, $this->language));
			$this->set('task', $task);
			
		} else {
			$this->oSession->getFlashBag()->add('error', \L10N::t('This availabilty request is no longer valid!'));
		}
		
	}
	
	public function requestAvailabilityConfirm(\MVC_Request $request, $task, $key) {
		
		\DB::begin(__METHOD__);
		
		$recipient = \TsAccommodation\Entity\Request\Recipient::getRepository()->findOneBy(['key'=>$key]);
		$provider = $recipient->getJoinedObject('provider');
		
		if($recipient instanceof \TsAccommodation\Entity\Request\Recipient) {
			
			$roomIdBed = $request->get('room_id');
			list($roomId, $bedNumber) = explode('|', $roomIdBed);

			$accommodationRequest = $recipient->getJoinedObject('request');
			
			if($recipient->accepted !== null || $recipient->rejected !== null) {
				$this->oSession->getFlashBag()->add('error', \L10N::t('This accommodation availability request has already been answered!'));
				return;
			}
			
			$inquiryAccommodation = $accommodationRequest->getJoinedObject('inquiry_accommodation');

			$success = false;
			
			if($task === 'accept') {
				
				if($accommodationRequest->isAccepted()) {
					
					$this->oSession->getFlashBag()->add('error', \L10N::t('This accommodation availability request has already been accepted by another accommodation provider!'));
					return;
					
				} else {

					// Sicherheitsprüfung
					$room = \Ext_Thebing_Accommodation_Room::getInstance($roomId);
					$roomProvider = $room->getProvider();

					// Raum-ID manipuliert?
					if($roomProvider->id != $provider->id) {
						$this->oSession->getFlashBag()->add('error', \L10N::t('Invalid room!'));
						return;
					}
					
					// Existiert schon eine manuelle Zuweisung?
					$existingAllocation = \Ext_Thebing_Accommodation_Allocation::getRepository()->findOneBy(['inquiry_accommodation_id'=>(int)$inquiryAccommodation->id, 'status'=>0]);
					if($existingAllocation) {
						$this->oSession->getFlashBag()->add('error', \L10N::t('The student has already been assigned in the meantime!'));
						return;
					}
					
					// Raum immernoch frei?
					$availableBeds = $this->getAvailableBeds($inquiryAccommodation, $provider);
					if(!isset($availableBeds[$roomId.'|'.$bedNumber])) {
						$this->oSession->getFlashBag()->add('error', \L10N::t('Another student has been assigned to this bed in the meantime. Please select another bed!'));
						$this->redirect('TsAccommodationLogin.accommodation_request_availability', ['task'=>$task, 'key'=>$key], false);
						return;
					}
					
					$recipient->accepted = date('Y-m-d H:i:s');
					$recipient->save();

					// Zuweisung
					$allocation = new \Ext_Thebing_Accommodation_Allocation();
					$allocation->room_id = (int)$roomId;
					$allocation->bed = (int)$bedNumber;
					$allocation->from = $inquiryAccommodation->from.' 00:00:00';
					$allocation->until = $inquiryAccommodation->until.' 00:00:00';
					$allocation->inquiry_accommodation_id = (int)$inquiryAccommodation->id;
					$allocation->save();
					
					$success = true;

					AccommodationRequestAccepted::dispatch($inquiryAccommodation);
				}
				
			} else {
				
				$recipient->rejected = date('Y-m-d H:i:s');
				$recipient->save();
				
				$success = true;

				AccommodationRequestRejected::dispatch($inquiryAccommodation);
			}
			
			$this->set('info', $inquiryAccommodation->getInfo(false, $this->language));
			$this->set('success', $success);
			
		} else {
			$this->oSession->getFlashBag()->add('error', \L10N::t('This availabilty request is no longer valid!'));
		}
		
		\DB::commit(__METHOD__);
		
	}

	protected function getAvailableBeds(\Ext_TS_Inquiry_Journey_Accommodation $inquiryAccommodation, \Ext_Thebing_Accommodation $provider) {
		
		$possibleProviders = $inquiryAccommodation->getPossibleProviders(true, true);

		$availableBeds = [];
		foreach($possibleProviders as $possibleProvider) {

			if($possibleProvider['id'] != $provider->id) {
				continue;
			}

			foreach($possibleProvider['rooms'] as $room) {
				if($room['isAssignable']) {
					$availableBeds[$room['id'].'|'.$room['bed_number']] = $room['name'];
				}
			}
		}
		
		return $availableBeds;
	}
	
}

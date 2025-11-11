<?php

namespace TsIvvy;

use TsIvvy\Api\Model;
use TsIvvy\Api\Operations;
use TsIvvy\DTO\BlockoutSpace;
use TsIvvy\Handler\ExternalApp;
use TsIvvy\Api\Request;
use TsIvvy\Exceptions;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

/**
 * https://developer.ivvy.com
 */
class Api {

	const API_VERSION = '1.0';

	const API_CONNECTION_TIMEOUT = 5;

	const API_REQUEST_TIMEOUT = 120;

	const API_ENDPOINTS = [
		'AU' => 'api.ap-southeast-2.ivvy.com',
    	'UK' => 'api.eu-west-2.ivvy.com',
    	'US' => 'api.us-west-2.ivvy.com'
	];

	/**
	 * @deprecated
	 */
	const ENTITY_CODE_PREFIX = 'FIDELO';

	const CACHE_GROUP = 'ts_ivvy_cache';

	private $host;

	private $key;

	private $secret;

	public function __construct(string $region, string $key, string $secret) {
		$this->host = self::getHost($region);
		$this->key = $key;
		$this->secret = $secret;
	}

	public function ping(): void {
		$this->request(new Operations\Ping());
	}

	/**
	 * @return Collection|Model\User[]
	 */
	public function getAccountUserList(): Collection {

		return \WDCache::remember(__METHOD__, 60*60*24, function () {
			return $this->requestWithPagination(new Operations\GetAccountUserList(), 100)
				->map(function ($user) {
					return new Model\User($this, $user, true);
				});
		}, false, self::CACHE_GROUP);

	}

	/**
	 * @return Collection|Model\Venue[]
	 */
	public function getVenueList(): Collection {

		return \WDCache::remember(__METHOD__, 60*60*24, function () {
			$response = $this->request(new Operations\GetVenueList());
			return collect($response->get('results'))
				->map(function ($venue) {
					return new Model\Venue($this, $venue, false);
				});
		}, false, self::CACHE_GROUP);

	}

	/**
	 * @param $id
	 * @return Model\Venue
	 */
	public function getVenue($id): Model\Venue {

		$key = __METHOD__.'_'.$id;

		return \WDCache::remember($key, 60*60*24, function () use ($id) {
			$response = $this->request(new Operations\GetVenue($id));
			return new Model\Venue($this, $response, true);
		}, false, self::CACHE_GROUP);

	}

	/**
	 * @return Collection|Model\Room[]
	 */
	public function getRoomList(): Collection {

		return \WDCache::remember(__METHOD__, 60*60*24, function () {
			$venues = $this->getVenueList();

			$roomList = new Collection();
			foreach($venues as $venue) {
				/* @var Model\Venue $venue */
				$roomList = $roomList->merge($venue->getRooms());
			}

			return $roomList;

		}, false, self::CACHE_GROUP);

	}

	/**
	 * @param $id
	 * @return Model\Room|null
	 */
	public function getRoom($id): ?Model\Room {

		return $this->getRoomList()->first(function(Model\Room $room) use($id) {
			return (strpos($room->getId(), '_'.$id) !== false);
		});

	}

	/**
	 * @param \DateTime|null $modifiedAfter
	 * @param \DateTime|null $modifiedBefore
	 * @return Collection|Model\Booking[]
	 */
	public function getBookingList(\DateTime $modifiedAfter = null, \DateTime $modifiedBefore = null): Collection {

		$venues = $this->getVenueList();

		$bookingList = new Collection();

		foreach($venues as $venue) {

			$operation = new Operations\GetBookingList($venue->getId());
			if($modifiedAfter) $operation->setModifiedAfter($modifiedAfter);
			if($modifiedBefore) $operation->setModifiedBefore($modifiedBefore);

			$bookings = $this->requestWithPagination($operation, 100);

			foreach($bookings as $booking) {
				$bookingList->push(new Model\Booking($this, $booking, false));
			}
		}

		return $bookingList;
	}

	/**
	 * @param $id
	 * @return Model\Booking
	 */
	public function getBooking($id): Model\Booking {
		$response = $this->request(new Operations\GetBooking($id));
		return new Model\Booking($this, $response, true);
	}

	/**
	 * @param BlockoutSpace $blockoutSpace
	 * @return Collection
	 */
	public function sendBlockoutSpace(BlockoutSpace $blockoutSpace): Collection {
		$operation = new Api\Operations\AddOrUpdateSpaceBlockout($blockoutSpace);
		return $this->request($operation);
	}

	/**
	 * @param BlockoutSpace $blockoutSpace
	 * @return Collection
	 */
	public function removeBlockoutSpace(BlockoutSpace $blockoutSpace): Collection {
		$operation = new Api\Operations\RemoveBlockoutSpace($blockoutSpace);
		return $this->request($operation);
	}

	/**
	 * @param Operations\AbstractOperation $operation
	 * @param bool $logging
	 * @return Collection
	 */
	public function request(Operations\AbstractOperation $operation, bool $logging = false): Collection {

		$request = new Request('POST', $this->host, $operation->getUri(), [
			'Content-Type' => 'application/json'
		]);

		if ($logging) {
			self::getLogger()->info('Request operation', ['operation' => get_class($operation)]);
			$operation->enableLogging();
		}

		// Default Daten setzen
		if (method_exists($operation, 'manipulateRequest')) {
			$operation->manipulateRequest($request);
		}

		// Request signieren
		$this->signRequest($request);

		$curl = curl_init($request->getUrl());

		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $request->getMethod());
		curl_setopt($curl, CURLOPT_HTTPHEADER, $request->getCurlHeaders());
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getBody()->toJson());
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::API_CONNECTION_TIMEOUT);
		curl_setopt($curl, CURLOPT_TIMEOUT, self::API_REQUEST_TIMEOUT);

		$response = json_decode(curl_exec($curl), true);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		curl_close($curl);

		if ($status !== 200) {
			switch ($status) {
				case 401:
					self::getLogger()->error('Authentification failed', ['url' => $request->getUrl(), 'request' => $request->getBody()->toArray(), 'response' => $response]);
					throw new Exceptions\AuthenticateException($response['message'], $status);
				default:
					self::getLogger()->error('Request failed', ['status' => $status, 'url' => $request->getUrl(), 'request' => $request->getBody()->toArray(), 'response' => $response]);
					throw (new Exceptions\FailedException($response['message'], $status))->response($response);
			}
		}

		$response = new Collection($response);

		if (method_exists($operation, 'handleResponse')) {
			$operation->handleResponse($response);
		}

		if ($logging) {
			self::getLogger()->info('Response', ['data' => $response->toArray()]);
		}

		return $response;
	}

	public function requestWithPagination(Operations\AbstractOperation $operation, int $perPage): Collection {

		$count = 0;
		$startAt = 0;

		$operation->setPerPage($perPage);

		return $this->paginate($operation, $startAt, $count);
	}

	private function paginate(Operations\AbstractOperation $operation, int &$startAt, &$count): Collection {

		$operation->setStartAt($startAt);

		$count++;

		$response = $this->request($operation);

		$meta = $response->get('meta', []);

		if(empty($meta)) {
			throw new \RuntimeException(sprintf('Cannot paginate "%s" when response doesn\'t contain meta data!', get_class($operation)));
		}

		$collection = new Collection($response->get('results', []));

		$startAt += $meta['count'];

		if($startAt < $meta['totalResults']) {
			// Sicherheitshalber noch ein count einbauen um eine Endlosschleife zu verhindern
			if($count > 10) {
				throw new \RuntimeException('Maximum number of pagination requests reached!');
			}

			$collection = $collection->merge($this->paginate($operation, $startAt, $count));
		}

		return $collection;
	}

	/**
	 * Request signieren
	 *
	 * https://developer.ivvy.com/getting-started/creating-the-request/signing-the-request
	 *
	 * @param Request $request
	 */
	private function signRequest(Request $request) {

		$body = $request->getBody()->toJson();

		$contentMD5 = md5($body);

		$date = (!$request->hasHeader('IVVY-Date'))
			? $request->getHeader('Date')
			: "";

		$ivvyHeaders = $request->getIvvyHeaders()
			->map(function($value, $key) {
				return str_replace(['-', '_'], '', $key).'='.$value;
			})
			->implode('&');

		$signatureData = [
			$request->getMethod(),
			$contentMD5,
			$request->getHeader('Content-Type'),
			$date,
			$request->getUri(),
			self::API_VERSION,
			$ivvyHeaders
		];

		$initialStringToSign = implode('', $signatureData);
		$stringToSign = strtolower($initialStringToSign);

		$signature = hash_hmac("sha1", $stringToSign, $this->secret);

		$request->header('Content-MD5', $contentMD5);
		$request->header('X-Api-Version', self::API_VERSION);
		$request->header('X-Api-Authorization', sprintf('IWS %s:%s', $this->key, $signature));

	}

	public static function default(): Api {

		$region = \System::d(ExternalApp::CONFIG_REGION, "UK");
		$key = \System::d(ExternalApp::CONFIG_ACCESS_KEY, "");
		$secret = \System::d(ExternalApp::CONFIG_ACCESS_SECRET, "");

		// Keiner der Werte darf leer sein
		if (in_array("", [$region, $key, $secret])) {
			throw new \RuntimeException('Missing configuration data for ivvy api!');
		}

		return new self($region, $key, $secret);
	}

	public static function getHost(string $region): string {

		$region = strtoupper($region);

		if (!isset(self::API_ENDPOINTS[$region])) {
			throw new \InvalidArgumentException(sprintf('Unknown region "%s" for ivvy!', $region));
		}

		return 'https://'.self::API_ENDPOINTS[$region];
	}

	public static function buildBlockoutMetaKey(int $day, int $roomId): string {
		return sprintf('ivvy_blockout_id_%d_%d', $day, $roomId);
	}

	public static function buildBlockoutVenueMetaKey(int $day, int $roomId): string {
		return sprintf('ivvy_blockout_venue_id_%d_%d', $day, $roomId);
	}

	public static function clearCache() {
		\WDCache::deleteGroup(self::CACHE_GROUP);
	}

	public static function getLogger() {
		return \Log::getLogger('api', 'iVvy');
	}

}

<?php

namespace FideloSoftware\Spam;

use FideloSoftware\Spam\Exceptions\BannedException;
use FideloSoftware\Spam\Exceptions\SpamDetectionException;
use FideloSoftware\Spam\Strategies\AbstractStrategy;
use FideloSoftware\Spam\Contracts\Form;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

class SpamShield {

	const MAX_ATTEMPTS = 5;

	/**
	 * @var Collection
	 */
	private $strategies;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var LoggerInterface|null
	 */
	private $logger;

	/**
	 * Spamshield
	 *
	 * @param AbstractStrategy[] $strategies
	 * @param Store $store
	 * @param LoggerInterface|null $logger
	 */
	public function __construct(array $strategies, Store $store, LoggerInterface $logger = null) {
		$this->strategies = new Collection($strategies);
		$this->store = $store;
		$this->logger = $logger;
	}

	/**
	 * Check if the user request is globally locked (because of to many attempts)
	 *
	 * @param Store $store
	 * @param Request $request
	 * @return bool
	 */
	public static function isBanned(Store $store, Request $request): bool {

		if (null === $cacheKey = self::buildStoreKey($request)) {
			return false;
		}

		$attempts = $store->get($cacheKey);

		if ($attempts !== null && (int)$attempts > self::MAX_ATTEMPTS) {
			return true;
		}

		return false;
	}

	/**
	 * Initialize all strategies when form is loaded
	 *
	 * @param Form $form
	 * @param Request $request
	 * @return void
	 */
	public function onload(Form $form, Request $request): void {

		$this->strategies->each(function(AbstractStrategy $strategy) use($form, $request) {
			$strategy->onload($form, $request);
		});

	}

	/**
	 * Include all necessary html parts from strategies
	 *
	 * @param Form $form
	 * @return string
	 */
	public function html(Form $form): string {

		$html = $this->strategies
			->map(function(AbstractStrategy $strategy) use($form) {
				return $strategy->html($form);
			})
			->implode("\r\n");

		if (strlen($html) > 0) {

			// Generate a random css class for styling
			$class = '__hp-'.Str::random(5);

			// Hide all html parts via CSS by avoiding display: none;
			$finalHtml = '
				<style>
					.%s {
						opacity: 0;
						position: absolute;
						top: 0;
						left: 0;
						height: 0;
						width: 0;
						z-index: -1;
					}
				</style>
				<div class="%s">%s</div>
			';

			return sprintf($finalHtml, $class, $class, $html);
		}

		return '';
	}

	/**
	 * Execute all strategies on form submit
	 *
	 * @param Form $form
	 * @param Request $request
	 * @return void
	 * @throws BannedException|SpamDetectionException
	 */
	public function detect(Form $form, Request $request) {

		if (self::isBanned($this->store, $request)) {
			$this->logAttempt($request);
			throw new BannedException('Banned!');
		}

		// Run through strategies to check for spam
		$detected = $this->strategies->first(function(AbstractStrategy $strategy) use($form, $request) {
			return $strategy->detect($form, $request);
		});

		// Clean up after spam detection process
		$this->strategies->each(function(AbstractStrategy $strategy) use($form) {
			$strategy->cleanUp($form);
		});

		if ($detected !== null) {
			$this->logAttempt($request, $detected);
			throw (new SpamDetectionException('Spam detected!'))->strategy($detected);
		}

	}

	/**
	 * Log detected spam attempt
	 *
	 * @param Request $request
	 * @param AbstractStrategy|null $strategy
	 * @return void
	 */
	private function logAttempt(Request $request, AbstractStrategy $strategy = null): void {

		if (null !== $cacheKey = self::buildStoreKey($request)) {
			$attempts = (int)$this->store->get($cacheKey);
			$this->store->put($cacheKey, ($attempts + 1), 60*60*24);
		}

		if ($this->logger !== null) {
			if($strategy !== null) {
				$this->logger->info('Spam detected by strategy', ['strategy' => get_class($strategy), 'info' => $strategy->getInfo()]);
			} else {
				$this->logger->info('Banned', []);
			}
		}

	}

	/**
	 * Returns logger instance if available
	 *
	 * @return LoggerInterface|null
	 */
	public function getLogger(): ?LoggerInterface {
		return $this->logger;
	}

	/**
	 * Generates a store key for the given user request
	 *
	 * @param Request $request
	 * @return string|null
	 */
	private static function buildStoreKey(Request $request): ?string {

		// Recognize user by ip-address
		if ($request->exists('X-Originating-IP')) {
			$ip = $request->input('X-Originating-IP');
		} else {
			$ip = $request->ip();
		}

		if (empty($ip)) {
			return null;
		}

		return 'spamshield_attempts_'.$ip;
	}

}

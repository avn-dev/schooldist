<?php

namespace FideloSoftware\Spam\Strategies;

use FideloSoftware\Spam\Contracts\Form;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Http\Request;

/**
 * Timestamp
 *
 * Ensures that a specific time has passed between loading and submitting the form. Bots are faster than normal users.
 */
class TimestampStrategy extends AbstractStrategy {

	private $store;

	private $minSeconds;

	public function __construct(Store $store, int $minSeconds) {
		$this->store = $store;
		$this->minSeconds = $minSeconds;
	}

	public function onload(Form $form, Request $request): void {
		$this->store->put($this->buildStoreKey($form), time(), 60*20);
	}

	public function cleanUp(Form $form): void {
		$this->store->forget($this->buildStoreKey($form));
	}

	public function detect(Form $form, Request $request): bool {

		$startedAt = $this->store->get($this->buildStoreKey($form));

		if($startedAt !== null) {
			// Check if the defined seconds has passed between form initialization and submit
			$diff = time() - $startedAt;
			if($diff < $this->minSeconds) {
				$this->info(['seconds' => $diff]);
				return true;
			}
		}

		return false;
	}

	/**
	 * Build store key for form instance
	 *
	 * @param Form $form
	 * @return string
	 */
	private function buildStoreKey(Form $form): string {
		return 'spamshield_form_timestamp_'.$form->getUid();
	}

}

<?php

namespace TsRegistrationForm\Handler;

use Core\Service\Cache\LaravelStore;
use FideloSoftware\Spam\Contracts\Form;
use FideloSoftware\Spam\Strategies;
use Illuminate\Http\Request;
use TsRegistrationForm\Generator\CombinationGenerator;

class SpamShield implements Form
{
	private \FideloSoftware\Spam\SpamShield $spamShield;

	public function __construct(private readonly CombinationGenerator $combination, private readonly Request $request)
	{
		$store = new LaravelStore();
		$logger = \Log::getLogger('frontend', 'spam');

		// TODO Wenn man direkt den Submit-Request abschickt, wird alles komplett übersprungen
		$this->spamShield = new \FideloSoftware\Spam\SpamShield([
			new Strategies\HoneypotStrategy(3),
			new Strategies\TimestampStrategy($store, 2),
			new Strategies\LinkStrategy(1, ['*']),
			// TODO Hiermit wäre weder John noch Smith in einem der beiden Felder noch möglich
			// 	Ein verschachteltes Array (kombinierte Werte) funktioniert durch einen Bug nicht
//			new Strategies\ValueBlacklistStrategy([
//				\Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_FIRSTNAME => 'John',
//				\Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_LASTNAME => 'Smith']
//			),
		], $store, $logger);
	}

	public function init(): void
	{
		$this->spamShield->onload($this, $this->request);
	}

	public function generate(): string
	{
		return $this->spamShield->html($this);
	}

	public function detect(): void
	{
		// HoneypotStrategy verwendet Request, aber alle anderen getFieldValues() (spezielle TA Form-Funktionsweise)
		$request = new Request($this->request->input('fields'));
		$this->spamShield->detect($this, $request);
	}

	public function getUid(): string
	{
		return $this->combination->getForm()->id . '_' . $this->combination->getToken();
	}

	public function getFieldValues(): array
	{
		return $this->request->input('fields');
	}
}
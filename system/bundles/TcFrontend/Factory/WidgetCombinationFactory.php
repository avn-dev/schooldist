<?php

namespace TcFrontend\Factory;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use TcFrontend\Interfaces\WidgetCombination;

class WidgetCombinationFactory {

	private $combination;

	public function __construct(\Ext_TC_Frontend_Combination $combination) {
		$this->combination = $combination;
	}

	/**
	 * @return WidgetCombination|\Ext_TC_Frontend_Combination_Abstract
	 */
	public function create(): WidgetCombination {

		$generator = $this->combination->getObjectForUsage();

		if (
			!$generator instanceof \Ext_TC_Frontend_Combination_Abstract ||
			!$generator instanceof WidgetCombination
		) {
			throw new \InvalidArgumentException(sprintf('Combination %s is not of type WidgetCombination', get_class($generator)));
		}

		return $generator;

	}

	public function initFromRequest(WidgetCombination $generator, Request $request) {

		// Nach Referrer-Check anfragende IP als trushed Proxy setzen, damit ->ip() usw. korrekt funktionieren
		$trustedHeaders = Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO;
		$request->setTrustedProxies([$request->server->get('REMOTE_ADDR')], $trustedHeaders);

		$generator->initCombination($request, $request->get('l'));

	}

	/**
	 * @param Request $request
	 * @return WidgetCombination|\Ext_TC_Frontend_Combination_Abstract
	 * @throws ModelNotFoundException
	 */
	public static function createFromRequest(Request $request): WidgetCombination {

		$combination = \Ext_TC_Frontend_Combination::getByKey($request->get('c'));

		if (!$combination instanceof \Ext_TC_Frontend_Combination) {
			throw new ModelNotFoundException('Combination not found');
		}

		$self = new self($combination);

		$generator = $self->create();

		$self->initFromRequest($generator, $request);

		return $generator;

	}

}
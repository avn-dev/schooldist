<?php

namespace TcFrontend\Interfaces;

interface WidgetCombination {

	/**
	 * @return \TcFrontend\Dto\WidgetPath[]
	 */
	public function getWidgetPaths(): array;

	/**
	 * @return \TcFrontend\Dto\WidgetPath[]
	 */
	public function getWidgetScripts(): array;

	/**
	 * @return \TcFrontend\Dto\WidgetPath[]
	 */
	public function getWidgetStyles(): array;

	/**
	 * Alle JS-Daten fürs Widget
	 *
	 * @param bool $checkCacheIgnore Cache nicht pauschal abschalten, sondern z.B. nur beim initialen Request
	 * @return array
	 */
	public function getWidgetData($checkCacheIgnore = false): array;

	/**
	 * Parameter, die bei Verwendung des iFrames weitergegeben werden (Whitelist)
	 *
	 * @return array
	 */
	public function getWidgetPassParams(): array;

	/**
	 * Widget in einem iframe einbinden (iframe wird auch vom Widget erzeugt)
	 *
	 * @return bool
	 */
	public function isUsingIframe(): bool;

	/**
	 * CSS-Styles-Bundle verwenden?
	 *
	 * @return bool
	 */
	public function isUsingBundle(): bool;

}

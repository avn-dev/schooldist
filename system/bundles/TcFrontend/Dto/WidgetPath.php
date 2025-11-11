<?php

namespace TcFrontend\Dto;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DTO, um einen Pfad als lokalen Pfad und Pfad für proxy.fidelo.com verwenden zu können
 *
 * Diese Einstellungen müssen auf dem Proxy auch nochmal existieren!
 *
 * prefix: Lokaler Pfad ohne konkrete Ressource
 * suffix: Konkrete Ressource (z.B. Dateiname), ggf. mit Pfad (wenn bei beiden Pfaden gleich)
 * app: App auf Fidelo Proxy. Kann auch einen Doppelpunkt enthalten, womit eine Sub-App (PATH1) für den externen Pfad generiert wird.
 */
class WidgetPath implements \JsonSerializable
{
	/**
	 * @var string
	 */
	public string $prefix = '';

	/**
	 * @var string
	 */
	public string $suffix = '';

	/**
	 * @var string
	 */
	public string $app = '';

	/**
	 * @var array
	 */
	public array $additional = [];

	/**
	 * @param string $prefix
	 * @param string $suffix
	 * @param string $app
	 */
	public function __construct(string $prefix, string $suffix, string $app)
	{
		$this->prefix = $prefix;
		$this->suffix = $suffix;
		$this->app = $app;
	}

	/**
	 * @param array $additional
	 * @return $this
	 */
	public function setAdditional(array $additional): self
	{
		$this->additional = $additional;
		return $this;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array
	{
		return get_object_vars($this);
	}

	/**
	 * @param array $data
	 * @return self
	 */
	public static function fromArray(array $data): self
	{
		$self = new self($data['prefix'], $data['suffix'], $data['app']);
		$self->setAdditional($data['additional']);
		return $self;
	}

	public function buildUrl(string $prepend = null): string
	{
		if ($prepend !== null) {
			// Fidelo Proxy: Übergebenen Pfad aus X-Fidelo-Widget-Path anhängen
			// Außerdem Proxy-App injecten, da Widget an dieser Stelle immer app/widget ist
			[$app, $suffix] = explode(':', $this->app, 2);
			$prepend = str_replace('app/widget', 'app/'.$app, $prepend);
			$prepared = 'proxy://'.$prepend.'/'.($suffix ? $suffix.'/' : '').$this->suffix;
		} else {
			// Direkte Einbindung: Lokaler Pfad
			$prepared = 'proxy://'.rtrim($this->prefix, '/').'/'.$this->suffix;
		}

		if (!empty($this->additional)) {
			$prepared .= !str_contains($prepared, '?') ? '?' : '&';
			$prepared .= http_build_query($this->additional);
		}

		return $prepared;
	}

	/**
	 * Header von Fidelo-Proxy ermitteln für buildUrl $prepend
	 */
	public static function buildPrependPath(Request $request): ?string
	{
		// app/widget/1.0/:CUSTOMER
		$path = $request->header('X-Fidelo-Widget-Path');

		if (
			Str::startsWith($path, 'app/') &&
			!Str::contains($path, '//') &&
			!Str::contains($path, '..')
		) {
			return $path;
		}

		return null;
	}
}
<?php

namespace TsMews\Api;

/**
 * https://mews-systems.gitbook.io/connector-api/guidelines
 */
class Request
{
    private $url;

    private $method;

    private $headers = [
        'Content-Type' => 'application/json'
    ];

    private $body = [];

    public function __construct(string $method, string $url, array $headers = []) {
        $this->method = $method;
        $this->url = $url;
        $this->headers = array_merge($this->headers, $headers);
    }

    public function delete(string $key): void {
        unset($this->body[$key]);
    }

    public function set(string $key, $value): self {
        $this->body[$key] = $this->format($value);
        return $this;
    }

    public function header(string $key, string $value): self {
        $this->headers[$key] = $value;
        return $this;
    }

    public function getUrl(): string {
        return $this->url;
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function getHeaders(): array {
        return $this->headers;
    }

	public function getCurlHeaders(): array {
		return collect($this->headers)
			->map(function($value, $key) {
				return sprintf("%s: %s", $key, $value);
			})
			->values()
			->toArray();
	}

    public function toArray(): array {
        return $this->body;
    }

    private function format($value) {

        if (is_array($value)) {
            foreach ($value as $index => $subvalue) {
                $value[$index] = $this->format($subvalue);
            }
        } else {
            if ($value instanceof \DateTime) {
                $value = $value->setTimezone(new \DateTimeZone('UTC'))->format(DATE_ISO8601);
            }
        }

        return $value;
    }
}

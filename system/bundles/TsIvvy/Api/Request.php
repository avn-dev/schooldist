<?php

namespace TsIvvy\Api;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Request {

	private $method;

    private $host;

    private $uri;

    private $headers;

    private $body;

    public function __construct(string $method, string $host, string $uri, array $headers = []) {
        $this->method = $method;
        $this->host = rtrim($host, '/');
        $this->uri = Str::start($uri, '/');
        $this->headers = new Collection($headers);
        $this->body = new Collection();

		$date = (new \DateTime())->setTimezone(new \DateTimeZone('UTC'));
		//$this->header('Date', $date->format('D, j M Y H:i:s e'));
		$this->header('IVVY-Date', $date->format('Y-m-d H:i:s'));
	}

    public function delete(string $key): void {
        $this->body->forget($key);
    }

    public function set(string $key, $value): self {
        $this->body->put($key, $this->format($value));
        return $this;
    }

    public function header(string $key, string $value): self {
        $this->headers->put($key,  $value);
        return $this;
    }

	public function getUri(): string {
		return $this->uri;
	}

    public function getUrl(): string {
        return $this->host.$this->uri;
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function hasHeader(string $key): bool {
    	return $this->headers->has($key);
	}

	public function getHeader(string $key) {
		return $this->headers->get($key);
	}

	public function getIvvyHeaders(): Collection {
		return $this->headers
			->filter(function($value, $key) {
				return (substr($key, 0, 5) === 'IVVY-');
			})
			->sort();
	}

    public function getHeaders(): Collection {
        return $this->headers;
    }

	public function getCurlHeaders(): array {
		return $this->headers
			->map(function($value, $key) {
				return sprintf("%s: %s", $key, $value);
			})
			->values()
			->toArray();
	}

    public function getBody(): Collection {
		return $this->body;
    }

    public function format($value) {

        if (is_array($value)) {
            foreach($value as $index => $subvalue) {
                $value[$index] = $this->format($subvalue);
            }
        } else if ($value instanceof \DateTime) {
        	$value->setTimezone(new \DateTimeZone('UTC'));
        	$value = $value->format('Y-m-d H:i:s');
		}

        return $value;
    }

	public function filter(string $field,  $value) {

		$filters = $this->body->get('filter', []);

		$filters[$field] = $this->format($value);

		$this->body->put('filter', $filters);
	}

    public function customField(int $id, $value) {

    	$customFields = $this->body->get('customFields', []);

    	$customFields[] = [
			'fieldId' => $id,
			'fieldValue' => $this->format($value),
		];

    	$this->body->put('customFields', $customFields);
	}
}

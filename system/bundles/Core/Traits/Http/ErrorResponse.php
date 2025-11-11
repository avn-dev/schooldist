<?php

namespace Core\Traits\Http;

use Illuminate\Http\Response;

trait ErrorResponse {

	protected function createErrorResponse(string $errorCode, int $statusCode = Response::HTTP_BAD_REQUEST, array $additionalData = [], array $messageBag = []): Response {
		return response(
			$this->buildErrorBlock($errorCode, $additionalData, $messageBag),
			$statusCode
		);
	}

	protected function buildErrorBlock(string $errorCode, array $additionalData = [], array $messageBag = []): array {

		[$errorCode, $message] = $this->provideReadableMessage($errorCode, $messageBag);

		$data = [];
		if ($message) {
			$data['message'] = $message;
		}

		$data['code'] = $errorCode;

		if(!empty($additionalData)) {
			$data['additional'] = $additionalData;
		}

		return $data;
	}

	private function provideReadableMessage(string $errorCode, array $errorMessages = []): array {

		if (empty($errorMessages) && !method_exists($this, 'getErrorMessages')) {
			return [$errorCode];
		}

		if (empty($errorMessages)) {
			$errorMessages = (array)app()->call([$this, 'getErrorMessages']);
		}

		return [$errorCode, $errorMessages[$errorCode] ?? null];
	}

}

<?php

namespace TsStudentApp\Http\Middleware;

use TsStudentApp\Messenger\Thread\AbstractThread;
use TsStudentApp\Pages\AbstractPage;
use TsStudentApp\Pages\Messenger\Thread as ThreadPage;
use TsStudentApp\Service\MessengerService;

class MessengerThread {

	public function __construct(private readonly AbstractPage $page) {}

	public function handle(\MVC_Request $request, \Closure $next) {

		/**
		 * TODO: Es ist nicht so schön das die Middleware für alle Seiten ausgeführt wird
		 */
		if($this->page instanceof ThreadPage) {

			if(!$this->validateRequest($request)) {
				return response('Bad request', 400);
			}

			$messenger = app()->make(MessengerService::class);
			/* @var MessengerService $messenger */
			$thread = $messenger->getThreadByToken((string)$request->header('X-Messenger-Thread'));

			if(
				$thread instanceof AbstractThread &&
				$thread->canCommunicate()
			) {
				app()->instance(AbstractThread::class, $thread);
				return $next($request);
			}

			return response('Forbidden', 403);
		}

		return $next($request);
	}

	/**
	 * Prüfen ob der Request alle nötigen Header enthält
	 *
	 * @param \MVC_Request $request
	 * @return bool
	 */
	private function validateRequest(\MVC_Request $request): bool {

		if($request->headers->has('X-Messenger-Thread')) {
			return true;
		}

		return false;
	}

}

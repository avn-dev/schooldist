<?php

namespace TsStudentApp\Http\Middleware;

use Closure;
use TsStudentApp\AppInterface;
use TsStudentApp\Pages\AbstractPage;

class Page {

	public function __construct(private AppInterface $appInterface) {}

	public function handle(\MVC_Request $request, Closure $next) {

		$pageKey = (string)$request->attributes->get('page');

		if(null !== $page = $this->appInterface->getPage($pageKey)) {
			app()->singleton(AbstractPage::class, $page['data']);
			return $next($request);
		}

		return response('Page not found', 404);
	}

}

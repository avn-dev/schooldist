<?php

namespace TcFrontend\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * PHP parst multipart/form-data nur bei POST-Requests
 *
 * https://bugs.php.net/bug.php?id=55815
 * https://github.com/laravel/framework/issues/13457
 * https://github.com/symfony/symfony/issues/9226
 */
class MultipartParser {

	/**
	 * Muss vor jeglicher Aktion auf dem Request-Objekt (has() usw.) ausgeführt werden!
	 * Der Grund ist, dass Laravel nur ein einziges Mal allFiles()/file() befüllt und das mit has() usw. passiert.
	 * https://github.com/laravel/framework/issues/27386
	 *
	 * @return int
	 */
	public function priority() {
		return 1;
	}

	public function handle(Request $request, \Closure $next) {

		if (
			$request->method() !== 'POST' &&
			Str::startsWith($request->header('Content-Type'), 'multipart/')
		) {
			$multipart = \Riverline\MultiPartParser\Converters\HttpFoundation::convert($request);
			foreach ($multipart->getParts() as $part) {
				if ($part->isFile()) {
					$name = tempnam(sys_get_temp_dir(), 'mp');
					file_put_contents($name, $part->getBody());
					$file = new UploadedFile($name, $part->getFileName(), $part->getMimeType(), null, true);
					$request->files->set($part->getName(), $file);
				} else {
					$request->request->set($part->getName(), $part->getBody());
				}
			}
		}

		return $next($request);

	}

}

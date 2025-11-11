<?php

namespace TsRegistrationForm\Controller;

use Core\Controller\Vendor\ResourceAbstractController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use TsRegistrationForm\Generator\CombinationGenerator;

class ResourceController extends ResourceAbstractController {

	protected $_sAccessRight = null;

	protected $_sInterface = 'frontend';

	protected $sPath = 'system/bundles/TsRegistrationForm/Resources/assets/';

	/**
	 * Dateien (Download-Block) müssen leider hierdurch geschleift werden, da der Pfad die Schule enthält und dieser
	 * Kontext nur in der API über die Kombination existiert, nicht aber über einen GET-Request. Lokal mag das noch
	 * funktionieren, aber mit proxy.fidelo.com ist dann Feierabend.
	 *
	 * @param Request $request
	 * @param CombinationGenerator $combination
	 * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
	 */
	public function file(Request $request, CombinationGenerator $combination) {

		$name = basename($request->input('name'));
		$files = $combination->getForm()->getDownloadFileList($combination->getSchool(), $combination->getLanguage()->getLanguage());

		$path = collect($files)->first(function ($path) use ($name) {
			return Str::endsWith($path, $name);
		});

		if ($path === null) {
			return response('', 404);
		}

		try {
			$path = storage_path(str_replace('/storage', '', $path));
			return response()->download($path);
		} catch (FileNotFoundException $e) {
			return response('', 404);
		}

	}

}
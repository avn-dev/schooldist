<?php

namespace TsFrontend\Controller;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PreviewController extends Controller
{
	public function preview(Request $request)
	{
		if (!$request->filled('key')) {
			abort(400, 'No key');
		}

		$url = '/assets/tc-frontend/js/widget.js?c='.$request->input('key');
		if ($request->boolean('proxy')) {
			$url = sprintf('%s/app/widget/1.0/%s/js/widget.js?c=%s', \Util::getProxyHost(true, false), \Util::getInstallationKey(), $request->input('key'));
		}

		return view('preview', [
			'url' => $url
		]);
	}
}
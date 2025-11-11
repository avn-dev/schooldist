<?php

namespace Tc\Controller;

use Gui2\Entity\InfoText;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tc\Service\Wizard;

class WizardController extends \Illuminate\Routing\Controller
{
	public function index(Wizard $wizard, Request $request)
	{
		return $wizard->showIndex($request);
	}

	public function start(Wizard $wizard, string $stepKey = null)
	{
		return $wizard->start($stepKey);
	}

	public function continue(Wizard $wizard)
	{
		return $wizard->continue();
	}

	public function step(Request $request, Wizard $wizard, Wizard\Structure\Step $step)
	{
		return $wizard->visit($step, $request);
	}

	public function save(Request $request, Wizard $wizard, Wizard\Structure\Step $step)
	{
		return $wizard->save($step, $request);
	}

	public function loadHelpTextModal(Request $request, Wizard $wizard)
	{
		[$key, $field] = array_values($request->only(['key', 'field']));

		if ($key === null || $field === null) {
			return response(Response::HTTP_BAD_REQUEST);
		}

		$helpText = InfoText::query()
			->where('dialog_id', 'wizard.'.$wizard->getKey())
			->where('gui_hash', $key)
			->where('field', $field)
			->first();

		$texts = [];
		if ($helpText) {
			$texts = $helpText->getInfoTexts();
		}

		return response()
			->json(['texts' => $texts]);
	}

	public function saveHelpTextModal(Request $request, Wizard $wizard)
	{
		$key = $request->get('key');
		$field = $request->get('field');
		$values = $request->all([])['values'];

		if ($key === null || $field === null) {
			return response(Response::HTTP_BAD_REQUEST);
		}

		$helpText = InfoText::query()
			->firstOrNew([
				'dialog_id' => 'wizard.'.$wizard->getKey(),
				'gui_hash' => $key,
				'field' => $field,
			]);

		foreach ($values as $iso => $value) {
			$helpText->setInfoText($iso, $value);
		}

		$helpText->save();

		return back();
	}

}
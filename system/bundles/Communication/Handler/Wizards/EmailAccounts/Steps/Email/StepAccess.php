<?php

namespace Communication\Handler\Wizards\EmailAccounts\Steps\Email;

use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use function Tc\Handler\Wizard\EmailAccounts\Steps\Email\http_build_query;
use function Tc\Handler\Wizard\EmailAccounts\Steps\Email\in_array;
use function Tc\Handler\Wizard\EmailAccounts\Steps\Email\is_array;
use function Tc\Handler\Wizard\EmailAccounts\Steps\Email\json_decode;

class StepAccess extends Step
{
	private function getAccessObject()
	{
		return new \Ext_TC_Communication_EmailAccount_AccessMatrix();
	}

	public function render(Wizard $wizard, Request $request): Response
	{
		$html = $this->getAccessObject()->generateHTML($wizard->getLanguageObject()->getContext());

		return $this->view($wizard, '@Communication/wizards/email_accounts/access', ['html' => $html]);
	}

	protected function save(Wizard $wizard, Request $request): ?MessageBag
	{
		$data = $request->input('save.access');

		try {

			$this->getAccessObject()->saveAccessData($data);

		} catch (\Throwable $e) {
			return new MessageBag([$wizard->translate('Authentifizierung fehlgeschlagen.')]);
		}

		$wizard->getSession()->getFlashBag()->add('success', $wizard->translate('Zugriffsrechte wurden erfolgreich gespeichert.'));

		return null;
	}

	public function next(Wizard $wizard, Request $request, $next): Response
	{
		return $wizard->redirectToHome();
	}


}
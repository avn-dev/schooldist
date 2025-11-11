<?php

namespace Communication\Handler\Wizards\EmailAccounts\Steps\Email\Smtp;

use Communication\Handler\Wizards\EmailAccounts\Steps\Email\Form as EmailForm;
use Communication\Handler\Wizards\EmailAccounts\Traits\OAuth2Verify;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use Tc\Traits\Wizard\FormStep;

class StepSettings2 extends Step
{
	use FormStep, OAuth2Verify;

	public function getForm(Wizard $wizard, Request $request): Wizard\Structure\Form
	{
		$account = \Factory::executeStatic(\Ext_TC_Communication_EmailAccount::class, 'query')->findOrFail($request->get('account_id', 0));
		$title = $account->email;

		$form = (new EmailForm($account, 'account_id', $title))
			->add('smtp_user', $wizard->translate('Benutzername'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required|email_mx'
			]);

		if ($account->smtp_auth === 'password') {
			$form->add('smtp_pass', $wizard->translate('Passwort'), Wizard\Structure\Form::FIELD_PASSWORD, [
				'rules' => 'required'
			]);
		}

		$form->add('smtp_host', $wizard->translate('Mailserver'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			])
			->add('smtp_port', $wizard->translate('Serveranschlussnummer (Port)'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			])
			->add('smtp_connection', $wizard->translate('Verschlüsselten Verbindungstyp wählen'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => \Ext_TC_Communication_EmailAccount::getConnectionTypes(),
				'rules' => 'required'
			])
			/*->add('smtp_auth', $wizard->translate('Authentifizierungsmethode'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => \Ext_TC_Communication_EmailAccount::getAuthTypes(),
				'rules' => 'required'
			])*/
		;

		return $form;
	}

	protected function saveForm(Wizard $wizard, Request $request): array
	{
		$form = $this->getForm($wizard, $request);

		$messageBag = $form->save($wizard, $request, $this);

		if ($messageBag === null && $form->getEntity()->smtp_auth !== 'oauth2') {
			if (true === $errorOrTrue = $form->getEntity()->checkSmtp()) {
				$wizard->getSession()->getFlashBag()->set('success', $wizard->translate('Das E-Mail-Konto wurde erfolgreich eingerichtet. Sie sollten eine E-Mail in ihrem Postfach haben.'));
			} else {
				$messageBag = new MessageBag([$wizard->translate('Authentifizierung fehlgeschlagen.'), $errorOrTrue]);
			}
		}

		return [$messageBag, $form->getEntity()];
	}

	public function next(Wizard $wizard, Request $request, $next): Response
	{
		$account = \Factory::executeStatic(\Ext_TC_Communication_EmailAccount::class, 'query')->findOrFail($request->get('account_id', 0));
		/* @var \Ext_TC_Communication_EmailAccount $account */

		if ($account->smtp_auth === 'oauth2' && $this->needsNewToken($account)) {
			return $wizard->redirect($this->getParent()->get('smtp_oauth2')->getFirstStep());
		}

		return $wizard->redirect($this->getParent()->getStep('imap_question'));
	}

}
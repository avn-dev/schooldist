<?php

namespace Communication\Handler\Wizards\EmailAccounts\Steps\Email\Smtp;

use Communication\Handler\Wizards\EmailAccounts\Steps\Email\Form as EmailForm;
use Illuminate\Http\Request;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use Tc\Traits\Wizard\FormStep;

class StepSettings extends Step
{
	use FormStep;

	public function getForm(Wizard $wizard, Request $request): Wizard\Structure\Form
	{
		if (0 < $accountId = $request->get('account_id', 0)) {
			$account = \Factory::executeStatic(\Ext_TC_Communication_EmailAccount::class, 'query')->findOrFail($accountId);
			$title = $account->email;
		} else {
			$account = \Factory::getObject(\Ext_TC_Communication_EmailAccount::class);
			$account->imap = 0;
			$title = $wizard->translate('Neues E-Mail-Konto anlegen');
		}

		$account->bValidateSettings = false;

		return (new EmailForm($account, 'account_id', $title))
			->add('email', $wizard->translate('E-Mail-Adresse'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required|email_mx'
			])
		;
	}

	public function prepareEntity(Wizard $wizard, Request $request, \WDBasic $entity): void
	{
		if ($entity->exist()) {
			return;
		}

		$emailObject = new \FideloSoftware\Mailing\Email($entity->email);

		$discover = (new \FideloSoftware\Mailing\AutoConfig\MailServer())
			->discover($emailObject);

		$outgoingServer = $discover->getOutgoingServer();

		if (strtolower($outgoingServer->getAuthentication()) === 'oauth2') {
			$entity->smtp_auth = 'oauth2';
			$entity->imap_auth = 'oauth2';
		} else {
			$entity->smtp_auth = 'password';
			$entity->imap_auth = 'password';
		}

		if (!$entity->exist()) {
			$entity->smtp_host = $outgoingServer->getHostname();
			$entity->smtp_port = $outgoingServer->getPort();
			if (empty($entity->smtp_user)) {
				$entity->smtp_user = ($outgoingServer->getUserName() === '%EMAILADDRESS%') ? $emailObject->getFull() : '';
			}
			$entity->smtp_connection = ($outgoingServer->getSocketType() === 'STARTTLS') ? 'TLS' : $outgoingServer->getSocketType();
		}
	}

}
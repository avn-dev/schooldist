<?php

namespace Communication\Handler\Wizards\EmailAccounts\Steps\Email\Imap;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;

class StepImapQuestion extends Step
{
	public function render(Wizard $wizard, Request $request): Response
	{
		return $this->view($wizard, '@Communication/wizards/email_accounts/imap_question_step', []);
	}

	public function action(Wizard $wizard, string $action, Request $request, $next): Response
	{
		$account = \Factory::executeStatic(\Ext_TC_Communication_EmailAccount::class, 'query')->findOrFail($request->get('account_id', 0));
		$account->bValidateSettings = false;

		switch ($action) {
			case 'no_imap':
				$account->imap = 0;
				$list = $this->getParent()->getParent()->getStep('list');
				$next = $wizard->redirect($list);
				break;
			default:

				if ($account->getOriginalData('imap') == 0) {
					$emailObject = new \FideloSoftware\Mailing\Email($account->email);

					$discover = (new \FideloSoftware\Mailing\AutoConfig\MailServer())
						->discover($emailObject);

					$incomingServer = $discover->getFirstIncomingServer();

					$account->imap_host = $incomingServer->getHostname();
					$account->imap_port = $incomingServer->getPort();
					$account->imap_user = ($incomingServer->getUserName() === '%EMAILADDRESS%') ? $emailObject->getFull() : '';

					$socketType = strtolower($incomingServer->getSocketType());

					$connectionTypes = \Ext_TC_Communication_EmailAccount::getConnectionTypes('imap');

					if (isset($connectionTypes[$socketType])) {
						$account->imap_connection = $connectionTypes;
					} else if (str_contains($socketType, 'tls')) {
						$account->imap_connection = '/imap/tls';
					} else if (str_contains($socketType, 'ssl')) {
						$account->imap_connection = '/imap/ssl';
					}
				}

				$account->imap = 1;

				$imap = $this->getParent()->get('imap')->getFirstStep();
				$next = $wizard->redirect($imap);
		}

		$account->save();

		return $next;
	}

}
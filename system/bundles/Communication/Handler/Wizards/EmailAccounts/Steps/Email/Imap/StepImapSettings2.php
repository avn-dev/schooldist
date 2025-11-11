<?php

namespace Communication\Handler\Wizards\EmailAccounts\Steps\Email\Imap;

use Communication\Handler\Wizards\EmailAccounts\Steps\Email\Form as EmailForm;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use Tc\Traits\Wizard\FormStep;

class StepImapSettings2 extends Step
{
	use FormStep;

	public function getForm(Wizard $wizard, Request $request): Wizard\Structure\Form
	{
		$account = \Ext_TC_Communication_Imap::query()->findOrFail($request->get('account_id', 0));
		$title = $account->email;

		$selection = new \Ext_TC_Communication_EmailAccount_Gui2_Selection_Folder();
		$folders = $selection->getOptions([], [], $account);

		return (new EmailForm($account, 'account_id', $title))
			->add('imap_filter', $wizard->translate('Welche E-Mail sollen eingelesen werden?'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => \Ext_TC_Communication_EmailAccount::getImapFilter(),
				'rules' => 'required'
			])
			->add('imap_folder', $wizard->translate('Aus welchem Ordner sollen E-Mails eingelesen werden?'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => $folders
			])
			->add('imap_closure', $wizard->translate('Was soll nach dem Einlesen mit den E-Mails passieren?'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => \Ext_TC_Communication_EmailAccount::getClosureOptions(),
				'rules' => 'required'
			])
			->add('imap_append_sent_mail', $wizard->translate('E-Mails in den Ordner "Gesendete Elemente" verschieben?'), Wizard\Structure\Form::FIELD_CHECKBOX, [])
			->add('imap_sent_mail_folder', $wizard->translate('Ordner "Gesendete Elemente"'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => $folders,
				'rules' => 'required'
			])
			->add('imap_sync_sent_mail', $wizard->translate('E-Mails aus dem Ordner "Gesendete Elemente" synchronisieren?'), Wizard\Structure\Form::FIELD_CHECKBOX, [])
		;
	}

	protected function saveForm(Wizard $wizard, Request $request): array
	{
		$form = $this->getForm($wizard, $request);

		$messageBag = $form->save($wizard, $request, $this);

		if ($messageBag === null) {
			if (true === $errorsOrTrue = $form->getEntity()->checkConnection()) {
				$wizard->getSession()->getFlashBag()->set('success', $wizard->translate('Das E-Mail-Konto wurde erfolgreich eingerichtet. Sie sollten eine E-Mail in ihrem Postfach haben.'));
			} else {
				$messageBag = new MessageBag([$wizard->translate('Authentifizierung fehlgeschlagen.'), $errorsOrTrue]);
			}
		}

		return [$messageBag, $form->getEntity()];
	}

}
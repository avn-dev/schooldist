<?php

namespace TsWizard\Handler\Setup\Steps\User;

use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use Illuminate\Http\Request;
use Tc\Traits\Wizard\FormStep;

class StepSettings extends Step
{
	use FormStep;

	public function getForm(Wizard $wizard, Request $request): Wizard\Structure\Form
	{
		if (0 < $userId = $request->get('user_id', 0)) {
			$user = \Ext_Thebing_User::query()->findOrFail($userId);
			$title = $user->getName();
		} else {
			$user = new \Ext_Thebing_User();
			$user->status = 1;
			$title = $wizard->translate('Neuen Benutzer anlegen');
		}

		return (new Wizard\Structure\Form($user, 'user_id', $title))
			->setGui2Information('7e7cd48b212ea0dd76eb54d7ffabba38', \Ext_Thebing_User_Gui2_Data::class)
			->add('firstname', $wizard->translate('Vorname'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			])
			->add('lastname', $wizard->translate('Nachname'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			])
			->add('email', $wizard->translate('E-Mail'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required|email_mx'
			])
			// Siehe \User::__set() - leere Passwörter werden nicht gespeichert
			->add('password', $wizard->translate('Passwort'), Wizard\Structure\Form::FIELD_PASSWORD, [
				'rules' => (!$user->exist()) ? 'required' : '',
			])
		;
	}

	public function prepareEntity(Wizard $wizard, Request $request, \WDBasic $entity): void
	{
		if ($entity->exist()) {
			return;
		}

		$firstRole = \Ext_Thebing_Admin_Usergroup::query()->pluck('name')->first();

		$entity->updateRoles([$firstRole], false);
	}

}
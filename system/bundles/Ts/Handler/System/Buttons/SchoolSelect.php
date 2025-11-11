<?php

namespace Ts\Handler\System\Buttons;

use Admin\Handler\System\Buttons\SystemButton;
use Admin\Http\InterfaceResponse;
use Core\Helper\Routing;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SchoolSelect implements SystemButton
{
	const KEY = 'school-switch';

	public function getKey(): string
	{
		return self::KEY;
	}

	public function getIcon(): string
	{
		return 'fa fa-school';
	}

	public function getTitle(): string
	{
		$selected = Arr::first($this->getOptions(), fn($entry) => $entry['selected']);
		if ($selected) {
			return $selected['text'];
		}
		return \L10N::t('Schule');
	}

	public function hasRight(\Access $access): bool
	{
		return count($this->getSchoolListByAccess()) > 1;
	}

	public function getOptions(): array
	{
		$schoolListByAccess = $this->getSchoolListByAccess();

		$currentSchool = \Ext_Thebing_School::getSchoolIdFromSession();

		$options = [
			['value' => 0, 'text' => \L10N::t('Alle Schulen'), 'selected' => ($currentSchool === 0)]
		];
		foreach ($schoolListByAccess as $schoolId => $name) {
			$options[] = ['value' => $schoolId, 'text' => $name, 'selected' => ($currentSchool === $schoolId)];
		}

		return $options;
	}

	public function handle(Request $request): bool|InterfaceResponse
	{
		$schoolIds = Arr::pluck($this->getOptions(), 'value');
		if (in_array($newSchool = $request->query('option', 0), $schoolIds)) {
			(new \Ts\Handler\SchoolId())->setSchool($newSchool);
		}

		return \Admin\Facades\InterfaceResponse::visit(Routing::generateUrl('Admin.index'));
	}

	public function isActive(): bool
	{
		return \Ext_Thebing_School::getSchoolIdFromSession() > 0;
	}

	private function getSchoolListByAccess(): array
	{
		$client = \Ext_Thebing_System::getClient();
		return $client->getSchoolListByAccess(true);
	}
}

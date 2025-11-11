<?php

namespace Ts\Admin;

use Admin\Dto\TenantDto;
use Admin\Http\InterfaceResponse;
use Core\Helper\Routing;
use Illuminate\Support\Collection;

class Tenants implements \Admin\Interfaces\Tenants
{
	public function getOptions(): Collection
	{
		$client = \Ext_Thebing_Client::getInstance();
		$schoolListByAccess = $client->getSchoolListByAccess(bAsObjects: true);

		$clientLogo = $client->getFilePath(false).'logo.png';

		$defaultColor = \Ext_Thebing_Client::getInstance()->system_color;

		$defaultLogo = null;
		if(is_file(\Util::getDocumentRoot().$clientLogo)) {
			$defaultLogo = $clientLogo;
		}

		$currentSchool = \Ext_Thebing_School::getSchoolIdFromSession();

		$tenants = collect([
			new TenantDto(0, \L10N::t('Alle Schulen'), $defaultLogo, true, ($currentSchool === 0), $defaultColor)
		]);

		foreach ($schoolListByAccess as $school) {
			$logo = !empty($logo = $school->getLogo()) ? $logo : $defaultLogo;
			$color = !empty($color = $school->system_color) ? $color : $defaultColor;

			$text = [$school->address, $school->zip, $school->city];

			$tenants->push(new TenantDto(
				$school->id,
				$school->ext_1,
				$logo,
				($logo && $logo !== $defaultLogo) ? false : true,
				($currentSchool === (int)$school->id),
				$color,
				implode(', ', array_filter($text, fn ($value) => !empty($value)))
			));
		}

		return $tenants;
	}

	public function switchTenant(TenantDto $tenant): bool|InterfaceResponse
	{
		(new \Ts\Handler\SchoolId())->setSchool((int)$tenant->getKey());

		return \Admin\Facades\InterfaceResponse::visit(Routing::generateUrl('Admin.index'));

	}
}
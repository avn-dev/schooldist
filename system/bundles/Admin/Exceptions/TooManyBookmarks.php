<?php

namespace Admin\Exceptions;

use Admin\Facades\Admin;
use Admin\Facades\InterfaceResponse;
use Core\Enums\AlertLevel;
use Core\Interfaces\Http\HttpResponse;
use Core\Notifications\ToastrNotification;
use Illuminate\Http\Request;

class TooManyBookmarks extends \RuntimeException implements HttpResponse
{
	public function toResponse(Request $request)
	{
		$notification = new ToastrNotification(Admin::translate('Bevor Sie einen neuen Eintrag zur Schnellansicht hinzufügen müssen Sie erst andere löschen.'), AlertLevel::DANGER);

		return InterfaceResponse::json(['success' => false])
			->notification($notification)
			->toResponse($request);
	}
}
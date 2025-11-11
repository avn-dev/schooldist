<?php

namespace TsStudentApp\Http\Resources;

use Illuminate\Container\Container;
use Illuminate\Http\Resources\Json\JsonResource;
use TsStudentApp\AppInterface;
use TsStudentApp\Service\MessengerService;
use TsStudentApp\Service\Util;

/**
 * @mixin \Ext_Thebing_Teacher
 */
class TeacherResource extends JsonResource
{
	private MessengerService $messengerService;

	public function __construct($resource)
	{
		parent::__construct($resource);
		$this->messengerService = Container::getInstance()->make(MessengerService::class);
	}

	public function toArray($request)
	{
		$thread = $this->messengerService->getThreadForEntity($this->resource);

		$lines = [];
		//if () {
		//$lines = [$this->email];
		//}

		$profilPicture = $this->getProfilePicture();

		return [
			'name' => $this->getName(),
			'image' => ($profilPicture) ? Util::imageUrl('teacher', $this->id) : null,
			'email' => $this->email,
			'thread' => (new MessengerThreadResource($thread))->toArray($request),
			'lines' => $lines,
		];
	}
}
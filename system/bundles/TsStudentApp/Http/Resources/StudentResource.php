<?php

namespace TsStudentApp\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use TsStudentApp\Service\Util;

/**
 * @mixin \Ext_TS_Inquiry_Contact_Traveller
 */
class StudentResource extends JsonResource
{
	public function toArray($request)
	{
		return [
			'name' => $this->getName(),
			'image' => !empty($this->getPhoto())
				? Util::imageUrl('student', $this->getId()).'?v='.md5(filemtime(base_path().$this->getPhoto()))
				: null,
			'email' => $this->getFirstEmailAddress()->email
		];
	}
}
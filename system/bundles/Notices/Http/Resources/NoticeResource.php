<?php

namespace Notices\Http\Resources;

use Admin\Http\Resources\UserResource;
use Core\Service\HtmlPurifier;
use Illuminate\Http\Resources\Json\JsonResource;
use Notices\Entity\Notice;

/**
 * @mixin Notice
 */
class NoticeResource extends JsonResource
{
	public function toArray($request)
	{
		$latestVersion = $this->getLatestVersion();

		/* @var \Ext_Gui2_View_Format_Date $format */
		$format = \Factory::getObject(\Ext_Gui2_View_Format_Date::class);
		$user = \Factory::getInstance(\User::class, $latestVersion->creator_id);

		$html = new HtmlPurifier(HtmlPurifier::SET_TCPDF);

		return [
			'id' => $this->id,
			'created' => $format->formatByValue($this->created),
			'author' => new UserResource($user),
			'text' => $html->purify($latestVersion->notice)
		];
	}
}
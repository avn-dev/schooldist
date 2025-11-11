<?php

namespace Admin\Http\Resources;

use Admin\Instance;
use Admin\Interfaces\Notification\AdminButton;
use Carbon\Carbon;
use Core\Entity\System\UserNotification;
use Core\Notifications\ToastrNotification;
use Core\Service\HtmlPurifier;
use Core\Service\NotificationService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

/**
 * @mixin UserNotification
 */
class UserNotificationResource extends JsonResource {

	public function __construct(
		$resource,
		private \Access_Backend $access,
		private Instance $admin
	) {
		parent::__construct($resource);
	}

	/**
	 * @param $request
	 * @return array
	 * @throws \Exception
	 */
	public function toArray($request)
	{
		$data = $this->getDataArray();

		$group = $this->getGroup($data);

		$date = Carbon::parse($this->created);

		$html = new HtmlPurifier(HtmlPurifier::SET_TCPDF);

		if ($this->type === ToastrNotification::class) {
			$alert = $data['alert'] ?? 'info';
		} else {
			$alert = $data['alert'] ?? 'default';
		}

		return [
			'id' => (int)$this->id,
			'type' => $this->type,
			'date' =>  $date->timestamp,
			'date2' =>  $date->toDateTimeString(),
			'date_formatted' =>  $this->formatDate($date),
			'group' => strip_tags($group ?? NotificationService::translate('Sonstiges')),
			'subject' => $data['subject'] ? strip_tags($data['subject']) : null,
			'message' =>  $data['message'] ? $html->purify($data['message']) : '',
			'attachments' =>  $data['attachments'] ?? [],
			'icon' => $data['icon'] ?? null,
			'alert' => $alert,
			'buttons' => array_map(fn($button) => Arr::only($button, ['key', 'text']), $this->getButtons($data)),
			// Alles andere unter "data" anfügen
			'data' => Arr::except($data, ['group_title', 'subject', 'message', 'attachments', 'buttons', 'icon', 'alert']),
			'read' =>  $this->isRead(),
			// Eindeutigen Key zum Sortieren generieren ansonsten "springen" Nachrichten im JS, wenn sie zur selben
			// Zeit erstellt wurden (siehe user.ts)
			'sort_key' => (int)$date->format('YmdHis'),
		];
	}

	private function getGroup($data): ?string
	{
		$group = null;
		if (method_exists($this->type, 'getGroupTitle')) {
			$group = call_user_func_array([$this->type, 'getGroupTitle'], [$data]);
		} else if (isset($data['group_title'])) {
			if (is_array($data['group_title'])) {
				$group = call_user_func($data['group_title']);
			} else if (is_string($data['group_title'])) {
				$group = $data['group_title'];
			}
		}

		return $group;
	}

	private function getButtons($data): array
	{
		try {
			$buttons = array_filter($data['buttons'] ?? [], function ($button) {
				$button = call_user_func_array([$button['class'], 'fromArray'], [$this->admin, $button['payload']]);
				return ($button instanceof AdminButton)
					? $button->isAccessible($this->access)
					: false;
			});
		} catch (\Throwable $e) {
			$this->admin->getLogger('Notifications')->error('Notification buttons failed', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
			$buttons = [];
		}

		return array_values($buttons);
	}

	/**
	 * Datum formatieren
	 *
	 * @param Carbon $date
	 * @return string
	 */
	private function formatDate(Carbon $date): string
	{
		$now = new Carbon();

		if ($now->toDateString() === $date->toDateString()) {

			/*$toUnit = function ($diff, array $units) {
				[$singular, $plural] = $units;
				$unit = ($diff === 1) ? $singular : $plural;
				return sprintf(NotificationService::translate('Vor %s '.$unit), $diff);
			};

			if (60 > $diffMinutes = $date->diffInMinutes($now)) {
				return $toUnit($diffMinutes, ['Minute', 'Minuten']);
			}

			if (8 > $diffHours = $date->diffInHours($now)) {
				return $toUnit($diffHours, ['Stunde', 'Stunden']);
			}*/

			$format = \Factory::getObject(\Ext_Gui2_View_Format_Time::class);
			$format->format = '%R';
			return NotificationService::translate('Heute'). ' - '. $format->formatByValue($date);
		}

		$format = \Factory::getObject(\Ext_Gui2_View_Format_Date_Time::class);
		return $format->formatByValue($date);
	}
}

<?php

namespace TsStudentApp\Messenger\Thread;

use Carbon\Carbon;
use Communication\Enums\MessageStatus;
use Illuminate\Support\Collection;
use TsStudentApp\Events\AppMessageReceived;
use TsStudentApp\Messenger\Message;
use TsStudentApp\Service\Util;

abstract class CoreCommunication extends AbstractThread {

	/**
	 * Anzahl der ungelesenen Nachrichten
	 *
	 * @return int
	 */
	public function getNumberOfUnreadMessages(): int {

		$bindings = ['status' => MessageStatus::SEEN->value];

		$sqlParts = $this->buildStandardQueryParts($bindings);

		$sqlParts['select'] = " COUNT(*) AS `count` ";

		$sqlParts['where'] .= "
			AND `status` != :status
			AND `direction` = 'out'
		";

		$sql = \DB::buildQueryPartsToSql($sqlParts);

		return (int) \DB::getQueryOne($sql, $bindings);
	}

	public function getMessages(int $limit, string $lastMessageId = null, array $directions = ['in', 'out']): Collection {

		$bindings = [];

		$sqlParts = $this->buildStandardQueryParts($bindings);

		$sqlParts['select'] = " 
			`tc_cm`.*,
			GROUP_CONCAT(
				CONCAT(`tc_cmf`.`id`, '{|}', `tc_cmf`.`name`) SEPARATOR '{||}'
			) attachments
		";

		$sqlParts['from'] .= " LEFT JOIN
			`tc_communication_messages_files` `tc_cmf` ON
				`tc_cmf`.`message_id` = `tc_cm`.`id`
		";

		if($lastMessageId !== null) {
			$bindings['last_message'] = $lastMessageId;
			$sqlParts['where'] .= " AND `tc_cm`.`id` < :last_message ";
		}

		$bindings['directions'] = $directions;
		$sqlParts['where'] .= " AND `tc_cm`.`direction` IN (:directions) ";

		$sqlParts['groupby'] = " `tc_cm`.`id` ";
		$sqlParts['orderby'] = " `tc_cm`.`date` DESC ";

		$bindings['limit'] = $limit;
		$sqlParts['limit'] = " :limit ";

		$sql = \DB::buildQueryPartsToSql($sqlParts);
		$messages = (array) \DB::getPreparedQueryData($sql, $bindings);

		return collect($messages)
			->map(function($message) {
				// Ansicht drehen (für den Schüler ist out = in)
				$message['direction'] = ($message['direction'] === 'out') ? 'in' : 'out';

				/** @var Message $model */
				$model = app()->makeWith(Message::class, [
					'thread' => $this,
					'id' => $message['id'],
					'direction' => $message['direction'],
					'date' => new \DateTime($message['date']),
					'message' => $message['content']
				]);

				if ($message['status'] !== null) {
					$model->status(MessageStatus::from($message['status']));
				}

				if(!is_null($message['attachments'])) {
					$attachments = explode('{||}', $message['attachments']);
					foreach($attachments as $attachment) {
						list($id, $name) = explode('{|}', $attachment);
						$model->attachment($name, Util::documentUrl('attachment', $id));
					}
				}

				return $model;
			});

	}

	/**
	 * Standard SQL-Query für die Core Communication
	 *
	 * @param array $bindings
	 * @return array
	 * @throws \Exception
	 */
	protected function buildStandardQueryParts(array &$bindings) {

		$threadRelation = (isset($this->threadConfig['relation']))
			? $this->threadConfig['relation']
			: [ $this->entity::class, $this->entity->getId() ];

		$bindings['inquiry_class'] = \Ext_TS_Inquiry::class;
		$bindings['inquiry_id'] = $this->inquiry->getId();
		$bindings['student_class'] = \Ext_TS_Inquiry_Contact_Traveller::class;
		$bindings['student_id'] = $this->student->getId();
		$bindings['relation_class'] = $threadRelation[0];
		$bindings['relation_id'] = $threadRelation[1];

		$sqlParts = [];

		$sqlParts['select'] = "";

		$sqlParts['from'] = "	
			`tc_communication_messages_app_index` `tc_cmai` INNER JOIN
			`tc_communication_messages` `tc_cm` ON
				`tc_cm`.`id` = `tc_cmai`.`message_id` AND
				`tc_cm`.`type` = 'app' AND
				`tc_cm`.`active` = 1
		";

		$sqlParts['where'] = "
			`tc_cmai`.`device_relation` = :student_class AND
			`tc_cmai`.`device_relation_id` = :student_id AND
			`tc_cmai`.`thread_relation` = :relation_class
		";

		if($threadRelation[1] !== '*') {
			$sqlParts['from'] .= " AND `tc_cmai`.`thread_relation_id` = :relation_id ";
		}

		return $sqlParts;
	}

	/**
	 * TODO - schöner lösen
	 * TODO - int $timestamp wird nicht (mehr) benutzt
	 *
	 * @param string $message
	 * @param string $direction
	 * @return \WDBasic
	 * @throws \Exception
	 */
	public function storeMessage(string $message, int $timestamp, string $direction, MessageStatus $status, string $subject = null): \Ext_TC_Communication_Message {

		$messageEntity = new \Ext_TC_Communication_Message();
		$messageEntity->direction = $direction;
		$messageEntity->content = $message;
		$messageEntity->type = 'app';
		$messageEntity->content_type = 'text';
		// In der Zeitzone der Schule abspeichern damit Nachrichten chronologisch angezeigt werden (siehe \Ext_TC_Util::setTimezone())
		$messageEntity->date = Carbon::now($this->inquiry->getJourney()->getSchool()->getTimezone())->toDateTimeString();
		$messageEntity->status = $status->value;

		if ($subject !== null) {
			$messageEntity->subject = $subject;
		}

		[$threadRelation, $threadRelationId] = (isset($this->threadConfig['relation']))
			? $this->threadConfig['relation']
			: [ $this->entity::class, $this->entity->getId() ];

		$messageEntity->app_index = [[
			'device_relation' => get_class($this->student),
			'device_relation_id' => $this->student->id,
			'thread_relation' => $threadRelation,
			'thread_relation_id' => ($threadRelationId !== '*') ? $threadRelationId : 0,
		]];

		$messageEntity->relations = [
			['relation' => get_class($this->inquiry), 'relation_id' => $this->inquiry->getId()],
		];

		/** @var \Ext_TC_Communication_Message_Address $from */
		$from = $messageEntity->getJoinedObjectChild('addresses');
		$from->type = 'from';
		/** @var \Ext_TC_Communication_Message_Address $to */
		$to = $messageEntity->getJoinedObjectChild('addresses');
		$to->type = 'to';

		if ($direction === 'in') {
			$from->address = $this->student->id;
			$from->name = $this->student->getName();
			$from->relations = [['relation' => $this->student::class, 'relation_id' => $this->student->id]];
			$to->address = $this->entity->id;
			$to->name = $this->getName();
			$to->relations = [['relation' => $threadRelation, 'relation_id' => ($threadRelationId !== '*') ? $threadRelationId : 0]];
		} else {
			$from->address = $this->entity->id;
			$from->name = $this->getName();
			$from->relations = [['relation' => $threadRelation, 'relation_id' => ($threadRelationId !== '*') ? $threadRelationId : 0]];
			$to->address = $this->student->id;
			$to->name = $this->student->getName();
			$to->relations = [['relation' => $this->student::class, 'relation_id' => $this->student->id]];
		}

		$messageEntity->save();

		if ($messageEntity->direction === 'in') {
			AppMessageReceived::dispatch($this, $messageEntity);
		}

		return $messageEntity;
	}

	public function markMessageAsSeen($messageKey): bool {

		$bindings = ['message_id' => $messageKey];

		$sqlParts = $this->buildStandardQueryParts($bindings);
		$sqlParts['select'] = "`tc_cm`.`id`";
		$sqlParts['where'] .= " AND `tc_cm`.`id` = :message_id ";
		$sqlParts['limit'] .= " 1 ";

		$sql = \DB::buildQueryPartsToSql($sqlParts);

		$messageId = (int)\DB::getQueryOne($sql, $bindings);

		if ($messageId > 0) {
			$message = \Ext_TC_Communication_Message::getInstance($messageId);
			// In der Zeitzone der Schule abspeichern damit Nachrichten chronologisch angezeigt werden (siehe \Ext_TC_Util::setTimezone())
			$message->seen_at = Carbon::now($this->inquiry->getJourney()->getSchool()->getTimezone())->toDateTimeString();
			$message->status = MessageStatus::SEEN->value;
			$message->save();
			return true;
		}

		return false;
	}

}

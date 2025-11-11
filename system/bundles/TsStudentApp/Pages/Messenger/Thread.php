<?php

namespace TsStudentApp\Pages\Messenger;

use Communication\Enums\MessageStatus as StatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use TsStudentApp\AppInterface;
use TsStudentApp\Http\Resources\PropertyResource;
use TsStudentApp\Messenger\Message;
use TsStudentApp\Messenger\Thread\AbstractThread;
use TsStudentApp\Pages\AbstractPage;
use TsStudentApp\Properties\NumberOfUnseenMessages;
use TsStudentApp\Service\MessengerService;

class Thread extends AbstractPage {

	const MESSAGE_LIMIT = 20;
	const COLOR_IN = '#FFFFFF';
	const COLOR_OUT = '#DEDEDE';

	public function __construct(private readonly AppInterface $appInterface, private readonly MessengerService $messenger) {}

	public function init(AbstractThread $thread) {

		$data = $this->buildData($thread);
		$data['student_messages'] = false;

		if (version_compare($this->appInterface->getAppVersion(), '2.2', '>=')) {
			$data['title'] = $thread->getName();
			// Schüler können auch Nachrichten schreiben
			$data['student_messages'] = (bool)$this->appInterface->getSchool()->getMeta('student_app_student_messages', false);

			// TODO Einstellung ob Status angezeigt werden soll
			$data['status_icons'] = [
				['status' => StatusEnum::SENT->value, 'icon' => 'checkmark-outline'],
				['status' => StatusEnum::RECEIVED->value, 'icon' => 'checkmark-done-outline'],
				['status' => StatusEnum::SEEN->value, 'icon' => 'checkmark-done-circle'],
				['status' => StatusEnum::FAILED->value, 'icon' => 'close-circle-outline'],
			];
		}

		$data['student_messages_enabled'] = $data['student_messages'];

		return $data;
	}

	public function load(Request $request, AbstractThread $thread) {
		$lastMessageId = (string) $request->get('last_message', null);
		return $this->buildData($thread, $lastMessageId);
	}

	public function send(Request $request, AbstractThread $thread)
	{
		$message = $request->input('message');

		$messageEntity = $thread->storeMessage($message['text'], ($message['date'] / 1000), 'in', StatusEnum::RECEIVED);

		/** @var Message $model */
		$model = app()->makeWith(Message::class, [
			'thread' => $thread,
			'id' => $message['id'],
			'direction' => 'out',
			'date' => new \DateTime('@'.$messageEntity->date), // Plötzlich ein Timestamp
			'message' => $messageEntity->content
		]);

		if ($messageEntity->status !== null) {
			$model->status(\Communication\Enums\MessageStatus::from($messageEntity->status));
		}

		$message = $model->toArray();
		$message['text'] = nl2br($message['text']);

		return response()->json(['message' => $message]);

//		$message['id'] = $messageEntity->id;
//		$message['status'] = $messageEntity->status;
//		// Kleines Timeout damit der Status nicht direkt umspringt, sieht einfach besser aus
//		$message['status_timeout'] = 500;
//
//		/*$property = $this->appInterface->getProperty(
//			PropertyKey::generate(MessageStatus::PROPERTY, ['id' => $message['id']])
//		);*/
//
//		$json = ['message' => $message];
//		//$json['property'] = (new PropertyResource($property))->toArray($request);
//
//		return response()->json($json);
	}

	public function markAsSeen(Request $request, AbstractThread $thread) {

		if (null === $messageId = $request->input('message_id')) {
			return response('Bad request', Response::HTTP_BAD_REQUEST);
		}

		$success = $thread->markMessageAsSeen($messageId);

		$property = $this->appInterface->getProperty(NumberOfUnseenMessages::PROPERTY);

		return response()->json([
			'success' => $success,
			'properties' => [
				(new PropertyResource($property))->toArray($request)
			]
		]);
	}

	private function buildData(AbstractThread $thread, string $lastMessageId = null) {

		$messages = $thread->getMessages(self::MESSAGE_LIMIT, $lastMessageId)
			->sortBy(fn (Message $message) => $message->getDate())
			->values();

		$data = [];
		// TODO - nicht optimal wenn messages->count() === self::MESSAGE_LIMIT
		$data['final'] = $messages->count() < self::MESSAGE_LIMIT;
		$data['messages'] =  $messages->map(function(Message $message) {
			$message = $message->toArray();
			$message['text'] = nl2br($message['text']);
			return $message;
		});

		return $data;
	}

	public function getTranslations(AppInterface $appInterface): array {
		return [
			'messenger.thread.your_message' => $appInterface->t('Type your message...'),
			'messenger.thread.load_more' => $appInterface->t('Load more'),
			'messenger.thread.today' => $appInterface->t('Today')
		];
	}

	public function getColors(AppInterface $appInterface): array {
		return [
			'--ion-color-messenger-in' => self::COLOR_IN,
			'--ion-color-messenger-out' => self::COLOR_OUT,
		];
	}

}

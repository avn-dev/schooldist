<?php

namespace TsStudentApp\Messenger;

use TsStudentApp\Messenger\Thread\AbstractThread;

class Notification {

	private $title;

	private $message;

	private $image = "";

	/**
	 * Darf kein verschachteltes Array sein: https://github.com/kreait/firebase-php/discussions/533
	 *
	 * @var string[]
	 */
	private array $additional = [];

	public function __construct(string $title, string $message) {
		$this->title = $title;
		$this->message = $message;
	}

//	public function image(string $image) {
//		$this->image = $image;
//		return $this;
//	}

	/**
	 * Messenger-Thread in der App öffnen
	 *
	 * @deprecated App >= 3.0.0
	 * @param AbstractThread $thread
	 */
	public function openThread(AbstractThread $thread) {
		$this->set('task', 'openThread');
		$this->set('thread', $thread->getToken());
		return $this;
	}

	/**
	 * Seite in der App öffnen
	 *
	 * @param string $page
	 */
	public function openPage(string $page, array $params = []) {
		$this->set('task', 'openPage');
		$this->set('page', $page);
		$this->set('params', $params);
//		$this->touchPage($page);
		return $this;
	}

//	/**
//	 * Notification betrifft eine Seite - dadurch wird der Cache der Seite in der App geleert
//	 *
//	 * @param string $page
//	 */
//	public function touchPage(string $page) {
//		$this->set('page', $page);
//		return $this;
//	}

//	/**
//	 * Leert den kompletten Cache der Seiten
//	 *
//	 * @deprecated Wurde nie implementiert
//	 * @return $this
//	 */
//	public function clearCache() {
//		$this->set('clear_cache', 1);
//		return $this;
//	}

	public function getTitle(): string {
		return $this->title;
	}

	public function getMessage(): string {
		return $this->message;
	}

	public function getImage(): string {
		return $this->image;
	}

	public function getAdditional(): array {
		return array_map(fn($v) => is_array($v) ? http_build_query($v) : $v, $this->additional);
	}

	private function set(string $key, $value) {
		$this->additional[$key] = $value;
		return $this;
	}
}

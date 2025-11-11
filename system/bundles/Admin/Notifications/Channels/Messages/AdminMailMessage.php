<?php

namespace Admin\Notifications\Channels\Messages;

use Illuminate\Support\Str;
use Symfony\Component\Mime\Address;

class AdminMailMessage
{
	private string $subject = '';

	private array $to = [];

	public function __construct(private string $bundle, private string $templateFile, private array $templateData) {}

	public function to(string $email, string $name = null): static
	{
		$this->to[] = new Address($email, (string)$name);
		return $this;
	}

	public function subject(string $subject): static
	{
		$this->subject = $subject;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getTo(): array
	{
		return $this->to;
	}

	/**
	 * @return string
	 */
	public function getBundle(): string
	{
		return $this->bundle;
	}

	/**
	 * @return string
	 */
	public function getTemplateFile(): string
	{
		return $this->templateFile;
	}

	/**
	 * @return array
	 */
	public function getTemplateData(): array
	{
		return $this->templateData;
	}

	/**
	 * @return string
	 */
	public function getSubject(): string
	{
		return $this->subject;
	}

	public function toArray(): array
	{
		$templateData = $this->templateData;

		array_walk_recursive($templateData, function (&$value) {
			if ($value instanceof \WDBasic) {
				$value = 'entity::'.$value::class.'#'.$value->getId();
			}
		});

		$data = [
			'bundle' => $this->bundle,
			'template_file' => $this->templateFile,
			'template_data' => $templateData,
			'subject' => $this->subject,
			'to' => array_map(fn (Address $address) => [$address->getAddress(), $address->getName()], $this->to)
		];

		return $data;
	}

	public static function fromArray(array $data): static
	{
		$templateData = $data['template_data'];

		array_walk_recursive($templateData, function (&$value) {
			if (str_starts_with($value, 'entity::')) {
				[$class, $id] = explode('#', Str::after($value, 'entity::'));
				$value = \Factory::getInstance($class, $id);
			}
		});

		$message = (new self($data['bundle'], $data['template_file'], $templateData))
			->subject($data['subject']);

		foreach ($data['to'] as $to) {
			$message->to(email: $to[0], name: $to[1]);
		}

		return $message;
	}
}
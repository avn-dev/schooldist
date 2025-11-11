<?php

namespace Communication\Dto\Message;

use Communication\Interfaces\Model\HasCommunication;
use Illuminate\Support\Arr;
use Tc\Service\LanguageAbstract;

class Attachment extends \Core\Notifications\Attachment
{
	private array $groups = [];

	/**
	 * siehe Ext_TC_Communication::getSelectInvoiceTypes() / Ext_TC_Communication::getSelectReceipts()
	 */
	private array $types = [];

	private ?HasCommunication $source = null;

	public function __construct(
		private string $key,
		string $filePath,
		?string $fileName = null,
		?\WDBasic $entity = null
	) {
		parent::__construct($filePath, $fileName, $entity);
	}

	public function groups(array|string $groups): static
	{
		$this->groups = array_unique(Arr::wrap($groups));
		return $this;
	}

	public function types(array|string $types): static
	{
		$this->types = array_unique(Arr::wrap($types));
		return $this;
	}

	public function source(HasCommunication $source) : static
	{
		$this->key = sprintf('%s::%s', $source->id, $this->key);
		$this->source = $source;
		return $this;
	}

	public function getKey() : string
	{
		return $this->key;
	}

	public function getSource(): ?HasCommunication
	{
		return $this->source;
	}

	public function getModel(): ?\WDBasic
	{
		return $this->entity;
	}

	public function getGroups(): array
	{
		return $this->groups;
	}

	public function getTypes(): array
	{
		return $this->types;
	}

	public function toArray(LanguageAbstract $l10n): array
	{
		return [
			'key' => $this->getKey(),
			'file_name' => $this->getFileName(),
			'file_size' => $this->getReadableFileSize(),
			'file_path' => $this->getUrl(),
			'icon' => $this->getIcon() ?? 'fa fa-file',
			'groups' => $this->groups,
			'model' => $this->source?->getCommunicationLabel($l10n) ?? $l10n->translate('Sonstige'),
		];
	}
}
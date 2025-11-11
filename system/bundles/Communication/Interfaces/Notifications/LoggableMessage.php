<?php

namespace Communication\Interfaces\Notifications;

use Core\Notifications\Attachment;
use Core\Notifications\Recipient;

interface LoggableMessage
{
	/**
	 * @param \Ext_TC_Communication_Message $log
	 * @return $this
	 */
	public function log(\Ext_TC_Communication_Message $log): static;

	/**
	 * @return \Ext_TC_Communication_Message|null
	 */
	public function getLog(): ?\Ext_TC_Communication_Message;

	/**
	 * @return string|null
	 */
	public function getSubject(): ?string;

	/**
	 * [string $content, string $contentType]
	 *
	 * @return array
	 */
	public function getContent(): array;

	/**
	 * [\Ext_TC_Communication_EmailAccount $account, \User $user = null]
	 * @return array|null
	 */
	public function getFrom(): ?array;

	/**
	 * @return Recipient[]
	 */
	public function getTo(): array;

	/**
	 * @return Recipient[]
	 */
	public function getCc(): array;

	/**
	 * @return Recipient[]
	 */
	public function getBcc(): array;

	/**
	 * @return \WDBasic[]
	 */
	public function getRelations(): array;

	/**
	 * @return Attachment[]
	 */
	public function getAttachments(): array;

	/**
	 * @return string[]
	 */
	public function getFlags(): array;
}
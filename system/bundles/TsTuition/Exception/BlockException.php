<?php

namespace TsTuition\Exception;

class BlockException extends \RuntimeException
{
	private \Ext_Thebing_School_Tuition_Block $block;

	public function block(\Ext_Thebing_School_Tuition_Block $block): static
	{
		$this->block = $block;
		return $this;
	}

	public function getBlock(): ?\Ext_Thebing_School_Tuition_Block
	{
		return $this->block;
	}
}
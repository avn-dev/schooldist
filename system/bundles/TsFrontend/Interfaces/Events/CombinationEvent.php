<?php

namespace TsFrontend\Interfaces\Events;

interface CombinationEvent
{
	public function getCombination(): \Ext_TC_Frontend_Combination;

	public function getLanguage(): string;
}
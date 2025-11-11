<?php

namespace Tc\Service\Wizard\Structure;

use Illuminate\Support\Collection;
use Tc\Service\Wizard;

class Table
{
	const ICON_NEW = 'fa fa-plus';
	const ICON_EDIT = 'fa fa-pencil';
	const ICON_DELETE = 'fa fa-trash';

	const COLOR_NEW = 'btn-success';
	const COLOR_EDIT = 'btn-warning';
	const COLOR_DELETE = 'btn-danger';

	private array $columns = [];

	private array $actions = [];

	private array $globalActions = [];

	public function __construct(private Collection|array $rows) {}

	public function column(string $label, \Closure $value, array $config = []): static
	{
		$this->columns[] = [
			'label' => $label,
			'getValue' => $value,
			'config' => $config,
		];
		return $this;
	}

	public function new(string $label, string|\Closure $link, $accessRight = null): static
	{
		return $this->globalAction($label, self::ICON_NEW, self::COLOR_NEW, $link);
	}

	public function edit(string $label, string|\Closure $link, \Closure $check = null, $accessRight = null): static
	{
		return $this->action($label, self::ICON_EDIT, self::COLOR_EDIT, $link, $check);
	}

	public function delete(string $label, string $confirmMessage, string|\Closure $link, \Closure $check = null, $accessRight = null): static
	{
		return $this->action($label, self::ICON_DELETE, self::COLOR_DELETE, $link, $check, $confirmMessage);
	}

	public function action(string $label, string $icon, string $color, string|\Closure $link, \Closure $check = null, string $confirmMessage = null): static
	{
		$action = [
			'label' => $label,
			'icon' => $icon,
			'color' => $color,
			'link' => $link,
			'isAvailable' => $check,
		];

		if ($confirmMessage !== null) {
			$action['confirm'] = $confirmMessage;
		}

		$this->actions[] = $action;
		return $this;
	}

	public function globalAction(string $label, string $icon, string $color, string|\Closure $link): static
	{
		$this->globalActions[] = [
			'label' => $label,
			'icon' => $icon,
			'color' => $color,
			'link' => $link
		];
		return $this;
	}

	public function render(Wizard $wizard): string
	{
		$globalAction = [];
		foreach ($this->globalActions as $action) {
			if ($action['link'] instanceof \Closure) {
				$action['link'] = $action['link']();
			}
			$globalAction[] = $action;
		}

		$rows = [];
		foreach ($this->rows as $row) {
			$data = [];
			foreach ($this->columns as $column) {
				$data[] = $column['getValue']($row);
			}
			foreach ($this->actions as $action) {
				if ($action['link'] instanceof \Closure) {
					$action['link'] = $action['link']($row);
				}
				$data['actions'][] = $action;
			}
			$rows[] = $data;
		}

		$smarty = new \SmartyWrapper();
		$smarty->assign('wizard', $wizard);
		$smarty->assign('globalActions', $globalAction);
		$smarty->assign('columns', $this->columns);
		$smarty->assign('actions', $this->actions);
		$smarty->assign('rows', $rows);

		return $smarty->fetch('@Tc/wizard/table.tpl');
	}

	public function getRows(): array
	{
		return $this->rows;
	}

	public function getColumns(): array
	{
		return $this->columns;
	}

	public function getActions(): array
	{
		return $this->actions;
	}
}
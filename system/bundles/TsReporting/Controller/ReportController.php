<?php

namespace TsReporting\Controller;

use Illuminate\Http\Request;
use TsReporting\Entity\Report;
use TsReporting\Generator\ReportGenerator;
use TsReporting\Generator\Filter\FilterFactory;
use TsReporting\Generator\ValueHandler;
use TsReporting\Gui2\AccessMatrix;
use TsReporting\Services\ExportService;
use TsReporting\Traits\TranslateTrait;

class ReportController extends \Illuminate\Routing\Controller
{
	use TranslateTrait;

	public function index()
	{
		$access = (new AccessMatrix())->getListByUserRight();

		$reports = Report::query()
			->get()
			->filter(fn (Report $report) => isset($access[$report->id]))
			->map(function (Report $report) {
				return ['id' => $report->id, 'name' => $report->name, 'description' => $report->description];
			})
			->sortBy('name')
			->values();

		$school = \Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		$dateFormat = \Ext_Thebing_Format::getDateFormat($school, 'backend_moment_format_long');

		$translations = [
			'overview' => $this->t('Übersicht'),
			'back' => $this->t('Zurück'),
			'reports' => $this->t('Auswertungen'),
			'refresh' => $this->t('Aktualisieren'),
			'filter' => $this->t('Filter'),
			'apply_filters' => $this->t('Filter anwenden'),
			'export' => $this->t('Export')
		];

		return response()->view('index', compact('reports', 'translations', 'dateFormat'));
	}

	public function query(Request $request)
	{
		$access = (new AccessMatrix())->getListByUserRight();
		if (!isset($access[$request->input('id')])) {
			abort(403);
		}

		/** @var Report $report */
		$report = Report::query()->findOrFail($request->input('id'));

		$report->last_access = time();
		$report->save();

		$valueHandler = new ValueHandler(\System::getInterfaceLanguage());

		$factory = new FilterFactory();
		$filters = $factory->fromConfig();
		$factory->applyRequest($filters, $request->input('filters', []), $valueHandler);

		if ($request->boolean('filter_dependency')) {
			return ['filters' => $factory->toJson($filters, $valueHandler)];
		}

		$generator = new ReportGenerator($report, $valueHandler, $filters);
		$data = $generator->generate();
		$data['filters'] = $factory->toJson($filters, $valueHandler);

		if ($request->boolean('debug')) {
			$data['config']['debug'] = json_encode($generator->getLog(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		}

		return $data;
	}

	public function export(Request $request)
	{
		/** @var Report $report */
		$report = Report::query()->findOrFail($request->input('id'));

		$valueHandler = new ValueHandler(\System::getInterfaceLanguage());
		$factory = new FilterFactory();
		$filters = $factory->fromConfig();
		$factory->applyRequest($filters, $request->input('filters', []), $valueHandler);

		$name = \Util::getCleanFilename($report->name).'.xlsx';
		$head = $request->input('head', []);
		$body = $request->input('body', []);
		$foot = $request->input('foot', []);

		$exportService = new ExportService($head, $body, $foot);
		$exportService->create();

		$period = $valueHandler->getPeriod();
		$spreadsheet = $exportService->getSpreadsheet();
		$spreadsheet->getProperties()->setTitle($report->name);
		$spreadsheet->getProperties()->setCreator('Fidelo School; '.\System::getCurrentUser()->getName());
		$spreadsheet->getProperties()->setLastModifiedBy($spreadsheet->getProperties()->getCreator());
		$spreadsheet->getProperties()->setSubject(\Ext_Thebing_Format::LocalDate($period->getStartDate()).' – '.\Ext_Thebing_Format::LocalDate($period->getEndDate()));
		$spreadsheet->getProperties()->setCategory('Reporting');

		return response($exportService->finish(), 200, [
			'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'Content-Disposition' => 'attachment; filename="'.$name.'", test="abc"',
			'Cache-Control' => 'max-age=0'
		]);
	}
}

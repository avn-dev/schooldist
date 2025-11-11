<div class="p-4 text-gray-900 dark:text-gray-50">
	<!-- Content Header (Page header) -->
	<!--<section class="content-header">
		<nav class="flex" aria-label="Breadcrumb">
			<ol role="list" class="flex items-center space-x-4">
				<li>
					<a href="#" class="text-gray-500 hover:text-gray-500">
						<a href="#" class="text-sm font-medium text-gray-500 hover:text-gray-700">{'Start'|L10N:'Framework'}</a>
					</a>
				</li>
				<li>
					<i class="fas fa-chevron-right size-5 shrink-0 text-gray-500"></i>
					<a href="#" class="text-sm font-medium text-gray-500 hover:text-gray-700">{'Credits'|L10N:'Framework'}</a>
				</li>
			</ol>
		</nav>
	</section>-->
	
	<!-- Main content -->
	<section class="content">
		<table class="text-left">
			<tr>
				<th class="relative isolate pr-3 text-left text-sm font-semibold text-gray-500">{'Version'|L10N:'Framework'}:</th>
				<td class="relative text-sm font-medium">{$sVersion}</td>
			</tr>
			<tr>
				<th class="relative isolate pr-3 text-left text-sm font-semibold text-gray-500">{'PHP-Version'|L10N:'Framework'}:</th>
				<td class="relative text-sm font-medium">{$sPHPVersion}</td>
			</tr>
			{if $sLaravelVersion}
				<tr>
					<th class="relative isolate pr-3 text-left text-sm font-semibold text-gray-500">{'Laravel-Version'|L10N:'Framework'}:</th>
					<td class="relative text-sm font-medium">{$sLaravelVersion}</td>
				</tr>
			{/if}
			{if $sElasticSearchVersion}
				<tr>
					<th class="relative isolate pr-3 text-left text-sm font-semibold text-gray-500">{'Elasticsearch-Version'|L10N:'Framework'}:</th>
					<td class="relative text-sm font-medium">{$sElasticSearchVersion}</td>
				</tr>
			{/if}
			<tr>
				<th class="relative isolate pr-3 text-left text-sm font-semibold text-gray-500">{'Server'|L10N:'Framework'}:</th>
				<td class="relative text-sm font-medium">{$sHost}</td>
			</tr>
			<tr>
				<th class="relative isolate pr-3 text-left text-sm font-semibold text-gray-500">{'Datenbank'|L10N:'Framework'}:</th>
				<td class="relative text-sm font-medium">{$databaseName}</td>
			</tr>
			<tr>
				<th class="relative isolate pr-3 text-left text-sm font-semibold text-gray-500">{'Copyright'|L10N:'Framework'}:</th>
				<td class="relative text-sm font-medium">2001-{'Y'|date} {'by'|L10N:'Framework'} {System::d('software_producer')}, {'Köln, Deutschland, Alle Rechte vorbehalten'|L10N:'Framework'}</td>
			</tr>
			<tr>
				<th class="relative isolate pr-3 text-left text-sm font-semibold text-gray-500">{'Bibliotheken'|L10N:'Framework'}:</th>
				<td class="relative text-sm font-medium">
					jQuery (<a href="http://jquery.com/" target="_blank">jquery.com</a>)<br>
					Toastr (<a href="http://www.toastrjs.com" target="_blank">www.toastrjs.com</a>)
				</td>
			</tr>
			<tr>
				<th class="relative isolate pr-3 text-left text-sm font-semibold text-gray-500">{'Grafiken / Icons'|L10N:'Framework'}:</th>
				<td class="relative text-sm font-medium">
					<a href="https://fontawesome.com/" target="_blank">Font Awesome</a><br>
					<a href="https://www.flaticon.com/authors/freepik" target="_blank">Freepik</a><br>
				</td>
			</tr>
			<tr>
				<th class="relative isolate pr-3 text-left text-sm font-semibold text-gray-500">{'Externe Pakete'|L10N:'Framework'}:</th>
				<td class="relative text-sm font-medium">{$sCredits}</td>
			</tr>
		</table>
	</section>
</div>
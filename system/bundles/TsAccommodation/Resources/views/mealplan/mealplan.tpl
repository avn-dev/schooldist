{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="header"}
	<link rel="stylesheet" href="resources/css/mealplan.css">
{/block}

{block name="content"}
    <!-- Main content -->
	<div class="row">
		<div class="col-md-12">
			<div class="box">

				<div class="box-header">
                    <form class="form-inline select-form" action="{route name='TsAccommodation.meal_plan'}">
						<div class="pull-left form-group-sm">
                            <label class="filter_label" for="weekSelect">{'Woche'|L10N:'Thebing » Accommodation » Mealplan'} </label>
                            <select name="week" class="input-sm form-control" id="weekSelect" onchange="this.form.submit();">
                                {html_options options=$aWeeks selected=$sWeek}
                            </select>
                        </div>
                        <div class="pull-left form-group-sm">
                            <label class="filter_label" for="categorySelect">{'Kategorie'|L10N:'Thebing » Accommodation » Mealplan'} </label>
                            <select name="category" class="input-sm form-control" id="categorySelect" onchange="this.form.submit()">
                                {html_options options=$aAccommodationCategories selected=$sCategory}
                            </select>
						</div>
                        <div class="pull-left form-group-sm">
                            <label class="filter_label" for="statusSelect">{'Status'|L10N:'Thebing » Accommodation » Mealplan'} </label>
                            <select name="status" class="input-sm form-control" id="statusSelect" onchange="this.form.submit()">
                                {html_options options=$statusOptions selected=$selectedStatus}
                            </select>
						</div>
						<label class="filter_label" for="export_xls"> :: {'Export'|L10N:'Thebing » Accommodation » Mealplan'}</label>
						<button class="fa fa-file-excel-o export-icon icon form-control input-sm" name="export_xls" onclick="bPageLoaderDisabled = true" formaction="{route name='TsAccommodation.meal_plan_export'}">
						</button>
					</form>
				</div>
                <div class="box-body no-padding with-border">
					<div>
					<table class=" table table-bordered data-table">
						<thead>
						<tr>
							<th>{'Montag'|L10N:'Thebing » Accommodation » Mealplan'}</th>
							<th>{'Dienstag'|L10N:'Thebing » Accommodation » Mealplan'}</th>
							<th>{'Mittwoch'|L10N:'Thebing » Accommodation » Mealplan'}</th>
							<th>{'Donnerstag'|L10N:'Thebing » Accommodation » Mealplan'}</th>
							<th>{'Freitag'|L10N:'Thebing » Accommodation » Mealplan'}</th>
							<th>{'Samstag'|L10N:'Thebing » Accommodation » Mealplan'}</th>
                            <th>{'Sonntag'|L10N:'Thebing » Accommodation » Mealplan'}</th>
						</tr>
						</thead>
						<tbody>
						<tr>
                            {foreach from=$aMeals item=meal}
							<td>
								<div class="small-box bg-aqua">
									<div class="inner">
										<h3>{$meal['breakfast']}</h3>

										<p>x {'Frühstück'|L10N:'Thebing » Accommodation » Mealplan'}</p>
									</div>
								</div>
							</td>
                            {/foreach}
						</tr>
						<tr>
                            {foreach from=$aMeals item=meal}
                                <td>
                                    <div class="small-box bg-green">
                                        <div class="inner">
                                            <h3>{$meal['lunch']}</h3>

                                            <p>x {'Mittagessen'|L10N:'Thebing » Accommodation » Mealplan'}</p>
                                        </div>
                                    </div>
                                </td>
                            {/foreach}
						</tr>
						<tr>
                            {foreach from=$aMeals item=meal}
                                <td>
                                    <div class="small-box bg-yellow">
                                        <div class="inner">
                                            <h3>{$meal['dinner']}</h3>

                                            <p>x {'Abendessen'|L10N:'Thebing » Accommodation » Mealplan'}</p>
                                        </div>
                                    </div>
                                </td>
                            {/foreach}
						</tr>
						<tr>
							{foreach from=$aMeals item=meal}
								<td>
									{if $meal['additional']}
										<div class="small-box bg-red">
											<div class="inner">
												<!-- If abfrage ob details leer-->
												{foreach from=$meal['additional'] item=additional}
													{$additional}<br>
												{/foreach}
											</div>
										</div>
									{/if}
								</td>
							{/foreach}
						</tr>
						</tbody>
					</table>
					</div>

				</div>

			</div>
		</div>
	</div>

{/block}


{function name=printField}
    <div class="form-group {if $oSession->getFlashBag()->has($sName)} has-error{/if}">
        <label for="{$sName}">{$sLabel|L10N}</label>

        {if isset($aOld[$sName])} {assign var=sValue value=$aOld[$sName]} {else} {assign var=sValue value=$oEntity->$sName} {/if}

        {if $sType === "select"}

            <select id="{$sName}" name="{$sName}" class="form-control" value="{if isset($aOld[$sName])} {$aOld[$sName]} {else} {$oEntity->$sName} {/if}">
                {foreach $aOptions as $iKey=>$sOptionLabel}
                <option value="{$iKey}" {if $iKey == $sValue} selected="selected" {/if}>{$sOptionLabel}</option>
                {/foreach}
            </select>

        {else}

            <input type="{$sType|default:"text"}" id="{$sName}" name="{$sName}" class="form-control" value="{$sValue}">

        {/if}

        {if $oSession->getFlashBag()->has($sName)}
            <span class="help-block"> {$oSession->getFlashBag()->get($sName)|join|replace:"%s":$sLabel}</span>
        {/if}
    </div>
{/function}
<script src="https://use.fontawesome.com/0fce6f50fb.js"></script>

<style>
	td {
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}
	td:hover {
		overflow: visible;
	}
</style>

<div class="container">
    {if
    $sTask === 'agency_data' ||
    $sTask === 'update_agency' ||
    $sTask === 'agency_employee_data' ||
    $sTask === 'create_employee' ||
    $sTask === 'edit_employee' ||
    $sTask === 'inquiries' ||
    $sTask === 'participants' ||
    $sTask === 'classes' ||
    $sTask === 'attendance'
    }
        <nav class="navbar navbar-default">
            <div class="container-fluid">
                <!-- Brand and toggle get grouped for better mobile display -->
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                </div>
                <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                    <ul class="nav navbar-nav">
                        <li {if $sTask === 'agency_data'} class="active" {/if}>
                            <a href="{$sBaseURL}">{'Agency'|L10N}</a>
                        </li>
                        <li {if $sTask === 'agency_employee_data' || $sTask === 'edit_employee'} class="active" {/if}>
                            <a href="{$sBaseURL}task=showAgencyEmployeeData">{'Employees'|L10N} <span class="label label-danger">{$oAgency->getNumberOfContacts()}</span></a>
                        </li>
                        <li {if $sTask === 'inquiries'} class="active" {/if}>
                            <a href="{$sBaseURL}task=inquiries">{'Bookings'|L10N}</a>
                        </li>
                        <li {if $sTask === 'participants'} class="active" {/if}>
                            <a href="{$sBaseURL}task=participants">{'Participants'|L10N}</a>
                        </li>
                        <li {if $sTask === 'classes'} class="active" {/if}>
                            <a href="{$sBaseURL}task=classes">{'Classes'|L10N}</a>
                        </li>
                        <li {if $sTask === 'attendance'} class="active" {/if}>
                            <a href="{$sBaseURL}task=attendance">{'Attendance'|L10N}</a>
                        </li>
                        <li>
                            <a href="{$sBaseURL}logout=ok">{'Logout'|L10N}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    {/if}
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            {if $oMessage}
                {foreach $oMessage->getMessages('error') as $sMessage}
                    <div class="alert alert-danger" role="alert">{$sMessage}</div>
                {/foreach}
                {foreach $oMessage->getMessages('success') as $sMessage}
                    <div class="alert alert-success" role="alert">{$sMessage}</div>
                {/foreach}
            {/if}

            {if $sTask === 'login'}
                <form action="{$sBaseURL}" method="post">

                    <input type="hidden" name="table_number" value="13">
                    <input type="hidden" name="loginmodul" value="1">
                    <div class="form-group">
                        <label for="inputEmail3" class="col-sm-2 control-label">{'E-mail'|L10N}</label>
                        <input type="email" class="form-control" id="inputEmail3" name="customer_login_1">
                    </div>
                    <div class="form-group">
                        <label for="inputPassword3" class="col-sm-2 control-label">{'Password'|L10N}</label>
                        <input type="password" class="form-control" id="inputPassword3" name="customer_login_3">
                    </div>
                    <div class="form-group">
                        <a class="btn btn-default pull-left" href="{$sBaseURL}task=requestPassword">{'Password forgotten'|L10N}</a>
                        <button type="submit" class="btn btn-primary pull-right">{'Login'|L10N}</button>
                    </div>
                </form>
            {elseif $sTask === 'sendPassword'}
                <h2>{'Passwort vergessen'|L10N}</h2>
                <a href="{$sBaseURL}" class="btn btn-default">{'Back'|L10N}</a>
            {elseif $sTask === 'requestPassword'}
                <h2>{'Passwort vergessen'|L10N}</h2>
                <form class="form-horizontal" method="post" action="{$sBaseURL}task=sendPassword">
                    <div class="form-group">
                        <label for="inputEmail" class="col-sm-2 control-label">{'E-mail'|L10N}</label>
                        <div class="col-sm-10">
                            <input type="email" class="form-control" id="inputEmail" name="user">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-10">
                            <a href="{$sBaseURL}" class="btn btn-default">{'Zurück'|L10N}</a>
                            <button type="submit" class="btn btn-primary pull-right">{'Send password'|L10N}</button>
                        </div>
                    </div>
                </form>
            {elseif $sTask == 'changePassword' or $sTask == 'executeChangePassword'}
                <h2>{'Passwort ändern'|L10N}</h2>
                <form class="form-horizontal" method="post" action="{$sBaseURL}task=executeChangePassword&activation_key={$sActivationCode}">
                    <input type="hidden" name="hash" value="{$hash|escape}">
                    <div class="form-group">
                        <label for="inputEmail3" class="col-sm-2 control-label">{'Password'|L10N}</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" id="inputEmail3" name="new_password">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputEmail3" class="col-sm-2 control-label">{'Password confirmation'|L10N}</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" id="inputEmail3" name="new_password_repeat">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-primary pull-right">{'Save password'|L10N}</button>
                            <a href="{$sBaseURL}" class="btn btn-default pull-left">{'Back'|L10N}</a>
                        </div>
                    </div>
                </form>
            {elseif $sTask == 'agency_data'}
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-xs-12 col-sm-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                {'Overview: Agency'|L10N}
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                        <p>{'Name'|L10N}</p>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                        <p>{$oAgency->ext_1}</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                        <p>{'Abbreviation'|L10N}</p>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                        <p>{$oAgency->ext_2}</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                        <p>{'E-mail'|L10N}</p>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                        <p>{$oAgency->email}</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                        <p>{'Website'|L10N}</p>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                        <p>{$oAgency->ext_10}</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                        <p>{'Address'|L10N}</p>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                        <p>{$oAgency->ext_3}</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                        <p>{'ZIP City'|L10N}</p>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                        <p>{$oAgency->ext_4} {$oAgency->ext_5}</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                        <p>{'Country'|L10N}</p>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                        <p>{$oCountryFormat->format($oAgency->ext_6)}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="panel-footer">
                                <a href="{$sBaseURL}task=editAgencyData" class="btn btn-primary">{'Edit data'|L10N}</a>
                            </div>
                        </div>
                    </div>
                </div>
            {elseif $sTask === 'agency_employee_data'}
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-xs-12 col-sm-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <h4 class="pull-left">{'Overview: Employees'|L10N}</h4>
                                        <a class="btn btn-primary pull-right" href="{$sBaseURL}task=createEmployee">{'Add employee'|L10N}</a>
                                    </div>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                        <tr>
                                            <th>{'Firstname'|L10N}</th>
                                            <th>{'Surname'|L10N}</th>
                                            <th>{'Gender'|L10N}</th>
                                            <th>{'E-mail'|L10N}</th>
                                            <th>{'Phone'|L10N}</th>
                                            <th>{'Mobile'|L10N}</th>
                                            <th>{'Fax'|L10N}</th>
                                            <th>{'Skype'|L10N}</th>
                                            <th>{'Tasks'|L10N}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        {foreach $oAgency->getContacts(false, true) as $oEmployee}
                                            <tr>
                                                <td>
                                                    {$oEmployee->firstname}
                                                </td>
                                                <td>
                                                    {$oEmployee->lastname}
                                                </td>
                                                <td>
                                                    {$oGenderFormat->format($oEmployee->gender)}
                                                </td>
                                                <td>
                                                    {$oEmployee->email}
                                                </td>
                                                <td>
                                                    {$oEmployee->phone}
                                                </td>
                                                <td>
                                                    {$oEmployee->mobile}
                                                </td>
                                                <td>
                                                    {$oEmployee->fax}
                                                </td>
                                                <td>
                                                    {$oEmployee->skype}
                                                </td>
                                                <td>
                                                    <div class="modal fade" id="confirmDeleteEmployee_{$oEmployee->id}" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                                                        <div class="modal-dialog" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h4 class="modal-title" id="myModalLabel">{'Do you really want to delete this entry?'}</h4>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>{'Employee'|L10N}: {$oEmployee->firstname} {$oEmployee->lastname}</p>
                                                                    {'Please note that it is not possible to restore the data.'|L10N}
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-default" data-dismiss="modal">{'No'|L10N}</button>
                                                                    <a href="{$sBaseURL}task=deleteEmployee&employee={$oEmployee->id}" class="btn btn-primary">{'Yes, want to delete.'|L10N}</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <a class="pull-left" href="{$sBaseURL}task=editEmployee&employee={$oEmployee->id}">{'Edit'|L10N}</a>
                                                    <a class="pull-right" href="#" data-toggle="modal" data-employee="{$oEmployee->id}" data-target="#confirmDeleteEmployee_{$oEmployee->id}">{'Delete'|L10N}</a>
                                                </td>
                                            </tr>
                                        {/foreach}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            {elseif
              $sTask === 'edit_employee' ||
              $sTask === 'create_employee'
            }
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-xs-12 col-sm-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                {if $sTask === 'edit_employee'}
                                    {'Overview: Edit employee'|L10N}
                                {else}
                                    {'Overview: Add employee'|L10N}
                                {/if}
                            </div>
                            <form action="{$sBaseURL}{if $sTask === 'edit_employee'}task=updateEmployee&employee={$oContact->id}{else}task=addEmployee{/if}" method="post">
                                <div class="panel-body">

                                    {printField sLabel="Firstname" sName="firstname" oEntity=$oContact}
                                    {printField sLabel="Surname" sName="lastname" oEntity=$oContact}
                                    {printField sLabel="Gender" sName="gender" oEntity=$oContact sType="select" aOptions=[0=>'male'|L10N, 1=>'female'|L10N]}
                                    {printField sLabel="E-mail" sName="email" oEntity=$oContact sType="email"}
                                    {printField sLabel="Phone" sName="phone" oEntity=$oContact}
                                    {printField sLabel="Mobile" sName="mobile" oEntity=$oContact}
                                    {printField sLabel="Fax" sName="fax" oEntity=$oContact}
                                    {printField sLabel="Skype" sName="skype" oEntity=$oContact}

                                </div>
                                <div class="panel-footer">
                                    <a class="btn btn-default" href="{$sBaseURL}task=showAgencyEmployeeData">{'Cancel'|L10N}</a>
                                    <input type="submit" class="btn btn-primary pull-right" value="{'Save'|L10N}">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            {elseif $sTask === 'update_agency'}
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-xs-12 col-sm-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                {'Overview: Edit agency'|L10N}
                            </div>
                            <form action="{$sBaseURL}task=updateAgencyData" method="post">
                                <div class="panel-body">

                                    {printField sLabel="Name" sName="ext_1" oEntity=$oAgency}
                                    {printField sLabel="Abbreviation" sName="ext_2" oEntity=$oAgency}
                                    {printField sLabel="E-mail" sName="email" oEntity=$oAgency sType="email"}
                                    {printField sLabel="Website" sName="ext_10" oEntity=$oAgency}
                                    {printField sLabel="Address" sName="ext_3" oEntity=$oAgency}

                                    <div class="row">
                                        <div class="col-lg-6 col-md-6 col-xs-12 col-sm-12">

                                            {printField sLabel="ZIP" sName="ext_4" oEntity=$oAgency}

                                        </div>
                                        <div class="col-lg-6 col-md-6 col-xs-12 col-sm-12">

                                            {printField sLabel="City" sName="ext_5" oEntity=$oAgency}

                                        </div>
                                    </div>

                                    {printField sLabel="Country" sName="ext_6" oEntity=$oAgency sType="select" aOptions=$aCountries}

                                </div>
                                <div class="panel-footer">
                                    <a class="btn btn-default" href="{$sBaseURL}task=showAgencyData">{'Back'|L10N}</a>
                                    <input type="submit" class="btn btn-primary pull-right" value="{'Save'|L10N}">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            {elseif $sTask === 'inquiries'}
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-xs-12 col-sm-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                {'Bookings'|L10N}
                            </div>
                            <div class="panel-body">
                                <table class="table table-bordered">
                                <tr>
                                    <th>
                                        <form class="form-inline" method="post" id="DateRangeForm">
                                            <input type="hidden" name="filter_from">
                                            <input type="hidden" name="filter_until">
                                            <input type="hidden" name="page" id="pagenumber">
                                            <label>{'Period'|L10N}:</label><input name="daterangepicker" class="form-control">
                                            <script>
                                                $(function() {
                                                	var oDatePicker = $('input[name="daterangepicker"]');
													oDatePicker.daterangepicker({
                                                    	locale: {
														    format: '{$sDatePickerFormat}'
													    },
														startDate: moment('{$dFrom}'),
														endDate: moment('{$dUntil}')
                                                    });
													oDatePicker.change(function() {
														var oPicker = oDatePicker.data('daterangepicker');
														$('input[name=filter_from]').val(oPicker.startDate.format('YYYY-MM-DD'));
														$('input[name=filter_until]').val(oPicker.endDate.format('YYYY-MM-DD'));
													});
													oDatePicker.change();
                                                })
                                            </script>
                                            <input type="submit" class="btn btn-primary pull-right" value="{'Search'|L10N}">
                                        </form>
                                    </th>
                                </tr>
                                </table>
                                <div class="table-responsive">
                                    <table class="table table-bordered" style="table-layout: fixed;">
                                        <thead>
                                        <tr>
                                            <th style="width: auto;">{'Name'|L10N}</th>
                                            <th style="width: 120px">{'Birthday'|L10N}</th>
                                            <th style="width: 200px">{'Course'|L10N}</th>
                                            <th style="width: 250px">{'Accommodation'|L10N}</th>
                                            <th style="width: 120px">{'Created at'|L10N}</th>
                                            <th style="width: 100px" class="text-right">{'Amount'|L10N}</th>
                                            <th style="width: 100px" class="text-right">{'Outstanding'|L10N}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
										{assign var=fTotalAmount value=0}
										{assign var=fTotalPending value=0}
                                        {foreach $aInquiries as $aInquiry}
                                            <tr>
                                                <td>
                                                    {$aInquiry['fields']['customer_lastname'][0]|cat:', '|cat:$aInquiry['fields']['customer_firstname'][0]}
                                                    {if $aInquiry['fields']['customer_gender_original'][0] == 1}
                                                        <i class="fa fa-mars pull-right"></i>
                                                    {elseif $aInquiry['fields']['customer_gender_original'][0] == 2}
                                                        <i class="fa fa-venus pull-right"></i>
                                                    {else}
                                                        <i class="fa fa-genderless pull-right"></i>
                                                    {/if}
                                                </td>
                                                <td>
                                                    {$aInquiry['fields']['customer_birthday_original'][0]|date_format:$sDateFormat}
                                                </td>
                                                <td>
                                                    {$aInquiry['fields']['course_fulllist'][0]}
                                                </td>
                                                <td>
                                                    {$aInquiry['fields']['accommodation_'|cat:$sLanguage][0]}
                                                </td>
                                                <td>
                                                    {$aInquiry['fields']['created_original'][0]|date_format:$sDateFormat}
                                                </td>
                                                <td class="text-right">
                                                    {$aInquiry['fields']['amount'][0]}
                                                </td>
                                                <td class="text-right">
                                                    {$aInquiry['fields']['amount_open'][0]}
                                                </td>
                                            </tr>
											{assign var=fTotalAmount value=$fTotalAmount+$aInquiry['fields']['amount_original'][0]}
											{assign var=fTotalPending value=$fTotalPending+$aInquiry['fields']['amount_open_original'][0]}
                                        {/foreach}
                                        </tbody>
										<tfoot>
											<th colspan="5">{'Total'|L10N}</th>
											<th class="text-right">{$oCurrencyFormat->format($fTotalAmount)}</th>
											<th class="text-right">{$oCurrencyFormat->format($fTotalPending)}</th>
										</tfoot>
                                    </table>
                                    {if $aResult['total'] > $aResult['limit']}
                                        <ul class="pagination">
                                            {if $aResult['page'] != 1}
                                                <li>
                                                    <a href="#" aria-label="Previous" onclick="$('#pagenumber').val('{$aResult['page'] - 1}'); $('#DateRangeForm').submit()">
                                                        <span aria-hidden="true">&laquo;</span>
                                                    </a>
                                                </li>
                                            {/if}
                                            {for $i = 1; $i <= ceil($aResult['total']/$aResult['limit']); $i++}
                                                <li class="{if $aResult['page'] == $i}active{/if}">
                                                    <a href="#" onclick="$('#pagenumber').val('{$i}'); $('#DateRangeForm').submit()">
                                                        {$i}
                                                    </a>
                                                </li>
                                            {/for}
                                            {if $aResult['page'] != ceil($aResult['total']/$aResult['limit'])}
                                                <li>
                                                    <a href="#" aria-label="Next" onclick="$('#pagenumber').val('{$aResult['page'] + 1}'); $('#DateRangeForm').submit()">
                                                        <span aria-hidden="true">&raquo;</span>
                                                    </a>
                                                </li>
                                            {/if}
                                        </ul>
                                    {/if}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
			
            {elseif $sTask === 'participants'}
				
				<div class="table-responsive">
					<table class="table table-bordered" style="table-layout: fixed;">
						<thead>
						<tr>
							<th style="width: auto;">{'Name'|L10N}</th>
							<th style="width: 80px">{'Language'|L10N}</th>
							<th style="width: 80px">{'Class'|L10N}</th>
							<th style="width: 200px">{'E-mail'|L10N}</th>
							<th style="width: 120px">{'Birthdate'|L10N}</th>
							<th style="width: 80px">{'Phone'|L10N}</th>
							<th style="width: 100px">{'Course start'|L10N}</th>
							<th style="width: 80px">{'Initial level'|L10N}</th>
							<th style="width: 80px">{'Current level'|L10N}</th>
						</tr>
						</thead>
						<tbody>
							{foreach $participants as $participant}
								<tr>
									<td>{$participant.lastname}, {$participant.firstname}</td>
									<td>{$courseLanguages[$participant.courselanguage_id]}</td>
									<td>{$participant.classes}</td>
									<td>{$participant.email}</td>
									<td>{$participant.birthday|date_format:"%d.%m.%Y"}</td>
									<td>{$participant.phone}</td>
									<td>{$participant.service_from|date_format:"%d.%m.%Y"}</td>
									<td>{$externalLevels[$participant.level_id]}</td>
								</tr>
							{foreachelse}
								
							{/foreach}
						</tbody>
					</table>
				</div>
				
            {elseif $sTask === 'classes'}
				
				<div class="table-responsive">
					<table class="table table-bordered" style="table-layout: fixed;">
						<thead>
						<tr>
							{*<th style="width: 80px;">{'Language'|L10N}</th>*}
							<th style="width: auto;">{'Class'|L10N}</th>
							<th style="width: 120px;">{'Category'|L10N}</th>
							<th style="width: 80px;">{'Level'|L10N}</th>
							<th style="width: 200px;">{'Participants'|L10N}</th>
							<th style="width: 200px;">{'Weekdays'|L10N}</th>
							<th style="width: 80px;">{'Room'|L10N}</th>
							<th style="width: 150px;">{'Teacher'|L10N}</th>
							<th style="width: 200px;">{'Comment'|L10N}</th>
						</tr>
						</thead>
						<tbody>
							{foreach $classes as $class}
								{$column = null}
								<tr>
									<td>{$class.name}</td>
									<td>{$class.categories}</td>
									<td>{$internalLevels[$class.level_id]}</td>
									<td>{$class.participants}</td>
									<td>{$dayFormat->format($class.days, $column, $class)}</td>
									<td>{$class.rooms}</td>
									<td>{$teachersFormat->format($class.teachers, $column, $class)}</td>
									<td>{$class.block_description}</td>
								</tr>
							{foreachelse}
								
							{/foreach}
						</tbody>
					</table>
				</div>
				
            {elseif $sTask === 'attendance'}

				<div class="table-responsive">
					<table class="table table-bordered" style="table-layout: fixed;">
						<thead>
						<tr>
							<th style="width: auto;">{'Name'|L10N}</th>
							<th style="width: 80px;">{'Language'|L10N}</th>
							<th style="width: 80px;">{'Class'|L10N}</th>
							<th style="width: 100px;">{'Course start'|L10N}</th>
							<th style="width: 100px;">{'Course end'|L10N}</th>
							<th style="width: 60px;">{'Present'|L10N}</th>
							<th style="width: 60px;">{'Sick'|L10N}</th>
							<th style="width: 60px;">{'Work'|L10N}</th>
							<th style="width: 60px;">{'Holiday'|L10N}</th>
							<th style="width: 60px;">{'Private'|L10N}</th>
							<th style="width: 60px;">{'Unexcused'|L10N}</th>
							<th style="width: 100px;">{'Percentage'|L10N}</th>
							<th style="width: 80px;">{'Lessons'|L10N}</th>
						</tr>
						</thead>
						<tbody>
							{foreach $attendees as $attendee}
								{$column = null}
								<tr>
									<td>{$attendee.lastname}, {$attendee.firstname}</td>
									<td>{$courseLanguages[$attendee.courselanguage_id]}</td>
									<td>{$attendee.classes}</td>
									<td>{$attendee.from|date_format:"%d.%m.%Y"}</td>
									<td>{$attendee.until|date_format:"%d.%m.%Y"}</td>
									<td class="text-right">0</td>
									<td class="text-right">0</td>
									<td class="text-right">0</td>
									<td class="text-right">0</td>
									<td class="text-right">0</td>
									<td class="text-right">0</td>
									<td class="text-right">{$attendee.attendance_percentage|number_format:0} %</td>
									<td class="text-right">{$attendee.attendance_lessons / 45}</td>
								</tr>
							{foreachelse}
								
							{/foreach}
						</tbody>
					</table>
				</div>

            {/if}
        </div>
    </div>
</div>

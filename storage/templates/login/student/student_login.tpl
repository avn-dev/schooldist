{oMessage->getMessages p1='error' assign='aErrors'}
{oMessage->getMessages p1='hint' assign='aHints'}
{oMessage->getMessages p1='info' assign='aInfos'}
<!-- Navigation -->
<nav class="navbar navbar-inverse-lg navbar-light bg-light navbar-default">
	<div class="navbar-header">
		<span class="navbar-brand" href="#">{'Schüler-Portal'|L10N}</span>
	</div>
	{if $iLoggedIn == 1}
		<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
			<span class="sr-only">Toggle navigation</span>
		</button>
		<ul class="nav navbar-nav">
			<li class="active">
				<form class="navbar-form navbar-left" action="">
					<div class="form-group">
						<select class="booking-select form-control" name="student_booking" onChange="this.form.submit();">
							{foreach $aBookings as $sBooking}
								{assign var=sSelected value=''}
								{if $iCurrentBooking == $sBooking@key}
									{assign var=sSelected value='selected'}
								{/if}
								<option value="{$sBooking@key}" {$sSelected} > {$sBooking}</option>
							{/foreach}
						</select>
					</div>
				</form>
			</li>
			{this->getNavItem task='showIndexData' title='Start'}
			{this->getNavItem task='showSchoolData' title='Schule'}
			{*{this->getNavItem task='showGeneralData' title='Daten'}*}
			{*{this->getNavItem task='showCourseData' title='Kurse'}*}
			{*{this->getNavItem task='showAccommodationData' title='Unterkünfte'}*}
			{*{this->getNavItem task='showTransferData' title='Transfer'}*}
			{*{this->getNavItem task='showInsuranceData' title='Versicherungen'}*}

		</ul>
		<a class="pull-right navbar-brand" href="{$sBaseURL}task=logout&logout=ok">{'Logout'|L10N}</a>
	{/if}
</nav>
<br>
{if
$sTask == 'showPersonalData' ||
$sTask == 'showBookingData' ||
$sTask == 'showDocuments' ||
$sTask == 'showMails'
}
	<!-- Navi General Data -->
	<div class="sub-navi">
		<ul class="student_sub_navigation">
			{this->getNavItem task='showPersonalData' title='Personal Data'}
			{this->getNavItem task='showBookingData' title='Booking Data'}
			{this->getNavItem task='showDocuments' title='Documents'}
			{this->getNavItem task='showMails' title='Mails'}
		</ul>
	</div>
{/if}
<div id="student_login" class="login_{$sTask}">
	{if $sTask == 'showPersonalData'}
	<!-- Show Personal Data -->
	<div class="content">
		<div class="content-left grid3" style="width: 280px">
			<div class="box">
				<div class="box-head">{'Contact details'|L10N}</div>
				<div class="box-content">{$sContactDetails}</div>
			</div>
			<div class="box">
				<div class="box-head">{'Emergency contact'|L10N}</div>
				<div class="box-content">{$sEmergencyDetails}</div>
			</div>
		</div>
		<div class="content-right grid3" style="width: 280px;margin:0 60px 0 60px;">
			<div class="box">
				<div class="box-head">{'Address details'|L10N}</div>
				<div class="box-content">{$sAddressDetails}</div>
			</div>
			<div class="box">
				<div class="box-head">{'Billing address'|L10N}</div>
				<div class="box-content">{$sBillingDetails}</div>
			</div>
		</div>
		<div class="content-right grid3" style="width: 225px">
			<div class="box">
				<div class="box-head">{'Uploads'|L10N}</div>
				<div class="box-content">{$sUploads}</div>
			</div>
		</div>
	</div>
	{elseif $sTask == 'showBookingData'}
	<!-- Show Booking Data -->
	<div class="content">
		<div class="content-left">
			<!-- Courses -->
			<div class="box">
				<div class="box-head">{'Courses'|L10N}</div>
				<div class="box-content">
					{$sCoursesData}
				</div>
			</div>
			<!-- Transfer-->
			<div class="box">
				<div class="box-head">{'Transfer'|L10N}</div>
				<div class="box-content">
					{$sTransferData}
				</div>
			</div>
		</div>
		<div class="content-right">
			<!-- Accommodations -->
			<div class="box">
				<div class="box-head">{'Accommodation'|L10N}</div>
				<div class="box-content">
					{$sAccommodationsData}
				</div>
			</div>
		</div>
	</div>
	{elseif $sTask == 'showDocuments'}
	<!-- Show Documents -->
	<div class="content">
		<div class="content-left">
			<div class="box box-table">
				<div class="box-head">{'Invoice Documents'|L10N}</div>
				<div class="box-content">
					<div>
						{$sInvoiceTable}
					</div>
				</div>
			</div>
			<!-- Payments -->
			<div class="box box-table">
				<div class="box-head">{'Payment Receipts'|L10N}</div>
				<div class="box-content">
					<div>
						{$sPaymentTable}
					</div>
				</div>
			</div>
		</div>
		<div class="content-right">
			<div class="box box-table">
				<div class="box-head">{'General Documents'|L10N}</div>
				<div class="box-content">
					<div>
						{$sAdditionalTable}
					</div>
				</div>
			</div>
		</div>
	</div>
	{elseif $sTask == 'login'}
	<!-- --------------------------------------------------------------
 *	Login Form
 ------------------------------------------------------------------ -->
	<div class="content">
		<div class="box grid3" style="width: 290px">
			<div class="box-content">
				<form action="{$sBaseURL}" method="post" style="margin: 0px;">
					<input type="hidden" value="1" name="loginmodul">
					<input type="hidden" value="{$iTableId}" name="table_number">
					<fieldset>
						<div class="form-row">
							<!-- Username -->
							<label for="input_username">{'Benutzer'|L10N}</label>
							<input type="text" class="form-control" id="input_username" name="customer_login_1"/>
						</div>
						<div class="form-row">
							<!-- Password -->
							<label for="input_password">{'Passwort'|L10N}</label>
							<input type="password" class="form-control" id="input_password" name="customer_login_3"/>
						</div>
						<span class="row">
							<a class="btn-block btn-default form-control" style="text-align: center" href="{$sBaseURL}task=requestPassword">{'Passwort vergessen?'|L10N}</a>
						</span>
						<br>
						<!-- submit -->
						<button type="submit" class="pull-right form-control btn btn-primary sc_gradient" >{'Login'|L10N}</button>
					</fieldset>
				</form>

			</div>
		</div>
	</div>
	{elseif $sTask == 'requestPassword'}
	<!-- --------------------------------------------------------------
*	Resend password
------------------------------------------------------------------ -->
<div class="content">
	<div class="box grid3" style="width: 290px">
		<div class="box-content">
			<form action="{$sBaseURL}task=sendPassword" method="post" style="margin: 0px;">
				<fieldset>
					<div class="student_fields student_form">
						<!-- EMail -->
						<div class="form-row">
							<!-- Username -->
							<label for="input_username">{'Benutzer'|L10N}</label>
							<input type="text" class="form-control" id="input_username" name="user"/>
						</div>
					</div>
					<!-- submit -->
					<span class="row">
						<a class="btn-block btn-default form-control" style="text-align: center" href="{$sBaseURL}">{'Zurück'|L10N}</a>
						<br>
						<button type="submit" class="btn-block btn-primary form-control" >{'Password anfordern'|L10N}</button>
					</span>
				</fieldset>
			</form>
		</div>
	</div>
</div>
	{elseif $sTask == 'sendPassword'}
	<!-- --------------------------------------------------------------
*	Password successfully
------------------------------------------------------------------ -->
	{elseif $sTask == 'showIndexData'}
	<!-- Show Index Data -->
	<div class="row">
		<div class="col-sm-8">
			<div>
				<p>Dear {$sFirstname},</p>
			</div>
			<div class="box box-table">
				<div class="box-head">{'Buchungsübersicht'|L10N}</div>
				<p>
					{$sBookingOverview}
				</p>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="box box-table">
				<div class="box-head">{'News'|L10N}</div>
				<br/>
				<hr class="hr_line" />
				<p style="text-align: center;">Your course is going to start in <span style="font-weight: bold;">{$iCourseCounter}</span> days</p>
			</div>
		</div>
	</div>
	{elseif $sTask == 'showSchoolData'}
	<!-- Show School Data -->
	<div class="content">
		<div style="width: 72%; float: left;">
			<h3>{'Generelle Informationen'|L10N}</h3>
			<h4>{'Adresse'|L10N}</h4>
			<p>
				{$sSchoolAddress} {$sSchoolAddressAdditional}<br/>
				{$sSchoolZip} {$sSchoolCity} {$sSchoolCountry}<br/>
				<br/>
				{'Telefon1'|L10N}: {$sSchoolPhone1}<br/>
				{'Telefon2'|L10N}: {$sSchoolPhone2}<br/>
				{'Fax'|L10N}: {$sSchoolMail}<br/>
				{'EMail'|L10N}: {$sSchoolMail}<br/>
				<br/>
				<a href="{$sSchoolUrl}">{$sSchoolUrl}</a>
				<br/>
			</p>
		</div>
		<div style="float:left; width: 222px; margin-left: 20px;">

		</div>
	</div>
	{elseif $sTask == 'showCourseData'}
	<!-- Show Kurs Data -->
	<div class="content">
		<div class="box box-table">
			<div class="box-head">
				<label style="float:left">
					{'Schedule'|L10N}
				</label>
				<div class="filter-course" style="float:right;">{$sFilterFormHtml}</div>
			</div>
			<div class="box-content">
				<div class="course-table">
					{$sTuitionTable}
				</div>
			</div>
		</div>
	</div>

	{elseif $sTask == 'showAccommodationData'}
	<!-- Show Accommodation Data -->
	<div class="content">
		<div class="box">
			<div class="box-head">{'Generelle Informationen'|L10N}</div>
			<p>{$sAccommodationDescription}</p>
		</div>
		<div style="float: left; width: 70%">
			<div class="box">
				<div class="box-head">{'Eindrücke'|L10N}</div>
			</div>
			<!-- "next slide" button -->
			<a class="forward">{'vor'|L10N}</a>
			<!-- the tabs -->
			<div class="slidetabs">
				<a href="#"/>
				<a href="#"/>
				<a href="#"/>
				<a href="#"/>
				<a href="#"/>
			</div>
		</div>
	</div>

	<div style="float:left; width: 240px; margin-left: 20px;">
		<div class="box">
			<div class="box-head">{'Unterkunft'|L10N}</div>
			<p>
				{$sAccommodationName}<br/>
				{$sAccommodationStreet}<br/>
				{$sAccommodationZip} {$sAccommodationCity}
			</p>
			<p>
				{'Telefon'|L10N}: {$sAccommodationPhone}<br/>
				{'Email'|L10N}: {$sAccommodationMail}
			</p>
		</div>
		<div class="box">
			<div class="box-head">{'Dokumente'|L10N}</div>
			<p>
				{$sAccommodationDocuments}
			</p>
		</div>
	</div>
</div>
{elseif $sTask == 'showTransferData'}
<!-- Show Transfer Data -->
<!-- Show Transfer Data -->
	<div class="content">
		<div class="content-left">
			<div class="box">
				<div class="box-head">{'Transfer'|L10N}</div>
				<div class="box-content">
					{$sTransferForm}
				</div>
			</div>
		</div>
		<div class="content-right">
			<div class="box">
				<div class="box-head">{'Provider Informationen'|L10N}</div>
				<div class="box-content">
					<p>The driver holding "<span style="font-weight:bold">Camden school</span>" sign will be waiting for you at the meeting hall of London Airpot on10/11/2011  since 01:00 pm. </p>
					<p>If the flight arrives too early, our representative might not be at the airport yet, in this case please wait until the scheduled arrival time of the aircraft and/or call our <span style="font-weight:bold">emergency telephone number</span> +44201259877 (English speaking representative). </p>
					<p>If you experience any kind of problems, please call our emergency number immediately. Please <span style="font-weight:bold">bring your passport and immigration card</span> on your first day to the school, which will start on 12/11/2011 at 08:00 am with introduction and group placement tests.<br/>
						If there are any changes in the timing you will be informed upon arrival.</p>
				</div>
			</div>
		</div>
	</div>
{elseif $sTask == 'showInsuranceData'}
<!-- Show Insurance Data -->
	<div class="content">
		<div class="content-left">
			<div class="box">
				<div class="box-head">{'Generelle Informationen'|L10N}</div>
				<div class="box-content">
					{$sInsuranceDetails}
				</div>
			</div>
		</div>
	</div>
{elseif $sTask == 'resetPassword'}
<!-- Password Reset -->
<div class="content">
    <div class="box grid3" style="width: 290px">
        <h3 class="box-head">{'Bitte geben Sie neues Passwort ein'|L10N}</h3>
		<form style="margin: 0px;" method="post" action="{$sBaseURL}task=executeChangePassword">
			<fieldset>
				<div class="student_fields student_form">
					<!-- EMail -->
					<div class="divFormElement">
						<label for="new_password">{'Passwort'|L10N}</label>
						<input type="password" name="new_password" id="new_password" class="form-control" />
					</div>
					<div class="divFormElement">
						<label for="new_password_repeat">{'Passwort wiederholen'|L10N}</label>
						<input type="password" name="new_password_repeat" id="new_password_repeat" class="form-control" />
					</div>
				</div>
				<input type="hidden" name="hash"  value="{$hash}"/>
				<!-- submit -->
                <span class="row">
                    <button type="submit" class="btn btn-primary">{'Passwort ändern'|L10N}</button>
                </span>
			</fieldset>
		</form>
    </div>
</div>
{elseif $sTask == 'showMails'}
<!-- Communication -->
	<div class="content">
		<div class="box box-table">
			<div class="search">
				<form action="{$sBaseUrl}task=showMails" method="post">
					<label>Search</label>
					<input type="text" name="search" value="{$sSearch}" />
					<div class="clearer"/>
				</form>
			</div>
			{$sMailsData}
		</div>
	</div>
{/if}
<!-- Reports -->
<div class="student_errors">
	<ul>
		{foreach $aErrors as $sError}
			<li>{'Fehler'|L10N}: {$sError}</li>
		{/foreach}
		{foreach $aHints as $sHint}
			<li>{'Hinweis'|L10N}: {$sHint}</li>
		{/foreach}
		{foreach $aInfos as $sInfo}
			<li>{'Info'|L10N}: {$sInfo}</li>
		{/foreach}
	</ul>
</div>
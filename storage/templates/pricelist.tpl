<div class="container">

		<h2>Pricelist of {$oSchool->getName()}</h2>

		{foreach from=$aSeasons item=oSeason}

			{'Saison'|L10N}: {$oSeason->getProperty('valid_from')|date_format:"%d/%m/%y"} - {$oSeason->getProperty('valid_until')|date_format:"%d/%m/%y"}

			{foreach from=$aCourses item=oCourse}
			
				{if $oCourse->getName() == 'Private lessons'}
					{assign var=aWeeks value=[1=>'1 lessons',10=>'10 lessons',15=>'15 lessons']}
				{elseif $oCourse->getName() == 'Exam prep CAE'}
					{assign var=aWeeks value=[6=>'6. weeks',12=>'12. weeks']}
				{else}
					{assign var=aWeeks value=[1=>'1. week',2=>'2. weeks',3=>'3. weeks',4=>'4. weeks']}
				{/if}

				{assign var=aCourseAccommdationCombinations value=$oCourse->getAccommodationCombinations()}
				
				<div>
					<h2>{$oCourse->getName()}</h2>
					
					{if $oCourse->getProperty('avaibility') == \Ext_Thebing_Tuition_Course::AVAILABILITY_UNDEFINED OR $oCourse->getProperty('avaibility') == \Ext_Thebing_Tuition_Course::AVAILABILITY_ALWAYS OR $oCourse->getProperty('avaibility') == \Ext_Thebing_Tuition_Course::AVAILABILITY_ALWAYS_EACH_DAY}
						{'Always available'|L10N}
					{else}
						{'Start dates: '|L10N}
						{if $oSeason->getFrom() < $oNow}
							{assign var=from value=$oNow}
						{else}
							{assign var=from value=$oSeason->getFrom()}
						{/if}
						
						{assign var=until value=$oNow->copy()->addMonths(6)}
	
						{if $oSeason->getUntil() > $until}
							{assign var=until value=$until}
						{else}
							{assign var=until value=$oSeason->getUntil()}
						{/if}
						
						{foreach $oCourse->getStartDatesWithDurations($from, $until) as $startDate}
							{$startDate->start|date_format:"d.m.Y"}
							{if $startDate->minDuration == $startDate->maxDuration}
								({$startDate->minDuration} {'weeks'|L10N})
							{else}
								({$startDate->minDuration}-{$startDate->maxDuration} {'weeks'|L10N})
							{/if}
							{if !$startDate@last}, {/if} 
						{/foreach}
					{/if}
					<div class="table-responsive">
						<table class="table table-condensed">
							<thead>
								<tr>
									<th style="width:35%;">{'%d lessons à %d minutes per week'|L10N|sprintf:\Illuminate\Support\Arr::first($oCourse->getProperty('lessons_list')):$oCourse->getProperty('lesson_duration')}</th>
									{foreach $aWeeks as $iWeek=>$sWeek}
									<th>{$sWeek}</th>
									{/foreach}
								</tr>
							</thead>
							<tbody>
								<tr>
									<th>{'Course only'|L10N}</th>
									{foreach $aWeeks as $iWeek=>$sWeek}
									<td class="text-right">{$oCourse->getPrice($oSeason, $iWeek)|number_format:2:",":"."} {$oSchool->getCurrency()->getProperty('sign')}</td>
									{/foreach}
								</tr>
								{foreach from=$aAccommodationCombinations item=oAccommodationCombination}
								{if empty($aCourseAccommdationCombinations) || in_array($oAccommodationCombination->getKey(), $aCourseAccommdationCombinations)}
								<tr>
									<th>{$oAccommodationCombination->getLabel()}</th>
									{foreach $aWeeks as $iWeek=>$sWeek}
									<td class="text-right">{($oCourse->getPrice($oSeason, $iWeek) + $oAccommodationCombination->getPrice($oSchool, $oSeason, $iWeek))|number_format:2:",":"."} {$oSchool->getCurrency()->getProperty('sign')}</td>
									{/foreach}
								</tr>
								{/if}
								{/foreach}
							</tbody>
						</table>
					</div>

			</div>

		{/foreach}
		
	{/foreach}

</div>	

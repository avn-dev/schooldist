
	<div class="box box-default">
		<div class="box-header with-border">
			<h3 class="box-title">{$title|L10N}</h3>
		</div>

		<div class="box-body table-responsive no-padding">

			<table class="table table-striped table-hover">
				<tr>
					{if $type == 'requests' OR $type == 'closed_requests'}
						<th style="width:100px;">{'Requested'|L10N}</th>
					{/if}
					{if $type == 'allocations'}
						{if in_array('image', $existingColumns)}
							<th style="width:150px;">{'Image'|L10N}</th>
						{/if}
					{/if}

					<th style="width:auto;">{'Lastname, Firstname'|L10N}</th>

					{if in_array('period', $existingColumns)}
					<th style="width:180px;">{'Period'|L10N}</th>
					{/if}

					{if in_array('school', $existingColumns)}
					<th style="width:250px;">{'School'|L10N}</th>
					{/if}

					{if in_array('courses', $existingColumns)}
					<th style="width:250px;">{'Courses'|L10N}</th>
					{/if}

					{if in_array('accommodation', $existingColumns)}
					<th style="width:250px;">{'Accommodation'|L10N}</th>
					{/if}

					{if in_array('wishes', $existingColumns)}
					<th style="width:200px;">{'Wishes'|L10N}</th>
					{/if}

					{if in_array('arrival', $existingColumns)}
					<th style="width:300px;">{'Arrival / Departure'|L10N}</th>
					{/if}

					{if in_array('arrival_with_details', $existingColumns)}
						<th style="width:300px;">{'Arrival / Departure with transfer provider details'|L10N}</th>
					{/if}

					{if $type == 'closed_requests'}
						<th style="width:150px;">{'Reason'|L10N}</th>
					{/if}
					{if $type == 'requests'}
						<th style="width:150px;">{'Actions'|L10N}</th>
					{/if}

				</tr>
				{foreach from=$items item=item}
					
					{if $item instanceof \TsAccommodation\Entity\Request}
						{$recipient = $item->getJoinedObjectChildByValue('recipients', 'accommodation_provider_id', $accommodation->id)}
					{/if}

					{assign var=inquiryAccommodation value=$item->getInquiryAccommodation()}
					{assign var=inquiry value=$inquiryAccommodation->getInquiry()}
					{assign var=matching value=$inquiry->getMatchingData()}
					{assign var=student value=$inquiry->getTraveller()}
					{assign var=school value=$inquiry->getSchool()}
					{assign var=dateFormat value=Ext_Thebing_Format::getDateFormat($school->id)}

					<tr>
						{if $item instanceof \TsAccommodation\Entity\Request}
						<td>{$item->created|date_format:$dateFormat}</td>
						{/if}
						{if $type == 'allocations' && in_array('image', $existingColumns)}
							<td style="vertical-align: top; text-align: center;">
								{if $student->getPhoto()}
									<img src="{route name="TsAccommodationLogin.accommodation_profile_picture" allocationId=$item->id}" />
								{/if}
							</td>
						{/if}

						<td style="vertical-align: top;">
							{$student->lastname}, {$student->firstname}<br/>
							{if $student->birthday}
								{$student->birthday|date_format:$dateFormat},
							{/if}
							{if $student->gender == 1}♂{elseif $student->gender == 2}♀{else}⚥{/if}
							{if $student->nationality}
								{$student->nationality} 
							{/if}
						</td>

						{if in_array('period', $existingColumns)}
							<td>
								{if $item instanceof \Ext_Thebing_Accommodation_Allocation}
									{$item->from|date_format:$dateFormat} -
									{$item->until|date_format:$dateFormat}
								{elseif $item instanceof \TsAccommodation\Entity\Request}
									{$inquiryAccommodation->from|date_format:$dateFormat} -
									{$inquiryAccommodation->until|date_format:$dateFormat}
								{/if}
							</td>
						{/if}
						{if in_array('school', $existingColumns)}
							<td>
								{$inquiry->getSchool()->getName()}
							</td>
						{/if}
						{if in_array('courses', $existingColumns)}
							<td>
								{foreach $inquiry->getCourses() as $inquiryCourse}
									{$inquiryCourse->getInfo()}<br>
								{foreachelse}
									{'No courses'|L10N}
								{/foreach}
							</td>
						{/if}
						{if in_array('accommodation', $existingColumns)}
							<td>
								{foreach $inquiry->getAccommodations() as $inquiryAccommodation}
									{$inquiryAccommodation->getInfo()}<br>
								{foreachelse}
									{'No accommodations'|L10N}
								{/foreach}
							</td>
						{/if}
						{if in_array('wishes', $existingColumns)}
							<td style="vertical-align: top;">

								{assign var=matchingYesNo value=\Ext_Thebing_Util::getMatchingYesNoArray($sInterfaceLanguage)}

								{if $inquiryAccommodation->comment}
								<p>{$inquiryAccommodation->comment}</p>
								{/if}

								{if $matching->acc_vegetarian}
									{'Vegetarian food'|L10N}: {$matchingYesNo[$matching->acc_vegetarian]}<br/>
								{/if}
								{if $matching->acc_muslim_diat}
									{'Muslim diet'|L10N}: {$matchingYesNo[$matching->acc_muslim_diat]}<br/>
								{/if}
								{if $matching->acc_allergies}
									{'Allergies'|L10N}: {$matching->acc_allergies}<br/>
								{/if}
								{if $matching->acc_smoker}
									{'Smoker'|L10N}: {$matchingYesNo[$matching->acc_smoker]}<br/>
								{/if}
								{if $matching->cats}
									{'Family can have cats'|L10N}: {$matchingYesNo[$matching->cats]}<br/>
								{/if}
								{if $matching->dogs}
									{'Family can have dogs'|L10N}: {$matchingYesNo[$matching->dogs]}<br/>
								{/if}
								{if $matching->pets}
									{'Family can have other pets'|L10N}: {$matchingYesNo[$matching->pets]}<br/>
								{/if}
								{if $matching->smoker}
									{'Family can smoke'|L10N}: {$matchingYesNo[$matching->smoker]}<br/>
								{/if}
								{if $matching->distance_to_school}
									{'Family Distance to School'|L10N}: {$matching->distance_to_school}<br/>
								{/if}
								{if $matching->air_conditioner}
									{'Family must have AC'|L10N}: {$matchingYesNo[$matching->air_conditioner]}<br/>
								{/if}
								{if $matching->bath}
									{'Student wants his own bathroom'|L10N}: {$matchingYesNo[$matching->bath]}<br/>
								{/if}
								{if $matching->family_age}
									{'Family Age'|L10N}: {$matching->family_age}<br/>
								{/if}
								{if $matching->residential_area}
									{'Student wants to stay in this Residential Area'|L10N}:	{$matching->residential_area}<br/>
								{/if}
								{if $matching->family_kids}
									{'Family can have Kids'|L10N}: {$matchingYesNo[$matching->family_kids]}<br/>
								{/if}
								{if $matching->internet}
									{'Student wants internet'|L10N}: {$matchingYesNo[$matching->internet]}<br/>
								{/if}

							</td>
						{/if}
						{if in_array('arrival', $existingColumns)}
							<td style="vertical-align: top;">
								{assign var=transfers value=$inquiry->getTransfers()}
								{foreach $transfers as $transfer}
									{$transfer->getName(null, 1, '', true)}<br>
								{/foreach}
							</td>
						{/if}
						{if in_array('arrival_with_details', $existingColumns)}
							<td style="vertical-align: top;">
								{assign var=transfers value=$inquiry->getTransfers()}
								{foreach $transfers as $transfer}
									{assign var=provider value=Ext_Thebing_Pickup_Company::getInstance($transfer->provider_id)}
									{$transfer->getName()}<br>
									{if !empty($provider->name)}
										{'Transferanbieter:'|L10N} {$provider->name}<br>
									{/if}
									{if !empty($provider->tel)}
										{'Telefonnummer:'|L10N} {$provider->tel}<br>
									{/if}
									{if !empty($provider->handy)}
										{'Handy:'|L10N} {$provider->handy}<br>
									{/if}
								{/foreach}
							</td>
						{/if}
						{if $type == 'closed_requests'}
						<td style="vertical-align: top;">
							{if $recipient->accepted != null}
								{'Accepted'|L10N}
							{elseif $recipient->rejected != null}
								{'Rejected'|L10N}
							{elseif $item->isAccepted()}
								{'Accepted by another provider'|L10N}
							{elseif !empty($inquiryAccommodation->getAllocations())}
								{'Assigned by school'|L10N}
							{/if}
						</td>
						{/if}
						{if $type == 'requests'}
						<td style="vertical-align: top;">
							<a href="{route name="TsAccommodationLogin.accommodation_request_availability" task="reject" key=$recipient->key}" target="_blank" class="btn btn-danger">{'Reject'|L10N}</a>
							<a href="{route name="TsAccommodationLogin.accommodation_request_availability" task="accept" key=$recipient->key}" target="_blank" class="btn btn-success">{'Accept'|L10N}</a>
						</td>
						{/if}
					</tr>
				{foreachelse}
					<tr>
					{if $item instanceof \Ext_Thebing_Accommodation_Allocation}
						<td colspan="10">{'No students found!'|L10N}</td>
					{elseif $item instanceof \TsAccommodation\Entity\Request}
						<td colspan="10">{'No requests found!'|L10N}</td>
					{/if}
				</tr>
				{/foreach}
			</table>
                
		</div>	
	</div>
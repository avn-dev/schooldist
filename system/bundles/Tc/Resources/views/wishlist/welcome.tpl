
					<div class="box-body no-padding">
						<table class="table table-striped">

							{foreach $aWishes as $oWish}
							
							<tr>
								<td>{$oWish->title}</td>
								<td width="25%">
									<div>
										<div>{$oFormat->formatByValue($oWish->created_at)}</div>
									</div>
								</td>
								<td width="10%"><span class="badge bg-light-blue">{$oWish->user->name}</span></td>
							</tr>
							{/foreach}
						</table>
					</div><!-- /.box-body -->
					<div class="box-footer clearfix">
						<a href="#" onclick="loadContentByUrl('tc-wishlist', 'Wishlist', '/wishlist'); return false;"><button type="button" class="pull-right btn btn-primary">{'Zur Wunschliste'|L10N:'Wishlist'}</button></a>
					</div>

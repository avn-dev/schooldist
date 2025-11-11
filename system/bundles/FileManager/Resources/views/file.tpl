						<li id="filemanager-file-{$oFile->id}" class="filemanager-file kom-overlay-1" style="width: 245px;">
							{if $oFile->isImage()}
							<span class="kom-overlay-1 mailbox-attachment-icon has-img">
								<img src="{$oFile->getThumbnail()}">
							{else}
							<span class="kom-overlay-1 mailbox-attachment-icon">
								<i class="fa {$oFile->getIconClass()}"></i>
							{/if}
								<div class="kom-overlay">
									<ul class="kom-info">
										<li>
											<a class="btn default btn-outline" href="{$oFile->getUrl()}" target="_blank">
												<i class="fa fa-search"></i>
											</a>
										</li>
										<li>
											<a class="btn default btn-outline filemanager-edit" data-id="{$oFile->id}" href="javascript:;">
												<i class="fa fa-edit"></i>
											</a>
										</li>
										<li>
											<a class="btn default btn-outline filemanager-delete" data-id="{$oFile->id}" href="javascript:;">
												<i class="fa fa-trash"></i>
											</a>
										</li>
									</ul>
								</div>
							</span>

							<div class="mailbox-attachment-info">
								<a href="#" class="mailbox-attachment-name" title="{$oFile->file}"><i class="fa {$oFile->getIconClass()}"></i> {$oFile->file}</a>
								<span class="mailbox-attachment-size">{$oFile->getFilesize()}</span>

								<div class="pull-right clearfix" data-toggle="buttons">
									{foreach $aTags as $iTagId=>$sTag}
									<label class="btn btn-xs btn-default {if in_array($sTag, $oFile->getTags())}active{/if}">
										<input type="checkbox" class="file-tags" name="tags[{$oFile->id}][]" value="{$iTagId|escape}" autocomplete="off" {if in_array($sTag, $oFile->getTags())}checked{/if}> {$sTag}
									</label>
									{/foreach}
								</div>

								<div class="clearfix"></div>

							</div>

							<div class="form-container">
							</div>

						</li>
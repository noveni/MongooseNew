<div class="row">
	<div class="col-lg-12">
		<div class="panel">
			<div class="panel-heading"><i class="icon-tasks"></i> Feed</div>
			<div class="panel-content">
				<div class="row">
					<p>{l s='List of feed files'}</p>
					{if $files}
					<div class="col-lg-12">
						<table class="table">
							<thead>
								<tr>
									<th width="30">#</th>
									<th width="150">{l s='Filename'}</th>
									<th width="50">{l s='Language'}</th>
									<th width="80">{l s='Nb of line'}</th>
									<th>{l s='Current line'}</th>
									<th>Action</th>
									<th>{l s='Progress'}</th>
								</tr>
							</thead>
							<tbody>
								{foreach from=$files item=file}
								<tr>
									<td>{$file.id_mongoose_xml_file}</td>
									<td>{$file.src_file}</td>
									<td>{$file.src_lang_iso}</td>
									<td>{$file.src_line_total}</td>
									<td class="info current_line">{$file.src_current_line}</td>
									<td class="text-center">
										<button type="button" class="btn btn-primary btn-sm copy-feed" data-url='{$module_link}' data-idfile='{$file.id_mongoose_xml_file}'>{l s='Copy the feed'}</button>
										<button type="button" class="btn btn-primary btn-sm reset-src_current_line" data-url='{$module_link}' data-idfile='{$file.id_mongoose_xml_file}'>{l s='RàZ'}</button>
									</td>
									<td>
										<div class="progress">
											<div class="progress-bar" role="progressbar" aria-valuenow="{$file.percent}" aria-valuemin="0" aria-valuemax="100" style="min-width: 3em;width: {$file.percent}%;">
												{$file.percent}%
											</div>
										</div>
									</td>
								</tr>
								{/foreach}
							</tbody>
						</table>
					</div>
					{else}
						<p>{l s='There is no files yet, please add one in the panel above.'}</p>
					{/if}
				</div>
			</div>
		</div>
	</div>
</div>
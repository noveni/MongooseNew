<div class="row">
	<div class="col-lg-12">
		<div class="panel">
			<div class="panel-heading"><i class="icon-tasks"></i> Feed</div>
			<div class="panel-content">
				<p>{l s='Import product in prestashop'}</p>
				<p>
					<span>{l s='Nombre de ligne'} : {$count_row}</span><br>
					<span>{l s='ligne courante'} : <span id="current_line_product">{$current_line_product}</span></span><br>
				</p>
				<div class="progress">
					<div class="progress-bar" id="import-progressbar" role="progressbar" aria-valuenow="{$percent}" aria-valuemin="0" aria-valuemax="100" style="min-width: 3em;width: {$percent}%;">
						{$percent} %
					</div>
				</div>
			</div>
			<div class="panel-footer">
				<button type="button" class="btn btn-primary import-product pull-left" data-url='{$module_link}'>{l s='Import product'}</button>
			</div>
		</div>
	</div>
</div>
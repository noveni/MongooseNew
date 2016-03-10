<div class="row">
	<div class="col-lg-12">
		<div class="panel">
			<div class="panel-heading"><i class="icon-cogs"></i> {l s='Configurer votre module'}</div>
			<div class="panel-wrapper">
				{foreach from=$list_tab key=k item=item}
				<div class="row">
					<label class="control-label col-lg-3">{l s='Page'} {$item.name}</label>
					<div class="col-lg-9">
						{if isset($item.active)}
							{if $item.active == 1}
								<span>{l s='Le controlleur est déjà installer.'}</span>
							{elseif $item.active == 0}
								<a href="{$item.link}" class="btn btn-default">{l s='Installer le controlleur'}</a>
							{/if}
						{/if}						
					</div>
				</div>
				{/foreach}
			</div>
		</div>
	</div>
</div>
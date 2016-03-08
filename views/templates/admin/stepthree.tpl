<div class="row">
	<div class="col-lg-12">
		<div class="panel">
			<div class="panel-heading"><i class="icon-cogs"></i> Feed the Prestashop product table</div>
			<div class="panel-content">
				<p>{l s='Pour synchroniser les produits de la table intermediare vers la table des produits prestashop cliquez sur le bouton ci-dessous.' mod='mongoose'}</p>
				<div class="mongoose-stats">
					<p>Nombre de produits importés dans la base intermediaire : <span id="mongoose_products_total">{$mongoose_products_total}</span></p>
					<p>Current row in mongoose_product table : <span id="current_mongoose_product_row">{$current_mongoose_product_row}</span></p>
				</div>
			</div>
			<div class="panel-footer">
				<button type="button" class="btn btn-default" id="transfer_to_products" data-url="{$link->getAdminLink('AdminMongooseImport',true)}"><i class="process-icon-download-alt"></i>{l s='Envoyer les produits sur la base'}</button>
			</div>
		</div>
		<div class="panel">
			<div class="panel-heading">Erreur</div>
			<div class="panel-content" id="error_stant"></div>
		</div>
	</div>
</div>
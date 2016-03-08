<div class="row">
	<div class="col-lg-12">
		<div class="panel">
			<div class="panel-heading"><i class="icon-cogs"></i> Feed with the feed</div>
			<div class="panel-content">
				<p>{l s='Pour synchroniser les produis, cliquer sur le bouton ce-dessous.' mod='mongoose'}</p>
				<form action="{$link->getAdminLink('AdminMongooseImport',true)}" method="post" id="step1form">
					<button type="submit" class="btn btn-default btn-md" id="step1start">{l s='Cliquez ici pour synchroniser les produits'}</button>
					<input type="hidden" id="submitStep1Mongoose" name="submitStep1Mongoose" value="1">
				</form>
				<div class="mongoose-stats">
					<p>Nom du fichier: <span id="xml_filename">{$src_file}</span></p>
					<p>Langue du fichier: <span id="xml_lang">{$src_lang}</span></p>
					<p>Il y a <span id="xml_line_count">{$src_line_total}</span> ligne dans le fichier.</p>
					<p>Current line in file : <span id="src_current_line">{$src_current_line}</span></p>
				</div>
			</div>
			<div class="panel-footer">
				<form action="{$link->getAdminLink('AdminMongooseImport',true)}" method="post" >
					<button type="submit" class="btn btn-default btn-md" id="step1nextstep">{l s='Cliquez ici pour synchroniser les produits du fournisseur avec prestashop'}</button>
					<input type="hidden" id="step1form-nextstep" name="step1form-nextstep" value="1">
				</form>
			</div>
		</div>
		<div class="panel">
			<div class="panel-heading">Erreur</div>
			<div class="panel-content" id="error_stant"></div>
		</div>
	</div>
</div>
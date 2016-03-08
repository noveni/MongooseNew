<div class="row">
	<div class="col-lg-12">
		<div class="panel">
			<div class="panel-heading"><i class="icon-cogs"></i> Feed with the feed</div>
			<div class="panel-content">
				<p>{l s='Pour synchroniser les produis, cliquer sur le bouton ce-dessous.' mod='mongoose'}</p>
				<p>{l s='Une fois cliquer sur le bouton. Le process va mapper les produits et les analyser pour les inserer dans une base de données intermédiaire.'}</p>
				<div class="mongoose-stats">
					<p>Nom du fichier: <span id="xml_filename">{$xml_filename}</span> </p>
					<p>Langue du fichier: <span id="xml_lang">{$xml_lang}</span> </p>
					<p>Il y a <span id="xml_line_count">{$xml_line_count}</span> ligne dans le fichier.</p>
					<p>Current key in the XML file : <span id="xml_current_key">{$xml_current_key}</span></p>
				</div>
				<form action="{$link->getAdminLink('AdminMongooseImport',true)}" method="post" id="step2form">
					<button type="submit" class="btn btn-default btn-md" id="step2from_start">{l s='Cliquez ici pour synchroniser les produits'}</button>
					<input type="hidden" id="submitStep2Mongoose" name="submitStep2Mongoose" value="1">
				</form>
				<form action="{$link->getAdminLink('AdminMongooseImport',true)}" method="post">
					<input type="hidden" id="submitTestImporter" name="submitTestImporter" value="1">
					<input type="submit" class="btn btn-default" value="Envoyer">
				</form>
			</div>
			<div class="panel-footer">
				<button type="button" class="btn btn-default" id="reset_current_key" data-url="{$link->getAdminLink('AdminMongooseImport',true)}"><i class="process-icon-refresh"></i>{l s='Reset XML current Key'}</button>
			</div>
		</div>
		<form action="{$link->getAdminLink('AdminMongooseImport',true)}" method="post">
			<input type="hidden" id="submitGoToStep3" name="submitGoToStep3" value="1">
			<input type="submit" class="btn btn-default" value="Envoyer">
		</form>
		<div class="panel">
			<div class="panel-heading">Importation</div>
			<div class="panel-content">
				<table class="table table-striped" id="product_table">
					<tr>
						<th>N°</th>
						<th>ID</th>
						<th>Art n°</th>
						<th>Titre</th>
						<th>b2b price(VAT excl.)</th>
						<th>b2c price(VAT incl.)</th>
						<th>Date d'ajout</th>
						<th>Date de modif</th>
						<th>Image</th>
					</tr>
				</table>
			</div>
		</div>
		<div class="panel">
			<div class="panel-heading">Erreur</div>
			<div class="panel-content" id="error_stant"></div>
		</div>
	</div>
</div>
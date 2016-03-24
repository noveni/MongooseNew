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
				<div class="custom-import">
					<form action="{$module_link}" method="post" id="custom-import-form">
						<div class="row">
							<div class="col-lg-3">
								<div class="row">
									<h4>Informations</h4>
									<div class="form-group">
										<div class="col-lg-12">
											<div class="checkbox"><label for="name"><input type="checkbox" name id="name">Import name</label></div>
										</div>
									</div>
									<div class="form-group">
										<div class="col-lg-12">
											<div class="checkbox"><label for="description"><input type="checkbox" name="description" id="description">Import description</label></div>
										</div>
									</div>
								</div>
								<div class="row">
									<h4>Price</h4>
									<div class="form-group">
										<div class="col-lg-12">
											<div class="checkbox"><label for="price"><input type="checkbox" name="price" id="price">Import price</label></div>
										</div>
									</div>
									<div class="form-group">
										<div class="col-lg-12">
											<div class="checkbox"><label for="wholesale_price"><input type="checkbox" name="wholesale_price" id="wholesale_price">Import wholesale price</label></div>
										</div>
									</div>
								</div>
							</div>
							<div class="col-lg-3">
								<h4>Caractéristiques</h4>
								<div class="form-group">
									<div class="col-lg-12">
										<div class="checkbox"><label for="width"><input type="checkbox" name="width" id="width">Import width</label></div>
									</div>
								</div>
								<div class="form-group">
									<div class="col-lg-12">
										<div class="checkbox"><label for="height"><input type="checkbox" name="height" id="height">Import height</label></div>
									</div>
								</div>
								<div class="form-group">
									<div class="col-lg-12">
										<div class="checkbox"><label for="depth"><input type="checkbox" name="depth" id="depth">Import depth</label></div>
									</div>
								</div>
								<div class="form-group">
									<div class="col-lg-12">
										<div class="checkbox"><label for="weight"><input type="checkbox" name="weight" id="weight">Import weight</label></div>
									</div>
								</div>
							</div>
							<div class="col-lg-3">
								<h4>Complemantary</h4>
								<div class="form-group">
									<div class="col-lg-12">
										<div class="checkbox"><label for="manufacturer"><input type="checkbox" name="manufacturer" id="manufacturer">Import manufacturer</label></div>
									</div>
								</div>
								<div class="form-group">
									<div class="col-lg-12">
										<div class="checkbox"><label for="category"><input type="checkbox" name="category" id="category">Import category</label></div>
									</div>
								</div>
								<div class="form-group">
									<div class="col-lg-12">
										<div class="checkbox"><label for="attribute"><input type="checkbox" name="attribute" id="attribute">Import attribute</label></div>
									</div>
								</div>
								<div class="form-group">
									<div class="col-lg-12">
										<div class="checkbox"><label for="supplier"><input type="checkbox" name="supplier" id="supplier">Import supplier</label></div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="form-group">
								<div class="col-lg-12">
									<div class="checkbox"><label for="image"><input type="checkbox" name="image" id="image">Import les images</label></div>
								</div>
							</div>
						</div>
						<div class="row">
							<br>
							<button type="submit" class="btn btn-primary">{l s='Import'}</button>
						</div>
					</form>
				</div>
			</div>
			<div class="panel-footer">
				<button type="button" class="btn btn-xs import-product pull-right" data-url='{$module_link}'>{l s='Hard import'}</button>
			</div>
		</div>
	</div>
</div>
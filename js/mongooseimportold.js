var xml_filename, xml_line_count, xml_current_key, current_row
$(document).ready(function(){
	$("#step2form").on('submit',step2go)
	$("#reset_current_key").on('click',resetCurrentKey)
	$("#transfer_to_products").on('click',transfer_to_products)
});
step2go = function(event){
	event.preventDefault()
	theloopXml(this)
}
transfer_to_products = function(event){
	event.preventDefault()
	theloopProduct(this)
}
theloopProduct = function(form) {
	$.ajax({
		url: $(form).attr('action'),
		data: {
			ajax: true,
			action: 'transfertProducts'
		},
		method: 'POST',
		dataType: 'json',
		success: function(data) {
			console.log('theloopProduct Success - '+JSON.stringify(data))
			if(data.status == 'loop_end')
				console.log('End of loop')
			else if (data.status = 'looping_on_products')
			{
				current_row = data.current_mongoose_product_row
				updateStatRow()
				theloopProduct(form)
			}
		},
		error: function(data) {
			console.log('Error'+data);
			$("#error_stant").html(data.responseText);
		}
	})
}
theloopXml = function(form) {
	$.ajax({
		url: $(form).attr('action'),
		data: {
			ajax: true,
			action: 'transfertXml'
		},
		method: "POST",
		dataType: 'json',
		success: function(data) {
			console.log('theloopXml Success - '+JSON.stringify(data))
			if(data.status !== undefined){
				if(data.status == 'loop_end')
					console.log('End of loop');
				else if (data.status == 'looping_on_xml')
				{
					xml_current_key = data.current_key_in_xml
					updateStat()
					theloopXml(form)
				}
			} 
		},
		error: function(data) {
			console.log('Error'+data);
			$("#error_stant").html(data.responseText);
		}
	})
}
resetCurrentKey = function() {
	$.ajax({
		url: $(this).data('url'),
		data: {
			ajax: 	true,
			action: 'resetXmlCurrentKey'
		},
		method: 'POST',
		dataType: 'json',
		success: function(data){
			console.log('resetCurrentKey - Success - ' +JSON.stringify(data));
			xml_current_key = data.current_key_in_xml
			updateStat()
		},
		error: function(data) {
			console.log('Impossible de resetter le xml_line_count')
		}
	})
}
updateStat = function() {
	$("#xml_current_key").html(xml_current_key)
}
updateStatRow = function()
{
	$("#current_mongoose_product_row").html(current_row)
}
var src_file, src_line_total, src_current_line, mongoose_product_line
$(document).ready(function(){
	$("#step1form").on('submit',step1go)
	// $("#reset_current_key").on('click',resetCurrentKey)
	$("#transfert_mongoose_to_ps").on('click',transfer_to_products)
});
step1go = function(event){
	event.preventDefault()
	importSrcLine(this)
}
transfer_to_products = function(event){
	event.preventDefault()
	transfertMongooseLine(this)
}
transfertMongooseLine = function(form) {
	$.ajax({
		url: $(form).attr('action'),
		data: {
			ajax: true,
			action: 'transfertMongooseLine'
		},
		method: 'POST',
		dataType: 'json',
		success: function(data) {
			console.log('transfertMongooseLine Success - '+JSON.stringify(data))
			if(data.status == 'loop_end')
				console.log('End of loop')
			else if (data.status = 'looping_on_products')
			{
				mongoose_product_line = data.current_mongoose_product_line
				updateStatRow()
				transfertMongooseLine(form)
			}
		},
		error: function(data) {
			console.log('Error'+JSON.stringify(data));
			$("#error_stant").html(data.responseText);
		}
	})
}
importSrcLine = function(form) {
	$.ajax({
		url: $(form).attr('action'),
		data: {
			ajax: true,
			action: 'importSrcLine'
		},
		method: "POST",
		dataType: 'json',
		success: function(data) {
			console.log('theloopXml Success - '+JSON.stringify(data))
			if(data.status !== undefined){
				if(data.status == 'end_file')
					
					console.log('end of file')
				else if (data.status == 'looping_src_line')
				{
					src_current_line = data.current_line_in_src
					updateStat()
					importSrcLine(form)
				}
			}
		},
		error: function(data) {
			console.log('Error'+JSON.stringify(data));
			$("#error_stant").html(data.responseText)
		}
	})
}
updateStat = function() {
	$("#src_current_line").html(src_current_line)
}
updateStatRow = function()
{
	$("#current_mongoose_product_row").html(mongoose_product_line)
}
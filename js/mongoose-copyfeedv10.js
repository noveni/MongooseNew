var copyInstance = false, importInstance = false
$(document).ready(function(){
	$(".copy-feed").on('click',CopyFeedLauncher)
	$(".reset_src_current_line").on('click',resetSrcCurrentLine)
	$(".stop-copying").on('click',stopCopying)
	$(".import-product").on('click',ImportProductLauncher)
})
CopyFeedLauncher = function(){
	copyInstance = true
	CopyFeed(this)
}
ImportProductLauncher = function(){
	importInstance = true
	ImportProduct(this)
}
CopyFeed = function(btn){
	id_file = $(btn).data('idfile')
	$btn = $(btn)
	if (!copyInstance){
		console.log('Stopped the copying')
		return 
	}
	$.ajax({
		url: $(btn).data('url'),
		data: {
			ajax: true,
			action: 'copyMongooseXmlLine',
			id_file: $(btn).data('idfile')
		},
		method: 'POST',
		dataType: 'json',
		success: function(data) {
			console.log('Success : '+JSON.stringify(data))
			if(data.status !== undefined){
				if(data.status == 'end_file'){
					console.log('end of file')
					console.log($btn.parent().parent().next('tr'));
					if($btn.parent().parent().next('tr').length){
						$btn.parent().parent().next('tr').find('.copy-feed').trigger('click')
					}
					else
					{
						$('.import-product').trigger('click');
					}
					
				}
				else if (data.status == 'looping_on_xml_file')
				{
					setTheCurrentLine($btn,data.current_line_in_xml_feed_file, data.percent)
					CopyFeed($btn)
					//data.current_line_in_xml_feed_file
				}
			}
		},
		error: function(data){
			console.log('Error : '+JSON.stringify(data))
			$("#error_stant").html(data.responseText);
			setTimeout(function(){
				CopyFeed($btn);
			}, 10000);
		}
	})
}
ImportProduct = function(btn){
	$btn = $(btn)
	if (!importInstance){
		console.log('Stopped the importation')
		return
	}
	$.ajax({
		url: $(btn).data('url'),
		data: {
			ajax: true,
			action: 'importMongooseProduct'
		},
		method: 'POST',
		dataType: 'json',
		success: function(data) {
			console.log('Success : '+JSON.stringify(data))
			if(data.status !== undefined){
				if(data.status == 'end_table')
					console.log('end of table line')
				else if (data.status == 'looping_on_db_table')
				{
					//setTheCurrentLine($btn,data.current_line_in_xml_feed_file, data.percent)
					$("#current_line_product").html(data.current_mongoose_product_line)
					$("#import-progressbar").html(data.percent + '%').attr('aria-valuenow',data.percent).width(data.percent + '%')
					ImportProduct($btn)
					//data.current_line_in_xml_feed_file
				}
			}
		},
		error: function(data){
			console.log('Error : '+JSON.stringify(data))
			setTimeout(function(){
				ImportProduct($btn);
			}, 10000);
		}
	})
}
resetSrcCurrentLine = function(){
	$.ajax({
		url: $(this).data('url'),
		data: {
			ajax: true,
			action: 'resetMongooseXmlCurrentLine',
			id_file: $(this).data('idfile')
		},
		method: 'POST',
		dataType: 'json',
		success: function(data) {
			console.log('Success : '+JSON.stringify(data))
			setTheCurrentLine($(this),0,0)
		},
		error: function(data){
			console.log('Error : '+JSON.stringify(data))
		}

	})
}
setTheCurrentLine = function($btn, nbr, percent){
	$progressBar = $btn.parent().next().find('.progress-bar')
	$btn.parent().prev('.current_line').html(nbr)
	$progressBar.html(percent + '%')
	$progressBar.attr('aria-valuenow',percent)
	$progressBar.width(percent + '%')
}

stopCopying = function(){
	copyInstance = false
}
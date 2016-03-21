$(document).ready(function(){
	$(".copy-feed").on('click',copy_feed_launcher)
	$(".reset-src_current_line").on('click',reset-src_current_line)
})
copy_feed_launcher = function(){
	copy_feed(this);
}
copy_feed = function(btn){
	id_file = $(btn).data('idfile')
	$btn = $(btn)
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
				if(data.status == 'end_file')
					console.log('end of file')
				else if (data.status == 'looping_on_xml_file')
				{
					$btn.parent().prev('.current_line').html(data.current_line_in_xml_feed_file)
					$btn.parent().next().find('.progress-bar').html(data.percent + '%')
					$btn.parent().next().find('.progress-bar').attr('aria-valuenow',data.percent)
					$btn.parent().next().find('.progress-bar').width(data.percent + '%')
					copy_feed($btn)
					//data.current_line_in_xml_feed_file
				}
			}
		},
		error: function(data){
			console.log('Error : '+JSON.stringify(data))
		}
	})
}
reset-src_current_line = function(){
	$.ajax({
		url: $(btn).data('url'),
		data: {
			ajax: true,
			action: 'resetMongooseXmlCurrentLine',
			id_file: $(this).data('idfile')
		},
		method: 'POST',
		dataType: 'json',
		success: function(data) {
			console.log('Success : '+JSON.stringify(data))
		},
		error: function(data){
			console.log('Error : '+JSON.stringify(data))
		}

	})
}
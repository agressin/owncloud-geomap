$(document).ready(function(){

	$('#apiKey').blur(function(event){
		event.preventDefault();
		$('#apiKeyMsg').text('Save in progres ...');
		var post = $( "#apiKey" ).serialize();
		$.post( OC.filePath('geomap', 'ajax', 'setapikey.php') , post, function(data){
			$('#apiKeyMsg').text('Finished saving: ' + data);
		});
	});
	
	$('#apiVersion').blur(function(event){
		event.preventDefault();
		$('#apiKeyMsg').text('Save in progres ...');
		var post = $( "#apiVersion" ).serialize();
		$.post( OC.filePath('geomap', 'ajax', 'setapikey.php') , post, function(data){
			$('#apiKeyMsg').text('Finished saving: ' + data);
		});
	});

});

<form name="geomapForm" action='#' method='post'>
	<fieldset class="personalblock">
		<strong>GeoMap</strong><br />
		<input name="apiVersion" id="apiVersion" style="width:180px;" value="<?php echo $_['apiVersion'] ?>" />
		<input name="apiKey" id="apiKey" style="width:180px;" value="<?php echo $_['apiKey'] ?>" />
		<label for="apiKey"><?php echo $l->t( 'Your API Version and Key' ); ?> </label><br />
		<span id="apiKeyMsg"> Automatic save </span>
	</fieldset>
</form>

<?php
	require_once("setUpEnvironment.php");
	$extRoot = "";
	if (isProduction()) {
		$extRoot = "http://extjs.cachefly.net";
	}
?>
		<link rel="stylesheet" type="text/css" href="<?php echo($extRoot); ?>/ext-3.0.0/resources/css/ext-all.css"/>
    	<script type="text/javascript" src="<?php echo($extRoot); ?>/ext-3.0.0/adapter/ext/ext-base.js"></script>
    	<script type="text/javascript" src="<?php echo($extRoot); ?>/ext-3.0.0/ext-all-debug.js"></script>
		<script type="text/javascript">
			Ext.BLANK_IMAGE_URL = '<?php echo($extRoot); ?>/ext-3.0.0/resources/images/default/s.gif';
		</script>

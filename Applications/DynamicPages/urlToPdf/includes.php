<?php
	require_once("../../Common/util.php");
	$currentDirectoryPath = getDirectoryPathFromRoot(__FILE__);
	$pdfIconPath = "pdfIcon.png";
?>
		<script type="text/javascript" src="<?php echo($currentDirectoryPath); ?>/functions.js"></script>
		
		<style type="text/css">
			.download-pdf {
				text-align:center;
				font-size : 20px;
				padding : 5px;
				background-color : white;
			}
			
			.download-pdf .pdf-icon {
				width : 128px;
				height : 128px;
			    background-image : url(<?php echo("$currentDirectoryPath/$pdfIconPath"); ?>) !important;
			}
		</style>
		
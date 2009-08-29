<pre>
<?php
	/*
	 * This script runs setupDevo.sh, installGitHubToDir.sh, or promoteDevoToProd.sh depening on the "id" passed to it.
	 * The output of thse scripts is captured to a log file through recordScript.sh.
	 */
	$id = htmlspecialchars_decode($_GET["id"]);
	$recordScriptPrefixCommand = "./recordScript.sh log";
	switch ($id) {
		case "setupDevo":
			$command = "$recordScriptPrefixCommand ./setupDevo.sh ../";
			system($command);
			break;
		case "installGitHubToDevo":
			$command = "$recordScriptPrefixCommand ./installGitHubToDir.sh ../DEVO/";
			system($command);
			break;
		case "promoteDevoToProd":
			$command = "$recordScriptPrefixCommand ./promoteDevoToProd.sh ../";
			system($command);
			break;
		default:
			echo "id '$id' is not valid.";
	}
?>
</pre>
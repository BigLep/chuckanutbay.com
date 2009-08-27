<?php
$title = "chuckantubay.com Install";
require('../dynamicPages/common/htmlToTitle.php');
require('../dynamicPages/common/extJsIncludes.php');
?>
	<script type="application/javascript">
		Ext.onReady(function() {
			/**
			 * @param {Element/String} $outputEl Element to load the contents of the script output to.
			 * @param {String} $scriptId Id of the script to run as defined in runScript.php
			 */
			function runScript($outputEl, $scriptId) {
				Ext.get($outputEl).load({
					url : 'runScript.php',
					params : 'id=' + $scriptId,
					method : 'GET'
				});
			}
			
			Ext.get("installGitHubToDevoButton").on(
				"click", 
				function() {
					runScript("installGitHubToDevoOutput", "installGitHubToDevo"); 
				}
			);
			Ext.get("promoteDevoToProdButton").on(
				"click", 
				function() {
					runScript("promoteDevoToProdOutput", "promoteDevoToProd"); 
				}
			);
		});
	</script>

<?php
require('../dynamicPages/common/endHeadToBody.php');
?>
	<div id="installGitHubToDevo" style="padding-bottom:25px;">
		<h2>Install GitHub to DEVO</h2>
		<p>
			This script will install the latest checked-in code in <a href="https://github.com/BigLep/chuckanutbay.com/tree">GitHub</a>
			to the <a href="../DEVO/">DEVO</a> environment.
		</p>
		<button id="installGitHubToDevoButton">Install GitHub to DEVO</button>
		<div id="installGitHubToDevoOutput"></div>
	</div>
	
	<div id="promoteDevoToProd" style="padding-bottom:25px;">
		<h2>Promote DEVO to PROD</h2>
		<p>
			This script will copy the contents from the <a href="../DEVO/">DEVO site</a> to the <a href="../">PROD site</a>.
			This should only be done once <a href="../DEVO/">DEVO</a> has been verified.
		</p>
		<button>Promote DEVO to PROD</button>
		<div id="promoteDevoToProdOutput"></div>
	</div>
<?php
require('../dynamicPages/common/footer.php');
?>
<?php
$title = "chuckantubay.com Install";
require('../dynamicPages/common/htmlToTitle.php');
require('../dynamicPages/common/extJsIncludes.php');
?>
	<script type="application/javascript">
		Ext.onReady(function() {
			/**
			 * @param {Element/String} outputEl Element to load the contents of the script output to.
			 * @param {String} scriptId Id of the script to run as defined in runScript.php
			 */
			function runScript(outputEl, scriptId) {
				Ext.get(outputEl).load({
					url : 'runScript.php',
					params : 'id=' + scriptId,
					method : 'GET'
				});
			}
			
			var scriptIds = [
				'setupDevo',
				'installGitHubToDevo',
				'promoteDevoToProd'
			];
			
			for (var i = 0; i < scriptIds.length; i++) {
				var scriptId = scriptIds[i];
				console.log(scriptId);
				Ext.get(scriptId + 'Button').on(
					"click", 
					runScript.createCallback(scriptId + 'Output', scriptId)
				);
			}
		});
	</script>

<?php
require('../dynamicPages/common/endHeadToBody.php');
?>
	<div id="setupDevo" style="padding-bottom:25px;">
		<h2>Setup DEVO</h2>
		<p>
			This script will setup the necessary directories for a DEVO site.  The DEVO site will be located at <a href="../DEVO/">../DEVO</a>.  
			After running this script, you can "Install GitHub to Devo" below.
		</p>
		<button id="setupDevoButton">Setup DEVO</button>
		<div id="setupDevoOutput"></div>
	</div>
	
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
		<button id="promoteDevoToProdButton">Promote DEVO to PROD</button>
		<div id="promoteDevoToProdOutput"></div>
	</div>
<?php
require('../dynamicPages/common/footer.php');
?>
<?php
require_once("setUpEnvironment.php");
if (isProduction()) {
?>
	<!-- Google Analytics Tracking -->
	<script type="text/javascript">
		var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
		document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
		try{
			var pageTracker = _gat._getTracker("UA-7548729-1");
			pageTracker._trackPageview();
		} catch(err) {}
	</script>
<?php
}
?>
	</body>
</html>

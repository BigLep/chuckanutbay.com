<?php
require_once("../common/setUpEnvironment.php");
require_once("../common/util.php");

/**
 * Converts a URL to a PDF using pdfonline.com service.
 * @param String $url url which a PDF should be generated for.
 * @param String $outputFilePath Path for the PDF to generate.
 * @param String $errorMessage Error message when PDF generation is unsuccessful.
 * @return true if PDF generation was successful; false if not.
 */
function convertToPdf($url, $outputFilePath, &$errorMessage) {
	if (!mkFileDirs($outputFilePath)) {
		$errorMessage = "Unable to create output directory for $outputFilePath.";
		return false;
	}
	
	$credentials = getPdfOnlineCredentials();
	$requestParameters = array(
		"cURL" => $url,
		"username" => $credentials['username'],
		"page" => "0",
		"top" => "0.5",
		"bottom" => "0.5",
		"left" => "0.5",
		"right" => "0.5"
	);
	// This url was found from viewing the source of:
	// "http://savepageaspdf.pdfonline.com/pdfonline/pdfonline.asp?" .  . http_build_query($requestParameters)
	// except using the author_id instead of the username.
	// There is an iframe that uses the url below for downloading the PDF.
	$createPdfUrl = "http://savepageaspdf.pdfonline.com/pdfonline/pdfoMaster.asp?" . http_build_query($requestParameters);

	copy($createPdfUrl, $outputFilePath);
	
	if (!file_exists($outputFilePath) || !filesize($outputFilePath)) {
		$errorMessage = "There was a problem creating the PDF.";
		return false;
	}
	
	// If pdfonline.com has a problem generating a PDF, it will return an HTML file instead of a PDF.
	// Example error line: "Unable to convert URL. Error message:Unable to open input documentURL: URL_GOES_HERE"
	$outputFileResource = fopen($outputFilePath, "r");
	$firstLine = fgets($outputFileResource);
	fclose($outputFileResource);
	if (strpos($firstLine, "Unable to convert URL") !== false) {
		$errorMessage = $firstLine;
		return false;
	}
	
	return true;
}
?>

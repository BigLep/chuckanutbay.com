<?php
require_once("../common/setUpEnvironment.php");
require_once("../common/util.php");

/**
 * Converts either HTML or a URL to a PDF using the htm2pdf.co.uk web service.
 * This was taken from: http://forum.htm2pdf.co.uk/viewtopic.php?f=4&t=9
 * @param Array $params Will be passed to the htm2pdf.co.uk.
 * Must include the key "html" or "aUrl", and then corresponding values.
 * @param String $outputFilePath Path for the PDF to generate.
 * @param String $errorMessage Error message when PDF generation is unsuccessful.
 * @return true if PDF generation was successful; false if not.
 */
function convertToPdf($params, $outputFilePath, &$errorMessage) {
	if (!mkFileDirs($outputFilePath)) {
		$errorMessage = "Unable to create output directory for $outputFilePath.";
		return false;
	}
	
	// Add API key to params.
	$credentials = getHtm2PdfCredentials();
	$params['key'] = $credentials['key'];
	
	// Check if SOAP is available.
	if (!class_exists('SoapClient')) {
		$errorMessage = "Unable to create SOAP client as SoapClient class doesn't exist.";
		return false;
	}

	
	// Client for pdf creation.
	$client = new SoapClient('http://webservice.htm2pdf.co.uk/htm2pdf.asmx?wsdl');
	
	// Check remaining credits.
	$checkCredits = true;
	if ($checkCredits) {
		try {
			$credits = $client->NumberOfCredits($params)->NumberOfCreditsResult;
			if ($credits == 0) {
				$errorMessage = 'No HTM2PDF credits remaining.';
				return false;
			} elseif ($credits < 100) {
				mail("it@chuckanutbay.com", "htm2pdf is low on credits", "There are only $credits credits available.");
			}
		} catch(Exception $e) {
			// credit count couldn't be retrieved... oh well!!
			// this is a non-essential error, so keep on going
		}
	}
	
	$outputFile = fopen($outputFilePath, 'w');
	$data;
	try {
		if (isset($params['html'])) {
			$data = $client->Htm2PdfDoc($params)->Htm2PdfDocResult;
		} elseif (isset($params['aUrl'])) {
			$data = $client->Url2PdfDoc($params)->Url2PdfDocResult;
		} else {
			$errorMessage = print_r($params, true)." doesn't include 'html' or 'aUrl'";
			return false;
		}
	} catch(Exception $e) {
		$errorMessage = 'Error getting PDF. ('.$e->getMessage().')';
		return false;
	}
	fwrite($outputFile, $data);
	fclose($outputFile);
	
	if (!file_exists($outputFilePath) || !filesize($outputFilePath)) {
		$errorMessage = 'There was a problem creating the PDF.';
		return false;
	}
	
	return true;
}
?>

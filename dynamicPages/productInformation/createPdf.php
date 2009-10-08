<?php
    // create the database connection and import common methods
	require_once("../common/databaseConnection.php");
	require_once("../common/util.php");
	require_once("common.php");
	require_once("../urlToPdf/convertToPdf.php");
	
	$id = htmlspecialchars($_POST["id"]);
	$idBase = getItemIdBase($id);
	if (!$idBase) {
		echo("$id doesn't have a valid idBase");
		return;
	}
	
	// Note: this is vulnerable to abuse,
	// as someone could construct a URL with arbitrary HTML, and we would create a PDF out of it.
	if (!isset($_POST["html"])) {
		echo("HTML was not sent to generate a PDF.");
		return;
	}
	// Since we'll be sending this HTML to a web-service, we need to make sure slashes haven't been added.
	$html = get_magic_quotes_gpc() ? stripslashes($_POST["html"]) : $_POST["html"];
	
	// quickbook_item_supplements
	$sizeKey = "size";
	$productTypeKey = "productType";

	$query = createSqlQuery(
		"SELECT qbis.size as '$sizeKey'",
			 ", qbis.product_type as '$productTypeKey'",
		"FROM quickbooks_item_supplements qbis",
		"WHERE qbis.id LIKE '$idBase-%'"
	);
	$result = queryDb($query);
	if (mysql_num_rows($result) == 0) { // quickbooks_item_supplements with this id doesn't exist
		echo("No information for products with id base: $idBase.");
		return;
	}
	
	$row = mysql_fetch_assoc($result);
	$productType = $row[$productTypeKey];
	$size = $row[$sizeKey];
	
	$pdfPath = getPdfPath($productType, $size);
	$params = array("html" => $html);
	
	if (!convertToPdf($params, $pdfPath, $errorMessage)) {
		echo(implode("\n", array(
			"PDF generation failed because: $errorMessage",
			"PDF path: $pdfPath",
			"HTML: $html"
		)));
	}
?>
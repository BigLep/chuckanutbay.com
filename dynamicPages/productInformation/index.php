<?php

// create the database connection and import common methods
require_once("../common/databaseConnection.php");
require_once("../common/util.php");
require_once("common.php");

// Determine whether we should print, saveAsPdf, or just display.
$shouldPrint = (bool)(htmlspecialchars($_GET["action"]) == "print");
$shouldSaveAsPdf = (bool)(htmlspecialchars($_GET["action"]) == "saveAsPdf");

$id = htmlspecialchars($_GET["id"]);
$idBase = getItemIdBase($id);
if (!$idBase) {
	echo("$id doesn't have a valid idBase");
	return;
}

// see if there's a quickbooks_item with this idBase
// shared key postfix
$lastModifiedTimePostfixKey = "LastModifiedTime";
// quickbook_items
$itemNumberKey = "itemNumber";
$upcKey = "upc";
$descriptionKey = "description";
$grossWeightLbKey = "grossWeightLb";
$packKey = "pack";
$caseCubeKey = "caseCube";
$unitWeightOzKey = "unitWeightOz";
$unitWeightGKey = "unitWeightG";
$qbiLastModifiedTimeKey = "qbi$lastModifiedTimePostfixKey";
// quickbook_item_supplements
$tagLineKey = "tagLine";
$caseDimensionsKey = "caseDimensions";
$casesPerPalletLayerKey = "casesPerPalletLayerKey";
$caseLayersPerPalletKey = "caseLayersPerPallet";
$sizeKey = "size";
$productTypeKey = "productType";
$qbisLastModifiedTimeKey = "qbis$lastModifiedTimePostfixKey";
// nutrition_labels
$ingredientsKey = "ingredients";
$allergensKey = "allergens";
$usNutritionLabelImageIdKey= "usNutritionLabelImageId";
$nlLastModifiedTimeKey = "nl$lastModifiedTimePostfixKey";
// storage_infos
$frozenShelfLifeKey = "frozenShelfLife";
$frozenTemperatureRangeKey = "frozenTemperatureRange";
$refrigeratedShelfLifeKey = "refrigeratedShelfLife";
$refrigeratedTemperatureRangeKey = "refrigeratedTemperatureRange";
$roomShelfLifeKey = "roomShelfLife";
$roomTemperatureRangeKey = "roomTemperatureRange";
$siLastModifiedTimeKey = "si$lastModifiedTimePostfixKey";
// production_codes
$productionCodeKey = "productionCode";
$pcLastModifiedTimeKey = "pc$lastModifiedTimePostfixKey";
// kosher_statuses
$kosherStatusKey = "kosherStatus";
$ksLastModifiedTimeKey = "ks$lastModifiedTimePostfixKey";

// Query the database for all of the columns below for every item that has the idBase.
$quickBooksItemIdQuery = createSqlQuery(
	"SELECT qbi.id as '$itemNumberKey'",
		 ", qbi.pack as '$packKey'",
		 ", qbi.unit_weight_oz as '$unitWeightOzKey'",
		 ", qbi.unit_weight_g as '$unitWeightGKey'",
		 ", qbi.case_cube as '$caseCubeKey'",
		 ", qbi.upc as '$upcKey'",
		 ", qbi.description as '$descriptionKey'",
		 ", qbi.gross_weight_lb as '$grossWeightLbKey'",
		 ", qbis.tag_line as '$tagLineKey'",
		 ", qbis.case_dimensions as '$caseDimensionsKey'",
		 ", qbis.cases_per_pallet_layer as '$casesPerPalletLayerKey'",
		 ", qbis.case_layers_per_pallet as '$caseLayersPerPalletKey'",
		 ", qbis.size as '$sizeKey'",
		 ", qbis.product_type as '$productTypeKey'",
		 ", nl.ingredients as '$ingredientsKey'", 
		 ", nl.allergens as '$allergensKey'", 
		 ", nl.us_label_image_id as '$usNutritionLabelImageIdKey'",
		 ", si.frozen_shelf_life as '$frozenShelfLifeKey'", 
		 ", si.frozen_temperature_range as '$frozenTemperatureRangeKey'", 
		 ", si.refrigerated_shelf_life as '$refrigeratedShelfLifeKey'", 
		 ", si.refrigerated_temperature_range as '$refrigeratedTemperatureRangeKey'", 
		 ", si.room_shelf_life as '$roomShelfLifeKey'", 
		 ", si.room_temperature_range as '$roomTemperatureRangeKey'", 
		 ", pc.description as '$productionCodeKey'",
		 ", ks.description as '$kosherStatusKey'", 
	"FROM (((((quickbooks_items qbi",  
		 "LEFT JOIN quickbooks_item_supplements qbis ON qbi.quickbooks_item_supplement_id = qbis.id)", 
		 "LEFT JOIN nutrition_labels nl ON qbi.nutrition_label_id = nl.id)",
		 "LEFT JOIN storage_infos si ON qbis.storage_info_id = si.id)", 
		 "LEFT JOIN production_codes pc ON qbis.production_code_id = pc.id)", 
		 "LEFT JOIN kosher_statuses ks ON qbis.kosher_status_id = ks.id)",
	"WHERE qbi.id LIKE '$idBase-%'"
);
$result = queryDb($quickBooksItemIdQuery);
if (mysql_num_rows($result) == 0) { // quickbooks_item with this id doesn't exist
	echo("No information for products with id base: $idBase.");
	return;
}

// Create a mapping of pack to the row in the result set that has that pack.
// This makes it very easy to get the single item (item with pack == 1) row.
while($row = mysql_fetch_assoc($result)) {
	$packToRowMap[$row[$packKey]] = $row;
}

$singleItemRow = $packToRowMap[1];
if (!$singleItemRow) {
	echo("No product with pack as 1 with id base: $idBase.");
	return;
}

$size = $singleItemRow[$sizeKey];
$productType = $singleItemRow[$productTypeKey];

// Create a list of image files, that we can check the last-modified date for to determine whether a new PDF would need to be generated.
$imageFilePaths = array();

// Create a list of Ext panels by using the getExtFormPanel function.
$extPanels = array();

// Create the "General Information" panel from the singleItemRow.
$generalInformationPanel = getExtFormPanel("General Information", array(
	getExtComponent("Product Description", $singleItemRow[$descriptionKey]),
	getExtComponent("Tag Line", $singleItemRow[$tagLineKey])
));
array_push($extPanels, $generalInformationPanel);

// Create the "Individual Information" panel from the singleItemRow.
$individualInformationComponents = array();
array_push($individualInformationComponents, getExtComponent("Item Number", $singleItemRow[$itemNumberKey]));
array_push($individualInformationComponents, getExtComponent("UPC", getEncodedUpcCode($singleItemRow[$upcKey])));
array_push($individualInformationComponents, getExtComponent("Gross Weight", $singleItemRow[$unitWeightOzKey] . " oz / " . $singleItemRow[$unitWeightGKey] .  " g"));
array_push($individualInformationComponents, getExtComponent("Ingredient Statment", $singleItemRow[$ingredientsKey]));
array_push($individualInformationComponents, getExtComponent("Allergen Statment", nl2br($singleItemRow[$allergensKey])));
array_push($individualInformationComponents, getExtComponent("Nutritional Data", "<img src=\"../getImage/?id=$singleItemRow[$usNutritionLabelImageIdKey]\" width=\"250\"/>"));
array_push($individualInformationComponents, getExtComponent("Frozen Shelf Life", $singleItemRow[$frozenShelfLifeKey]));
array_push($individualInformationComponents, getExtComponent("Frozen Temp Range", $singleItemRow[$frozenTemperatureRangeKey]));
array_push($individualInformationComponents, getExtComponent("Refrigerated Shelf Life", $singleItemRow[$refrigeratedShelfLifeKey]));
array_push($individualInformationComponents, getExtComponent("Refrigerated Temp Range", $singleItemRow[$refrigeratedTemperatureRangeKey]));
array_push($individualInformationComponents, getExtComponent("Room Temp Shelf Life", $singleItemRow[$roomShelfLifeKey]));
array_push($individualInformationComponents, getExtComponent("Room Temp Range", $singleItemRow[$roomTemperatureRangeKey]));
array_push($individualInformationComponents, getExtComponent("Production Code", $singleItemRow[$productionCodeKey]));
array_push($individualInformationComponents, getExtComponent("Kosher Status", $singleItemRow[$kosherStatusKey]));
if (doesProductImageExist($productType, $size, $idBase,"Label")) {
	array_push($imageFilePaths, getProductImagePath($productType, $size, $idBase, "Label"));
	array_push($individualInformationComponents, getExtComponent("Individual Label", getProductImageHtml($productType, $size, $idBase,"Label")));
}
if (doesProductImageExist($productType, $size, $idBase,"Label1")) {
	array_push($imageFilePaths, getProductImagePath($productType, $size, $idBase, "Label1"));
	array_push($individualInformationComponents, getExtComponent("Individual Label 2", getProductImageHtml($productType, $size, $idBase,"Label1")));
}
if (doesProductImageExist($productType, $size, $idBase,"CaseLabel")) {
	// TODO: ask Mitch why we do this check.
	if ($idBase < 2000) {
		array_push($imageFilePaths, getProductImagePath($productType, $size, $idBase, "CaseLabel"));
		array_push($individualInformationComponents, getExtComponent("Box Label", getProductImageHtml($productType, $size, $idBase,"CaseLabel")));
	}
}

$individualInformationPanel = getExtFormPanel("Individual Information", $individualInformationComponents);
array_push($extPanels, $individualInformationPanel);

// Remove the singleItemRow, because we are done with it.
// We now will create a panel for each row that has a pack > 1.
unset($packToRowMap[1]);
// Sort the array keys (which are the pack values) in numerica ascending order.
ksort($packToRowMap);

foreach ($packToRowMap as $pack => $multipleItemRow) {
	// Create the "Group Information" panel for each pack value.

	$groupInformationComponents = array();
	array_push($groupInformationComponents, getExtComponent("Item Number", $multipleItemRow[$itemNumberKey]));
	array_push($groupInformationComponents, getExtComponent("UPC", getEncodedUpcCode($multipleItemRow[$upcKey])));
	array_push($groupInformationComponents, getExtComponent("Gross Weight (lbs.)", $multipleItemRow[$grossWeightLbKey]));
	array_push($groupInformationComponents, getExtComponent("Pack / Unit (oz.)", "$multipleItemRow[$packKey] / $multipleItemRow[$unitWeightOzKey]"));
	array_push($groupInformationComponents, getExtComponent("Case Cube", $multipleItemRow[$caseCubeKey]));
	array_push($groupInformationComponents, getExtComponent("Ti x Hi", "$multipleItemRow[$casesPerPalletLayerKey] / $multipleItemRow[$caseLayersPerPalletKey]"));
	array_push($groupInformationComponents, getExtComponent("Case (L'' X W'' X H'')", $multipleItemRow[$caseDimensionsKey]));
	array_push($groupInformationComponents, getExtComponent("Production Code", $multipleItemRow[$productionCodeKey]));
	if (doesProductImageExist($productType, $size, $idBase,"CaseLabel")){
		array_push($imageFilePaths, getProductImagePath($productType, $size, $idBase, "CaseLabel"));
		array_push($groupInformationComponents, getExtComponent("Case Label", getProductImageHtml($productType, $size, $idBase,"CaseLabel")));
	}
	$groupInformationPanel = getExtFormPanel("Group Information ($pack)", $groupInformationComponents);
	array_push($extPanels, $groupInformationPanel);
}

// Determine whether a PDF needs to be generated for this product or not.
$pdfPath = getPdfPath($productType, $size);
$pdfExists = true;
if (file_exists($pdfPath)) {
	// Since the file exists, we need to determine if it has outdated information.
	$pdfLastModifiedTime = filemtime($pdfPath);
	$pdfLastModifiedTimeKey = "pdfLastModifiedTime";
	$quickBooksItemIdQuery = createSqlQuery(
		"SELECT qbi.id",
			 ", FROM_UNIXTIME($pdfLastModifiedTime) as '$pdfLastModifiedTimeKey'",
			 ", qbi.last_modified_time as '$qbiLastModifiedTimeKey'",
			 ", qbis.last_modified_time as '$qbisLastModifiedTimeKey'",
			 ", nl.last_modified_time as '$nlLastModifiedTimeKey'", 
			 ", si.last_modified_time as '$siLastModifiedTimeKey'",
			 ", pc.last_modified_time as '$pcLastModifiedTimeKey'",
			 ", ks.last_modified_time as '$ksLastModifiedTimeKey'",
		"FROM (((((quickbooks_items qbi",  
			 "LEFT JOIN quickbooks_item_supplements qbis ON qbi.quickbooks_item_supplement_id = qbis.id)", 
			 "LEFT JOIN nutrition_labels nl ON qbi.nutrition_label_id = nl.id)",
			 "LEFT JOIN storage_infos si ON qbis.storage_info_id = si.id)", 
			 "LEFT JOIN production_codes pc ON qbis.production_code_id = pc.id)", 
			 "LEFT JOIN kosher_statuses ks ON qbis.kosher_status_id = ks.id)",
		"WHERE qbi.id LIKE '$idBase-%'",
			"AND (qbi.last_modified_time > FROM_UNIXTIME($pdfLastModifiedTime)",
				"OR qbis.last_modified_time > FROM_UNIXTIME($pdfLastModifiedTime)",
				"OR nl.last_modified_time > FROM_UNIXTIME($pdfLastModifiedTime)",
				"OR si.last_modified_time > FROM_UNIXTIME($pdfLastModifiedTime)",
				"OR pc.last_modified_time > FROM_UNIXTIME($pdfLastModifiedTime)",
				"OR ks.last_modified_time > FROM_UNIXTIME($pdfLastModifiedTime))"
	);
	$result = queryDb($quickBooksItemIdQuery);
	if (mysql_num_rows($result) == 0) { // None of the database rows have been updated since the PDF was generated.
		// Check the images on the file-system to ensure they haven't been updated.
		foreach($imageFilePaths as $imageFilePath) {
			if (filemtime($imageFilePath) > $pdfLastModifiedTime) {
				$pdfExists = false;
				break;
			}
		}
	} else {
		$pdfExists = false;
	}
} else {
	$pdfExists = false;
}

$title = "Chuckanut Bay Foods: $singleItemRow[$descriptionKey]";
require('../common/htmlToTitle.php');
require('../common/extJsIncludes.php');
require('../urlToPdf/includes.php');
if (!($shouldPrint || $shouldSaveAsPdf)) {
	require("../common/iconIncludes.php");	
}
?>
<link rel="stylesheet" type="text/css" href="index.css"/>
<script type="text/javascript">
	Ext.onReady(function() {
		/**
		 * Handler for when the "Print" button is clicked.
		 * It opens a new window to this url with "action=print" appended so that we know to print the page.
		 */
		var printHandler = function() {
			window.open(document.location.href + '&action=print', "Print", "scrollbars=yes,resizable=yes,width=640,height=480,menubar,toolbar,location");
		};
		
		var pdfPath = '<?php echo($pdfPath); ?>';
		var pdfExists = <?php echoBooleanForJavaScript($pdfExists); ?>;
		
		/**
		 * Handerl for when the "Save As PDF" button is clicked.
		 * Opens a new window with a properly formatted url for savepageaspdf.pdfonline.com that takes care of generating the PDF.
		 */
		var saveAsPdfHandler = function() {
			if (pdfExists) {
				showPdfDownloadWindow(pdfPath);
			} else {
				var delaySeconds = 30;
				// Make sure the Ext.Msg is wide enough so the message fits on one line.
				Ext.Msg.minProgressWidth = 300;
				Ext.Msg.wait('Your PDF should be generated in 30 seconds.', 'Generate PDF', {
					duration : delaySeconds * 1000,
					interval : 1000,
					increment : delaySeconds + 1, // By adding 1, our duration ends when the progress bar is "full".
					fn : function() {
						// The PDF should exist by now.
						// We set this to true so that if the user hits the button again, they don't have to wait for 30 seconds.
						pdfExists = true;
						Ext.Msg.hide();
						showPdfDownloadWindow(pdfPath);
					}
				});
				// The PDF should be generated without the toolbar buttons, thus we load an IFrame that will render the page in a PDF-friendly way.
				Ext.getBody().insertFirst({
					tag : 'iframe',
					src : document.location.href + '&action=saveAsPdf',
					width : 0,
					height : 0,
					style : 'width:0px;width:0px;display:none;'
				});
			}
		};
		
		var shouldPrint = <?php echoBooleanForJavaScript($shouldPrint); ?>;
		var shouldSaveAsPdf = <?php echoBooleanForJavaScript($shouldSaveAsPdf); ?>;
		
		var containerCfg = {items : <?php echo(json_encode($extPanels)); ?>};
		if (shouldPrint || shouldSaveAsPdf) {
			Ext.apply(containerCfg, {
				xtype : 'container',
				layout : 'table',
				layoutConfig: {
					columns: 1
				}
			});
		} else {
			Ext.apply(containerCfg, {
				xtype : 'panel',
				tbar : [
				/* // Print support is currently disabled as it doesn't yield satisfactory results.
				{
					text : 'Print',
					iconCls : 'print-icon',
					handler : printHandler
				}, 
				'-', // seperator
				*/
				{
					text : 'Save as PDF',
					iconCls : 'pdf-icon',
					handler : saveAsPdfHandler
				}]
			});
		}
		var container = Ext.ComponentMgr.create(containerCfg);
		container.render(Ext.getBody());
		
		// Handle these after the dynamic Ext content has been rendered.
		if (shouldPrint) {
			window.print();
			window.close();
		} else if (shouldSaveAsPdf) {
			Ext.Ajax.request({
				url : 'createPdf.php',
				params : {
					id : '<?php echo($id); ?>',
					html : getDomHtml()
				}
			});
		}
	});
	
</script>

<?php
require('../common/endHeadToBody.php');
require('../common/footer.php');
?>
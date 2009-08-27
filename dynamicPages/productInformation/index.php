<?php

// create the database connection and import common methods
require("../common/databaseConnection.php");
require("../common/util.php");

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
// quickbook_items
$itemNumberKey = "itemNumber";
$upcKey = "upc";
$descriptionKey = "description";
$grossWeightKey = "grossWeight";
$packKey = "pack";
$caseCubeKey = "caseCube";
$unitWeightKey = "unitWeight";
// quickbook_item_supplements
$tagLineKey = "tagLine";
$caseDimensionsKey = "caseDimensions";
$casesPerPalletLayerKey = "casesPerPalletLayerKey";
$caseLayersPerPalletKey = "caseLayersPerPallet";
// nutrition_labels
$ingredientsKey = "ingredients";
$allergensKey = "allergens";
$usNutritionLabelImageIdKey= "usNutritionLabelImageId";
// storage_infos
$frozenShelfLifeKey = "frozenShelfLife";
$frozenTemperatureRangeKey = "frozenTemperatureRange";
$refrigeratedShelfLifeKey = "refrigeratedShelfLife";
$refrigeratedTemperatureRangeKey = "refrigeratedTemperatureRange";
$roomShelfLifeKey = "roomShelfLife";
$roomTemperatureRangeKey = "roomTemperatureRange";
// production_codes
$productionCodeKey = "productionCode";
// kosher_statuses
$kosherStatusKey = "kosherStatus";

// Query the database for all of the columns below for every item that has the idBase.
$quickBooksItemIdQuery = createSqlQuery(
	"SELECT qbi.id as '$itemNumberKey'",
	     ", qbi.pack as '$packKey'",
		 ", qbi.unit_weight as '$unitWeightKey'",
		 ", qbi.case_cube as '$caseCubeKey'",
	     ", qbi.upc as '$upcKey'",
		 ", qbi.description as '$descriptionKey'",
		 ", qbi.gross_weight as '$grossWeightKey'",
		 ", qbis.tag_line as '$tagLineKey'",
		 ", qbis.case_dimensions as '$caseDimensionsKey'",
		 ", qbis.cases_per_pallet_layer as '$casesPerPalletLayerKey'",
		 ", qbis.case_layers_per_pallet as '$caseLayersPerPalletKey'",
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

// Create a list of Ext panels by using the getExtFormPanel function.
$extPanels = array();

// Create the "General Information" panel from the singleItemRow.
$generalInformationPanel = getExtFormPanel("General Information", array(
	getExtComponent("Product Description", $singleItemRow[$descriptionKey]),
	getExtComponent("Tag Line", $singleItemRow[$tagLineKey])
));
array_push($extPanels, $generalInformationPanel);

// Create the "Individual Information" panel from the singleItemRow.
$individualInformationPanel = getExtFormPanel("Individual Information", array(
	getExtComponent("Item Number", $singleItemRow[$itemNumberKey]),
	getExtComponent("UPC", getEncodedUpcCode($singleItemRow[$upcKey])),
	getExtComponent("Gross Weight (oz.)", $singleItemRow[$unitWeightKey]),
	getExtComponent("Ingredient Statment", $singleItemRow[$ingredientsKey]),
	getExtComponent("Allergen Statment", $singleItemRow[$allergensKey]),
	getExtComponent("Nutritional Data", "<img src=\"/dynamicPages/getImage/?id=$singleItemRow[$usNutritionLabelImageIdKey]\" width=\"250\"/>"),
	getExtComponent("Frozen Shelf Life", $singleItemRow[$frozenShelfLifeKey]),
	getExtComponent("Frozen Temp Range", $singleItemRow[$frozenTemperatureRangeKey]),
	getExtComponent("Refrigerated Shelf Life", $singleItemRow[$refrigeratedShelfLifeKey]),
	getExtComponent("Refrigerated Temp Range", $singleItemRow[$refrigeratedTemperatureRangeKey]),
	getExtComponent("Room Temp Shelf Life", $singleItemRow[$roomShelfLifeKey]),
	getExtComponent("Room Temp Range", $singleItemRow[$roomTemperatureRangeKey]),
	getExtComponent("Production Code", $singleItemRow[$productionCodeKey]),
	getExtComponent("Kosher Status", $singleItemRow[$kosherStatusKey]),
	getExtComponent("Label Image", "<img src=\"/downloads/label_scans/$singleItemRow[$itemNumberKey].png\" width=\"250\"/>")
));
array_push($extPanels, $individualInformationPanel);

// Remove the singleItemRow, because we are done with it.
// We now will create a panel for each row that has a pack > 1.
unset($packToRowMap[1]);
// Sort the array keys (which are the pack values) in numerica ascending order.
ksort($packToRowMap);

foreach ($packToRowMap as $pack => $multipleItemRow) {
	// Create the "Group Information" panel for each pack value.
	$groupInformationPanel = getExtFormPanel("Group Information ($pack)", array(
		getExtComponent("Item Number", $multipleItemRow[$itemNumberKey]),
		getExtComponent("UPC", getEncodedUpcCode($multipleItemRow[$upcKey])),
		getExtComponent("Gross Weight (lbs.)", $multipleItemRow[$grossWeightKey]),
		getExtComponent("Pack / Unit", "$multipleItemRow[$packKey] / $multipleItemRow[$unitWeightKey]"),
		getExtComponent("Case Cube", $multipleItemRow[$caseCubeKey]),
		getExtComponent("Ti x Hi", "$multipleItemRow[$casesPerPalletLayerKey] / $multipleItemRow[$caseLayersPerPalletKey]"),
		getExtComponent("Case (L'' X W'' X H'')", $multipleItemRow[$caseDimensionsKey]),
		getExtComponent("Production Code", $multipleItemRow[$productionCodeKey]),
		getExtComponent("Label Image", "<img src=\"/downloads/label_scans/$multipleItemRow[$itemNumberKey].png\" width=\"250\"/>")
	));
	array_push($extPanels, $groupInformationPanel);
}

$title = "Chuckanut Bay Foods: $singleItemRow[$descriptionKey]";
if ($shouldPrint) {
	$title .= " - Print";
} else if ($shouldSaveAsPdf) {
	$title .= " - Save As PDF";
}
require('../common/htmlToTitle.php');
require('../common/extJsIncludes.php');
?>

<style>
	.x-form-item label {
		font-weight : bold;
		padding-top: 0px;
		padding-left: 3px;
	}
	
	.print-icon {
	    background-image:url(../common/images/icons/silk/printer.png) !important;
	}
	
	.pdf-icon {
	    background-image:url(../common/images/icons/silk/page_white_acrobat.png) !important;
	}
</style>

<script>
	Ext.onReady(function() {
		/**
		 * Handler for when the "Print" button is clicked.
		 * It opens a new window to this url with "action=print" appended so that we know to print the page.
		 */
		var printHandler = function() {
			window.open(document.location.href + '&action=print', "Print", "scrollbars=yes,resizable=yes,width=640,height=480,menubar,toolbar,location");
		};
		
		/**
		 * Handerl for when the "Save As PDF" button is clicked.
		 * Opens a new window with a properly formatted url for savepageaspdf.pdfonline.com that takes care of generating the PDF.
		 */
		var saveAsPdfHandler = function() {
			var requestParameters = {
				cURL : document.location.href + '&action=saveAsPdf',
				author_id : '77C411E0-09A1-407F-A88B-4F198C1BD5D7',
				page : '0',
				top : '0.5',
				bottom : '0.5',
				left : '0.5',
				right : '0.5'
			}
			var url = 'http://savepageaspdf.pdfonline.com/pdfonline/pdfonline.asp?' + Ext.urlEncode(requestParameters);
			window.open(url, "SaveAsPdf", "scrollbars=yes,resizable=yes,width=640,height=480,menubar,toolbar,location");	
		};
		
		var shouldPrint = <?php echoBooleanForJavaScript($shouldPrint); ?>;
		var shouldSaveAsPdf = <?php echoBooleanForJavaScript($shouldSaveAsPdf); ?>;
		
		var containerCfg = {items : <?php echo(json_encode($extPanels)); ?>};
		if (!shouldPrint && !shouldSaveAsPdf) {
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
		} else { // should print or save as PDF
			Ext.apply(containerCfg, {
				xtype : 'container',
				layout : 'table',
				layoutConfig: {
				    columns: 1
				}
			});
		}
		var container = Ext.ComponentMgr.create(containerCfg);
		container.render(Ext.getBody());
		
		if (shouldPrint) {
			window.print();
			window.close();
		}
	});
	
</script>

<?php
require('../common/endHeadToBody.php');
require('../common/footer.php');
?>
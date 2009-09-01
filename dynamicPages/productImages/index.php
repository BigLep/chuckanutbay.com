<?php

// create the database connection and import common methods
require("../common/databaseConnection.php");
require("../common/util.php");

// Determine whether we should print, saveAsPdf, or just display.
$shouldPrint = (bool)(htmlspecialchars($_GET["action"]) == "print");
$shouldSaveAsPdf = (bool)(htmlspecialchars($_GET["action"]) == "saveAsPdf");

// Load product id and generate the idBase
$id = htmlspecialchars($_GET["id"]);
$idBase = getItemIdBase($id);
// Check if idBase is valid
if (!$idBase) {
	echo("$id doesn't have a valid idBase");
	return;
}
$id = "$idBase-1";


// see if there's a quickbooks_item with this idBase
// quickbook_items
$itemNumberKey = "itemNumber";
$upcKey = "upc";
$descriptionKey = "description";
$grossWeightLbKey = "grossWeightLb";
$packKey = "pack";
$caseCubeKey = "caseCube";
$unitWeightOzKey = "unitWeightOz";
// nutrition_label
$usNutritionLabelImageIdKey= "usNutritionLabelImageId";
// quickbook_item_supplements
$sizeKey = "size";
$productTypeKey = "productType";

// Database Query
$quickBooksItemIdQuery = createSqlQuery(
	"SELECT qbi.id as '$itemNumberKey'",
	     ", qbi.pack as '$packKey'",
		 ", qbi.unit_weight_oz as '$unitWeightOzKey'",
		 ", qbi.case_cube as '$caseCubeKey'",
	     ", qbi.upc as '$upcKey'",
		 ", qbi.description as '$descriptionKey'",
		 ", qbi.gross_weight_lb as '$grossWeightLbKey'", 
		 ", nl.us_label_image_id as '$usNutritionLabelImageIdKey'",
		 ", qbis.size as '$sizeKey'",
		 ", qbis.product_type as '$productTypeKey'",
	"FROM ((quickbooks_items qbi",  
		 "LEFT JOIN quickbooks_item_supplements qbis ON qbi.quickbooks_item_supplement_id = qbis.id)", 
		 "LEFT JOIN nutrition_labels nl ON qbi.nutrition_label_id = nl.id)",
	"WHERE qbi.id LIKE '$id'"
);
	
$result = queryDb($quickBooksItemIdQuery);
if (mysql_num_rows($result) == 0) { // quickbooks_item with this id doesn't exist
	echo("No information for products with id: $id.");
	return;
}

$singleItemRow = mysql_fetch_assoc($result);

$extPanels = array();

// General Information Panel
array_push($extPanels, getExtFormPanel("General Information", array(
	getExtComponent("Product", $singleItemRow[$descriptionKey]),
	getExtComponent("Item Number", $singleItemRow[$itemNumberKey]),
	getExtComponent("UPC", getEncodedUpcCode($singleItemRow[$upcKey])),
	getExtComponent("Gross Weight (oz.)", $singleItemRow[$unitWeightOzKey]),
)));

// Product Images Panel
$size = $singleItemRow[$sizeKey];
$productType = $singleItemRow[$productTypeKey];

$productImages = array();
if (doesProductImageExist($productType, $size, $idBase,"Marketing1-LowRes")){
	array_push($productImages, getExtComponent("Photo 1", getProductImageHtml($productType, $size, $idBase,"marketing1-LowRes")));
}
if (doesProductImageExist($productType, $size, $idBase,"Marketing2-LowRes")){
	array_push($productImages, getExtComponent("Photo 2", getProductImageHtml($productType, $size, $idBase,"marketing2-LowRes")));
}
if (doesProductImageExist($productType, $size, $idBase,"Marketing3-LowRes")){
	array_push($productImages, getExtComponent("Photo 3", getProductImageHtml($productType, $size, $idBase,"marketing3-LowRes")));
}
if (doesProductImageExist($productType, $size, $idBase,"Packaged-LowRes")){
	array_push($productImages, getExtComponent("Packaging", getProductImageHtml($productType, $size, $idBase,"Packaged-LowRes")));
}
array_push($extPanels, getExtFormPanel("Product Images", $productImages));

// Nutrition Label Panel
array_push($extPanels, getExtFormPanel("Nutrition Label", array(
	getExtComponent("Nutritional Data", "<img src=\"../getImage/?id=$singleItemRow[$usNutritionLabelImageIdKey]\" width=\"250\"/>")
)));

// Label Images Panel
$labelImages = array();
if (doesProductImageExist($productType, $size, $idBase,"Label")){
	array_push($labelImages, getExtComponent("Individual Label", getProductImageHtml($productType, $size, $idBase,"Label")));
}
if (doesProductImageExist($productType, $size, $idBase,"Label1")){
	array_push($labelImages, getExtComponent("Individual Label 2", getProductImageHtml($productType, $size, $idBase,"Label1")));
}
if (doesProductImageExist($productType, $size, $idBase,"CaseLabel")){
	array_push($labelImages, getExtComponent("Case Label", getProductImageHtml($productType, $size, $idBase,"CaseLabel")));
}
array_push($extPanels, getExtFormPanel("Labels", $labelImages));

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
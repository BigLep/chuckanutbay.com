<?php
	
	main();
	
	/**
	 * The main method for importing QuickBooks CSV items.
	 * For all the files within a directory, will attempt to parse it if it's a CSV data.
	 * Every record of the data that has an a complete id (e.g. ######-#) is imported.
	 * @return 
	 */
	function main() {
		// create the database connection and import common methods
		require "../../dynamicPages/common/databaseConnection.php";
		require "../../dynamicPages/common/util.php";
		
		// get the files to process
	    $dirToProcess = "toProcess/";
		echo("Looking for files to process in \"$dirToProcess\"\n");
		$filesToProcess = scandir($dirToProcess);
	
		// setup the output directory where the files will be copied
		$processedBaseDir = "processed/";
		$processedDir = $processedBaseDir.strftime("%Y-%m-%d_%H:%M").'/';
		echo("Once these files are processed, they'll be moved to \"$processedDir\".\n");
		mkdir($processedDir, 0777, true); // ensure the directory is readable, writable, and executable for the owner, group, and others
		
		// for every file, attempt to import records if it's csv data
		foreach($filesToProcess as $path) {
			$fileName = basename($path);
			echo("Found \"$fileName\" to process.\n");
			$matched = preg_match("/.csv$/i", $fileName);
			if ($matched) { // the file is a csv file
				// import the file
				echo("\tProcessing...\n");
				importQuickBooksItemsFromFile($dirToProcess.$fileName);
				// move the file to the processed directory
				echo("\tMoving \"$fileName\" to $processedDir\n");
				$srcPath = $dirToProcess.$fileName;
				$destPath = $processedDir.$fileName;
				copy($srcPath, $destPath);
				unlink($srcPath);
			} else {
				echo("\tIgnoring as it doesn't end with \".csv\".\n");
			}
		}
		
		// close the database connection
		mysql_close($dbConnection);
	}
	
	/**
	 * Imports data into quickbook_items from the provided file.
	 * It's expected that the provided file is a CSV file and that the first row is a header row, 
	 * with the following columns:
	 * - Item
	 * - Description
	 * - Prcie
	 * - UPC / GTIN
	 * - Gross Wt (lbs)
	 * - Pack/Unit
	 * - Case Cube
	 * @return 
	 * @param $path String path to the csv file to import
	 */
	function importQuickBooksItemsFromFile($path) {
		// open the file for reading
		$handle = fopen($path, "r");
		// read the header row and determine the column index for the columns we're interested in
		$headerRow = fgetcsv($handle);
		$itemIndex = array_search("Item", $headerRow);
		$descriptionIndex = array_search("Description", $headerRow);
		$priceIndex = array_search("Price", $headerRow);
		$upcIndex = array_search("UPC / GTIN", $headerRow);
		$grossWeightLbIndex = array_search("Gross Wt (lbs)", $headerRow);
		$packUnitIndex = array_search("Pack/Unit", $headerRow);
		$caseCubeIndex = array_search("Case Cube", $headerRow);
		// attempt to import the remaining rows
		while (($row = fgetcsv($handle)) !== FALSE) { // for every row in the file, parse it as CSV
			// See if the item column has a valid id.
			// For example: if the item column is: 9":16 Slice:1612-1
			// the item id would be 1612-1.
			$matched = preg_match("/:([0-9-]+)$/", $row[$itemIndex], $matchGroups);
			if ($matched) {
				$itemId = $matchGroups[1];
				$description = $row[$descriptionIndex];
				$price = $row[$priceIndex];
				$upc = $row[$upcIndex];
				$grossWeightLb = $row[$grossWeightLbIndex] ? $row[$grossWeightLbIndex] : 0;
				// Get the pack and unit, which are one columns.
				// We initialize them to zero, but will update their value if we have valid data within the column.
				$pack = 0;
				$unitWeightOz = 0;
				// attempts to pull out the pack and unit from the column.
				// Example column value to match against: "1 / 5.5 oz""
				$matched = preg_match("/^([0-9]+) \/ ([0-9.]+) oz$/", $row[$packUnitIndex], $matchGroups);
				if ($matched) {
					$pack = $matchGroups[1];
					$unitWeightOz = $matchGroups[2];
				}
				$caseCube = $row[$caseCubeIndex];
				// udpate the database with the information extracte from the row
				insertOrUpdateQuickBooksItem($itemId, $description, $price, $upc, $grossWeightLb, $pack, $unitWeightOz, $caseCube);
			}
		}
		// close the file
		fclose($handle);
	}
	
	/**
	 * Insert or update the quickbooks_items table with the provided values.
	 * Note: the nutrition_label_id and quickbooks_item_supplement_id field are both given the value of the provided.
	 * @return 
	 * @param $id String id of the item.
	 * @param $description String
	 * @param $price Decimal
	 * @param $upc String
	 * @param $grossWeightLb Decimal
	 * @param $pack Integer
	 * @param $unitWeightOz Decimal
	 * @param $caseCube Decimal
	 */
	function insertOrUpdateQuickBooksItem($id, $description, $price, $upc, $grossWeightLb, $pack, $unitWeightOz, $caseCube) {
		echo("\tInserting/updating QuickBooks item:\n");
		echoWithIndentAndCutoff("id", $id, "\t\t", 100);
		echoWithIndentAndCutoff("description", $description, "\t\t", 100);
		echoWithIndentAndCutoff("price", $price, "\t\t", 100);
		echoWithIndentAndCutoff("upc", $upc, "\t\t", 100);
		echoWithIndentAndCutoff("grossWeightLb", $grossWeightLb, "\t\t", 100);
		echoWithIndentAndCutoff("pack", $pack, "\t\t", 100);
		echoWithIndentAndCutoff("unitWeightOz", $unitWeightOz, "\t\t", 100);
		// The gross weight in grams is not in the file, but we can calculate it from the gross weight in ounces.
		// There are 28.35 g per 1 oz.
		$unitWeightG = $unitWeightOz * 28.35;
		echoWithIndentAndCutoff("unitWeightG", $unitWeightG, "\t\t", 100);
		echoWithIndentAndCutoff("caseCube", $caseCube, "\t\t", 100);
		
		$itemId = mysql_real_escape_string($id);
		$description = mysql_real_escape_string($description);
		$price = mysql_real_escape_string($price);
		$upc = mysql_real_escape_string($upc);
		$grossWeightLb = mysql_real_escape_string($grossWeightLb);
		$pack = mysql_real_escape_string($pack);
		$unitWeightOz = mysql_real_escape_string($unitWeightOz);
		$unitWeightG = mysql_real_escape_string($unitWeightG);
		$caseCube = mysql_real_escape_string($caseCube);
		$caseCube = empty($caseCube) ? 'null' : $caseCube;

		// see if there's a quickbooks_item with this ide
		$quickBooksItemIdQuery = 
			"SELECT id " . 
			"FROM quickbooks_items " .
			"WHERE id = '$id'";
		$result = queryDb($quickBooksItemIdQuery);
		if (mysql_num_rows($result) == 0) { // quickbooks_item with this id doesn't exist
			$insertQuery = 
				"INSERT INTO quickbooks_items " .
				"(id, description, price, upc, gross_weight_lb, pack, unit_weight_oz, unit_weight_g, case_cube) " .
				"VALUES " .
				"('$id', '$description', $price, '$upc', $grossWeightLb, $pack, $unitWeightOz, $unitWeightG, $caseCube)";
			queryDb($insertQuery);
		} else { // a quickbooks_item with this id already exists
			$updateQuery =
				"UPDATE quickbooks_items " .
				"SET description='$description', 
				     price=$price, upc='$upc', 
					 gross_weight_lb=$grossWeightLb, 
					 pack=$pack, 
					 unit_weight_oz=$unitWeightOz,
					 unit_weight_g=$unitWeightG,
					 case_cube=$caseCube " .
				"WHERE id='$id'";
			queryDb($updateQuery);
		}
	}
?>

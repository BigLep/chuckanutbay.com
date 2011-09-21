<?php

	main();
	
	/**
	 * The main method for importing Inventory CSV items.
	 * For all the files within a directory, will attempt to parse it if it's a CSV data.
	 * Every record of the data that has an a complete id (e.g. ######-#) is imported.
	 * @return 
	 */
	function main() {
		// create the database connection and import common methods
		require "../Common/databaseConnection.php";
		require "../Common/util.php";
		
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
				importInventoryItemsFromFile($dirToProcess.$fileName);
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
	 * - U/M
	 * @return 
	 * @param $path String path to the csv file to import
	 */
	function importInventoryItemsFromFile($path) {
		// open the file for reading
		$handle = fopen($path, "r");
		// read the header row and determine the column index for the columns we're interested in
		$headerRow = fgetcsv($handle);
		$itemIndex = array_search("Item", $headerRow);
		$descriptionIndex = array_search("Description", $headerRow);
		$unitOfMeasureIndex = array_search("U/M", $headerRow);
		// attempt to import the remaining rows
		while (($row = fgetcsv($handle)) !== FALSE) { // for every row in the file, parse it as CSV
			$itemId = $row[$itemIndex];
			$description = $row[$descriptionIndex];
			$unitOfMeasure = $row[$unitOfMeasureIndex];
			// udpate the database with the information extracte from the row
			insertOrUpdateInventoryItem($itemId, $description, $unitOfMeasure);
		}
		// close the file
		fclose($handle);
	}
	
	/**
	 * Insert or update the inventory_items table with the provided values.
	 * @return 
	 * @param $id String id of the item.
	 * @param $description String
	 * @param $unitOfMeasure String
	 */
	function insertOrUpdateInventoryItem($id, $description, $unitOfMeasure) {
		echo("\tInserting/updating Inventory item:\n");
		echoWithIndentAndCutoff("id", $id, "\t\t", 100);
		echoWithIndentAndCutoff("description", $description, "\t\t", 100);
		echoWithIndentAndCutoff("unit of measure", $unitOfMeasure, "\t\t", 100);
		$itemId = mysql_real_escape_string($id);
		$description = mysql_real_escape_string($description);
		$unitOfMeasure = mysql_real_escape_string($unitOfMeasure);

		// see if there's a quickbooks_item with this ide
		$inventoryItemIdQuery = 
			"SELECT id " . 
			"FROM inventory_items " .
			"WHERE id = '$id'";
		$result = queryDb($inventoryItemIdQuery);
		if (mysql_num_rows($result) == 0) { // inventory_item with this id doesn't exist
			$insertQuery = 
				"INSERT INTO inventory_items " .
				"(id, description, unit_of_measure) " .
				"VALUES " .
				"('$id', '$description', '$unitOfMeasure')";
			queryDb($insertQuery);
		} else { // an inventory_item with this id already exists
			$updateQuery =
				"UPDATE inventory_items " .
				"SET description='$description',
					 unit_of_measure='$unitOfMeasure' " .
				"WHERE id='$id'";
			queryDb($updateQuery);
		}
	}
?>

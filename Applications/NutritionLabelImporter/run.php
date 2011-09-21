<?php
	
	main();
	
	/**
	 * The main method for creating nutrition label records.
	 * For all the files within a directory, will create/update nutrion_labels rows in the database.
	 * An item id base (the part of the quickbooks_item_id before the "-1") is extracted from each file,
	 * and then a nutritition label is created/updated for each id base.
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
		$processedDir = $processedBaseDir.strftime("%Y-%m-%d_%H:%M:%S").'/';
		echo("Once these files are processed, they'll be moved to \"$processedDir\".\n");
		mkdir($processedDir, 0777, true);  // ensure the directory is readable, writable, and executable for the owner, group, and others
		
		// map of item id base (the digits before the "-1") to the array of files that correspond with that item id
		$itemIdBaseToFilesMap = array();
		
		// for every file, create a group of files for each itemId base
		foreach($filesToProcess as $path) {
			$fileName = basename($path);
			echo("Found \"$fileName\" to process.\n");
			// get the item id base
			$matched = preg_match("/^(\d+)-1/", $fileName, $matchGroups);
			if (!$matched) {
				echo("\tIgnoring as it doesn't have an item id.\n");
				continue;
			}
			$itemIdBase = $matchGroups[1];
			echo("\tHas item id base: \"$itemIdBase\"");
			$filesForItemIdBase = &$itemIdBaseToFilesMap[$itemIdBase];
			if ($filesForItemIdBase == null) { // we don't have an array for this item id base, so create one and store it in the map
				$filesForItemIdBase = array();
				$itemIdBaseToFilesMap[$itemIdBase] = &$filesForItemIdBase;
			}
			// add the file to end of the array
			array_push($filesForItemIdBase, $fileName);
			echo("\n");
		}
		
		echo("\n");
		
		// process each item id base
		foreach($itemIdBaseToFilesMap as $itemIdBase => $fileNames) {
			echo(str_repeat("-", 80) . "\n");
			echo("Processing files with item id base: \"$itemIdBase\"\n");
			$usLabelImageId;
			$cdnLabelImageId = null; // Canadian label is not required
			$ingredientsText;
			$allergensText;
			
			// process the files for each itemIdBase
			foreach($fileNames as $fileName) {
				if (isImage($fileName)) {
					echo("\t\"$fileName\" is an image.\n");
					// load the image into the database
					$imageId = insertOrUpdateImage($dirToProcess.$fileName);
					if (strstr($fileName, "CDN") != null) {  // filename has CDN within it, must be a Canadian image
						$cdnLabelImageId = $imageId;
					} else {
						$usLabelImageId = $imageId;
					}
				} else if (strstr($fileName, "Ingredient") != null) { // ingredients
					echo("\t\"$fileName\" is an ingredients file.\n");
					$ingredientsTextLines = file($dirToProcess.$fileName); // read the contents of the file
					$ingredientsText = $ingredientsTextLines[1]; // index 0 has an unnecessary header, we just read the second line (index 1)
				} else if (strstr($fileName, "Allergens") != null) { // allergens
					echo("\t\"$fileName\" is an allergens file.\n");
					$allergensTextLines = file($dirToProcess.$fileName); // read the contents of the file
					array_shift($allergensTextLines);
					$allergensText = implode("\n",$allergensTextLines); // index 0 has an unnecessary header, we just read everything after the first line
				}
			}
			
			echo("\n");
			
			// confirm that we have a US label image id
			if (is_null($usLabelImageId)) {
				echo("\tNo US label image exists with this base.  Skipping...\n\n");
				continue;
			}
			
			// insert/update nutrition label in db
			insertOrUpdateNutritionLabel($itemIdBase, $usLabelImageId, $cdnLabelImageId, $ingredientsText, $allergensText);
			
			echo("\n");
			
			// move the files to the processed directory
			foreach($fileNames as $fileName) {
				echo("\tMoving \"$fileName\" to $processedDir\n");
				$srcPath = $dirToProcess.$fileName;
				$destPath = $processedDir.$fileName;
				copy($srcPath, $destPath);
				unlink($srcPath);
			}
			
			echo("\n");
		}
		// close the database conection
		mysql_close($dbConnection);
	}
	
	
	/**
	 * Loads the file at the provided path into the database.
	 * @return id of the image that was inserted/updated.
	 * @param $path String for the path to the file to load
	 */
	function insertOrUpdateImage($path) {
		$imageId;
		$fileName = basename($path);
		echo("\t\tLoading \"$fileName\" into the images table.\n");
		
		// escape the database input to make sure its safe
		$imageName = mysql_real_escape_string($fileName);
		$imageData = mysql_real_escape_string(file_get_contents($path));
		$imageSizeArray = getimagesize($path);
		$imageWidth = mysql_real_escape_string($imageSizeArray[0]);
		$imageHeight = mysql_real_escape_string($imageSizeArray[1]);
		$imageMimeType = mysql_real_escape_string($imageSizeArray["mime"]);
		
		// Define the shared column-value pairs that will be used if we insert or update.
		// When we insert, we add additional column-values.
		$commonColumnValuePairs = array(
			"data" => "'$imageData'", 
			"width" => "'$imageWidth'",
			"height" => "'$imageHeight'",
			"mime_type" => "'$imageMimeType'"
		);
		
		// see if an image exists with this name yet
		$idQuery = createSqlQuery(
			"SELECT id", 
			"FROM images",
			"WHERE name = '$imageName'"
		);
		$result = queryDb($idQuery);
		if (mysql_num_rows($result) == 0) { // an image with this name doesn't exist
			echo("\t\tAn image with this name doesn't exist.  Inserting it.\n");
			$insertQuery = createSqlInsertQuery("images", array_merge(array(
				"id" => "''",
				"name" => "'$imageName'"
			), $commonColumnValuePairs));
			queryDb($insertQuery);
			$result = queryDb($idQuery);
			$imageIdRow = mysql_fetch_array($result);
			$imageId = $imageIdRow[0];
		} else { // an image with this name has already been inserted
			echo("\t\tAn image with this name already exists.  Updating the image data and size.\n");
			$imageIdRow = mysql_fetch_array($result);
			$imageId = $imageIdRow[0];
			$updateQuery = createSqlQuery(
				"UPDATE images",
				createSqlSetString($commonColumnValuePairs),
				"WHERE id=$imageId"
			);
			queryDb($updateQuery);
		}
		return $imageId;
	}
	
	/**
	 * Inserts or updates a nutrition label for the provided $itemIdBase with provided values.
	 * @return nothing returned
	 * @param $itemIdBase String id of the quickbooks item id base this nutrition label is for
	 * @param $usLabelImageId int id of the US label in the images table.
	 * @param $cdnLabelImageId int id of the Canadian label in the images table.  Can be null.
	 * @param $ingredientsText String the ingredients text for the label.
	 * @param $allergensText String the allergens text for the label.
	 */
	function insertOrUpdateNutritionLabel($itemIdBase, $usLabelImageId, $cdnLabelImageId, $ingredientsText, $allergensText) {
		// if the Canadian label is empty, set it to the string of null value for updating the database
		if (is_null($cdnLabelImageId)) {
			$cdnLabelImageId = 'null';
		}
		$ingredientsText = mysql_real_escape_string(trim($ingredientsText));
		$allergensText = mysql_real_escape_string(trim($allergensText));
		
		echo("\tInserting/updating nutrion label:\n");
		$columnValuePairs = array(
			"id" => "'$itemIdBase'", 
			"us_label_image_id" => $usLabelImageId, 
			"cdn_label_image_id" => $cdnLabelImageId, 
			"ingredients" => "'$ingredientsText'", 
			"allergens" => "'$allergensText'"
		);
		foreach ($columnValuePairs as $column => $value) {
			echoWithIndentAndCutoff($column, $value, "\t\t", 100);
		}
		
		// determine whether we have a nutrition label for the provided it
		$nutritionLabelIdQuery = createSqlQuery(
			"SELECT id",
			"FROM nutrition_labels",
			"WHERE id = '$itemIdBase'"
		);
		$result = queryDb($nutritionLabelIdQuery);
		
		if (mysql_num_rows($result) == 0) { // we don't have a label for this id
			if (empty($itemIdBase) || empty($usLabelImageId) || empty($ingredientsText) || empty($allergensText)) {
				echo("\t\tRequired value is missing.  Skipping...\n");
			}
			$insertQuery = createSqlInsertQuery("nutrition_labels", $columnValuePairs);
			queryDb($insertQuery);
		} else { // a nutrition label with this itemIdBase already exists
			$updateQuery = createSqlQuery(
				"UPDATE nutrition_labels",
				createSqlSetString($columnValuePairs),
				"WHERE id='$itemIdBase'"
			);
			queryDb($updateQuery);
		}
	}
	
	/**
	 * @return true if the provide file name is an image; false if not.
	 * @param $fileName String name of file
	 */
	function isImage($fileName) {
		return preg_match("/(jpg|gif|png|jpeg)/i", $fileName);
	}	
?>

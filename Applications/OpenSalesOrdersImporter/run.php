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
				
				closeAllOrders();
				
				importOpenSalesOrdersFromFile($dirToProcess.$fileName);
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
	 * - Special Instructions
	 * @return 
	 * @param $path String path to the csv file to import
	 */
	function importOpenSalesOrdersFromFile($path) {
		echo("\tOpenning Sales Orders From File\n");
		// open the file for reading
		$handle = fopen($path, "r");
		// read the header row and determine the column index for the columns we're interested in
		$headerRow = fgetcsv($handle);
		$salesOrderNumberIndex = array_search("Num", $headerRow);
		$poNumberIndex = array_search("P. O. #", $headerRow);
		$customerNameIndex = array_search("Name", $headerRow);
		$shipDateIndex = array_search("Ship Date", $headerRow);
		$shipToAddressOneIndex = array_search("Ship To Address 1", $headerRow);
		$shipToAddressTwoIndex = array_search("Ship To Address 2", $headerRow);
		$shipToCityIndex = array_search("Ship To City", $headerRow);
		$shipToStateIndex = array_search("Ship To State", $headerRow);
		$shipToZipCodeIndex = array_search("Ship Zip", $headerRow);
		$specialInstructionsIndex = array_search("Special Instructions", $headerRow);
		$quickBooksItemIdIndex = array_search("Item", $headerRow);
		$casesIndex = array_search("Qty", $headerRow);
		$amountIndex = array_search("Amount", $headerRow);
		// attempt to import the remaining rows
		echo("\tAttempting to import each valid row from file\n");
		while (($row = fgetcsv($handle)) !== FALSE) { // for every row in the file, parse it as CSV
			//echo("\tChecking a new row\n");
			$matched = preg_match("/[0-9]{1,}/", $row[$salesOrderNumberIndex], $matchGroups);
			if ($matched) {
				//echo("\tRow is valid\n");
				$salesOrderNumber = $row[$salesOrderNumberIndex];
				$poNumber = $row[$poNumberIndex];
				$customerName = $row[$customerNameIndex];
				$shipToAddressOne = $row[$shipToAddressOneIndex];
				$shipToAddressTwo = $row[$shipToAddressTwoIndex];
				$shipToAddressThree = $row[$shipToCityIndex] . " " . $row[$shipToStateIndex] . " " . $row[$shipToZipCodeIndex];
				//echo ("\tship date: " . $row[$shipDateIndex] . "\n");
				$shipDate = date("Y-m-d", strtotime($row[$shipDateIndex]));
				preg_match("/:([0-9-]+)$/", $row[$quickBooksItemIdIndex], $matchGroups);
				//echo ("\tquickbooks item id: " . $matchGroups[1] . "\n");
				$specialInstructions = $row[$specialInstructionsIndex];
				$quickBooksItemId = $matchGroups[1];
				$cases = $row[$casesIndex];
				$amount = $row[$amountIndex];
				
				// udpate the database with the information extracte from the row
				if(!in_array($salesOrderNumber, $salesOrderNumbersImported)) {
					insertOrUpdateSalesOrder($salesOrderNumber, $poNumber, $customerName, $shipToAddressOne, $shipToAddressTwo, $shipToAddressThree, $shipDate, $specialInstructions);
					$salesOrderNumbersImported[]=$salesOrderNumber;
				}
				insertOrUpdateSalesOrderLineItem($salesOrderNumber, $quickBooksItemId, $cases, $amount);
			}
		}
		// close the file
		fclose($handle);
	}
	
	/**
	 * Close every order in sales_orders so that only the orders added from quickbooks open orders export are marked as open.
	 * @return
	 */
	function closeAllOrders() {
		echo("\tSetting all orders to closed\n");
		$updateQuery =
				"UPDATE sales_orders " .
				"SET order_closed=1";
			queryDb($updateQuery);
	}
	/**
	 * Insert or update the sales_orders table with the provided values.
	 * @return 
	 * @param $salesOrderNumber Integer id of the item.
	 * @param $poNumber String
	 * @param $customerName String
	 * @param $shipToAddressOne String
	 * @param $shipToAddressTwo String
	 * @param $shipToAddressThree String
	 * @param $shipDate DateTime
	 * @param $specialInstructions String
	 */
	function insertOrUpdateSalesOrder($salesOrderNumber, $poNumber, $customerName, $shipToAddressOne, $shipToAddressTwo, $shipToAddressThree, $shipDate, $specialInstructions) {
		echo("\tInserting/updating Sales Order:\n");
		echoWithIndentAndCutoff("salesOrderNumber", $salesOrderNumber, "\t\t", 100);
		echoWithIndentAndCutoff("poNumber", $poNumber, "\t\t", 100);
		echoWithIndentAndCutoff("customerName", $customerName, "\t\t", 100);
		echoWithIndentAndCutoff("shipToAddressOne", $shipToAddressOne, "\t\t", 100);
		echoWithIndentAndCutoff("shipToAddressTwo", $shipToAddressTwo, "\t\t", 100);
		echoWithIndentAndCutoff("shipToAddressThree", $shipToAddressThree, "\t\t", 100);
		echoWithIndentAndCutoff("shipDate", $shipDate, "\t\t", 100);
		echoWithIndentAndCutoff("specialInstructions", $specialInstructions, "\t\t", 100);
		
		$salesOrderNumber = mysql_real_escape_string($salesOrderNumber);
		$poNumber = mysql_real_escape_string($poNumber);
		$customerName = mysql_real_escape_string($customerName);
		$shipToAddressOne = mysql_real_escape_string($shipToAddressOne);
		$shipToAddressTwo = mysql_real_escape_string($shipToAddressTwo);
		$shipToAddressThree = mysql_real_escape_string($shipToAddressThree);
		$shipDate = mysql_real_escape_string($shipDate);
		$specialInstructions = mysql_real_escape_string($specialInstructions);

		// see if there's a sales_order with this id
		$salesOrdersIdQuery = 
			"SELECT id " . 
			"FROM sales_orders " .
			"WHERE id = $salesOrderNumber";
		$result = queryDb($salesOrdersIdQuery);
		if (mysql_num_rows($result) == 0) { // sales_order with this id doesn't exist
			$insertQuery = 
				"INSERT INTO sales_orders " .
				"(id, purchase_order, customer_name, ship_date, address_line_one, address_line_two, address_line_three, special_instructions, order_closed) " .
				"VALUES " .
				"($salesOrderNumber, '$poNumber', '$customerName', '$shipDate', '$shipToAddressOne', '$shipToAddressTwo', '$shipToAddressThree', '$specialInstructions', 0)";
			queryDb($insertQuery);
		} else { // a sales_order with this id already exists
			$updateQuery =
				"UPDATE sales_orders " .
				"SET purchase_order='$poNumber',
					 customer_name='$customerName', 
					 ship_date='$shipDate', 
					 address_line_one='$shipToAddressOne',
					 address_line_two='$shipToAddressTwo',
					 address_line_three='$shipToAddressThree',
					 special_instructions='$specialInstructions',
					 order_closed=0 " .
				"WHERE id=$salesOrderNumber";
			queryDb($updateQuery);
			$updateQuery =
				"UPDATE sales_order_line_items " .
				"SET cases=0,
					 amount=0 " .
				"WHERE sales_order_id=$salesOrderNumber";
			queryDb($updateQuery);
		}
	}
	/**
	 * Insert or update the sales_order_line_items table with the provided values.
	 * @return 
	 * @param $salesOrderId Integer id of the sales_order.
	 * @param $quickBooksItemId String id of the quickboos_item.
	 * @param $cases Integer of the number of cases.
	 * @param $shipToAddressOne Decemal of the cost of the line item.
	 */
	function insertOrUpdateSalesOrderLineItem($salesOrderId, $quickBooksItemId, $cases, $amount) {
		if ($quickBooksItemId == null) {
			echo("\tLine item does not have a QB Item id and will not be imported to database.\n");
		} else {
			echo("\tInserting/updating Sales Order Line Item:\n");
			echoWithIndentAndCutoff("salesOrderId", $salesOrderId, "\t\t", 100);
			echoWithIndentAndCutoff("quickBooksItemId", $quickBooksItemId, "\t\t", 100);
			echoWithIndentAndCutoff("cases", $cases, "\t\t", 100);
			echoWithIndentAndCutoff("amount", $amount, "\t\t", 100);
			
			$salesOrderId = mysql_real_escape_string($salesOrderId);
			$quickBooksItemId = mysql_real_escape_string($quickBooksItemId);
			$cases = mysql_real_escape_string($cases);
			$amount = mysql_real_escape_string($amount);
	
			// see if there's a sales_order_line_item with this sales_order_id and quickbooks_item_id
			$salesOrdersIdQuery = 
				"SELECT id " . 
				"FROM sales_order_line_items " .
				"WHERE sales_order_id = $salesOrderId and qb_item_id = '$quickBooksItemId'";
			$result = queryDb($salesOrdersIdQuery);
			echo("\tResults for query of sales order id (" . $salesOrderId . ") and  qb item id: (" . $quickBooksItemId . "): " . mysql_num_rows($result) . "\n");
			if (mysql_num_rows($result) == 0) { // sales_order with this id doesn't exist
				echo("\tline item with sales order id (" . $salesOrderId . ") and  qb item id: (" . $quickBooksItemId . ") does NOT exist\n");  
				$insertQuery = 
					"INSERT INTO sales_order_line_items " .
					"(sales_order_id, qb_item_id, cases, amount) " .
					"VALUES " .
					"($salesOrderId, '$quickBooksItemId', $cases, $amount)";
				queryDb($insertQuery);
			} else { // a sales_order with this id already exists
				echo("\tline item with sales order id (" . $salesOrderId . ") and  qb item id: (" . $quickBooksItemId . ") DOES exist\n");
				$updateQuery =
					"UPDATE sales_order_line_items " .
					"SET cases=(cases+$cases), 
						 amount=(amount+$amount) " .
					"WHERE sales_order_id = $salesOrderId and qb_item_id = '$quickBooksItemId'";
				queryDb($updateQuery);
			}
		}
	}
?>

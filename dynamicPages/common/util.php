<?php
	/*#######################################################################*
	 * File helper functions
	 *#######################################################################*/
	/**
	 * @param String $fileAbsolutePath
	 * @return Path of the provided file's directory, from the document root.
	 */
	function getDirectoryPathFromRoot($fileAbsolutePath) {
		return str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname($fileAbsolutePath));
	}
	
	
	/*#######################################################################*
	 * SQL helper functions
	 *#######################################################################*/
	/**
	 * @return Sql query where all the query lines have been joined together. 
	 * @param varags String argruments to make a SQL out of.
	 */
	function createSqlQuery() {
		$args = func_get_args();
		return join("\n", $args);
	}
	
	/**
	 * @return INSERT Sql query into the provided table where column names and values are defined in the provided associative array. 
	 * @param $tableName Name of the table to generate an INSERT query for.
	 * @param $columnValuePairs List of column names to values for inserting.
	 */
	function createSqlInsertQuery($tableName, $columnValuePairs) {
		return createSqlQuery(
			"INSERT INTO $tableName",
			"(" . join(",", array_keys($columnValuePairs)) . ")",
			"VALUES",
			"(" . join(",", array_values($columnValuePairs)) . ")"
		);
	}
	
	/**
	 * @return Sql SET string to use for an UPDATE query where the provided column names and values are provided. 
	 * @param $columnValuePairs Associative array of column names to values to set.
	 * @param $skipEmptyValues Whether empty values (including '') should be included in the SET string.
	 */
	function createSqlSetString($columnValuePairs, $skipEmptyValues = true) {
		$setStringParts = array();
		foreach($columnValuePairs as $column => $value) {
			if (!$skipEmptyValues || !(empty($value) || $value === "''")) {
				array_push($setStringParts, "$column=$value");
			}
		}
		return "SET " . join(",", $setStringParts);
	}

	/**
	 * @return result from running the provided query.
	 * @param String $query
	 */
	function queryDb($query) {
		$result = mysql_query($query) 
			or die(
				"Database query failed.\n" . 
				"Query: $query \n" .
				"Error: " . mysql_error());
		return $result;
	}
	
	
	/*#######################################################################*
	 * Output helper functions
	 *#######################################################################*/
	/**
	 * @return The provided value String prefixed with the provided indent and label.
	 * The echoed string will be no longer than the provided maxLength.
	 * @param $label String The label to prefix the value with.
	 * @param $value String The value to print.
	 * @param $indent String The String to indent with before printing the label.
	 * @param $maxLength int The maximium number of characters to print.
	 */
	function echoWithIndentAndCutoff($label, $value, $indent, $maxLength) {
		$str = $indent.$label." : \"".$value;
		if (strlen($str) > $maxLength) {
			$str = substr($str, 0, $maxLength);
			$str .= "...";
		}
		$str .= "\"\n";
		echo($str);
	}
	
	/**
	 * @return UPC code with proper spacing 
	 * 603812270304 -> 603812 27030 4 (for 12 digit codes)
	 * 10603812270304 -> 10 603812 27030 4 (for 14 digit codes)
	 * @param $upcCode Object
	 */
	function getFormattedUpcCode($upcCode) {
		$matched = preg_match("/^([0-9]{2})?([0-9]{6})([0-9]{5})([0-9]{1})$/", $upcCode, $matchGroups);
		if ($matched) {
			// Remove index 0 as its the entire UPC code.
			array_shift($matchGroups);
			// Remove the first match group for the 12 digit code case.
			if (empty($matchGroups[0])) {
				array_shift($matchGroups);
			}
			return join(" ", $matchGroups);
		} else {
			return $upcCode;
		}
	}
	
	/**
	 * @return the provided upcCode that has been formatted (@see getFormattedUpcCode($upcCode))
	 * and then been encoded for proper HTML output so that any formatting (especially spacing) shows up correctly.
	 * @param $upcCode String UPC code to format and encode.
	 */
	function getEncodedUpcCode($upcCode) {
		return htmlentities(getFormattedUpcCode($upcCode));
	}
	
	/**
	 * @return nothing as "true" is echoed if the boolean is true, or "false" is echoed otherwise.
	 * @param $boolean Boolean
	 */
	function echoBooleanForJavaScript($boolean) {
		echo($boolean ? "true" : "false");
	}
	
	
	/*#######################################################################*
	 * ExtJS helper functions
	 *#######################################################################*/
	/**
	 * @return associative array that can be encoded to JSON to represent an Ext.Component.
	 * @param $label String The label to give this component when it is part of an Ext.form.FormPanel.
	 * @param $html String The HTML to render for the component.  This is expected to be a simple text string or image tage.
	 */
	function getExtComponent($label, $html) {
		return array(
			"fieldLabel" => $label,
			"html" => $html
		);
	}
	
	/**
	 * @return associative array that can be encoded to JSON to represent an Ext.form.FormPanel.
	 * This Ext.form.FormPanel will have the provided title and include the provided items.
	 * @param $title String Title to give the panel.
	 * @param $items Object[] The Ext compatible items to include in the panel.
	 */
	function getExtFormPanel($title, $items) {
		global $shouldPrint, $shouldSaveAsPdf;
		return array(
			"layout" => "form",
			"title" => $title,
			"collapsible" => !($shouldPrint || $shouldSaveAsPdf),
			"labelWidth" => 150,
			"hideBorders" => true,
			"items" => $items
		);
	}
	
	
	/*#######################################################################*
	 * Chuckanut Bay Foods specific helper functions
	 *#######################################################################*/
	/**
	 * @return The id base of an id String.
	 * If 1401-1 is provided, 1401 should be returned.
	 * If 1401 is provided, then 1401 should be returned.
	 * Basically all digits before the "-" should be returned.
	 * If an id base cannot be found, then null will be returned.
	 * Note: this function will fail if there is proceeding or terminating whitespace.
	 * @param $id String The id to get the base for.
	 */
	function getItemIdBase($id) {
		$matched = preg_match("/^([0-9]+)(-[0-9]*)?$/", $id, $matchGroups);
		if ($matched) {
			return $matchGroups[1];
		} else {
			return null;
		}
	}
	
	/**
	 * @param String $productType Valid values are defined in the database: quickbooks_item_supplements.product_type.
	 * @param String $size Valid values are defined in the database: quickbooks_item_supplements.size.
	 * @param String $idBase A quickbooks_items.id with everything before the "-" (e.g., 1400 for 1400-1)
	 * @param String $imageType Valid values include: Label, Label1, CaseLabel, Marketing1, Marketing2, Marketing3, and Packaged 
	 * @return File path for the corresponding product image.
	 */
	function getProductImagePath($productType, $size, $idBase, $imageType) {
		return "../../Images/Products/$productType/$size/$idBase-$imageType.jpg";
	}
	
	/**
	 * @param String $productType Valid values are defined in the database: quickbooks_item_supplements.product_type.
	 * @param String $size Valid values are defined in the database: quickbooks_item_supplements.size.
	 * @param String $idBase A quickbooks_items.id with everything before the "-" (e.g., 1400 for 1400-1)
	 * @param String $imageType Valid values include: Label, Label1, CaseLabel, Marketing1, Marketing2, Marketing3, and Packaged 
	 * @return HTML Code for the given image Type
	 */
	function getProductImageHtml($productType, $size, $idBase, $imageType) {
		return "<img src= \"" . getProductImagePath($productType, $size, $idBase, $imageType) ."\" width=\"250\"/>";
	}
	
	/**
	 * @param String $productType Valid values are defined in the database: quickbooks_item_supplements.product_type.
	 * @param String $size Valid values are defined in the database: quickbooks_item_supplements.size.
	 * @param String $idBase A quickbooks_items.id with everything before the "-" (e.g., 1400 for 1400-1)
	 * @param String $imageType Valid values include: Label, Label1, CaseLabel, Marketing1, Marketing2, Marketing3, and Packaged 
	 * @return True of False if the image exists of not
	 */
	function doesProductImageExist($productType, $size, $idBase, $imageType) {
		return file_exists(getProductImagePath($productType, $size, $idBase, $imageType));
	}

?>
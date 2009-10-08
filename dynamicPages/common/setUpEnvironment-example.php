<?php
	/**
	 * @return {Boolean} true if this is a production environemnt, false if not. 
	 */
    function isProduction() {
    	return false;
    }
	
	/**
	 * @return array with these keys:
	 * - host: The host where the database lives.
	 * - login
	 * - password
	 * - name: Name of the database to connect. 
	 */
	function getDatabaseSettings() {
		if (isProduction()) {
			return array(
				"host" => "localhost",
				"login" => "FILL-ME-IN", 
				"password" => "FILL-ME-IN",
				"name" => "loeppky_chuckanutbayextra"
			);
		} else {
			return array(
				"host" => "localhost",
				"login" => "root", 
				"password" => "root",
				"name" => "loeppky_chuckanutbayextra"
			);
		}
	}
	
	/**
	 * @return API key to use with htm2pdf.co.uk web service.
	 * This file should not be checked in to source control.
	 */
	function getPdfServiceKey() {
		return "FILL-ME-IN";
	}
?>
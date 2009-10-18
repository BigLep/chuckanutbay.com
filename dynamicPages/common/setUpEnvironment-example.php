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
	 * @return Credentials to use with pdfonline.com service.
	 */
	function getPdfOnlineCredentials() {
		return array(
			"author_id" => "FILL-ME-IN",
			"username" => "FILL-ME-IN"
		);
	}
	
	/**
	 * @return Credentials use with htm2pdf.co.uk web service.
	 */
	function getHtm2PdfCredentials() {
		return array(
			"key" => "FILL-ME-IN"
		);
	}
?>
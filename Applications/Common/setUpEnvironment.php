<?php
	/**
	 * @return {Boolean} true if this is a production environemnt, false if not. 
	 */
	function isProduction() {
		return true;
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
				"login" => "root", 
				"password" => "root",
				"name" => "chuckanut_bay_internal"
			);
		} else {
			return array(
				"host" => "localhost",
				"login" => "root", 
				"password" => "root",
				"name" => "chuckanut_bay_internal"
			);
		}
	}
	
	/**
	 * @return Credentials to use with pdfonline.com service.
	 */
	function getPdfOnlineCredentials() {
		return array(
			"author_id" => "77C411E0-09A1-407F-A88B-4F198C1BD5D7",
			"username" => "chuckanutbay"
		);
	}
	
	/**
	 * @return Credentials use with htm2pdf.co.uk web service.
	 */
	function getHtm2PdfCredentials() {
		return array(
			"key" => "93787a37-2585-4e99-982e-c96561a6dd90"
		);
	}
?>
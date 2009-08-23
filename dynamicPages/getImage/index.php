<?php
// create the database connection and import common methods
require("../common/databaseConnection.php");
require("../common/util.php");

// Note: this code was derived from: http://www.anyexample.com/programming/php/php_mysql_example__image_gallery_(blob_storage).xml
$id = htmlspecialchars($_GET["id"]);

$imageQuery = join("\n", array(
	"SELECT mime_type, UNIX_TIMESTAMP(last_modified_time), data",
	"FROM images",
	"WHERE id=$id",
	"LIMIT 1"));
$result = queryDb($imageQuery);

if (mysql_num_rows($result) == 0) {
	die("No image with id: $id");
}
	
list($mimeType, $lastModifiedTime, $data) = mysql_fetch_row($result);

$HEADER_IF_MODIFIED_SINCE = "If-Modified-Since";
$HEADER_LAST_MODIFIED = "Last-Modified";
$HEADER_EXPIRES = "Expires";
$HEADER_CONTENT_LENGTH = "Content-Length";
$HEADER_CONTENT_TYPE = "Content-Type";

// Determine if we can send a "304" due to the client already having the current version.
$ifModifiedSinceHeader = $_SERVER[$HEADER_IF_MODIFIED_SINCE];
if ($ifModifiedSinceHeader && 
	(strtotime($ifModifiedSinceHeader) >= $lastModifiedTime)) {
	// Send 304
	generateTimeHeader($HEADER_LAST_MODIFIED, $lastModifiedTime, 304);
	exit();
}

// output headers
generateTimeHeader($HEADER_LAST_MODIFIED, $lastModifiedTime, 200);
generateTimeHeader($HEADER_EXPIRES, $lastModifiedTime + 365*24*60*60, 200); // Set expiration time +1 year
generateHeader($HEADER_CONTENT_LENGTH, strlen($data), 200);
generateHeader($HEADER_CONTENT_TYPE, $mimeType, 200);

// outputing image
//echo(headers_sent());
echo $data;
exit();

function generateHeader($name, $value, $responseCode) {
	header("$name: $value", true, $responseCode);
}

function generateTimeHeader($name, $unixTime, $responseCode) {
	generateHeader($name, gmdate("D, d M Y H:i:s", $unixTime) . " GMT", $responseCode);
}

?>
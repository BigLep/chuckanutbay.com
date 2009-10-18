<?php
	
	/**
	 * Where PDFs are stored.
	 */
	define("pdfDirPath", "pdfs");
	
	/**
	 * Where the static HTML files for PDF generation are stored.
	 */
	define("htmlForPdfDirPath", "htmlForPdfs");
	
	/**
	 * @param String $productType
	 * @param String $size
	 * @return Path to the PDF relative to this directory for the product with the provided type and size.
	 */
	function getPdfPath($productType, $size) {
		return pdfDirPath . "/" . getPdfName($productType, $size);
	}
	
	/**
	 * @param String $productType
	 * @param String $size
	 * @return Path to the archived PDF relative to this directory for the product with the provided type and size.
	 */
	function getArchivedPdfPath($productType, $size) {
		return pdfDirPath . "/archived/" . getPdfName($productType, $size);
	}
	
	/**
	 * @param String $productType
	 * @param String $size
	 * @return File name of pdf for the product with the provided type and size.
	 */
	function getPdfName($productType, $size) {
		return "$productType-$size-ProductInformation.pdf";
	}
	
	/**
	 * @param String $productType
	 * @param String $size
	 * @return Path to static html file to generate the PDF for the product with the provided type and size
	 */
	function getHtmlForPdfPath($productType, $size) {
		return htmlForPdfDirPath . "/" . getPdfName($productType, $size) . ".html";
	}
?>
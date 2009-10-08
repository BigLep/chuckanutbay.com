<?php
	
	/**
	 * @param String $productType
	 * @param String $size
	 * @return Path to the PDF relative to this directory for the product with the provided type and size.
	 */
	function getPdfPath($productType, $size) {
		return "pdfs/$productType-$size-ProductInformation.pdf";
	}
?>
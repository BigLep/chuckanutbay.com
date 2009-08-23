<?php
	require("unitTest.php");
	require("../util.php");
	
    /**
     * @see getItemIdBase
     */
	function testGetItemIdBase() {
		assertEquals("1401", getItemIdBase("1401-1"));
		assertEquals("1401", getItemIdBase("1401"));
	}
	testGetItemIdBase();
?>

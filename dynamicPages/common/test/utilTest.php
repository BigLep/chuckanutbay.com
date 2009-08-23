<?php
	require("../unitTest.php");
	require("../util.php");
	
    /**
     * @see getIdBase
     */
	function testGetIdBase() {
		assertEquals("1401", getIdBase("1401-1"));
		assertEquals("1401", getIdBase("1401"));
	}
	testGetIdBase();
?>

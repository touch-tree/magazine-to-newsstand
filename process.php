<?php
include ("scripts/controller.php");
//##########################################################

digi_pdf_to_html::process($_GET['path'],9,11);
//echo "<pre>".print_r( digi_pdf_to_html::$arrayPages,true )."</pre>";

echo digi_pdf_to_html::returnPageHtml(9);

?>
<?php
require "vendor/autoload.php";
$parser = new \Smalot\PdfParser\Parser();
$pdf = $parser->parseFile('/tmp/PR2306-0058ocomplet.pdf');

// $data = $pdf->getPages()[0]->getDataTm();
// $objects = $pdf->getObjects();
$objects = $pdf->getPages();
print json_encode($objects);

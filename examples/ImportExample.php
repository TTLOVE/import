<?php

use Import\ImportTask;


require './vendor/autoload.php';

$filename = 'pl.xlsx';
$import   = new ImportTask();
$import->handle($filename);

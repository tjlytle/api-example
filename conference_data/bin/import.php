#!/usr/local/bin/php
<?php
require __DIR__ . '/../../vendor/autoload.php';

$import = new \WorldSpeakers\ReadOnly\Import(__DIR__ . '/../../data.json');
$import->importFile(__DIR__ . '/../sessions.csv');
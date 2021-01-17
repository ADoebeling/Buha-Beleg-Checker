<?php

namespace DOEBELING\BuHaJournal;

require_once './src/app/journal.php';


$journal = new journal();

$journal->add(kontoauszug::load(['csvDir' => '../2020/KA - Kontoauszug/']));
$journal->debug();

file_put_contents(".test.md", $journal->getMdTable()->getMd());

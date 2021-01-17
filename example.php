<?php

namespace DOEBELING\BuHaJournal;

require_once './src/app/journal.php';


$journal = new journal();

$journal->add(kontoauszug::load(['csvDir' => '../2019/KA - Kontoauszug/']));
$journal->add(buchungssatz::load(['csvDir' => '../2019/BP - Buchungsprotokoll']));
$journal->add(pdf::load(['pdfDir' => '../2019/ER - Eingangsrechnungen/']));
$journal->debug();

file_put_contents(".test.md", $journal->getMdTable()->getMd());

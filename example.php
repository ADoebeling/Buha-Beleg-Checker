<?php

namespace DOEBELING;
use DOEBELING\BuHaJournal\parser\parseKontoauszug;

$journal = new \DOEBELING\BuHaJournal\journal();
$journal->add(new parseKontoauszug('../sfsdf'));


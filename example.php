<?php
/**
 * Example BuhaBelegChecker
 * @package DOEBELING\BuhaBelegChecker
 *
 * @author      Andreas Döbeling
 * @copyright   DÖBELING Web&IT <http://www.Doebeling.de>
 * @link        https://github.com/ADoebeling/Buha-Beleg-Checker
 * @license     CC-BY-SA <https://creativecommons.org/licenses/by-sa/3.0/de/>
 */

require_once 'src/app/buchungsChecker.php';

$buha2020   = new \DOEBELING\buhaJournal\buchungsChecker();
$buha2020   -> parseBelege('../2020/ER - Eingangsrechnungen/')
            -> parseBelege('../2020/AR - Ausgangsrechnungen/')
            -> parseKontoauszuege('../2020/KA - Kontoauszug/')
            -> parseBuchungen('../2020/BP - Buchungsprotokoll/')

            -> writeMdReportEigenbeleg('../2020/EB - Eigenbelege/')

            -> writeMdReport('../2020/README.md')
            -> writeMdReportBelegerfassung('../2020/Belegerfassung.md')
            -> writeMdReportBuchungen('../2020/Buchungen.md')
            -> writeMdReportKontobewegung('../2020/Kontobewegung.md');

            //-> printDebug();


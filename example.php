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

require_once 'doebeling.buhaBelegChecker.checker.class.php';

$buha2020   = new \DOEBELING\BuhaBelegChecker\Checker();
$buha2020   -> parseBelege('../2020/ER - Eingangsrechnungen/')
            -> parseBelege('../2020/AR - Ausgangsrechnungen/')
            -> parseKontoauszuege('../2020/KA - Kontoauszug/')
            -> parseBuchungen('../2020/BP - Buchungsprotokoll/')
            -> writeMdReport('../2020/README.md');
            //-> printDebug();




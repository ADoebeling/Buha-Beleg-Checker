<?php

namespace DOEBELING\BuHaJournal\buchungen\buchung;

use DOEBELING\BuHaJournal\parser;

/**
 * Class buchungsElementKontoauszug
 *
 * Stell Buchungsdaten aus einem Kontoauszug bereit
 *
 * @package   DOEBELING\BuHaJournal\buchungen\buchung
 * @author    Andreas Döbeling <opensource@doebeling.de>
 * @copyright DÖBELING Web&IT
 * @link      https://github.com/ADoebeling/
 * @link      https://www.Doebeling.de
 * @license   CC BY-SA 4.0 <https://creativecommons.org/licenses/by-sa/4.0/>
 *
 */
class buchungsElementKontoauszug extends buchungsElement
{
    protected function validateRaw()
    {
        parent::validateRaw(); // TODO: Change the autogenerated stub
    }

    protected function parseRawToNr()
    {
        parent::parseRawToNr(); // TODO: Change the autogenerated stub
    }

    /**
     * Methode parseRawToMd
     *
     * @return buchungsElement
     * @example
     * | | Wertstellung: | 01.02.3004 | kontoauszug.csv |
     * | | Kontobewegung | $bankVon *AN*\n$bankAn | kontoauszug.csv |
     * | | Überweisungsbetrag | `1.234,56 €` | kontoauszug.csv |
     * | | Verwendungszweck | lskjdflksjfl dsjfs | kontoauszug.csv |
     * | | Überweisungsvermerk | Testbuchung | kontoauszug.csv |
     *
     *
     */
    protected function parseRawToMd(): buchungsElement
    {
        return parent::parseRawToMd(); // TODO: Change the autogenerated stub
    }
}
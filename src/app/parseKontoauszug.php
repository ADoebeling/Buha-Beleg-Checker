<?php
/**
 * File parseKontoauszug
 *
 * @author    Andreas Döbeling <opensource@doebeling.de>
 * @copyright DÖBELING Web&IT
 * @link      https://www.Doebeling.de
 * @link      https://github.com/ADoebeling/
 * @license   CC BY-SA 4.0 <https://creativecommons.org/licenses/by-sa/4.0/>
 */

namespace DOEBELING\BuHaJournal\parser;
use DOEBELING\BuHaJournal\buchungen\buchung\buchungsElementKontoauszug;
use DOEBELING\BuHaJournal\journal;
use DOEBELING\BuHaJournal\parser;

class parseKontoauszug extends parser
{
    public function __construct ($dir)
    {
        $this->journal = new journal();
        foreach (self::getCsvAsArray($dir) as $row)
        {
            $element = new buchungsElementKontoauszug($row);
            $this->journal->add($element);
        }
    }
}
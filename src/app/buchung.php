<?php

namespace DOEBELING\BuHaJournal\buchungen;

use DOEBELING\BuHaJournal\buchungen\buchung\buchungsElement;
use phpDocumentor\GraphViz\Exception;

/**
 * Class buchung
 *
 * Repräsentiert eine einzelne Buchung bestehend aus BuchungsElementen (bspw. der Info aus einem Kontoauszug)
 *
 * @package   DOEBELING\BuHaJournal
 * @author    Andreas Döbeling <opensource@doebeling.de>
 * @copyright DÖBELING Web&IT
 * @link      https://github.com/ADoebeling/
 * @link      https://www.Doebeling.de
 * @license   CC BY-SA 4.0 <https://creativecommons.org/licenses/by-sa/4.0/>
 */
class buchung
{
    /**
     * @var bool|int BuchungsNr
     */
    protected $nr = false;

    /**
     * @var array Alternative Buchungsbezeichnung
     */
    protected $nrAlt = false;

    /**
     * @var bool|array Array aller Elemente
     */
    protected $elements = false;

    public function getNr()
    {
        return $this->nr;
    }

    /**
     * Methode get
     *
     * Gibt alle BuchungsElemente zurücl
     *
     * @return    array
     */
    public function get() : array
    {
        return $this->elements;
    }

   public function __construct(buchungsElement $buchungsElement) {
        $this->add($buchungsElement);
   }


    public function add(buchungsElement $buchungsElement) : buchung
    {

    }

    /**
     * Methode getMd
     *
     * Gibt das Markdown aller Elemente aus
     *
     * @return string
     * @throws Exception
     */
    public function getMd()
    {
        if ($this->elements === false)
        {
            throw new Exception("Diese Buchung enhält keine Elemente");
        }
        else
        {
            $md = '';
            foreach ($this->elements as $element)
            {
                $md .= $element->getMd();
            }
            return $md;
        }
    }


}
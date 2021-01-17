<?php

namespace DOEBELING\BuHaJournal;

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
     * @var array Alternative Buchungsbezeichnung
     */
    protected $nrAlt = false;

    /**
     * @var array Array aller Buchungen
     */
    protected $elements = [];

    public function getNr()
    {
        /** @var buchungsElement $buchungsElement */
        foreach ($this->get() as $buchungsElement)
        {
            if (!empty($buchungsElement->getNr()))
            {
                return $buchungsElement->getNr();
            }
        }
        return 'fehlt';
    }

    public function getDate()
    {
        // TODO Implement
        return '01.02.2013';
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


    public function add(buchungsElement $buchungsElement) : self
    {
        $this->elements[] = $buchungsElement;
        return $this;
    }

    /**
     * Methode getMdTable
     *
     * Gibt eine Buchung als MD zurück
     *
     * @return mdTable
     */
    public final function getMdTable()
    {
        $md = new mdTable();

        //Headline
        $headline = new mdTableRow();
        $headline->setNr("**#{$this->getNr()}**");
        $md->add($headline);

        // Buchungselemente
        /** @var buchungsElement $element */
        foreach ($this->elements as $element)
        {
            $md->add($element->getMdTable());
        }

        //Leerzeile
        $md->add(new mdTableRow());

        return $md;
    }


}
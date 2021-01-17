<?php

namespace DOEBELING\BuHaJournal\buchungen\buchung;

use DOEBELING\BuHaJournal\exception;

/**
 * Interface buchungsElement
 *
 * Vorlage für alle BuchungsElemente wie bspw. Kontoauszug
 *
 * @package   DOEBELING\BuHaJournal\buchungen\buchung
 * @author    Andreas Döbeling <opensource@doebeling.de>
 * @copyright DÖBELING Web&IT
 * @link      https://github.com/ADoebeling/
 * @link      https://www.Doebeling.de
 * @license   CC BY-SA 4.0 <https://creativecommons.org/licenses/by-sa/4.0/>
 *
 */
class buchungsElement
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
     * @var mixed
     */
    protected $raw;

    /**
     * @var string Markdown
     */
    protected $md = '';

    /**
     * buchungsElement constructor.
     *
     * Nimmt RAW-Daten eines Buchungs-Elements entgegen und bereitet sie auf
     *
     * @param $raw
     */
    public function __construct($raw) {
        $this->setRaw($raw);
        $this->validateRaw($raw);
        $this->parse();
    }

    public function parse()
    {
        $this->parseRawToNr();
        $this->parseRawToNrAlt();
        $this->parseRawToMd();
    }

    protected function validateRaw()
    {
        throw new exception("validateRaw() muss durch abgeleitete Methode überschrieben werden");
    }

    /**
     * Methode parseRawToNr
     *
     * @throws exception
     */
    protected function parseRawToNr()
    {
        throw new exception("parseRawToNr() muss durch abgeleitete Methode überschrieben werden");
    }

    protected function parseRawToNrAlt()
    {
        throw new exception("parseRawToNrAlt() muss durch abgeleitete Methode überschrieben werden");
    }

    public final function getNr()
    {
        return $this->nr;
    }

    public final function getNrAlt()
    {
        return $this->nrAlt;
    }

    /**
     * Methode setRaw
     * @param $raw
     */
    protected function setRaw($raw)
    {
          $this->raw = $raw;
          $this->parseRawToMd();
    }

    /**
     * Methode parseRaw
     *
     * Sollte von jedem Buchungselement implementiert werden
     *
     */
    protected function parseRawToMd() : buchungsElement
    {
        $this->md = "| | Beispiel | Dies ist eine exemplarische Referenzimplementierung von buchungsElement | buchungsElement.php |\n";
        return $this;
    }

    /**
     * Methode getMd
     *
     * Gibt das betreffende BuchungsElement als Markdown für das Journal aus
     *
     * @return    string
     */
    public final function getMd() : string
    {
        return $this->md;
    }
}
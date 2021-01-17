<?php

namespace DOEBELING\BuHaJournal;

require_once 'vendor/autoload.php';
require_once 'buchungsElement.php';
require_once 'buchung.php';
require_once 'buchungsbeleg.php';
require_once 'buchungssatz.php';
require_once 'exception.php';
require_once 'kontoauszug.php';
require_once 'log.php';
require_once 'mail.php';
require_once 'mdTable.php';
require_once 'mdTableRow.php';
require_once 'pdf.php';


/**
 * Class journal
 *
 * Object verwaltet eine Liste von BuchungsElementen
 * Kann neue BuchungsElemente aufnehmen
 * Kann die Liste an Buchungen gefiltert ausgeben
 *
 * @package   DOEBELING\BuHaJournal
 * @author    Andreas Döbeling <opensource@doebeling.de>
 * @copyright DÖBELING Web&IT
 * @link      https://github.com/ADoebeling/
 * @link      https://www.Doebeling.de
 * @license   CC BY-SA 4.0 <https://creativecommons.org/licenses/by-sa/4.0/>
 */
class journal
{
    /**
     * @var array Array aller Buchungen
     */
    protected $buchungen = array();

    /**
     * @var array Array aller Buchungen ohne Nummer
     */
    protected $buchungenOhneNr = array();

    /**
     * @var bool|array Array aus Referenzen auf Buchungen, dass die aktuelle Filterung repräsentiert
     */
    protected $buchungenFiltered = false;

    /**
     * @var object log
     */
    protected $log;

    /**
     * buchungen constructor.
     *
     * Initialisiert Monolog
     */
    public function __construct($element = false)
    {
        if ($element !== false)
        {
            $this->add($element);
        }
        $this->log = new log(get_class($this));
    }

    /**
     * Methode add
     *
     * Nimmt ein Objekt vom Typ buchung entgegen und fügt es hinzu.
     * Nimmt ein Objekt vom Typ buchungen entgegen und integriert sie
     *
     * @param array|journal|buchung|buchungsElement $object
     * @return journal
     * @throws exception
     */
    public function add($object): journal
    {
        if (is_array($object))
        {
            $this->log->debug("add(array)");
            foreach ($object as $arrayElement)
            {
                $this->add(($arrayElement));
            }
        }
        else if ($object instanceof journal)
        {
            $this->log->debug("add(journal)");
            /** @var journal $object */
            $this->add($object->get());
        }
        else if ($object instanceof buchung)
        {
            /** @var buchung $object */
            if (isset($this->buchungen[$object->getNr()]))
            {
                $this->log->debug("add(buchung)");
                $this->add($object->get());
            }
            else
            {
                $this->log->debug("add(buchung via element)");
                $this->buchungen[$object->getNr()] = $object;
            }
        }
        else if ($object instanceof buchungsElement)
        {
            if ($object->getNr() !== false)
            {
                if (isset($this->buchungen[$object->getNr()]))
                {
                    $this->log->debug("add(buchungselement zu bestehender Buchung)");
                    $this->buchungen[$object->getNr()]->add($object);
                }
                else
                {
                    $this->log->debug("add(Buchungselement als neue Buchung)");
                    $this->buchungen[$object->getNr()] = new buchung($object);
                }
            }
            else
            {
                $this->log->debug("add(Buchungselement als neue Buchung ohne Buchungsnummer)");
                $this->buchungenOhneNr[] = new buchung($object);
            }
        }
        else
        {
            throw new exception("Übergebenes Element ist ungültig");
        }
        return $this;
    }

    /**
     * Methode get
     *
     * Gibt eine Buchung oder ein Array aller Buchungen aus
     * Eine einzelne Buchung wird immer ausgegeben
     *
     * @param int|bool $buchungsNr
     * @return    false|buchung|array
     * @throws    \DOEBELING\BuHaJournal\exception
     * @author    Andreas Döbeling <opensource@doebeling.de>
     * @copyright DÖBELING Web&IT
     * @link      https://github.com/ADoebeling/
     * @link      https://www.Doebeling.de
     * @license   CC BY-SA 4.0 <https://creativecommons.org/licenses/by-sa/4.0/>
     */
    public function get($buchungsNr = false)
    {
        $buchungen = $this->buchungenFiltered === false ? $this->buchungen : $this->buchungenFiltered;
        ksort($buchungen);

        /*if (empty($buchungen))
        {
            throw new exception("Buchungen ist leer");
        }
        else*/
        if ($buchungsNr === false)
        {
            return $buchungen;
        }
        else if (isset($buchungen[$buchungsNr]))
        {
            return $buchungen[$buchungsNr];
        }
        else
        {
            throw new exception("Buchungsnummer existiert nicht");
        }
    }


    /**
     * Methode getMd
     *
     * Gibt das MD aller BuchungsElemente aller Buchungen aus
     *
     * @return    string
     * @throws    \Exception
     */
    public function getMdTable(): mdTable
    {
        $md = new mdTable();
        $md->setTitle('BelegNr.', 'Vorgang', 'Beschreibung', 'Quelle / Link');

        /** @var buchung $buchung */
        foreach ($this->get() as $buchung)
        {
            $md->add($buchung->getMdTable());
        }
        return $md;
    }


    /**
     * Methode addfilter
     *
     * Alle Elemente die nicht dem Filter entsprechen werden aus BuchungenFiltered gelöscht
     *
     * @return $this
     */
    public function addfilter(): journal
    {
        if (empty($this->buchungenFiltered))
        {
            foreach ($this->buchungen as $buchungsNr => &$buchung)
            {
                $this->buchungenFiltered[$buchungsNr] = &$buchung;
            }
        }
        // TODO
        return $this;
    }

    public function debug()
    {
        return;
        echo "Journal debug()\n";
        print_r($this->buchungen);
        echo "\n \n";
    }

    public function __destruct()
    {
        //print_r($this->buchungen);
    }
}


<?php

namespace DOEBELING\BuHaJournal;

/**
 * Class buchungsElementBuchungssatzexport
 *
 * Stell Buchungsdaten aus einem Buchungssatz-Export bereit
 *
 * @package   DOEBELING\BuHaJournal\buchungen\buchung
 * @author    Andreas Döbeling <opensource@doebeling.de>
 * @copyright DÖBELING Web&IT
 * @link      https://github.com/ADoebeling/
 * @link      https://www.Doebeling.de
 * @license   CC BY-SA 4.0 <https://creativecommons.org/licenses/by-sa/4.0/>
 *
 */
class buchungssatz extends buchungsElement
{
    public static function load(array $input): journal
    {
        if (!is_array($input))
            throw new exception("Load erwartet ein Array");

        // Buchungsnummer;Teilbuchung;Belegdatum;Buchungstext;Konto;Kontobezeichnung;Gegenkonto;Gegenkontobezeichnung;Soll;Haben;Steuerschluessel;Steuersatz;Steuerschluesselbezeichnung;Belegnummer;Buchungsdatum;UStID;Umsatzart
        $input['csvFelder'] = ['Buchungsnummer' => 'id', 'Belegnummer' => 'nr', 'Belegdatum' => 'datum', 'Buchungstext' => 'text', 'Konto' => 'kontoVon', 'Gegenkonto' => 'kontoAn', 'Kontobezeichnung' => 'kontoVonName', 'Gegenkontobezeichnung' => 'kontoAnName', 'Soll' => 'betragSoll', 'Haben' => 'betragHaben'];
        $input['class'] = self::class;
        return parent::load($input);
    }

    protected function parseRawToNr()
    {
        $this->nr = isset($this->raw->nr) ? $this->raw->nr : $this->raw->id;
        parent::parseRawToNr(); // TODO: Change the autogenerated stub
    }

    protected function parseRawToMd()
    {
        $row = new mdTableRow();
        $row->setTitle("Buchungssatz");
        $row->setText("{$this->raw->kontoVon} {$this->raw->kontoVonName} *an*<br>{$this->raw->kontoAn} {$this->raw->kontoAnName}");
        $row->setLink("{$this->raw->fileName}");
        $this->mdTable->add($row);

        $row = new mdTableRow();
        $row->setTitle("Buchungsbetrag");
        $betrag = !empty($this->raw->betragSoll) ? $this->raw->betragSoll : $this->raw->betragHaben;
        $row->setText($betrag.' €');
        $row->setLink("{$this->raw->fileName}");
        $this->mdTable->add($row);

        if (!empty($this->raw->text))
        {
            $row = new mdTableRow();
            $row->setTitle("Buchungstext");
            $row->setText($this->raw->text);
            $row->setLink("{$this->raw->fileName}");
        }
        return parent::parseRawToMd();
    }
}
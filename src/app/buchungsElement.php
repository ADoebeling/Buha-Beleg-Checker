<?php

namespace DOEBELING\BuHaJournal;


use stdClass;

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
     * @var stdClass
     */
    protected $raw;

    /**
     * @var mdTable $mdTable
     */
    protected $mdTable;

    /**
     * @var log
     */
    protected $log;

    /**
     * buchungsElement constructor.
     *
     * Nimmt RAW-Daten eines Buchungs-Elements entgegen und bereitet sie auf
     *
     * @param $raw
     */
    public final function __construct($raw)
    {
        $this->mdTable = new mdTable();
        $this->log = new log(get_class($this));
        $this->setRaw($raw);
        $this->parse();
    }

    protected final function parse()
    {
        $this->parseRawToNr();
        $this->parseRawToNrAlt();
        $this->parseRawToMd();
    }

    protected function validateRaw()
    {
        return $this;
    }

    /**
     * Methode parseRawToNr
     *
     * @throws exception
     */
    protected function parseRawToNr()
    {
        return $this;
    }

    protected function parseRawToNrAlt()
    {
        return $this;
    }

    protected function parseRawToMd()
    {
        return $this;
    }

    public final function getNr()
    {
        return !empty($this->nr) ? $this->nr : $this->nrAlt;
    }

    public final function getNrAlt()
    {
        return $this->nrAlt;
    }

    /**
     * Methode setRaw
     *
     * @param $raw
     * @return self
     */
    protected final function setRaw($raw): self
    {
        $this->raw = $raw;
        return $this->validateRaw();
    }


    /**
     * Methode getMd()
     *
     * Gibt das Element als Array aus priorisierten MD-Zeilen zurück
     */
    public final function getMdTable(): mdTable
    {
        return $this->mdTable;
    }

    /**
     * Methode load
     *
     * Lädt eine Ressource und stellt sie als Journal bereit
     *
     * @param array $input
     * @return    journal
     */
    public static function load(array $input): journal
    {
        $journal = new journal();

        if (!isset($input['class']) || !class_exists($input['class']))
        {
            throw new exception("Ungültiges Objekt '{$input['class']}' übergeben");
        }

        // Laden eines CSV-Files
        else if (isset($input['csvDir']))
        {
            foreach (self::getFilesAsCsvArray($input['csvDir'], $input['csvFelder']) as $file)
            {
                //$this->log->debug("Lade CSV-File $file");
                foreach ($file as $raw)
                {
                    $journal->add(new $input['class']($raw));
                }
            }
        }

        // Laden von PDFs
        else if (isset($input['pdfDir']))
        {
            foreach (self::getPdfsAsArray($input['pdfDir']) as $file => $fileName)
            {
                $raw = new stdClass();
                $raw->file = $file;
                $raw->fileName = $fileName;
                $journal->add(new $input['class']($raw));
            }
        }

        else
        {
            throw new exception("Nichts zum Laden gefunden");
        }

        return $journal;
    }

    // Helper

    /**
     * Methode getFiles
     *
     * @param string $dir
     * @todo implement
     */
    protected static function getFilesAsArray(string $dir, $regEx = '/.*$/'): array
    {
        $result = array();
        $cdir = scandir($dir);
        foreach ($cdir as $value)
        {
            if (!in_array($value, array(".", "..")) && preg_match($regEx, $value))
            {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
                {
                    $result = array_merge_recursive($result, self::getFilesAsArray($dir . '/' . $value)); //DIRECTORY_SEPARATOR
                }
                else
                {
                    $result[$dir . '/' . $value] = $value; //DIRECTORY_SEPARATOR
                }
            }
        }
        return $result;
    }

    /**
     * Methode getFilesAsCsvArray
     *
     * @param string $file
     * @return array
     * @todo implement
     */
    public static function getFilesAsCsvArray($dir, $felder, $delimeter = ';', $eol = PHP_EOL)
    {
        $r = array();
        foreach (self::getFilesAsArray($dir) as $file => $fileName)
        {
            $f = trim(file_get_contents($file));
            $f = str_replace("﻿", '', $f); //ZWNBSP - geschütztes Leerzeichen
            $f = explode($eol, $f);
            $h = str_getcsv(array_shift($f), $delimeter);
            foreach ($f as $row => $line)
            {
                if (empty(trim($line))) continue;
                foreach (str_getcsv($line, $delimeter) as $k => $v)
                {
                    if (isset($felder[$h[$k]]))
                    {
                        if (!isset($r[$file][$row]) ||  !($r[$file][$row] instanceof stdClass)) {
                            $r[$file][$row] = new stdClass();
                    }
                        $r[$file] [$row] -> {$felder[$h[$k]]} = $v;
                        $r[$file] [$row] -> file = $file;
                        $r[$file] [$row] -> fileName = $fileName;
                    }
                }
            }
        }
        //print_r($r);
        return $r;
    }

    protected static function getBuchungsNrFromString($string)
    {
        $regEx = '/#(\d{1,6})/';
        if (preg_match($regEx, $string, $matches) == false)
        {
            return false;
        }
        else
        {
            return $matches[1];
        }
    }


    /**
     * Methode getPdfsAsArray
     *
     * @param string $dir
     * @return array
     * @todo implement
     */
    public static function getPdfsAsArray(string $dir): array
    {
        return self::getFilesAsArray($dir);;
    }
}
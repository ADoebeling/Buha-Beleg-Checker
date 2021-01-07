<?php

namespace DOEBELING\BuhaBelegChecker;
setlocale (LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');

//PHP7.2
if (!function_exists(array_key_first))
{
    function array_key_first ($array) {
        foreach ($array as $a) return $a;
    }
}


/**
 * Class Checker
 * @package DOEBELING\BuhaBelegChecker
 *
 * @author      Andreas Döbeling
 * @copyright   DÖBELING Web&IT <http://www.Doebeling.de>
 * @link        https://github.com/ADoebeling/Buha-Beleg-Checker
 * @license     CC-BY-SA <https://creativecommons.org/licenses/by-sa/3.0/de/>
 */
class Checker
{
    /**
     * RegEx für Belege im Format: "#12345_1 - df - Rechnung RG 456465.pdf"
     */
    const regExBelege = '/^#(?<id>[0-9]*)(_?(?<subid>[0-9]*)?)(?<desc>.*)(?<fileext>.pdf|.msg|.doc|.docx|.xls|.xlsx)$/';  //#?(?<belegId>[0-9]*)';

    /**
     * RegEx für Buchungstext
     */
    const regExBuchungstext = '/^#(?<id>[0-9]*).*$/';


    /**
     * @var array Array aller Belege
     */
    protected $b = array();

    /**
     * @var string
     */
    protected $md = '';

    /**
     * @var float
     */
    protected $timer = 0.0;

    /**
     * @var bool
     */
    protected $isParseBelegeActive = false;

    /**
     * @var bool
     */
    protected $isParseBuchungenActive = false;

    /**
     * @var bool
     */
    protected $isParseKontoauszuegeActive = false;

    /**
     * @var bool
     */
    protected $isEigenbelegActive = false;

    // --------------------------------------------------
    // Public API
    // --------------------------------------------------

    /**
     * Checker constructor
     */
    public function __construct()
    {
        $this->getTimer();
    }

    /**
     * Checker destructor
     */
    public function __destruct()
    {
        echo "{$this->getTimer()}s - EOF";
    }

    /**
     * Gibt das B-Array zu Debug-Zwecken aus
     * @return $this
     */
    public function printDebug()
    {
        print_r($this->b);
        return $this;
    }

    /**
     * Parse den übergebenen Ordner mit Kontoauszügen im CSV-Format
     * Folgende Spalten werden benötigt:
     * - BuchungID
     * - Wertstellung
     * - Empfänger/Auftraggebe
     * - Verwendungszweck
     * - Betrag
     *
     * @param $dirKontoauszuege
     * @return $this
     */
    public function parseKontoauszuege($dirKontoauszuege)
    {
        $this->isParseKontoauszuegeActive = true;
        $files = self::getDirRecursivelyAsArray($dirKontoauszuege, '/.*\.csv/i');
        foreach ($files as $filepath => $filename) {
            $kas = self::getCsvAsArray($filepath, ';');
            foreach ($kas as $k) {
                if (empty($k['BuchungID'])) Continue;
                $k['file'] = $filename; // Deprecated
                $k['filename'] = $filename;
                $k['filepath'] = "$dirKontoauszuege/$filename";
                $k['Empfänger/Auftraggeber'] = empty($k['Empfänger/Auftraggeber'])  ? 'Unbekannt' : $k['Empfänger/Auftraggeber'];
                $this->b[$k['BuchungID']]['Kontoauszug'] = $k;

            }
        }
        ksort($this->b);
        return $this;
    }


    /**
     * Parse den übergebenen Ordner mit Belegen (PDF-Rechnunge, ...)
     * Der Dateiname sollte im Prefix die Buchungs-ID enthalten, bspw. "#1234 - Github - invoice57536465.pdf"
     *
     * @param $dirBelege
     * @return $this
     */
    public function parseBelege($dirBelege)
    {
        $this->isParseBelegeActive = true;
        $files = self::getDirRecursivelyAsArray($dirBelege);
        foreach ($files as $path => $filename) {
            preg_match(self::regExBelege, $filename, $matches);
             if(isset($matches['id']) && $matches['id'] != '') {
                 $belegId = $belegId = $matches['id'];
             }
             else {
                 $belegId = "Beleg $filename";
                 $this->b[$belegId]['hinweis']['BelegOhneBelegnummer'] = true;
             }
             $this->b[$belegId]['belege'][$path] = $filename;
        }
        ksort($this->b);
        return $this;
    }


    /**
     * Parse den übergebenen Ordner mit Buchungsprotokollen im CSV-Format
     * Folgende Spalten werden benötigt
     * - Buchungsnummer
     * - Belegdatum
     * - Buchungstext
     * - Gegenkontobezeichnung
     * - Soll
     * - Haben
     * - Belegnummer
     * - Buchungsdatum
     *
     * @param $dirBuchungen
     * @return $this
     */
    public function parseBuchungen($dirBuchungen)
    {
        $this->isParseBuchungenActive = true;
        $files = self::getDirRecursivelyAsArray($dirBuchungen);
        foreach ($files as $filepath => $file)
        {
            $buchungen = self::getCsvAsArray($filepath, ';');
            $hinweis = [];
            foreach ($buchungen as $b)
            {
                // Bugfix for empty/invalid csv-lines
                if (!isset($b['Buchungstext']) || !isset($b['Buchungsnummer'])) Continue;

                preg_match(self::regExBuchungstext, $b['Buchungstext'], $matches);

                if (isset($matches['id']))
                {
                    $b['id'] = $matches['id'];
                }
                else if (!empty($b['Belegnummer']))
                {
                    $b['id'] = $b['Belegnummer'];
                }
                else
                {
                    $b['id'] = 'Lfd. Buchung '. sprintf('%05d', $b['Buchungsnummer']);

                    if (strstr($b['Buchungstext'], 'Saldovortrag'))
                    {
                        $hinweis[$b['id']]['Saldovortrag'] =  true;
                    }
                    else if (strstr($b['Buchungstext'], 'AfA: '))
                    {
                        $hinweis[$b['id']]['AfA'] =  true;
                    }
                    else
                    {
                        $hinweis[$b['id']]['BuchungOhneBelegnummer'] = true;
                    }
                }

                $b['Betrag'] = self::getFloat($b['Soll']) != 0 ? $b['Soll'] : $b['Haben'];
                $b['file'] = $file;
                $b['filename'] = $file;
                $b['filepath'] = $filepath;

                $this->b[$b['id']]['buchungen'][$b['Buchungsnummer']] = $b;
                if (isset($hinweis[$b['id']])) $this->b[$b['id']]['hinweis'] = $hinweis[$b['id']];
            }
        }
        ksort($this->b);
        return $this;
    }

    // --------------------------------------------------
    // Interne Funktionen
    // --------------------------------------------------

    /**
     * Generiert Markdown für jeden Datensatz
     * @return $this
     */
    protected function generateMd()
    {
       foreach ($this->b as $bid => &$b)
        {
                $b['md'] = '';

                // Datum
                if (isset($b['Kontoauszug']['Wertstellung'])) {
                    $datum = $b['Kontoauszug']['Wertstellung'];
                } else if (isset($b['buchungen'])) {
                    $datum = $b['buchungen'][array_key_first($b['buchungen'])]['Belegdatum'];
                } else {
                    $datum = "";
                }

                $betrag = 0;
                if (isset($b['Kontoauszug']['Betrag'])) {
                    $betrag = $b['Kontoauszug']['Betrag'] . ' €';
                }
                else if (isset($b['buchungen']))
                {
                    foreach ($b['buchungen'] as $bs)
                    {
                        $betrag += self::getFloat($bs['Soll']);
                        $betrag += self::getFloat($bs['Haben']);
                    }
                    setlocale(LC_MONETARY, 'de_DE');
                    $betrag = number_format($betrag, 2, ',', '.'). ' €';
                }

                $b['md'] .= "## #$bid\n";
                $b['md'] .= "| #$bid | $datum | $betrag |\n|---|---|---|\n";

                if (isset($b['hinweis']))
                {
                    foreach ($b['hinweis'] as $flag => $bool)
                    {
                        switch ($flag) {
                            case 'BuchungOhneBelegnummer':
                                $b['md'] .= "| Belegnummer | **FEHLT**<br> in Buchung | |\n";
                                break;

                            case 'BelegOhneBelegnummer':
                                $b['md'] .= "| Belegnummer | **FEHLT**<br> in Dateiname | |\n";
                                break;

                            case 'AfA':
                                $b['md'] .= "| Sonderbuchung | Diese Buchung betrifft eine AfA (Abschreibung für Abnutzung) und stellt damit im eigentlichen Sinn keinen eigenen Geschäftsvorfall dar. | |\n";
                                break;

                            case 'Saldovortrag':
                                $b['md'] .= "| Sonderbuchung | Diese Buchung betrifft einen Saldovortrag (aus dem Vorjahr) und stellt damit im eigentlichen Sinn keinen eigenen Geschäftsvorfall dar. | |\n";
                                break;
                        }
                    }
                }

                // Überweisung
                if (!empty($b['Kontoauszug']))
                {
                    $b['Kontoauszug']['Kontoname'] = empty($b['Kontoauszug']['Kontoname']) ? 'Bank' : $b['Kontoauszug']['Kontoname'];
                    if (!isset($b['Kontoauszug']['Betrag']))
                    {
                        $b['md'] .= "| Kontobewegung: | **FEHLER**<br>Datensatz unvollständig / Betrag fehlt | ". static::getMdLink($b['Kontoauszug']['filename'], $b['Kontoauszug']['filepath']) ." |\n";
                    }
                    elseif (self::getFloat($b['Kontoauszug']['Betrag']) < 0)
                    {
                        $b['md'] .= "| Kontobewegung: | `{$b['Kontoauszug']['Kontoname']}` *an*<br>`{$b['Kontoauszug']['Empfänger/Auftraggeber']}` *mit*<br>`{$b['Kontoauszug']['Betrag']} €` | ". static::getMdLink($b['Kontoauszug']['filename'], $b['Kontoauszug']['filepath']) ." |\n";
                    }
                    else
                    {
                        $b['md'] .= "| Kontobewegung: |`{$b['Kontoauszug']['Empfänger/Auftraggeber']}` *an*<br>`{$b['Kontoauszug']['Kontoname']}` *mit*<br>`{$b['Kontoauszug']['Betrag']} €`| {". static::getMdLink($b['Kontoauszug']['filename'], $b['Kontoauszug']['filepath']) ." |\n";
                    }

                    // Verwendungszweck
                    $b['md'] .= isset($b['Kontoauszug']['Verwendungszweck']) ? "| Verwendungszweck: | `{$b['Kontoauszug']['Verwendungszweck']}` | ". static::getMdLink($b['Kontoauszug']['filename'], $b['Kontoauszug']['filepath']) ." |\n" : "";

                }
                else
                {
                    $b['md'] .= "| Kontobewegung: | **FEHLT**<br> Keine Überweisung gefunden |   |\n";
                }

                // Buchungssatz
                if (isset($b['buchungen']))
                {
                    foreach ($b['buchungen'] as $bs)
                    {
                        if (self::getFloat($bs['Soll']) > self::getFloat(self::getFloat($bs['Haben']))) {
                            $b['md'] .= "| Buchungssatz: | `{$bs['Konto']} {$bs['Kontobezeichnung']}` *an*<br>`{$bs['Gegenkonto']} {$bs['Gegenkontobezeichnung']}` *mit* <br>`{$bs['Betrag']} €`  | ". static::getMdLink($bs['filename'], $bs['filepath']) ." |\n";
                        } else {
                            $b['md'] .= "| Buchungssatz: | `{$bs['Gegenkonto']} {$bs['Gegenkontobezeichnung']}` *an*<br>`{$bs['Konto']} {$bs['Kontobezeichnung']}` *mit* <br>`{$bs['Betrag']} €`  | ". static::getMdLink($bs['filename'], $bs['filepath']) ." |\n";
                        }

                        // Buchungstext ausgeben, wenn dieser nicht den Verwendungszweck beinhaltet
                        //if (!isset($b['buchung']['Verwendungszweck']) || strstr($bs['Buchungstext'], $b['buchung']['Verwendungszweck']) === false)
                        //{
                        $bt = str_replace('|', '/', $bs['Buchungstext']);
                        $b['md'] .= "| Buchungstext: | `$bt` | ". static::getMdLink($bs['filename'], $bs['filepath']) ." |\n";
                        //}
                    }
                }
                else
                {
                    $b['md'] .= "| Buchungssatz: | **FEHLT**  | **FEHLER**:<br>Keine Buchungssätze gefunden |\n";
                }

                // Notiz
                $b['md'] .= !empty($b['Kontoauszug']['Notiz']) ? "| Vermerk: | `{$b['Kontoauszug']['Notiz']}` | ". static::getMdLink($b['Kontoauszug']['filename'], $b['Kontoauszug']['filepath']) ." |\n" : "";

                // Belege
                if ($this->isParseBelegeActive || $this->isEigenbelegActive)
                {
                    $belege = '';
                    if (isset($b['belege']))
                    {
                        foreach ($b['belege'] as $filepath => $filename)
                        {
                            $belege .= self::getMdLink($filename, "$filepath") . "<br>";
                        }
                        if (isset($b['eigenbeleg'])) {
                            foreach ($b['eigenbeleg'] as $filepath => $filename) {
                                $belege .= self::getMdLink($filename, "$filepath") . "<br>";
                            }
                        }
                        $b['md'] .= "| Belege: | Sind abgelegt | $belege |\n";
                    }
                    else {
                        if (isset($b['eigenbeleg'])) {
                            foreach ($b['eigenbeleg'] as $filepath => $filename) {
                                $belege .= self::getMdLink($filename, "$filepath") . "<br>";
                            }
                            $b['md'] .= "| Beleg: | **FEHLT**<br>Keine Rechnungen gefunden, Vorlage für Eigenbeleg erstellt | $belege |\n";
                        }
                        else {
                            $b['md'] .= "| Beleg: | **FEHLT**<br>Keine Rechnungen gefunde |  |\n";
                        }
                    }
                }
                $b['md'] .= "\n\n";
        }
        return $this;
    }

    /**
     * Schreibe den Report als MD-File
     *
     * @param $fileMdReport
     * @return $this
     */
    public function writeMdReport($fileMdReport, $filter = '')
    {
        $this->generateMd();
        $md = '';

        // Headline
        switch ($filter)
        {
            case 'belegerfassung':
                $md .= "## Liste aller Datensätze mit ausstehender Belegerfassung\n";
                $md .= "Zu allen nachfolgenden Datensätzen müssen noch die Belege erfasst werden\n\n";
                break;

            case 'buchungen':
                $md .= "## Liste aller Datensätze mit ausstehender Buchung\n";
                $md .= "Zu allen nachfolgenden Datensätzen müssen noch die Buchungen korrekt erfasst werden\n\n";
                break;

            case 'kontobewegung':
                $md .= "## Liste aller Datensätze mit fehlender Kontobewegung\n";
                $md .= "Zu allen nachfolgenden Datensätzen muss geprüft werden, ob es hierzu eine Kontobewegung geben sollte \n\n";
                break;

            case 'saldovortrag':
                $md .= "## Liste aller Saldovorträge\n";
                break;

            case 'afa':
                $md .= "## Liste aller Abschreibungen\n";
                break;

            default:
                $md .= "## Buchungsjournal\n";
                break;
        }

        $md .= "\n\n";

        foreach ($this->b as $bid => &$b)
        {
            if ($filter == 'belegerfassung' && $this->isParseBelegeActive && isset($b['belege']) && !isset($b['hinweis']['BelegOhneBelegnummer']))
            {
                Continue;
            }
            else if ($filter == 'buchungen' && $this->isParseBuchungenActive && isset($b['buchungen']) && !isset($b['hinweis']['BuchungOhneBeleg']))
            {
                Continue;
            }
            else if ($filter == 'kontobewegung' && $this->isParseKontoauszuegeActive && isset($b['Kontoauszug']))
            {
                Continue;
            }
            else if ($filter == 'afa' && isset($b['hinweis']['AfA']))
            {
                Continue;
            }
            else if ($filter == 'afa' && isset($b['hinweis']['Saldovortrag']))
            {
                Continue;
            }
            else
            {
                $md .= $b['md'];
            }
        }

        file_put_contents($fileMdReport, $md);
        return $this;
    }

    public function writeMdReportBelegerfassung($fileMdReport)
    {
        return $this->writeMdReport($fileMdReport, 'belegerfassung');
    }

    public function writeMdReportBuchungen($fileMdReport)
    {
        return $this->writeMdReport($fileMdReport, 'buchungen');
    }

    public function writeMdReportKontobewegung($fileMdReport)
    {
        return $this->writeMdReport($fileMdReport, 'kontobewegung');
    }

    public function writeMdReportSaldovortrag($fileMdReport)
    {
        return $this->writeMdReport($fileMdReport, 'saldovortrag');
    }

    public function writeMdReportAfa($fileMdReport)
    {
        return $this->writeMdReport($fileMdReport, 'afa');
    }

    /**
     * @param $dirMdReportPath
     * @return $this
     * @author anagai@yahoo.com
     * @link https://www.php.net/manual/de/function.unlink.php#109971
     */
    public function deleteMdReportEigenbeleg($dirMdReportPath)
    {
        //array_map('var_dump', glob("$dirMdReportPath/*.md"));
        array_map('unlink', glob("$dirMdReportPath/*.md"));
        return $this;
    }

    public function writeMdReportEigenbeleg($dirMdReportPath, $deleteOldFiles = true)
    {
        $this->isEigenbelegActive = true;
        $this->isEigenbelegActive = true;
        if ($deleteOldFiles) $this->deleteMdReportEigenbeleg($dirMdReportPath);

       $this->generateMd();
       foreach ($this->b as $bid => &$b)
        {
            $md = "# Buchungsbeleg\n\n";

            $md .= "* [ ] Buchungsbeleg zur internen Dokumentation\n* [ ] Vorläufiger Eigenbeleg, Rechnung wurde angefragt, liegt noch nicht vor\n* [ ] Eigenbeleg, da: <br>\n<br>\n<br>\n\n ` ________________________________________________________________________ ` \n<br>\n\n";
            $md .= $b['md'];
            $md .=  "\n<br>\n<br>\n<br>\n<br>\n\n ` ________________________________________________________________________ ` \n<br>\nDatum, Stempel, Unterschrift";

            $filename = "#$bid - Buchungsbeleg.md";
            $filepath = "$dirMdReportPath/$filename";
            file_put_contents($filepath, $md);
            $b['eigenbeleg'][$filepath] = $filename;
        }
        return $this;
    }

    // --------------------------------------------------
    // Helper-Functions
    // --------------------------------------------------

    /**
     * @param $dir
     * @param string $regEx
     * @return array
     * @author mmda.nl@gmail.com
     * @link https://www.php.net/manual/de/function.scandir.php#110570
     */
    static function getDirRecursivelyAsArray($dir, $regEx = '/.*/')
    {
        $result = array();
        $cdir = scandir($dir);
        foreach ($cdir as $value) {
            if (!in_array($value, array(".", "..")) && preg_match($regEx, $value)) {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                    //$result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
                    $result = array_merge_recursive($result, self::getDirRecursivelyAsArray($dir . '/' . $value)); //DIRECTORY_SEPARATOR
                } else {
                    //$result[] = $value;
                    $result[$dir . '/' . $value] = $value; //DIRECTORY_SEPARATOR
                }
            }
        }
        return $result;
    }

    /**
     * @param $file
     * @param string $delimeter
     * @param string $eol
     * @return array
     */
    static function getCsvAsArray($file, $delimeter = ',', $eol = PHP_EOL)
    {
        $r = array();
        $f = file_get_contents($file);
        $f = str_replace("﻿", '', $f); //ZWNBSP - geschütztes Leerzeichen
        $f = explode($eol, $f);
        $h = str_getcsv(array_shift($f), $delimeter);
        //array_map('trim', $h); //Bugfix: Geschütztes Leerzeichen am Anfang des CSV-Files :-/
        foreach ($f as $row => $line) foreach (str_getcsv($line, $delimeter) as $k => $v) $r[$row][$h[$k]] = $v;
        return $r;
    }

    /**
     * @param $string
     * @return mixed
     * @author Marcel / MM Newmedia
     * @link https://www.mm-newmedia.de/2013/11/die-validierung-von-deutschen-kommazahlen-mit-php/
     */
    static function getFloat($string)
    {
        return filter_var($string, FILTER_VALIDATE_FLOAT, array('options' => ['decimal' => ','], 'flags' => FILTER_FLAG_ALLOW_THOUSAND));
    }

    /**
     * @return float
     */
    protected function getTimer()
    {
        if ($this->timer == 0.0) {
            $this->timer = microtime(true);
            return 0.0;
        } else {
            $diff = round(microtime(true) - $this->timer, 3);
            $this->timer = microtime(true);
            return $diff;
        }
    }

    /**
     * @param $text
     * @param $link
     * @param string $alt
     * @return string
     */
    static function getMdLink($text, $link, $alt = "")
    {
        $text = trim($text);
        if (empty($alt))
        {
            $alt = explode('/', rawurldecode(stripslashes(trim($link))));
            $alt = 'Öffne '.$alt[count($alt)-1];
        }
        if (strstr($link, '/'))
        {
            $link = str_replace('%2F', '/', rawurlencode(stripslashes(trim($link))));
        }
        else
        {
            $link = str_replace('%23', '#', (str_replace('%2F', '/', rawurlencode(stripslashes(trim($link))))));
        }
        return "[`$text`]($link \"$alt\")";
    }
}
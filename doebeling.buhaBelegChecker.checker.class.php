<?php

namespace DOEBELING\BuhaBelegChecker;
setlocale (LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');


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
    protected array $b = array();

    /**
     * @var string
     */
    protected string $md = '';

    /**
     * @var float
     */
    protected float $timer = 0.0;

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
        $files = self::getDirRecursivelyAsArray($dirKontoauszuege, '/.*\.csv/i');
        foreach ($files as $filepath => $filename) {
            $kas = self::getCsvAsArray($filepath, ';');
            foreach ($kas as $k) {
                $k['file'] = $filename;
                $this->b[$k['BuchungID']]['Kontoauszug'] = $k;
            }
        }
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
        $files = self::getDirRecursivelyAsArray($dirBelege);
        foreach ($files as $path => $filename) {
            preg_match(self::regExBelege, $filename, $matches);
            $belegId = isset($matches['id']) && $matches['id'] != '' ? $matches['id'] : '?';
            $this->b[$belegId]['belege'][$path] = $filename;
        }
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
        $files = self::getDirRecursivelyAsArray($dirBuchungen);
        foreach ($files as $filepath => $file) {
            $buchungen = self::getCsvAsArray($filepath, ';');
            foreach ($buchungen as $b) {
                // Bugfix for empty csv-lines
                if (empty($b['Buchungsnummer'])) continue;

                preg_match(self::regExBuchungstext, $b['Buchungstext'], $matches);

                if (isset($matches['id'])) $b['id'] = $matches['id'];
                else if (!empty($b['Belegnummer'])) $b['id'] = $b['Belegnummer'];
                else                                     $b['id'] = '?';

                $b['Betrag'] = self::getFloat($b['Soll']) != 0 ? $b['Soll'] : $b['Haben'];

                $b['filename'] = $filepath;
                $b['file'] = $file;
                $this->b[$b['id']]['buchungen'][] = $b;
                $this->b[$b['id']]['Betrag'] = &$b['Betrag'];
            }
        }
        return $this;
    }

    // --------------------------------------------------
    // Interne Funktionen
    // --------------------------------------------------

    protected function datenaufbereitung()
    {
        arsort($this->b);
        foreach ($this->b as $bid => &$b)
        {
            // Betrag
            if (isset($b['Kontoauszug']['Betrag']))         $b['Betrag'] = &$b['Kontoauszug']['Betrag'];
            else if (isset($b['buchungen'][0]['Betrag']))   $b['Betrag'] = &$b['buchungen'][0]['Betrag'];
            else                                            $b['Betrag'] = 'Unbekannt';

            if (
                isset($b['Kontoauszug']['Betrag']) &&
                isset($b['buchungen'][0]['Betrag']) &&
                $b['Kontoauszug']['Betrag'] != $b['buchungen'][0]['Betrag'])
            {
                $b['Fehler'][] = "Betrag in Kontoauszug und Buchung unterschiedlich";
            }
        }
        return $this;
    }

    protected function addTodosToMd()
    {
        $mdBuchungOhneBelegnummer = '';
        $mdBelegeOhneBelegnummer = '';
        $mdBuchungFehlt = '';
        $mdBelegeFehlen = '';
        $mdBuchungMitFalschemDatum = '';
        $mdBuchungMitFalschemBetrag = '';

        if (isset($this->b['?'])) {
            $b = &$this->b['?'];

            if (!empty($b['buchungen'])) {
                $mdBuchungOhneBelegnummer .= "## ToDo: Unvollständige Buchungen\nBei folgenden Buchungen ist keine Belegnummer angegeben\n";
                foreach ($b['buchungen'] as $bu) {
                    $bu['Betrag'] = self::getFloat($bu['Soll']) != 0 ? $bu['Soll'] : $bu['Haben'];
                    $mdBuchungOhneBelegnummer .= "* [ ] `{$bu['Belegdatum']}` | `{$bu['Betrag']} €` von `{$bu['Kontobezeichnung']}` an `{$bu['Gegenkontobezeichnung']}`\n";
                }
            }

            if (!empty($b['belege'])) {
                $mdBelegeOhneBelegnummer .= "## ToDo: Nicht erfasste Belege\nBei folgenden Belegen fehlt noch die Belegnummer im Dateinamen\n";
                foreach ($b['belege'] as $filepath => $file) {
                    $mdBelegeOhneBelegnummer .= "* [ ] ".self::getMdLink($file, $filepath)."\n";
                }
            }
        }


        foreach ($this->b as $bid => $b)
        {
            //Fehlende Verbuchung
            if (!isset($b['buchungen']))
            {
                $mdBuchungFehlt .= isset($b['Betrag']) ? "* [ ] ".self::getMdLink($bid, "#$bid", "Zeige Datensatz #$bid")." über `{$b['Betrag']}`\n" : "* ".self::getMdLink($bid, "#$bid", "Zeige Datensatz #$bid")."\n";
            }

            //Fehlende Belege
            if (!isset($b['belege']))
            {
                $mdBelegeFehlen .= isset($b['Betrag']) ? "* [ ] [#$bid](#$bid) über `{$b['Betrag']}`\n" : "* [#$bid](#$bid)";
            }

            // UnterschiedlichesDatum
            if (
                isset($b['buchungen'][0]['Belegdatum']) &&
                isset($b['Kontoauszug']['Wertstellung']) &&
                $b['buchungen'][0]['Belegdatum'] != $b['Kontoauszug']['Wertstellung'])
            {
                $mdBuchungMitFalschemDatum .= "* [ ] [#$bid](#$bid) vom `{$b['buchungen'][0]['Belegdatum']} bzw. {$b['Kontoauszug']['Wertstellung']}`\n";
            }

            if (
                isset($b['buchung']['Soll']) &&
                isset($b['buchung']['Haben']) &&
                isset($b['Kontoauszug']['Wertstellung']) &&
                self::getFloat($b['buchung']['Soll']) + self::getFloat($b['buchung']['Haben']) != self::getFloat($b['Kontoauszug']['Wertstellung']))
            {
                $mdBuchungMitFalschemBetrag .= "* [ ] [#$bid](#$bid) über {$b['Kontoauszug']['Wertstellung']} wurde verbucht mit Soll: {$b['buchung']['Soll']} / Haben: {$b['buchung']['Haben']}`\n";
            }
        }

        $this->md .= $mdBelegeOhneBelegnummer;
        $this->md .= !empty($mdBelegeFehlen) ? "## TODO: Fehlende Belege\nFolgende Fälle haben noch keinen Beleg\n$mdBuchungFehlt\n\n":'';
        $this->md .= $mdBuchungOhneBelegnummer;
        $this->md .= !empty($mdBuchungFehlt) ? "## TODO: Verbuchung ausstehend\nFolgende Fälle sind noch nicht verbucht\n$mdBuchungFehlt\n\n":'';
        $this->md .= !empty($mdBuchungMitFalschemDatum) ? "## ToDo: Fehlerhaftes Datum\nBei folgenden Buchungen unterscheidet sich das Datum zwischen Buchungsprotokoll und Kontoauszug\n$mdBuchungMitFalschemDatum\n\n":'';
        $this->md .= !empty($mdBuchungMitFalschemBetrag) ? "## ToDo: Falscher Betrag\nBei folgenden Buchungen unterscheidet sich der Betrag\n$mdBuchungMitFalschemBetrag\n\n":'';

        return $this;
    }


    protected function addListToMd()
    {
        $md = &$this->md;
        $md .= "## Liste aller Buchungen\n\n";
        foreach ($this->b as $bid => $b) {
            if ($bid != '?') {
                $md .= "### #$bid\n";
                $md .= "| Beleg | Information | Bewertung |\n|---|---|---|\n";

                if (isset($b['Kontoauszug']['Wertstellung']))
                {
                    $md .= "| Datum: | `{$b['Kontoauszug']['Wertstellung']}` | OK gem. `{$b['Kontoauszug']['file']}` |\n";
                }
                else if (isset($b['buchungen'][0]['Belegdatum']))
                {
                    $md .= "| Datum: | `{$b['buchungen'][0]['Belegdatum']}` | OK gem. `{$b['buchungen'][0]['file']}` |\n";
                }
                else if (isset($b['buchungen'][0]['Belegdatum']))
                {
                    $md .= "| Datum: | Unbekannt | Bitte Belege prüfen` |\n";
                }

                $md .= isset($b['Betrag']) ? "| Betrag: | `{$b['Betrag']}` | OK |\n" : "| Betrag: | **Unbekannt** | **FEHLER:** Keine Zahlung gefunden |\n";
                $md .= isset($b['Kontoauszug']['Empfänger/Auftraggebe']) ? "| Überweisung von/an: | `{$b['Kontoauszug']['Empfänger/Auftraggebe']}` | OK gem. `{$b['Kontoauszug']['file']}` |\n" : "";
                $md .= isset($b['Kontoauszug']['Verwendungszweck']) ? "| Verwendungszweck: | `{$b['Kontoauszug']['Verwendungszweck']}` | OK gem. `{$b['Kontoauszug']['file']}` |\n" : "";
                $md .= isset($b['buchungen'][0]) ? "| Buchungssatz: | `{$b['buchungen'][0]['Kontobezeichnung']}` an `{$b['buchungen'][0]['Gegenkontobezeichnung']}`  | OK gem. `{$b['buchungen'][0]['file']}` |\n" : "";

                if (isset($b['belege'])) foreach ($b['belege'] as $filepath => $filename)
                {
                    $md .= "| Beleg: | ".self::getMdLink($filename, "$filepath")." | Bitte Beleg prüfen |\n";
                }
                else $md .= "| Belege: | **Fehlen** | **FEHLER:** Keine Belege gefunden |\n";
            }
        }
        $md .= "\n\nGeneriert am " . date("d.m.Y H:i:s");
        return $this;
    }

    /**
     * Schreibe den Report als MD-File
     *
     * @param $fileMdReport
     * @return $this
     */
    public function writeMdReport($fileMdReport)
    {
        $this->datenaufbereitung();
        $this->addTodosToMd();
        $this->addListToMd();
        file_put_contents($fileMdReport, $this->md);
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
        $f = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', file_get_contents($file));
        $f = explode($eol, $f);
        $h = str_getcsv(array_shift($f), $delimeter);
        array_map('trim', $h); //Bugfix: Geschütztes Leerzeichen am Anfang des CSV-Files :-/
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
        return "[$text]($link \"$alt\")";
    }
}
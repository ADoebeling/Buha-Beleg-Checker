<?php

namespace de\doebeling;

setlocale (LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');

class buhaBelegChecker
{
    /**
     * Ordner mit Kontoauszügen als csv
     *
     */
    const dirKontoauszuege = '../2020/KA - Kontoauszug/';

    /**
     * Ordner mit Buchungen als CSV
     */
    const dirBuchungen = '../2020/BP - Buchungsprotokoll';

    /**
     * Ordner mit Belegen
     */
    const dirBelege = '../2020/';

    const mdReport = '../report2020.md';

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
     *
     * [123][konto] = DWI SPK
     * [?][beleg][] = rechnung.pdf
     */
    private $b = array();

    /**
     * @var string
     */
    private $md = '';

    private $timer = 0.0;




    public function __construct()
    {
        $this->getTimer();

        $this->parseBelege()->
        parseBuchungen()->
        parseKontoauszuege()->
        datenaufbereitung()->
        //addTodosToMd()->
        addListToMd()->
        writeMd();

        echo "done, {$this->getTimer()} s";

        print_r($this->b);
    }


    /**
     * @param string $dir
     */
    private function parseKontoauszuege($dir = self::dirKontoauszuege)
    {
        $files = self::getDirRecursivelyAsArray($dir, '/.*\.csv/i');
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
     * @param string $dir
     */
    private function parseBelege($dir = self::dirBelege)
    {
        $files = self::getDirRecursivelyAsArray($dir);
        foreach ($files as $path => $filename) {
            preg_match(self::regExBelege, $filename, $matches);
            $belegId = isset($matches['id']) && $matches['id'] != '' ? $matches['id'] : '?';
            $this->b[$belegId]['belege'][$path] = $filename;
        }
        return $this;
    }


    private function parseBuchungen($dir = self::dirBuchungen)
    {
        $files = self::getDirRecursivelyAsArray($dir);
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

    private function datenaufbereitung()
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

            // Belege
            if (!empty($b['belege'])) foreach ($b['belege'] as $filepath => $filename)
            {
                $b['mdBelege'] .= "[$filename]($filepath) ";
            }
        }
        return $this;
    }

    private function addTodosToMd()
    {
        $md = &$this->md;
        if (isset($this->b['?'])) {
            $b = &$this->b['?'];

            if (!empty($b['belege'])) {
                $md .= "## ToDo: Nicht erfasste Belege\nBei folgenden Belegen fehlt noch die Belegnummer im Dateinamen\n";
                foreach ($b['belege'] as $filepath => $file) $md .= "* [ ] `$file`\n";
                $md .= "\n\n";
            }

            if (!empty($b['buchungen'])) {
                $md .= "## ToDo: Fehlerhafte Buchungen\nBei folgenden Buchungen ist keine Belegnummer angegeben\n";
                foreach ($b['buchungen'] as $bu) {
                    $bu['Betrag'] = self::getFloat($bu['Soll']) != 0 ? $bu['Soll'] : $bu['Haben'];
                    $md .= "* [ ] `{$bu['Belegdatum']}` | `{$bu['Betrag']} €` von `{$bu['Kontobezeichnung']}` an `{$bu['Gegenkontobezeichnung']}`\n";
                }
            }
        }
        return $this;
    }


    private function addListToMd()
    {
        $md = &$this->md;
        $md .= "## Liste aller Buchungen\n\n";
        foreach ($this->b as $bid => $b) {
            if ($bid != '?') {
                $md .= "### #$bid\n";
                $md .= "| Beleg | Information | Bewertung |\n|---|---|---|\n";

                $md .= isset($b['buchungen'][0]['Belegdatum']) ? "| Beleg-/Rechnungsdatum: | {$b['buchungen'][0]['Belegdatum']} | OK gem. `{$b['buchungen'][0]['file']}` |\n" : "| Beleg-/Rechnungsdatum: | **Unbekannt** | **FEHLER:** Keine Buchung gefunden |\n";
                $md .= isset($b['Kontoauszug']['Wertstellung']) ? "| Zahlungsdatum: | {$b['Kontoauszug']['Wertstellung']} | OK gem. `{$b['Kontoauszug']['file']}` |\n" : "| Zahlungsdatum: | **Unbekannt** | **FEHLER:** Keine Zahlung gefunden |\n";
                $md .= isset($b['Betrag']) ? "| Betrag: | {$b['Betrag']} | OK, bitte mit Beleg abgleichen` |\n" : "| Betrag: | **Unbekannt** | **FEHLER:** Keine Zahlung gefunden |\n";
                $md .= isset($b['Kontoauszug']['Empfänger/Auftraggebe']) ? "| Überweisung von/an: | {$b['Kontoauszug']['Empfänger/Auftraggebe']} | OK gem. `{$b['Kontoauszug']['file']}` |\n" : "";
                $md .= isset($b['Kontoauszug']['Verwendungszweck']) ? "| Verwendungszweck: | {$b['Kontoauszug']['Verwendungszweck']} | OK gem. `{$b['Kontoauszug']['file']}` |\n" : "";
                $md .= isset($b['buchungen'][0]) ? "| Buchungssatz: | {$b['buchungen'][0]['Kontobezeichnung']} an {$b['buchungen'][0]['Gegenkontobezeichnung']}  | OK gem. `{$b['buchungen'][0]['file']}` |\n" : "";
                $md .= isset($b['mdBelege']) ? "| Belege: | {$b['mdBelege']} | OK` |\n" : "| Belege: | **Fehlen** | **FEHLER:** Keine Belege gefunden |\n";




                /*
                | Datum | 01.01.2020  | OK gem. `Kontoauszug XXXX.qif` und `buchungsprotokoll.csv`, bitte mit RE abgleichen |
                | Betrag |1234,56 €   | OK gem. `Kontoauszug XXXX.qif` und `buchungsprotokoll.csv`, bitte mit RE abgleichen |
                | Kontobewegung: | Sparkasse an Amazon UK  | OK gem. `Kontoauszug XXXX.qif` und `buchungsprotokoll.csv`  |
                | Verwendungszweck | sdf98z23jklsf893kjsdf  | OK gem. `Kontoauszug XXXX.qif` |
                | Buchung: | *Bank* an *Hostingaufwendungen* | OK gem. `buchungsprotokoll.csv` |
                | Belege : | `#1234 - df - invoice 1234.pdf`, `#1234 - df - invoice 1234.msg` |  |
                */
            }
        }

        $md .= "\n\nGeneriert am " . date("d.m.Y H:i:s");
        return $this;
    }

    private function writeMd()
    {
        file_put_contents(self::mdReport, $this->md);
        return $this;
    }


    /**
     * @param $dir
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
                    $result = array_merge_recursive($result, self::getDirRecursivelyAsArray($dir . DIRECTORY_SEPARATOR . $value));
                } else {
                    //$result[] = $value;
                    $result[$dir . DIRECTORY_SEPARATOR . $value] = $value;
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

    private function getTimer()
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

}
$bbc = new buhaBelegChecker();


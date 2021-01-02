<?php

namespace de\doebeling;

use MimoGraphix\QIF\Parser;


class buhaBelegChecker
{
    /**
     * Ordner mit Kontoauszügen aus quif
     *
     */
    const dirQif  = '../2020/KA - Kontoauszug/';

    /**
     * Ordner mit Buchungen als CSV
     */
    const dirBuchungen  = '../2020/BP - Buchungsprotokoll';

    /**
     * Ordner mit Belegen
     */
    const dirBelege  = '../2020/';

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


    public function __construct()
    {
        // DEBUG
        //print_r(self::getDirRecursivelyAsArray(self::dirBelege));
        //$this->parseBelege();
        //$this->parseBuchungen();
        $this->parseQif();
        print_r($this->b);
    }


    /**
     * @param string $dir
     */
    private function parseBelege($dir = self::dirBelege)
    {
        $files = self::getDirRecursivelyAsArray($dir);
        foreach ($files as $path => $filename)
        {
            preg_match(self::regExBelege, $filename, $matches);
            $belegId = isset($matches['id']) && $matches['id'] != '' ? $matches['id'] : '?';
            $this->b[$belegId]['belege'][$path] = $filename;
        }
        return $this;
    }

    private function parseQif($dir = self::dirQif)
    {
        $files = self::getDirRecursivelyAsArray($dir);
        foreach ($files as $filepath => $filename)
        {
            $file = file_get_contents($filepath);
            $qifParser = new Parser( $file );
            $qifParser->parse();
            print_r($qifParser->getTransactions());
            foreach( $qifParser->getTransactions() as $transaction )
            {
                // your code
            }
        }
    }

    private function parseBuchungen($dir = self::dirBuchungen)
    {
        $files = self::getDirRecursivelyAsArray($dir);
        foreach ($files as $filepath => $filename)
        {
            $buchungen = self::getCsvAsArray($filepath, ';');
            foreach ($buchungen as $b)
            {
                // Bugfix for empty csv-lines
                if (empty($b['Buchungsnummer'])) continue;

                preg_match(self::regExBuchungstext, $b['Buchungstext'], $matches);
                if (isset($matches['id']))               $b['id'] = $matches['id'];
                else if (!empty($b['Belegnummer']))      $b['id'] = $b['Belegnummer'];
                else                                     $b['id'] = '?';
                $b['filename'] = $filepath;
                $this->b[$b['id']]['buchungen'][] = $b;
            }
        }
    }

    private function getBAsMd()
    {

    }



    /**
     * @param $dir
     * @return array
     * @author mmda.nl@gmail.com
     * @link https://www.php.net/manual/de/function.scandir.php#110570
     */
    static function getDirRecursivelyAsArray($dir)
    {
        $result = array();
        $cdir = scandir($dir);
        foreach ($cdir as $value)
        {
            if (!in_array($value,array(".","..")))
            {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
                {
                    //$result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
                    $result = array_merge_recursive($result, self::getDirRecursivelyAsArray($dir . DIRECTORY_SEPARATOR . $value));
                }
                else
                {
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
        $f = preg_replace( '/[\x{200B}-\x{200D}\x{FEFF}]/u', '', file_get_contents($file));
        $f = explode($eol, $f);
        $h = str_getcsv(array_shift($f), $delimeter);
        array_map('trim', $h); //Bugfix: Geschütztes Leerzeichen am Anfang des CSV-Files :-/
        foreach ($f as $row => $line) foreach (str_getcsv($line, $delimeter) as $k => $v) $r[$row][$h[$k]] = $v;
        return $r;
    }

}

$bbc = new buhaBelegChecker();


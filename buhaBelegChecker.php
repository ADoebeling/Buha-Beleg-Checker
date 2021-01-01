<?php

namespace de\doebeling;

require_once '3rd-party/qif-library/src/Parser.php';

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
        $this->parseBelege();
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

    }

    private function parseBuchungen($dir = self::dirBuchungen)
    {

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

}

$bbc = new buhaBelegChecker();
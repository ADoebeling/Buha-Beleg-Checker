<?php

namespace DOEBELING\BuHaJournal;


use DOEBELING\BuHaJournal\buchungen\buchung\buchungsElement;

class parser
{
    /**
     * @var journal
     */
    protected $journal;

    /**
     * Methode getFiles
     *
     * @param string $dir
     */
    public static function getFilesAsArray(string $dir) : array
    {

    }

    public static function getCsvAsArray(string $dir, $felder) : array
    {
        $files = self::getCsvAsArray($dir);
        if (is_array($files) && !empty($files))
        {
            // todo
        }
    }

    /**
     * Methode get
     *
     * @return journal
     * @throws exception
     */
    public final function get() : journal
    {
        if (!($this->journal instanceof journal))
        {
            throw new exception("$journal muss ein journal sein");
        }
        return $this->journal;
    }
}
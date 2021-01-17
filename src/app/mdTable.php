<?php

namespace DOEBELING\BuHaJournal;

/**
 * Class mdTable
 *
 * @package   DOEBELING\BuHaJournal
 * @author    Andreas Döbeling <opensource@doebeling.de>
 * @copyright DÖBELING Web&IT
 * @link      https://github.com/ADoebeling/
 * @link      https://www.Doebeling.de
 * @license   CC BY-SA 4.0 <https://creativecommons.org/licenses/by-sa/4.0/>
 */
class mdTable
{
    /**
     * @var array
     */
    protected $rows = [];

    /**
     * Methode setTitle
     *
     * @param string $nr
     * @param string $row
     * @param string $text
     * @param string $link
     * @return $this
     */
    public function setTitle(string $nr, string $title, string $text, string $link)
    {
        $row = new mdTableRow();
        $row->setNr("**$nr**");
        $row->setTitle("**$title**");
        $row->setText("**$text**");
        $row->setLink("**$link**");
        $this->rows[0] = $row;

        return $this;
    }

    /**
     * Methode add
     *
     * @param int        $order
     * @param mdTableRow $row
     * @return $this
     */
    public function add($rows) : self
    {
        if ($rows instanceof mdTableRow)
        {
            //$this->rows[$order] = $rows;
            $this->rows[] = $rows;
        }
        else if ($rows instanceof mdTable)
        {
            $this->rows = array_merge($this->rows, $rows->getRows());
        }
        else
        {
            throw new exception("Falscher Datentyp");
        }

        return $this;
    }

    public function getMd() : string
    {
        ksort($this->rows);
        $md = '';

        /** @var mdTableRow $headline */
        $headline = array_shift($this->rows);
        $md .= $headline->getMd();

        $md .= "|---|---|---|---|\n";

        /** @var mdTableRow $row */
        foreach ($this->rows as $row)
        {
            $md .= $row->getMd();
        }
        return $md;
    }

    /**
     * @return array
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    public function __destruct()
    {
        //print_r($this->rows);
        //echo $this->getMd();
        // TODO: Implement __destruct() method.
    }
}
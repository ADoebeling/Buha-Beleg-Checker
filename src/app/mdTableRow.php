<?php

namespace DOEBELING\BuHaJournal;

/**
 * Class mdTableRow
 *
 * @package   DOEBELING\BuHaJournal
 * @author    Andreas Döbeling <opensource@doebeling.de>
 * @copyright DÖBELING Web&IT
 * @link      https://github.com/ADoebeling/
 * @link      https://www.Doebeling.de
 * @license   CC BY-SA 4.0 <https://creativecommons.org/licenses/by-sa/4.0/>
 */
class mdTableRow
{
    /**
     * @var string
     */
    protected $nr = '';

    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $text = '';

    /**
     * @var string
     */
    protected $link = '';

    /**
     * Methode setNr
     *
     * @param string $nr
     * @return $this
     */
    public function setNr(string $nr): self
    {
        $this->nr = $nr;
        return $this;
    }

    /**
     * @param string $title
     * @return mdTableRow
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param string $text
     * @return mdTableRow
     */
    public function setText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @param string $link
     * @return mdTableRow
     */
    public function setLink(string $link): self
    {
        $this->link = $link;
        return $this;
    }

    /**
     * Methode get
     *
     * @return string
     */
    public function getMd(): string
    {
        $md = "| {$this->nr} | {$this->title} | {$this->text} | {$this->link} |\n";
        return $md;
    }
}
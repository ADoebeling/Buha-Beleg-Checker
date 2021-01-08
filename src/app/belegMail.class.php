<?php

declare(strict_types=1);

namespace DOEBELING\buhaJournal;

require_once 'phpFunctionExtensions.php';
use DOEBELING\phpFunctionExtensions;

error_reporting(E_ALL);

/**
 * Class Belegmail
 *
 * @author    Andreas Döbeling <opensource@doebeling.de>
 * @copyright DÖBELING Web&IT
 * @link      https://www.Doebeling.de
 * @link      https://github.com/ADoebeling/buhaJournal
 * @license   CC BY-SA 4.0 <https://creativecommons.org/licenses/by-sa/4.0/>
 *
 * @package   DOEBELING\buhaJournal
 */
class Belegmail
{
    protected $mb;
    protected $msgno;
    protected $uid;
    protected $header = array();
    protected $headerText = '';
    protected $body = '';

    public function __construct(&$mb, int &$msgno)
    {
        $this->mb = &$mb;
        $this->msgno = $msgno;
    }

    public function getUid(): int
    {
        if (empty($this->uid)) $this->uid = imap_uid($this->mb, $this->msgno);
        return $this->uid;
    }

    public function getHeader(): object
    {
        if (empty($this->header)) $this->header = imap_headerinfo($this->mb, $this->msgno);
        return phpFunctionExtensions::imap_utf8($this->header);
    }

    public function getHeaderText(): string
    {
        if (empty($this->headerText)) $this->headerText = imap_fetchheader($this->mb, $this->msgno, FT_PREFETCHTEXT);
        return phpFunctionExtensions::imap_utf8($this->headerText);
    }

    public function getBody(): string
    {
        if (empty($this->Body)) $this->body = imap_body($this->mb, $this->msgno);
        return phpFunctionExtensions::imap_utf8($this->body);
    }

    public function getEml(): string
    {
        return $this->getHeaderText() . "\n" . $this->getBody();
    }

    public function getFromDomain(): string
    {
        return $this->getHeader()->from[array_key_first($this->getHeader()->from)]->host;
    }

    public function getTimestamp(): int
    {
        return $this->getHeader()->udate;
    }

    public function getDateAsYMDString(): string
    {
        return date("Y-m-d", $this->getTimestamp());
    }

    public function getDateAsY(): string
    {
        return date("Y", $this->getTimestamp());
    }
}
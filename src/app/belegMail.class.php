<?php

declare(strict_types=1);

namespace DOEBELING\buhaJournal;
require_once 'phpFunctionExtensions.php';

use DOEBELING\phpFunctionExtensions;

error_reporting(E_ALL);

/**
 * Class Belegmail
 *
 * @package   DOEBELING\buhaJournal
 * @author    Andreas Döbeling <opensource@doebeling.de>
 * @copyright DÖBELING Web&IT
 * @link      https://github.com/ADoebeling/buhaJournal
 * @link      https://www.Doebeling.de
 * @license   CC BY-SA 4.0 <https://creativecommons.org/licenses/by-sa/4.0/>
 *
 */
class belegMail
{
    protected $mailbox;
    protected $messageNmb;
    protected $uid;
    protected $header = array();
    protected $headerText = '';
    protected $body = '';
    protected $structure = '';
    protected $attachments;

    public function __construct(&$mb, int &$msgno)
    {
        $this->mailbox = &$mb;
        $this->messageNmb = $msgno;
    }

    public function getUid(): int
    {
        if (empty($this->uid)) $this->uid = imap_uid($this->mailbox, $this->messageNmb);
        return getMimeAsUtf8($this->uid);
    }

    public function getHeader(): object
    {
        if (empty($this->header)) $this->header = imap_headerinfo($this->mailbox, $this->messageNmb);
        return getMimeAsUtf8($this->header);
    }

    public function getHeaderText($returnAsUtf8 = true): string
    {
        if (empty($this->headerText)) $this->headerText = imap_fetchheader($this->mailbox, $this->messageNmb, FT_PREFETCHTEXT);
        return $returnAsUtf8 ? getMimeAsUtf8($this->headerText) : $this->headerText;
    }

    public function getBody($returnAsUtf8 = true): string
    {
        if (empty($this->body)) $this->body = imap_body($this->mailbox, $this->messageNmb);
        return getMimeAsUtf8($this->body);
    }

    public function getStructure(): object
    {
        if (empty($this->structure)) $this->structure = imap_fetchstructure($this->mailbox, $this->messageNmb);
        return $this->structure;
    }

    public function getSubject(): string
    {
        return $this->getHeader()->subject;
    }

    public function getAttachments($subtype = 'PDF'): object
    {
        if (empty($this->attachments))
        {
            $this->attachments = (object)array();
            if (!empty($this->getStructure()->parts))
            {
                foreach ($this->getStructure()->parts as $section => $part)
                {
                    if ($part->subtype == 'PDF')
                    {
                        $file = $part->dparameters[\array_key_first($part->dparameters)]->value;
                        $body = imap_fetchbody($this->mailbox, $this->messageNmb, strval($section + 1));
                        $this->attachments->$file = base64_decode($body);
                    }
                    else if ($part->subtype == 'PLAIN')
                    {
                        $file = 'text.text';
                        $body = imap_fetchbody($this->mailbox, $this->messageNmb, strval($section + 1));
                        $this->attachments->$file = getMimeAsUtf8($body);
                    }
                }
            }
        }
        return $this->attachments;
    }

    public function getEml(): string
    {
        return $this->getHeaderText(false) . "\n" . $this->getBody(false);
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

    public function moveMailsTo($dir)
    {
        imap_mail_move ( $this->mailbox , strval($this->uid) , "INBOX.$dir" , CP_UID);
        imap_expunge($this->mailbox);
    }
}
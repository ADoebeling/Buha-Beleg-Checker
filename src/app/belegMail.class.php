<?php

declare(strict_types=1);

namespace DOEBELING\buhaJournal;
require_once 'phpFunctionExtensions.php';

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
    protected $msgNumber;
    protected $uid;
    protected $header = array();
    protected $headerText = '';
    protected $body = '';
    protected $parts;
    protected $downloadFiles; // [$url] = $filename

    /**
     * @var log
     */
    public $log;

    public function __construct(&$mb, int $msgNumber, array $log)
    {
        $this->mailbox = &$mb;
        $this->msgNumber = $msgNumber;
        $this->setLog($log);
    }

    public function setLog(array $log)
    {
        $this->log = new log("belegAbruf->belegMails->belegMail[{$this->getUid()}]");
        foreach ($log as $handler)
        {
            $this->log->pushHandler($handler);
        }
        return $this;
    }

    public function getUid(): int
    {
        if (empty($this->uid))
        {
            $this->uid = imap_uid($this->mailbox, $this->msgNumber);
        }
        return getMimeAsUtf8($this->uid);
    }

    public function getHeader(): object
    {
        if (empty($this->header))
        {
            $this->header = imap_headerinfo($this->mailbox, $this->msgNumber);
            $this->log->debug(__METHOD__, [$this->getHeader()]);
        }
        return getMimeAsUtf8($this->header);
    }

    public function getHeaderText($returnAsUtf8 = true): string
    {
        if (empty($this->headerText))
        {
            $this->headerText = imap_fetchheader($this->mailbox, $this->msgNumber, FT_PREFETCHTEXT);
            $this->log->debug(__METHOD__, $this->getHeaderText());
        }
        return $returnAsUtf8 ? getMimeAsUtf8($this->headerText) : $this->headerText;
    }

    public function getBody($returnAsUtf8 = true): string
    {
        if (empty($this->body))
        {
            $this->body = imap_body($this->mailbox, $this->msgNumber);
            $this->log->debug(__METHOD__, $this->getBody());
        }
        return $returnAsUtf8 ? getMimeAsUtf8($this->body) : $this->body;
    }


    public function getParts($returnAsUtf8 = true): object
    {
        if (empty($this->parts))
        {
            $this->parts = (object) imap_fetchstructure($this->mailbox, $this->msgNumber)->parts;
            foreach ($this->parts as $sectionId => $part)
            {
                $this->parts->$sectionId->body = imap_fetchbody($this->mailbox, $this->msgNumber, strval($sectionId + 1));
                $this->parts->$sectionId->filename = isset($part->disposition) && $part->disposition == 'attachment' ? $part->parameters[0]->value : 'INLINE';
            }
            $this->log->debug(__METHOD__);
        }

        return $returnAsUtf8 ? getMimeAsUtf8($this->parts) : $this->parts;
    }

    /**
     * Methode getFullyQualifiedFileName
     *
     * @todo Refactor
     */
    public function getFilePrefix()
    {
        $return = (object) array();
        $return->path = "{$this->getDateAsY()}/MailAttachments/{$this->getFromDomain()}/";
        $return->filePrefix = "{$this->getFromDomain()}__{$this->getUid()}__";
        return $return;
    }


    public function &getSubject(): string
    {
        $this->log->debug(__METHOD__, $this->getHeader()->subject);
        return $this->getHeader()->subject;
    }

    /*
    public function &getAttachments($subtype): object
    {
        if (empty($this->attachments))
        {
            $this->log->debug(__METHOD__, func_get_args());
            $this->attachments = (object) array();
            if (!empty($this->getParts()))
            {
                foreach ($this->getParts() as $section => $part)
                {
                    if ($part->subtype == 'PDF' && $subtype == 'PDF')
                    {
                        // TODO
                        $file = $part->dparameters[\array_key_first($part->dparameters)]->value;
                        $body = imap_fetchbody($this->mailbox, $this->messageNmb, strval($section + 1));
                        $this->attachments->$file = base64_decode($body);
                    }
                    else if ($part->subtype == 'PLAIN' && $subtype == 'PLAIN')
                    {
                        $file = 'PLAIN';
                        $body = imap_fetchbody($this->mailbox, $this->messageNmb, strval($section + 1));

                        $header = array('toaddress' => "An", 'fromaddress' => "Von", 'date' => "Empfangsdatum", 'reply_toaddress' => "Antwort an", 'senderaddress' => "Sender");
                        $headerText = "| E-Mail | {$this->getHeader()->subject}  |\n|---|---|\n";
                        foreach ($header as $k => $v)
                        {
                            $headerText .= "| $v | {$this->getHeader()->$k} |\n";
                        }

                        $this->attachments->$file = getMimeAsUtf8("$headerText\n\n$body");
                    }
                }
            }
        }
        return $this->attachments;
    }
    */

    public function getBodyAsText(): string
    {
        foreach ($this->getParts() as $part)
        {
            if ($part->type == TYPETEXT && $part->subtype == 'PLAIN')
            {
                return $part->body;
            }
            else if ($part->type == TYPETEXT && $part->subtype == 'HTML')
            {
                return strip_tags($part->body);
            }
        }
        return '';
    }

    public function getEml(): string
    {
        $this->log->debug(__METHOD__);
        return $this->getHeaderText(false) . "\n" . $this->getBody(false);
    }

    public function getFromDomain(): string
    {
        return $this->getHeader()->from[array_key_first($this->getHeader()->from)]->host;
    }

    public function &getFrom(): string
    {
        return $this->getHeader()->fromaddress;
    }

    public function &getTo(): string
    {
        return $this->getHeader()->toaddress;
    }

    public function &getTimestamp(): int
    {
        return $this->getHeader()->udate;
    }


    public function getDateAsY(): string
    {
        return date("Y", $this->getTimestamp());
    }

    public function moveMailsTo($dir)
    {
        $this->log->debug(__METHOD__, func_get_args());
        imap_mail_move($this->mailbox, strval($this->uid), "INBOX.$dir", CP_UID);
        imap_expunge($this->mailbox);
    }

    public function addDownloadFile(string $fileName, string $fileUrl)
    {
        if (empty($this->downloadFiles))
        {
            $this->downloadFiles = (object) array();
        }
        $this->downloadFiles->$fileUrl = $fileName;
        return $this;
    }

    public function &getDownloadFiles()
    {
        if (empty($this->downloadFiles))
        {
            $this->downloadFiles = (object) array();
        }
        return $this->downloadFiles;
    }
}
<?php

namespace DOEBELING\buhaJournal;

require_once 'belegMail.class.php';

/**
 * Class Belegmails
 *
 * @package   DOEBELING\buhaJournal
 * @author    Andreas Döbeling <opensource@doebeling.de>
 * @copyright DÖBELING Web&IT
 * @link      https://github.com/ADoebeling/
 * @link      https://www.Doebeling.de
 * @license   CC BY-SA 4.0 <https://creativecommons.org/licenses/by-sa/4.0/>
 *
 */
class belegMails
{
    /**
     * @var resource Mailbox IMAP-Stream
     */
    protected $mailbox;

    /**
     * @var object Belegmail-Array
     */
    protected $belegMail;

    public function __construct(string $sslHostname, int $sslPort, string $user, string $pwd)
    {
        $this->setMailbox($sslHostname, $sslPort, $user, $pwd);
        $this->setBelegMail();
    }

    protected function setMailbox(string $sslHostname, int $sslPort, string $user, string $pwd)
    {
        $server = "{{$sslHostname}:{$sslPort}/imap/ssl}INBOX";
        $this->mailbox = imap_open($server, $user, $pwd);
        return $this;
    }

    protected function setBelegMail()
    {
        $this->belegMail = (object) array();

        for ($i = imap_num_msg($this->mailbox); $i > 0; $i--)
        {
            $bm = new belegMail($this->mailbox, $i);
            $this->belegMail->{$bm->getUid()} = $bm;
        }
        return $this;
    }

    public function getByUid($uid): belegMail
    {
        return $this->belegMail->$uid;
    }

    public function getAll(): \stdClass
    {
        return $this->belegMail;
    }

    public function hasMails(): bool
    {
        return (count(get_object_vars($this->getAll())) > 0);
    }

    public function __destruct()
    {
        imap_close($this->mailbox);
    }
}
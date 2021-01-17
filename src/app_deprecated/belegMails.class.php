<?php

namespace DOEBELING\buhaJournal;
use stdClass;

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

    /**
     * @var log
     */
    public $log;

    public function __construct(string $sslHostname, int $sslPort, string $user, string $pwd, array $log)
    {
        $this->setLog($log);
        $this->log->debug(__METHOD__, [$sslHostname, $sslPort, $user]);
        $this->setMailbox($sslHostname, $sslPort, $user, $pwd, $log);
        $this->setBelegMail($log);
    }

    public function setLog(array $log)
    {
        $this->log = new log("belegAbruf->belegMails");
        foreach ($log as $handler) $this->log->pushHandler($handler);
        return $this;
    }

    protected function setMailbox(string $sslHostname, int $sslPort, string $user, string $pwd)
    {
        $this->log->debug(__METHOD__, [$sslHostname, $sslPort, $user]);
        $server = "{{$sslHostname}:{$sslPort}/imap/ssl}INBOX";
        $this->mailbox = imap_open($server, $user, $pwd);
        return $this;
    }

    protected function setBelegMail(array $log)
    {
        $this->log->debug(__METHOD__, func_get_args());
        $this->belegMail = (object) array();

        for ($i = imap_num_msg($this->mailbox); $i > 0; $i--)
        {
            $bm = new belegMail($this->mailbox, $i, $log);
            $this->belegMail->{$bm->getUid()} = $bm;
        }
        return $this;
    }

    public function getByUid($uid): belegMail
    {
        $this->log->debug(__METHOD__, func_get_args());
        return $this->belegMail->$uid;
    }

    public function getAll(): stdClass
    {
        $this->log->debug(__METHOD__, func_get_args());
        return $this->belegMail;
    }

    public function hasMails(): bool
    {
        $this->log->debug(__METHOD__, func_get_args());
        $count = count(get_object_vars($this->getAll()));
        return ($count > 0);
    }

    public function __destruct()
    {
        $this->log->debug(__METHOD__, func_get_args());
        imap_close($this->mailbox);
        return $this;
    }
}
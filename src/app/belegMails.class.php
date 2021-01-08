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
    protected $mb;

    /**
     * @var array Belegmail-Array
     */
    protected $bm = [];

    public function __construct(string $sslHostname, int $sslPort, string $user, string $pwd)
    {
        $mailbox = "{{$sslHostname}:{$sslPort}/imap/ssl}INBOX";
        $this->mb = imap_open($mailbox, $user, $pwd);
        for ($i = imap_num_msg($this->mb); $i > 0; $i--)
        {
            $bm = new Belegmail($this->mb, $i);
            $this->bm[$bm->getUid()] = $bm;
        }
        return $this;
    }

    public function get($id): Belegmail
    {
        return $this->bm[$id];
    }

    public function getAll(): array
    {
        return $this->bm;
    }

    public function __destruct()
    {
        imap_close($this->mb);
    }
}
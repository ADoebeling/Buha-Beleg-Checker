<?php

declare(strict_types=1);

namespace DOEBELING\buhaJournal;

use Monolog\Handler\StreamHandler;

error_reporting(E_ALL);

require_once 'phpFunctionExtensions.php';
require_once 'belegMails.class.php';
require_once 'belegMail.class.php';

/**
 * Class Belegabruf
 *
 * @package   DOEBELING\buhaJournal
 *
 * @author    Andreas Döbeling <opensource@doebeling.de>
 * @copyright DÖBELING Web&IT
 * @link      https://github.com/ADoebeling/
 * @link      https://www.Doebeling.de
 * @license   CC BY-SA 4.0 <https://creativecommons.org/licenses/by-sa/4.0/>
 */
class belegAbruf
{
    /**
     * @var object belegMails
     */
    protected $belegMails;

    /**
     * @var log
     */
    public $log;

    public function __construct(string $sslHostname, int $sslPort, string $user, string $pwd, array $log)
    {
        $this->setLog($log);
        $this->log->debug(__METHOD__, [$sslHostname, $sslPort, $user]);
        $this->belegMails = new belegMails($sslHostname, $sslPort, $user, $pwd, $log);
    }

    public function setLog(array $log)
    {
        $this->log = new log("belegAbruf");
        foreach ($log as $handler)
            $this->log->pushHandler($handler);
        return $this;
    }

    public function downloadMailsAsEml($dir)
    {
        foreach ($this->belegMails->getAll() as &$m)
        {
            /** @var $m belegMail */

            // Dir
            $dir = sprintf($dir, $m->getDateAsY());
            if (!file_exists($dir))
            {
                mkdir($dir, 0770, true);
            }

            // Filename
            $fileName = $m->getFilePrefix()->filePrefix . getStringAsFilename($m->getSubject()).'.eml';
            file_put_contents($dir.$fileName, $m->getEml());
            $m->addDownloadFile($fileName, $dir.$fileName);
            $this->log->info("Download $fileName", [__METHOD__]);
        }
        return $this;
    }



    public function downloadMailAttachments($dir): belegAbruf
    {
        foreach ($this->belegMails->getAll() as $belegMail)
        {
            /** @var $belegMail belegMail */
            foreach ($belegMail->getParts() as $part)
            {
                if ($part->subtype == 'PDF')
                {
                    // Dir
                    $dir = sprintf($dir, $belegMail->getDateAsY());
                    if (!file_exists($dir))
                        mkdir($dir, 0770, true);

                    // Filename
                    $file = $belegMail->getFilePrefix()->filePrefix . getStringAsFilename($part->filename);

                    file_put_contents($dir.$file, base64_decode($part->body));
                    $this->log->info("Download $file", [__METHOD__]);
                }
            }
        }
        return $this;
    }

    /**
     * Downloads Mails als MD and
     *
     * @param $dir
     * @return $this
     */
    public function downloadMailsAsMd($dir): belegAbruf
    {
        foreach ($this->belegMails->getAll() as $belegMail)
        {
            /** @var belegMail $belegMail */
            $belegMail;

            $metaText = '`' . getStringAsMd($belegMail->getFrom()) . '` *an* <br>';
            $metaText .= '`' . getStringAsMd($belegMail->getTo()) . '` <br><br>';
            $metaText .= '`' . getStringAsMd($belegMail->getSubject()) . '`';

            $attachements = '';
            foreach ($belegMail->getParts() as $partId => $part)
            {
                $attachements .= getMdLink($part->filename, $belegMail->getFilePrefix()->path . $belegMail->getFilePrefix()->filePrefix . $part->filename) . "<br>";
            }

            $body = "| E-Mail #{$belegMail->getUid()} |  |   |\n";
            $body .= "|---|---|---|\n";
            $body .= "| E-Mail: | $metaText | $attachements |\n";
            $body .= "\n \n";
            $body .= $belegMail->getBodyAsText();



            // Dir
            $dir = sprintf($dir, $belegMail->getDateAsY());
            if (!file_exists($dir))
                mkdir($dir, 0770, true);

            // Filename
            $file = $belegMail->getFilePrefix()->filePrefix . getStringAsFilename($belegMail->getSubject()) . '.md';

            file_put_contents($dir.$file, $body);
            $this->log->info("Download $file", __METHOD__);
        }
        return $this;
    }

    public function hasMails(): bool
    {
        $hasMail = $this->belegMails->hasMails();
        if ($hasMail)
        {
            foreach ($this->belegMails->getAll() as $belegMail)
            {
                /** @var belegMail $belegMail */
                $this->log->info("Mail gefunden: {$belegMail->getSubject()}", __METHOD__);
            }
        }
        return $hasMail;
    }

    public function moveMailsToDir($dir): belegAbruf
    {
        $this->log->debug(__METHOD__, [$dir]);
        foreach ($this->belegMails->getAll() as $m)
        {
            /** @var $m belegMail */
            $m->moveMailsTo($dir);
            $this->log->info("Mail verschoben: {$m->getSubject()}", __METHOD__);
        }
        return $this;
    }


    public function __destruct()
    {
        $this->log->debug(__METHOD__);
    }
}

<?php

declare(strict_types=1);

namespace DOEBELING\buhaJournal;
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

    public function __construct(string $sslHostname, int $sslPort, string $user, string $pwd)
    {
        $this->belegMails = new belegMails($sslHostname, $sslPort, $user, $pwd);
    }

    public function downloadMailsAsEml($dir)
    {
        foreach ($this->belegMails->getAll() as $m)
        {
            /** @var $m belegMail */

            // Dir
            $dir = sprintf($dir, $m->getDateAsY());
            if (!file_exists($dir))
                mkdir($dir, 0770, true);

            // Filename
            $fileName = [];
            $fileName[] = getStringAsFilename($m->getFromDomain());
            $fileName[] = $m->getUid();
            $fileName[] = getStringAsFilename($m->getSubject());
            $fileName[] = $m->getDateAsYMDString();
            $fileName[] = $m->getUid();
            $fileName = $dir . '/' . implode('__', $fileName) . '.eml';

            file_put_contents($fileName, $m->getEml());
        }
        return $this;
    }

    protected function downloadMailParts($dir, $subtype = 'PDF'): belegAbruf
    {
        foreach ($this->belegMails->getAll() as $m)
        {
            /** @var $m belegMail */
            if (!empty($m->getAttachments()))
            {
                // Dir
                $dir = sprintf($dir, $m->getDateAsY());
                if (!file_exists($dir))
                    mkdir($dir, 0770, true);

                foreach ($m->getAttachments($subtype) as $attachmentName => $attachementBody)
                {
                    // Filename
                    $fileName = [];
                    $fileName[] = getStringAsFilename($m->getFromDomain());
                    $fileName[] = $m->getUid();
                    $fileName[] = getStringAsFilename($m->getSubject());
                    $fileName[] = getStringAsFilename($attachmentName);
                    $fileName[] = $m->getDateAsYMDString();

                    $fileExt = explode('.', $attachmentName);
                    $fileExt = end($fileExt);

                    $fileName = $dir . '/' . implode('__', $fileName) . ".$fileExt";

                    file_put_contents($fileName, $attachementBody);
                }
            }
        }
        return $this;
    }

    public function downloadPdfMailAttachments($dir): belegAbruf
    {
        return $this->downloadMailParts($dir, 'PDF');
    }

    public function downloadMailsAsTxt($dir): belegAbruf
    {
        return $this->downloadMailParts($dir, 'PLAIN');
    }

    public function hasMails(): bool
    {
        return $this->belegMails->hasMails();
    }

    public function moveMailsToDir($dir): belegAbruf
    {
        foreach ($this->belegMails->getAll() as $m)
        {
            /** @var $m belegMail */
            $m->moveMailsTo($dir);
        }
        return $this;
    }
}

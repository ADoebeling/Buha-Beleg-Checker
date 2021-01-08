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
    protected $bm;

    public function __construct(string $sslHostname, int $sslPort, string $user, string $pwd)
    {
        $this->bm = new belegMails($sslHostname, $sslPort, $user, $pwd);
    }

    public function downloadMailsAsEml($dir)
    {
        foreach ($this->bm->getAll() as $m)
        {
            /** @var $m belegMail */
            $dir = sprintf($dir, $m->getDateAsY());
            if (!file_exists($dir)) mkdir($dir);
            $file = "{$m->getFromDomain()}_{$m->getDateAsYMDString()}.eml";
            file_put_contents("$dir/$file", $m->getHeaderText() . "\n" . $m->getBody());
        }
        return $this;
    }
}

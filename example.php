<?php
/**
 * Script
 * @package DOEBELING\BuhaBelegChecker
 *
 * @author      Andreas Döbeling
 * @copyright   DÖBELING Web&IT <http://www.Doebeling.de>
 * @link        https://github.com/ADoebeling/Buha-Beleg-Checker
 * @license     CC-BY-SA <https://creativecommons.org/licenses/by-sa/3.0/de/>
 */

require_once './src/app/belegAbruf.class.php';
require_once './src/app/buchungsChecker.php';
require_once './vendor/autoload.php';

// Execute Script
if (file_exists('../script.php'))
{
    require '../script.php';
}

// Example code
die();

// Optionaler Passwortschutz
if (!isset($_REQUEST['pwd']) || $_REQUEST['pwd'] != 'ein-passwort')
{
    header('HTTP/1.0 403 Forbidden');
    die('You are not allowed to access this file.');
}

// TODO


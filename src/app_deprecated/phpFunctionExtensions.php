<?php

declare(strict_types=1);
error_reporting(E_ALL);


/**
 * Function getMimeAsUtf8
 * Returns an uft8-encoded array/object/string
 * All properties needs to be public
 *
 * @param mixed $mime
 * @return mixed
 *
 * @author    Andreas Döbeling <opensource@doebeling.de>
 * @copyright DÖBELING Web&IT
 * @link      https://www.Doebeling.de
 * @link      https://github.com/ADoebeling/
 * @license   CC BY-SA 4.0 <https://creativecommons.org/licenses/by-sa/4.0/>
 */
function getMimeAsUtf8($mime)
{
    switch (gettype($mime))
    {
        case 'array':
            $result = [];
            foreach ($mime as $k => $v)
                $result[getMimeAsUtf8($k)] = getMimeAsUtf8($v);
            return $result;

        case 'object':
            $class = get_class($mime);
            $result = new $class;
            foreach ($mime as $k => $v)
                $result->{getMimeAsUtf8($k)} = getMimeAsUtf8($v);
            return $result;

        case 'string':
            return imap_utf8($mime);

        default:
            return $mime;
    }
}

/**
 * Function imap_utf8_recursive
 * Alias: getMimeAsUtf8
 *
 * @param mixed $mime_encoded_contents
 * @return mixed
 */
function imap_utf8_recursive($mime_encoded_contents)
{
    return getMimeAsUtf8($mime_encoded_contents);
}


function getStringAsFilename(string $string)
{
    global $log;
    $log->debug(__METHOD__, $string);

    setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');

    $string = str_replace(' ', '-', $string);               // Spaces
    //$string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);   // Umlaute
    // TODO
    $string = mb_ereg_replace("([^\w\-\._])", '', $string); // Specialchars
    $string = mb_ereg_replace("([\.]{2,})", '', $string);
    return $string;
}


function getStringAsMd($string)
{
    $string = str_replace('|', '/', $string);
    return $string;
}


/**
 * @param        $text
 * @param        $link
 * @param string $alt
 * @return string
 */
function getMdLink($text, $link, $alt = "")
{
    $text = trim($text);
    if (empty($alt))
    {
        $alt = explode('/', rawurldecode(stripslashes(trim($link))));
        $alt = 'Öffne ' . $alt[count($alt) - 1];
    }
    if (strstr($link, '/'))
    {
        $link = str_replace('%2F', '/', rawurlencode(stripslashes(trim($link))));
    }
    else
    {
        $link = str_replace('%23', '#', (str_replace('%2F', '/', rawurlencode(stripslashes(trim($link))))));
    }
    return "[`$text`]($link \"$alt\")";
}
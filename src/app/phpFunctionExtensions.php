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
            foreach ($mime as $k => $v) $result[getMimeAsUtf8($k)] = getMimeAsUtf8($v);
            return $result;

        case 'object':
            $class = get_class($mime);
            $result = new $class;
            foreach ($mime as $k => $v) $result->{getMimeAsUtf8($k)} = getMimeAsUtf8($v);
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
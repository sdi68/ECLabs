<?php
/**
 * @package        Econsult Labs Library
 * @version          1.0.3
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2023 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

/**
 * @package     ECLabs\Library
 *
 * @since       1.0.2
 */
class ECLHTMLHelper extends HTMLHelper
{
    /**
     * Переопределяем базовую функцию из-за ошибки
     * Write a `<img>` element
     *
     * @param   string        $file        The relative or absolute URL to use for the src attribute.
     * @param   string        $alt         The alt text.
     * @param   array|string  $attribs     Attributes to be added to the `<img>` element
     * @param   boolean       $relative    Flag if the path to the file is relative to the /media folder (and searches in template).
     * @param   integer       $returnPath  Defines the return value for the method:
     *                                     -1: Returns a `<img>` tag without looking for relative files
     *                                     0: Returns a `<img>` tag while searching for relative files
     *                                     1: Returns the file path to the image while searching for relative files
     *
     * @return  string|null  HTML markup for the image, relative path to the image, or null if path is to be returned but image is not found
     *
     * @since   1.0.2
     */
    public static function image($file, $alt, $attribs = null, $relative = false, $returnPath = 0): ?string
    {
        // Ensure is an integer
        $returnPath = (int) $returnPath;

        // The path of the file
        $path = $file;

        // The arguments of the file path
        $arguments = '';

        // Get the arguments positions
        $pos1 = strpos($file, '?');
        $pos2 = strpos($file, '#');

        // Check if there are arguments
        if ($pos1 !== false || $pos2 !== false) {
            // Get the path only
            // SDI исправление ошибки BOF
            //$path = substr($file, 0, min($pos1, $pos2));
            $length = strlen($file);
            $path = substr($file, 0, min($pos1 === false ? $length : $pos1, $pos2 === false ? $length : $pos2));
            // SDI исправление ошибки EOF

            // Determine the arguments is mostly the part behind the #
            $arguments = str_replace($path, '', $file);
        }

        // Get the relative file name when requested
        if ($returnPath !== -1) {
            // Search for relative file names
            $includes = static::includeRelativeFiles('images', $path, $relative, false, false);

            // Grab the first found path and if none exists default to null
            $path = \count($includes) ? $includes[0] : null;
        }

        // Compile the file name
        $file = ($path === null ? null : $path . $arguments);

        // If only the file is required, return here
        if ($returnPath === 1) {
            return $file;
        }

        // Ensure we have a valid default for concatenating
        if ($attribs === null || $attribs === false) {
            $attribs = [];
        }

        // When it is a string, we need convert it to an array
        if (is_string($attribs)) {
            $attributes = [];

            // Go through each argument
            foreach (explode(' ', $attribs) as $attribute) {
                // When an argument without a value, default to an empty string
                if (strpos($attribute, '=') === false) {
                    $attributes[$attribute] = '';
                    continue;
                }

                // Set the attribute
                list($key, $value) = explode('=', $attribute);
                $attributes[$key]  = trim($value, '"');
            }

            // Add the attributes from the string to the original attributes
            $attribs = $attributes;
        }

        // Fill the attributes with the file and alt text
        $attribs['src'] = $file;
        $attribs['alt'] = $alt;

        // Render the layout with the attributes
        return LayoutHelper::render('joomla.html.image', $attribs);
    }

}
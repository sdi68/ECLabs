<?php
/**
 * @package             Econsult Labs Library
 * @version             __DEPLOYMENT_VERSION__
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2025 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

\defined('_JEXEC') or die;

use ECLabs\Library\ECLLanguage;
use ECLabs\Library\ECLVersion;
use Joomla\CMS\Layout\LayoutHelper;

/**
 * Layout variables
 * @var Array $displayData current extension information
 *
 */
ECLLanguage::loadExtraLanguageFiles("com_plugins");

echo LayoutHelper::render('default-' . ECLVersion::getJoomlaVersionSuffix("j"), $displayData, __DIR__);


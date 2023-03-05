<?php
/*
 * authorize.php  01.03.2023, 16:56
 * Created for project Joomla 3.x
 * Subpackage ___
 * www.econsultlab.ru
 * mail: info@econsultlab.ru
 * Released under the GNU General Public License
 * Copyright (c) 2023 Econsult Lab.
 */

use ECLabs\Library\ECLLanguage;
use ECLabs\Library\ECLVersion;
use Joomla\CMS\Language\Text;
/**
 * @var stdClass $vars
 */
ECLLanguage::loadLibLanguage();

include 'default'.ECLVersion::getJoomlaVersion().'.php';

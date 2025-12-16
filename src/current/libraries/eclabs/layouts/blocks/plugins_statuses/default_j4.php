<?php
/**
 * @package             Econsult Labs Library
 * @version             __DEPLOYMENT_VERSION__
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2025 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

/**** Шаблон вывода блока для Joomla 4 ****/

use ECLabs\Library\ECLLanguage;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

extract($displayData);

/**
 * Layout variables
 * -----------------
 *
 * @var  array  $plugins_info Массив выводимых плагинов.
 * @var  string $title        Заголовок блока.
 * @var  string $folder       Каталог плагинов.
 *
 */

ECLLanguage::loadExtraLanguageFiles('com_plugins', JPATH_ADMINISTRATOR);

?>
<div class="row">
    <div class="span12">
        <h3><?php echo $title; ?></h3>
    </div>
</div>
<div class="row">
    <div class="span12">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th class="left nowrap" width="25px"><?php echo Text::_("JSTATUS"); ?></th>
                <th class="left nowrap" width="25px"><?php echo Text::_("JGRID_HEADING_ID"); ?></th>
                <th class="left"><?php echo Text::_("COM_PLUGINS_NAME_HEADING"); ?></th>
                <th class="left"><?php echo Text::_("JGLOBAL_DESCRIPTION"); ?></th>
            </tr>
            </thead>
            <?php
            foreach ($plugins_info as $plugin)
            {
                $description = "";
                $extension   = 'plg_' . $folder . '_' . $plugin['element'];
                ECLLanguage::loadExtraLanguageFiles($extension, JPATH_ADMINISTRATOR);
                if (!empty($plugin['manifest_cache']))
                {
                    $manifest_cache = json_decode($plugin['manifest_cache'], true);
                    if (array_key_exists('description', $manifest_cache))
                    {
                        $description = Text::_($manifest_cache['description']);
                    }
                }
                else if (!empty($plugin['description']))
                {
                    $description = $plugin['description'];
                }
                $link = '<a href="' . Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $plugin['extension_id']) . '" target = "_blank">' . $plugin['element'] . '</a>';
                ?>
                <tr>
                    <td class="center nowrap">
                        <div class="btn-group">
                            <?php echo HTMLHelper::_('jgrid.published', $plugin['enabled'], 0, '.', false); ?>
                        </div>
                    </td>
                    <td class="left nowrap"><?php echo $plugin['extension_id']; ?></td>
                    <td class="left"><?php echo $link; ?></td>
                    <td class="left"><?php echo $description; ?></td>
                </tr>
                <?php
            }
            ?>
        </table>
    </div>
</div>
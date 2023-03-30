<?php
/**
 * @package        Econsult Labs Library
 * @version          1.0.1
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2023 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use ECLabs\Library\ECLVersion;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

/**
 * Layout variables
 * @var SimpleXMLElement    $info  current extension information
 * @var JFormFieldECL_About $field current field object
 *
 */
extract($displayData);
?>

<div class="about-wrap <?php echo ECLVersion::getJVersionClass(); ?>">
    <div class="about-img">
        <img src="<?php echo $field->ext_image; ?>"/>
    </div>
    <div class="about-intro">
        <div class="about-title">
			<?php echo Text::_($info->name); ?>
        </div>
        <?php if ($field->ext_page || $field->ext_doc): ?>
        <div class="about-links">
			<?php if ($field->ext_page): ?>
                <a href="<?php echo $field->ext_page; ?>"><?php echo Text::_('ECLABS_ABOUT_FIELD_PAGE'); ?></a>
			<?php endif; ?>
			<?php if ($field->ext_doc): ?>
                <a href="<?php echo $field->ext_doc; ?>"><?php echo Text::_('ECLABS_ABOUT_FIELD_DOC'); ?></a>
			<?php endif; ?>
        </div>
        <?php endif; ?>
		<?php echo LayoutHelper::render('default-version', $displayData, __DIR__); ?>
        <div class="about-copyright">
			<?php echo $info->copyright; ?>
        </div>
    </div>
</div>
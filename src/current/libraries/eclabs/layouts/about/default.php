<?php
/**
 * @package         Econsult Labs Library
 * @version         1.0.0
 *
 * @author          ECL <info@econsultlab.ru>
 * @link            https://econsultlab.ru
 * @copyright       Copyright © 2023 ECL All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

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

<div class="about-wrap">
    <div class="about-img">
        <img src="<?php echo $field->ext_image; ?>"/>
    </div>
    <div class="about-intro">
        <div class="about-title">
			<?php echo Text::_($info->name); ?>
        </div>
        <div class="about-links">
			<?php if (!empty($field->ext_page)): ?>
                <a href="<?php echo $field->ext_page; ?>"><?php echo Text::_('ECLABS_ABOUT_FIELD_PAGE'); ?></a>
			<?php endif; ?>
			<?php if (!empty($field->ext_doc)): ?>
                <a href="<?php echo $field->ext_doc; ?>"><?php echo Text::_('ECLABS_ABOUT_FIELD_DOC'); ?></a>
			<?php endif; ?>
        </div>
		<?php echo LayoutHelper::render('default-version', $displayData, __DIR__); ?>
        <div class="about-copyright">
			<?php echo $info->copyright; ?>
        </div>
    </div>
</div>
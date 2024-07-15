<?php
/**
 * @package        Econsult Labs Library
 * @version          1.0.19
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2024 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

extract($displayData);

/**
 * Layout variables
 * -----------------
 *
 * @var  string $class      Classes for the input.
 * @var  string $id         DOM id of the field.
 * @var  string $name       Name of the input field.
 * @var  array  $value      Filed value array.
 * @var  int    $length     Key characters length.
 */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wr = $wa->getRegistry();
$wr->addRegistryFile('/media/eclabs/joomla.assets.json');
// TODO подключить ресурсы компонента
$wa->useScript('eclabs.field-token');

?>
<div class="<?php echo $class; ?>" input-key="container" data-length="<?php echo $length; ?>">
	<p input-key="success" class="alert alert-success" style="display: none;">
		<?php echo Text::_('ECLLABS_TOKEN_REGENERATE_SUCCESS'); ?>
	</p>
	<p>
		<a href="#" input-key="show" class="btn btn-danger"><?php echo Text::_('JSHOW'); ?></a>
		<a href="#" input-key="generate" class="btn btn-success">
			<?php echo Text::_('ECLLABS_TOKEN_REGENERATE'); ?>
		</a>
	</p>
	<code input-key="key" style="display: none;"></code>
	<input type="hidden" input-key="field" value="<?php echo $value; ?>" name="<?php echo $name; ?>">
</div>
<?php
/**
 * @package             Econsult Labs Library
 * @version             2.0.1
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2025 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

use Joomla\CMS\Factory;

\defined('_JEXEC') or die;

extract($displayData);

/**
 * Layout variables
 * -----------------
 *
 * @var  string $class       Classes for the input.
 * @var  string $id          DOM id of the field.
 * @var  string $name        Name of the input field.
 * @var  array  $value       Filed value array.
 * @var  string $token_field Associated token field.
 * @var  string $task_url    Base task url.
 */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wr = $wa->getRegistry();
$wr->addRegistryFile('/media/eclabs/joomla.assets.json');
// TODO подключить ресурсы компонента
$wa->useScript('eclabs.field-taskurl');

?>
<div class="<?php echo $class; ?>" input-taskurl="container" data-token_field="<?php echo $token_field; ?>"
     data-task_url="<?php echo JUri::root() . $task_url; ?>">
    <div input-taskurl="field" class="success"></div>
</div>
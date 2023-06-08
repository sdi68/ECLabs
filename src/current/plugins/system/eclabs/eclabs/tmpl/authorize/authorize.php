<?php
/**
 * @package         Econsult Labs Library
 * @subpackage   Econsult Labs system plugin
 * @version           1.0.1
 * @author            ECL <info@econsultlab.ru>
 * @link                 https://econsultlab.ru
 * @copyright      Copyright © 2023 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

use ECLabs\Library\ECLLanguage;
use ECLabs\Library\ECLTools;
use ECLabs\Library\ECLVersion;
use Joomla\CMS\Language\Text;

/**
 * @var stdClass $vars
 */
ECLLanguage::loadLibLanguage();

if (!$vars->is_free) {
    ob_start();
    include 'default' . ECLVersion::getJoomlaVersion() . '.php';
    $html = ob_get_contents();
    ob_end_clean();

    Text::script('JCLOSE');
    Text::script('JAPPLY');
    Text::script('JSUBMIT');
    Text::script('ECLUPDATEINFO_STATUS_SUCCESS_TEXT');
    Text::script('JVERSION');
    $modal_params = array('debug_mode' => $vars->debug_mode,
        'wrapId' => 'eclModal',
        'dialogClass' => '',
        'hideHeader' => false,
        'hideFooter' => false,
        'hiddenClass' => 'hidden',
        'saveBtnCaption' => Text::_('JSUBMIT'),
        'content' => $html,
        'title' => Text::_('PLG_SYSTEM_ECLABS_AUTHORISATION_TITLE'),
        'shown' => 'showAuthorization',
        'hidden' => '');

    $modal_params = ECLTools::encodeParams($modal_params);
}
$lb_tooltip = $vars->version_tooltip ? 'data-original-title="'.$vars->version_tooltip.'"' :"";
	?>
    <span><?php echo Text::_("JVERSION") . '&nbsp;' . $vars->version['current']; ?>&nbsp;</span>
    <span class="<?php echo $vars->class; ?>" <?php echo $lb_tooltip ?> >
    <?php echo $vars->text; ?>&nbsp;
        <span id="new-<?php echo $vars->container_id; ?>">
            <?php echo $vars->version['new']; ?>
        </span>
    </span>
	<?php
if (!$vars->is_free && $vars->show_auth_btn) {
	if (ECLVersion::getJoomlaVersion() == '3'):
		?>
        <a class="btn btn-mini button btn-info hasTooltip" data-original-title="<?php echo Text::_("PLG_SYSTEM_ECLABS_AUTHORISATION_BTN_TOOLTIP");?>" data-eclmodal="<?php echo $modal_params; ?>">
            <span class="icon-refresh" aria-hidden="true"></span>
        </a>
	<?php else: ?>
        <button type="button" class="btn btn-info btn-refresh" data-eclmodal="<?php echo $modal_params; ?>">
            <span class="icon-refresh" aria-hidden="true"></span>
        </button>
	<?php endif; ?>
	<?php
}
?>

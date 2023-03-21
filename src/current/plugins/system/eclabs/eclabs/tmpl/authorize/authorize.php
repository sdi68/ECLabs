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
use ECLabs\Library\ECLTools;
use ECLabs\Library\ECLVersion;
use Joomla\CMS\Language\Text;

/**
 * @var stdClass $vars
 */
ECLLanguage::loadLibLanguage();

if (!$vars->is_free)
{

	ob_start();
	include 'default' . ECLVersion::getJoomlaVersion() . '.php';
	$html = ob_get_contents();
	ob_end_clean();

	Text::script('JCLOSE');
	Text::script('JAPPLY');
	Text::script('JSUBMIT');
	Text::script('ECLUPDATEINFO_STATUS_SUCCESS_TEXT');
	Text::script('JVERSION');
	$modal_params = array('debug_mode'     => $vars->debug_mode,
	                      'wrapId'         => 'eclModal',
	                      'dialogClass'    => '',
	                      'hideHeader'     => false,
	                      'hideFooter'     => false,
	                      'hiddenClass'    => 'hidden',
	                      'saveBtnCaption' => Text::_('JSUBMIT'),
	                      'content'        => $html,
	                      'title'          => Text::_('PLG_SYSTEM_ECLABS_AUTHORISATION_TITLE'),
	                      'shown'          => 'showAuthorization',
	                      'hidden'         => '');

	$modal_params = ECLTools::encodeParams($modal_params);

	?>
    <span><?php echo Text::_("JVERSION") . '&nbsp;' . $vars->version['current']; ?></span>&nbsp;
    <span class="<?php echo $vars->class; ?>">
    <?php echo $vars->text; ?>&nbsp;
    <span id="new-<?php echo $vars->container_id; ?>">
        <?php echo $vars->version['new']; ?>
    </span>
</span>
	<?php
	if (ECLVersion::getJoomlaVersion() == '3'):
		?>
        <a class="btn btn-mini button btn-info" data-eclmodal="<?php echo $modal_params; ?>">
            <span class="icon-refresh" aria-hidden="true"></span>
        </a>
	<?php else: ?>
        <button type="button" class="btn btn-info btn-refresh" data-eclmodal="<?php echo $modal_params; ?>">
            <span class="icon-refresh" aria-hidden="true"></span>
        </button>
	<?php endif; ?>
	<?php
}

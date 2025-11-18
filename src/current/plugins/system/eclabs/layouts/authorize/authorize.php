<?php
/**
 * @package              Econsult Labs Library
 * @subpackage           Econsult Labs system plugin
 * @version              __DEPLOYMENT_VERSION__
 * @author               ECL <info@econsultlab.ru>
 * @link                 https://econsultlab.ru
 * @copyright            Copyright © 2025 ECL All Rights Reserved
 * @license              http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
\defined('_JEXEC') or die;

use ECLabs\Library\ECLLanguage;
use ECLabs\Library\ECLTools;
use ECLabs\Library\ECLVersion;
use Joomla\CMS\Language\Text;

extract($displayData);
/**
 * @var stdClass $vars
 */
ECLLanguage::loadLibLanguage();

$suff = "";
switch (ECLVersion::getJoomlaVersion())
{
    case "5":
        $suff = "5";
        break;
    default:
}


if (!$vars->is_free)
{
    ob_start();
    include 'default' . $suff . '.php';
    $html = ob_get_contents();
    ob_end_clean();

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
                          'hidden'         => 'hideAuthorization');

    $modal_params = ECLTools::encodeParams($modal_params);
}
$lb_tooltip = $vars->version_tooltip ? 'data-bs-toggle="tooltip" data-bs-placement="top" title="' . $vars->version_tooltip . '"' : "";
?>
    <span><?php echo Text::_("JVERSION") . '&nbsp;' . $vars->version['current']; ?>&nbsp;</span>
    <span class="<?php echo $vars->class; ?>" <?php echo $lb_tooltip ?> >
    <?php echo $vars->text; ?>&nbsp;
        <span id="new-<?php echo $vars->container_id; ?>">
            <?php echo $vars->version['new']; ?>
        </span>
    </span>
<?php
if (!$vars->is_free && $vars->show_auth_btn)
{
    ?>
    <button type="button" class="btn btn-info btn-refresh btn-sm" data-eclmodal="<?php echo $modal_params; ?>">
        <span class="icon-refresh" aria-hidden="true"></span>
    </button>
    <?php
}


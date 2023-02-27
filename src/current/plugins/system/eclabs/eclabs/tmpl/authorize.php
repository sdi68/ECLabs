<?php
/**
 * @package         Econsult Labs Library
 * @subpackage   Econsult Labs system plugin
 * @version           1.0.0
 * @author            ECL <info@econsultlab.ru>
 * @link                 https://econsultlab.ru
 * @copyright      Copyright © 2023 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

use ECLabs\Library\ECLLanguage;
use Joomla\CMS\Language\Text;

/**
 * @var stdClass $vars
 */
ECLLanguage::loadLibLanguage();
?>
<span><?php echo Text::_("JVERSION") . '&nbsp;' . $vars->version['current']; ?></span>&nbsp;
<span class="<?php echo $vars->class; ?>">
        <?php echo $vars->text; ?>&nbsp;
        <span id="new-<?php echo $vars->container_id; ?>">
            <?php echo $vars->version['new']; ?>
        </span>
    </span>
<?php

Text::script('JCLOSE');
Text::script('JAPPLY');
Text::script('JSUBMIT');
Text::script('ECLUPDATEINFO_STATUS_SUCCESS_TEXT');
?>

<?php if ($vars->version['error']): ?>
    <button type="button" class="btn btn-info btn-refresh" data-eclmodal data-shown="showAuthorization"
            data-title="<?php echo Text::_('PLG_SYSTEM_ECLABS_AUTHORISATION_TITLE'); ?>"
            data-content_id="ecl-authorize">
        <span class="icon-refresh" aria-hidden="true"></span>
    </button>
<?php endif; ?>

<div id="ecl-authorize" class="ecl-modal">
    <div class="body-wrap container">
        <div class="row">
            <div class="col-sm-9 ecl-fields">
                <div class="input-group mb-3 flex-nowrap input-group-sm mt-3">
                    <span class="input-group-text" id="basic-addon1">@</span>
                    <input type="text" id="user" class="form-control"
                           placeholder="<?php echo Text::_('JGLOBAL_USERNAME'); ?>"
                           aria-label="<?php echo Text::_('JGLOBAL_USERNAME'); ?>"
                           aria-describedby="basic-addon1" value="<?php echo $vars->user_data['ECL']['user'] ?? ''; ?>">
                </div>
                <div class="input-group mb-3 flex-nowrap input-group-sm">
                    <span class="input-group-text" id="basic-addon2">@</span>
                    <input type="password" id="password" class="form-control"
                           placeholder="<?php echo Text::_('JGLOBAL_PASSWORD'); ?>"
                           aria-label="<?php echo Text::_('JGLOBAL_PASSWORD'); ?>"
                           aria-describedby="basic-addon2"
                           value="<?php echo $vars->user_data['ECL']['password'] ?? ''; ?>">
                </div>
                <div class="input-group mb-3 flex-nowrap input-group-sm">
                    <span class="input-group-text" id="basic-addon3">Токен</span>
                    <input type="text" readonly="readonly" id="token" class="form-control"
                           placeholder=""
                           aria-label=""
                           aria-describedby="basic-addon3"
                           value="<?php echo $vars->user_data['ECL']['token'] ?? ''; ?>">
                </div>
                <input type="hidden" id="element_name" value="<?php echo $vars->element_name; ?>"/>
                <input type="hidden" id="extension_info" value='<?php echo $vars->extension_info; ?>'/>
            </div>
            <div class="ecl-spinner col-sm-3 text-center align-self-center"></div>
        </div>
        <div class="col-sm-12 results_group mb-3 d-none">
            <div class="results-alert"></div>
        </div>
    </div>
</div>
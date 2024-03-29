<?php
/**
 * @package              Econsult Labs Library
 * @subpackage           Econsult Labs system plugin
 * @version              1.0.2
 * @author               ECL <info@econsultlab.ru>
 * @link                 https://econsultlab.ru
 * @copyright            Copyright © 2023 ECL All Rights Reserved
 * @license              http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

/**
 * @var stdClass $vars
 */

use Joomla\CMS\Language\Text;

?>
<div id="ecl-authorize">
    <div class="body-wrap container-fluid">
        <div class="row">
            <div class="span-9 ecl-fields">
                <div class="control-group">
                    <div class="control-label">
                        <label id="user-lbl" for="user" class="hasPopover"
                               title="" data-content="<?php echo Text::_('JGLOBAL_USERNAME'); ?>"
                               data-original-title="<?php echo Text::_('JGLOBAL_USERNAME'); ?>">
							<?php echo Text::_('JGLOBAL_USERNAME'); ?>
                        </label>
                    </div>
                    <div class="controls">
                        <input type="text" id="user" class="form-control"
                               placeholder="<?php echo Text::_('JGLOBAL_USERNAME'); ?>"
                               aria-label="<?php echo Text::_('JGLOBAL_USERNAME'); ?>"
                               aria-describedby="basic-addon1"
                               value="<?php echo $vars->user_data['ECL']['user'] ?? ''; ?>">
                    </div>
                </div>
                <div class="control-group">
                    <div class="control-label">
                        <label id="password-lbl" for="password" class="hasPopover"
                               title="" data-content="<?php echo Text::_('JGLOBAL_PASSWORD'); ?>"
                               data-original-title="<?php echo Text::_('JGLOBAL_PASSWORD'); ?>">
							<?php echo Text::_('JGLOBAL_PASSWORD'); ?>
                        </label>
                    </div>
                    <div class="controls">
                        <input type="password" id="password" class="form-control"
                               placeholder="<?php echo Text::_('JGLOBAL_PASSWORD'); ?>"
                               aria-label="<?php echo Text::_('JGLOBAL_PASSWORD'); ?>"
                               aria-describedby="basic-addon2"
                               value="<?php echo $vars->user_data['ECL']['password'] ?? ''; ?>">
                    </div>
                </div>
                <div class="control-group horizontal">
                    <div class="control-label">
                        <label id="hasToken-lbl" for="has_token" class="hasPopover"
                               title="" data-content="<?php echo Text::_('PLG_SYSTEM_ECLABS_HAS_TOKEN'); ?>"
                               data-original-title="<?php echo Text::_('PLG_SYSTEM_ECLABS_HAS_TOKEN'); ?>">
							<?php echo Text::_('PLG_SYSTEM_ECLABS_HAS_TOKEN'); ?>
                        </label>
                    </div>
                    <div class="controls">
                        <input type="checkbox" id="has_token" class="form-control"
                               placeholder="<?php echo Text::_('PLG_SYSTEM_ECLABS_HAS_TOKEN'); ?>"
                               aria-label="<?php echo Text::_('PLG_SYSTEM_ECLABS_HAS_TOKEN'); ?>"
                               aria-describedby="basic-addon2"
							<?php echo isset($vars->user_data['ECL']['has_token']) && $vars->user_data['ECL']['has_token'] ? 'checked' : ''; ?>
                               value="">
                    </div>
                </div>
                <div class="control-group">
                    <div class="control-label">
                        <label id="token-lbl" for="token" class="hasPopover"
                               title=""
                               data-content="<?php echo Text::_('PLG_SYSTEM_ECLABS_AUTHORISATION_TOKEN'); ?>"
                               data-original-title="<?php echo Text::_('PLG_SYSTEM_ECLABS_AUTHORISATION_TOKEN'); ?>">
							<?php echo Text::_('PLG_SYSTEM_ECLABS_AUTHORISATION_TOKEN'); ?>
                        </label>
                    </div>
                    <div class="controls">
                        <input type="text" readonly="readonly" id="token" class="form-control"
                               placeholder=""
                               aria-label=""
                               aria-describedby="basic-addon3"
                               value="<?php echo $vars->user_data['ECL']['token'] ?? ''; ?>">
                    </div>
                </div>
                <input type="hidden" id="element_name" value="<?php echo $vars->element_name; ?>"/>
                <input type="hidden" id="extension_info" value='<?php echo $vars->extension_info; ?>'/>
                <input type="hidden" id="is_free" value='<?php echo $vars->is_free ? 1 : 0; ?>'/>
            </div>
            <div class="ecl-spinner span-3 text-center align-self-center"></div>
        </div>
        <div class="span-12 results_group d-none">
            <div class="results-alert"></div>
        </div>
    </div>
</div>

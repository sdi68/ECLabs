<?php
/*
 * default_3.php  01.03.2023, 17:23
 * Created for project Joomla 3.x
 * Subpackage ___
 * www.econsultlab.ru
 * mail: info@econsultlab.ru
 * Released under the GNU General Public License
 * Copyright (c) 2023 Econsult Lab.
 */

/**
 * @var stdClass $vars
 */

use Joomla\CMS\Language\Text;

?>
    <span><?php echo Text::_("JVERSION") . '&nbsp;' . $vars->version['current']; ?></span>&nbsp;
    <span class="<?php echo $vars->class; ?>">
        <?php echo $vars->text; ?>&nbsp;
        <span id="new-<?php echo $vars->container_id; ?>">
            <?php echo $vars->version['new']; ?>
        </span>
    </span>
<?php
if (!$vars->is_free)
{
	Text::script('JCLOSE');
	Text::script('JAPPLY');
	Text::script('JSUBMIT');
	Text::script('ECLUPDATEINFO_STATUS_SUCCESS_TEXT');
    Text::script('JVERSION');
	?>

    <a class="btn btn-mini button btn-info" data-eclmodal data-shown="showAuthorization"
       data-title="<?php echo Text::_('PLG_SYSTEM_ECLABS_AUTHORISATION_TITLE'); ?>"
       title="<?php echo Text::_('PLG_SYSTEM_ECLABS_AUTHORISATION_TITLE'); ?>"
       data-content_id="ecl-authorize">
        <span class="icon-refresh" aria-hidden="true"></span>
    </a>

    <div id="ecl-authorize" class="ecl-modal">
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
                    <input type="hidden" id="is_free" value='<?php echo $vars->is_free ? 1:0; ?>'/>
                </div>
                <div class="ecl-spinner span-3 text-center align-self-center"></div>
            </div>
            <div class="span-12 results_group d-none">
                <div class="results-alert"></div>
            </div>
        </div>
    </div>
<?php } ?>
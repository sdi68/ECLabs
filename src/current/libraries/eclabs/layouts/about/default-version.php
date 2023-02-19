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

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;
extract($displayData);

?>
<div id="<?php echo $version['container_id']; ?>" class="ecl-version-container">
	<?php if ($field->free_update): ?>
        <span><?php echo Text::_("JVERSION") . '&nbsp;' . $version['current']; ?></span>&nbsp;
	<?php else: ?>
		<?php echo $version['html'] ?? ''; ?>
        <script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function () {
                const _btn = Joomla.Text._('JSUBMIT');
                let params = {
                    hideHeader: false,
                    saveBtnCaption: _btn
                };
                let eclm = new ECLModal();
                eclm.initialize(params);
            });
        </script>
	<?php endif; ?>
</div>


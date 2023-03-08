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
extract($displayData);

?>
<div id="<?php echo $version['container_id']; ?>" class="ecl-version-container">
	<?php echo $version['html'] ?? ''; ?>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function () {
            const _btn = Joomla.Text._('JSUBMIT');
            let params = {
                hideHeader: false,
                saveBtnCaption: _btn
            };
            let _debug_mode = typeof ecl_enable_log !== "undefined" ? ecl_enable_log : false;
            let eclm = new ECLModal(_debug_mode);
            eclm.initialize(params);
        });
    </script>
</div>


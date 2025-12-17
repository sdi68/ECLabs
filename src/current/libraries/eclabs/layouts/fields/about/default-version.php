<?php
/**
 * @package             Econsult Labs Library
 * @version             2.0.1
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2025 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

\defined('_JEXEC') or die;

extract($displayData);

?>
<div id="<?php echo $version['container_id']; ?>" class="ecl-version-container">
    <?php echo $version['html'] ?? ''; ?>
</div>
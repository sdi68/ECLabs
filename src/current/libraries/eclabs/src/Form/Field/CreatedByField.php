<?php
/**
 * @package             Econsult Labs Library
 * @version             __DEPLOYMENT_VERSION__
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2025 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library\Form\Field;

\defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;

/**
 * The form field implementation
 */
class CreatedByField extends FormField
{
	/**
	 * The form field type
	 *
	 * @var        string
	 * @since    1.6
	 */
	protected $type = 'createdby';

	/**
	 * Method to get the field input markup
	 *
	 * @return    string    The field input markup
	 * @throws \Exception
	 * @since    1.6
	 */
	protected function getInput(): string
	{
		// Get the current user
		$user = Factory::getApplication()->getIdentity();

		// Set this to be sure the user texts are displayed as default
		$userExists = true;

		// If the value is set
		if ($this->value)
		{
			// Look for the user in the DB
			$db    = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true)
				->select('id')
				->from('#__users')
				->where($db->qn('id') . ' = ' . $db->q($this->value));
			$db->setQuery($query);
			$userId = $db->loadResult();

			// If the user exists in the DB
			if ($userId)
			{
				// Get the user from the value in the input box
				//$user = Factory::getApplication()->loadIdentity($this->value);
				$user = new User($userId);
				Factory::getApplication()->loadIdentity($user);
			}
			else
			{
				$userExists = false;

				// Otherwise set the value to the current user
				$this->value = $user->id;
			}
		}
		else
		{
			// Set the value to the current user
			$this->value = $user->id;
		}

		// If the user ID exists in the DB (this user exists)
		if ($userExists)
		{
			$html = $user->name . " (" . $user->username . ")";
		}

		$html .= '<input type="hidden" name="' . $this->name . '" value="' . $this->value . '" />';

		return $html;
	}
}

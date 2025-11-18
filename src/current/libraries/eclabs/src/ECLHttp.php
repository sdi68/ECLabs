<?php
/**
 * @package             Econsult Labs Library
 * @version             __DEPLOYMENT_VERSION__
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2025 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library;

\defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Http\HttpFactory;
use Joomla\Registry\Registry;

/**
 * Отправляет http запросы
 * @package     ECLabs\Library
 *
 * @since       1.0.0
 */
class ECLHttp
{
	/**
	 * Send HTTP request using GET method.
	 *
	 * @param   string  $link     The URL to send request to.
	 * @param   int     $cache    The number of seconds to cache request results.
	 * @param   bool    $force    Whether to send request even when cached results available?
	 * @param   mixed   $cookies  Whether to send current cookies data along with request.
	 * @param   int     $timeout  Read timeout in seconds.
	 *
	 * @return  mixed
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public static function get(string $link, int $cache = 0, bool $force = false, $cookies = false, int $timeout = 30)
	{
		$result = null;

		// Get results from cache file if not expired.
		$cacheFile = Factory::getApplication()->getConfig()->get('tmp_path') . '/eclabs/' . md5($link);

		if (!$force && is_numeric($cache) && $cache > 0)
		{
			if (File::exists($cacheFile) && time() - filemtime($cacheFile) < $cache)
			{
				$result = file_get_contents($cacheFile);
			}
		}

		// Send request if cached results not available.
		if (empty($result))
		{
			// Prepare request headers.
			$headers = array();

			if ($cookies)
			{
				$headers['cookie'] = self::cookies($cookies);
			}

			// Get remote content.
			$coptions = array();

			if ($_SERVER['REMOTE_ADDR'] === '127.0.0.1')
			{
				// Для локальной отладки
				$coptions = array(
					CURLOPT_SSL_VERIFYHOST => false,
					CURLOPT_SSL_VERIFYPEER => false);
			}

			$coptions[CURLOPT_HTTPHEADER] = array('Content-Type: application/json');
			$options['transport.curl']    = $coptions;
			switch (ECLVersion::getJoomlaVersion())
			{
				case 4:
					break;
				case 3:
				default:
					$options = new Registry($options);
			}
			$http = (new HttpFactory)->getHttp($options, ['curl']);

			//TODO SDI не работает установка опций http->setOption('transport.curl', $options);

			try
			{
				$result = $http->get($link, $headers, $timeout);
			}
			catch (Exception $e)
			{
				$result = null;
			}

			if ($result)
			{
				$result = $result->body;

				// Cache results.
				if (is_numeric($cache) && $cache > 0 && Folder::create(dirname($cacheFile)))
				{
					File::write($cacheFile, $result);
				}
			}
			elseif (File::exists($cacheFile))
			{
				$result = file_get_contents($cacheFile);

				// Update last modification time of the cache file.
				touch($cacheFile);
			}
			else
			{
				throw new Exception(Text::sprintf('ECLABS_ERROR_HTTP_REQUEST_FAIL', parse_url($link, PHP_URL_HOST)));
			}
		}

		return json_decode($result, true);
	}

	/**
	 * Helper method to generate content for cookie header.
	 *
	 * @param   array|null  $cookies  Array of cookies to set.
	 *
	 * @return  string
	 * @since 1.0.0
	 */
	public static function cookies(array $cookies = null): string
	{
		// Prepare cookies data.
		if (!$cookies || !is_array($cookies))
		{
			$cookies = $_COOKIE;
		}

		// Generate content for cookie header.
		$_cookies = '';

		foreach ($cookies as $k => $v)
		{
			$_cookies .= urlencode($k) . '=' . urlencode($v) . '; ';
		}

		return substr($cookies, 0, -2);
	}

	/**
	 * Send HTTP request using POST method.
	 *
	 * @param   string        $link     The URL to send request to.
	 * @param   String|array  $data     Data to post array or json.
	 * @param   mixed         $cookies  Whether to send current cookies data along with request.
	 * @param   int           $timeout  Read timeout in seconds.
	 *
	 * @return  mixed
	 * @throws Exception
	 * @since 1.0.0
	 */
	public static function post(string $link, array|string $data = "", $cookies = false, int $timeout = 3): mixed
	{
		// Prepare request headers.
		$headers = array();

		if ($cookies)
		{
			$headers['cookie'] = self::cookies($cookies);
		}

		// Send request.
		$coptions = array();

		if ($_SERVER['REMOTE_ADDR'] === '127.0.0.1')
		{
			// Для локальной отладки
			$coptions = array(
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSL_VERIFYPEER => false);
		}

		$coptions[CURLOPT_HTTPHEADER]     = array('Content-Type: application/json');
		$coptions[CURLOPT_RETURNTRANSFER] = true;
		$coptions[CURLOPT_POST]           = 1;

		//TODO SDI не работает установка опций http->setOption('transport.curl', $options);

		switch (ECLVersion::getJoomlaVersion())
		{
			case '3':
				$options = new Registry();
				$options->set('transport.curl', $coptions);
				$http = (new HttpFactory)->getHttp(null, ['curl']);
				$http->setOption('transport.curl', $coptions);
				break;
			case '4':
			default:
				$options['transport.curl'] = $coptions;
				$http                      = (new HttpFactory)->getHttp($options, ['curl']);
		}

		try
		{
			$result = $http->post($link, $data, $headers, $timeout);
		}
		catch (Exception $e)
		{
			$result = null;
		}
		if (!$result)
		{
			throw new Exception(Text::sprintf('ECLABS_ERROR_HTTP_REQUEST_FAIL', parse_url($link, PHP_URL_HOST)));
		}

		return json_decode($result->body, true);
	}
}
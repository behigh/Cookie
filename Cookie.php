<?php
/**
 * Cookie wrapper class
 *
 * all documentation text is from http://php.net/setcookie
 *
 * @package Cookie
 * @version 1.0.0
 */

class Cookie
{
	/**
	 * Permanent cookie period
	 */
	const PERMANENT = '+1 year';

	/**
	 * @var bool
	 */
	private static $inited = false;

	/**
	 * The domain that the cookie is available to
	 * @var string
	 */
	private static $domain;

	/**
	 * The path on the server in which the cookie will be available on.
	 * @var string
	 */
	private static $path = '/';

	/**
	 * @var bool
	 */
	private static $secure = false;

	/**
	 * Prefix for all cookies
	 * @var string
	 */
	private static $prefix = 'mc_';

/* -------------------------------------cut------------------------------------- */

	private function __construct(){}

	/**
	 * @param array $opts Array of options. Supported keys:<ul>
	 *              <li>'path' - The path on the server in which the cookie will be available on.
	 *                         If set to '/', the cookie will be available within the entire domain.
	 *                         If set to '/foo/', the cookie will only be available within the /foo/ directory and all sub-directories such as /foo/bar/ of domain.
	 *                         Default value is: /<br>
	 *              <li>'domain' - The domain that the cookie is available to.
	 *                           Setting the domain to 'www.example.com' will make the cookie available in the www subdomain and higher subdomains.
	 *                           Cookies available to a lower domain, such as 'example.com' will be available to higher subdomains, such as 'www.example.com'.
	 *                           Older browsers still implementing the deprecated » RFC 2109 may require a leading . to match all subdomains.
	 *                           Default value is '.' + $_SERVER['SERVER_NAME'] [www is omitted]<br>
	 *              <li>'prefix' - Prefix for cookies. See: self::$prefix
	 */
	public static function init(array $opts = array())
	{
		if (self::$inited) return;
		if (isset($opts['path']) AND (string) $opts['path']) self::$path = (string) $opts['path'];
		if (isset($opts['prefix'])) self::$prefix = (string) $opts['prefix'];

		if (isset($opts['domain']) AND (string) $opts['domain']) self::$domain = (string) $opts['domain'];
		else
		{
			self::$domain = $_SERVER['SERVER_NAME'];
			self::$domain = '.' . preg_replace('#^www\.#', '', strtolower(self::$domain));
		}

		self::$secure = (isset($_SERVER['HTTPS']) AND strtolower($_SERVER['HTTPS']) == 'on');
	}

	/**
	 * Get current domain
	 * @return string
	 */
	public static function getDomain()
	{
		return self::$domain;
	}

	/**
	 * Get info about prefix setting
	 * @return string
	 */
	public static function getPrefix()
	{
		return self::$prefix;
	}

	/**
	 * Get info about path setting
	 * @return string
	 */
	public static function getPath()
	{
		return self::$path;
	}

	/**
	 * Get info about secure setting
	 * @return boolean
	 */
	public static function isSecure()
	{
		return self::$secure;
	}

	/**
	 * Get realname for cookie
	 * @param string $name
	 * @return string
	 */
	public function realname($name)
	{
		return self::$prefix . $name;
	}

	/**
	 * Set a cookie
	 * @param string $name The name of the cookie.
	 * @param string $value The value of the cookie. This value is stored on the clients computer; do not store sensitive information.
	 *                      Assuming the name is 'cookiename', this value is retrieved through $_COOKIE['cookiename'] or Cookie::get('cookiename')
	 * @param bool|string|int $expire The time the cookie expires. Possible values:<br>
	 *                                <strong>true</strong> - set cookie permanently<br>
	 *                                <strong>false</strong> - set cookie for current session only<br>
	 *                                <strong>int</strong> - The number of seconds to add to the current time<br>
	 *                                <strong>string</strong> - Relative DateTime Formats (ex: '+3 days', '+1 week' etc.)<br>
	 *                                         See http://es.php.net/manual/en/datetime.formats.relative.php
	 * @param bool $httponly When TRUE the cookie will be made accessible only through the HTTP protocol.
	 *                       This means that the cookie won't be accessible by scripting languages, such as JavaScript.
	 *                       It has been suggested that this setting can effectively help to reduce identity theft through XSS attacks
	 *                       (although it is not supported by all browsers), but that claim is often disputed. Added in PHP 5.2.0. TRUE or FALSE
	 * @return boolean If output exists prior to calling this function, setcookie() will fail and return FALSE.
	 *                 If setcookie() successfully runs, it will return TRUE. This does not indicate whether the user accepted the cookie.
	 */
	public static function set($name, $value, $expire = self::PERMANENT, $httponly = false)
	{
		if (empty($value)) // Unset cookie, hmm...
		{
			$expire = '-1 day';
		}

		$name = self::realname($name);
		$value = (string) $value;

		if ($expire === true) $expire = self::PERMANENT;
		if (is_numeric($expire)) $expire = time() + $expire;
		elseif (is_string($expire) AND $expire) $expire = strtotime($expire);
		else $expire = 0;

		if (time() > $expire)
		{
			$value = '';
			unset($_COOKIE[$name]);
		}
		else $_COOKIE[$name] = $value;

		if (headers_sent())
		{
			return false;
		}

		return setcookie($name, $value, $expire, self::$path, self::$domain, self::$secure, $httponly);
	}

	/**
	 * Clear (unset) a cookie
	 * @param string $name
	 * @return bool
	 */
	public static function clear($name)
	{
		return self::set($name, '', '-1 day');
	}

	/**
	 * Get a cookie
	 * @param string $name
	 * @return mixed
	 */
	public static function get($name)
	{
		$name = self::realname($name);
		return @$_COOKIE[$name];
	}
}

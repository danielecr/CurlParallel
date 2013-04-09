<?php

if(defined('MAINPATH')) {
  define('CERTDIR',MAINPATH.DIRECTORY_SEPARATOR."CERTS");
}

/**
 * Curl
 *
 * Provides an Object-Oriented interface to the PHP cURL
 * functions and clean up some of the curl_setopt() calls.
 *
 * @package CurlParallel
 * @author Daniele Cruciani <daniele@smartango.com>
 * @version 1.0
 * @copyright Copyright (c) 2013, Daniele Cruciani
 * @license http://www.opensource.org/licenses/mit-license.php
 */


/**
 * Curl connection object
 *
 * Provides an Object-Oriented interface to the PHP cURL
 * functions and a clean way to replace curl_setopt().
 *
 * Instead of requiring a setopt() function and the CURLOPT_*
 * constants, which are cumbersome and ugly at best, this object
 * implements curl_setopt() through overloaded getter and setter
 * methods.
 *
 * For example, if you wanted to include the headers in the output,
 * the old way would be
 *
 * <code>
 * curl_setopt($ch, CURLOPT_HEADER, true);
 * </code>
 *
 * But with this object, it's simply
 *
 * <code>
 * $ch->header = true;
 * </code>
 *
 * <b>NB:</b> Since, in my experience, the vast majority
 * of cURL scripts set CURLOPT_RETURNTRANSFER to true, the {@link Curl}
 * class sets it by default. If you do not want CURLOPT_RETURNTRANSFER,
 * you'll need to do this:
 *
 * <code>
 * $c = new Curl;
 * $c->returntransfer = false;
 * </code>
 *
 * @package CurlParallel
 * @author Daniele Cruciani <daniele@smartango.com>
 * @version 1.0
 * @copyright Copyright (c) 2013, Daniele Cruciani
 * @license http://www.opensource.org/licenses/mit-license.php
 */
//use Zend\Translator\Adapter\ArrayAdapter;

require_once "httpresponse.class.php";

class Curl
{
	/**
	 * Store the curl_init() resource.
	 * @var resource
	 */
	protected $ch = NULL;

	/**
	 * Store the CURLOPT_* values.
	 *
	 * Do not access directly. Access is through {@link __get()}
	 * and {@link __set()} magic methods.
	 *
	 * @var array
	 */
	protected $curlopt = array();

	/**
	 * Store some CURLOPT_* values.
	 * init in constructor to a usefull default
	 * 
	 * @var array
	 * @author Daniele Cruciani <daniele@smartango.com>
	 */
	protected $mydefaultopt = array();
	
	
	/**
	 * Flag the Curl object as linked to a {@link CurlParallel}
	 * object.
	 *
	 * @var bool
	 */
	protected $multi = false;

	/**
	 * Store the response. Used with {@link fetch()} and
	 * {@link fetch_json()}.
	 *
	 * @var string
	 */
	protected $response;

	/**
	 * callback function array
	 * @var callback
	 */
	protected $callbackfun = array();

	/**
	 * callback arguments
	 * @var array
	 */
	protected $callbackargs = array();
	
	/**
	 * The version of the OOCurl library.
	 * @var string
	 */
	const VERSION = '0.3';
	
	/**
	 * the url to connect to or connected to
	 * @var string
	 */
	private $url = '';
	
	/**
	 * Create the new {@link Curl} object, with the
	 * optional URL parameter.
	 *
	 * @param string $url The URL to open (optional)
	 * @return Curl A new Curl object.
	 * @throws ErrorException
	 */
	public function __construct ( $url = NULL )
	{
		// Make sure the cURL extension is loaded
		if ( !extension_loaded('curl') )
			throw new ErrorException("cURL library is not loaded. Please recompile PHP with the cURL library.");


		// Set some default options
		$this->url = $url;
		if($this->url != NULL) {
		  // Create the cURL resource
		  $this->ch = curl_init($this->url);
		}
		
		$this->returntransfer = true;

		//curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
		//curl_setopt($this->ch, CURLOPT_CAPATH, dirname(__DIR__)."/CERTS");
		if(defined('CERTDIR')) {
		  //$this->capath = CERTDIR;
		}
		// Applications can override this User Agent value
		$this->useragent = 'OOCurl '.self::VERSION;

		
		// Return $this for chaining
		return $this;
	}

	/**
	 * When destroying the object, be sure to free resources.
	 */
	public function __destruct()
	{
		$this->close();
	}

	/**
	 * If the session was closed with {@link Curl::close()}, it can be reopened.
	 *
	 * This does not re-execute {@link Curl::__construct()}, but will reset all
	 * the values in {@link $curlopt}.
	 *
	 * @param string $url The URL to open (optional)
	 * @return bool|Curl
	 */
	public function init ( $url = NULL )
	{
		// If it's still init'ed, return false.
		if ( $this->ch ) return false;

		// init a new cURL session
		$this->ch = curl_init();

		//curl_setopt($this->ch, CURLOPT_CAPATH, dirname(__DIR__)."/CERTS");
		// reset all the values that were already set
		foreach ( $this->curlopt as $const => $value ) {
			curl_setopt($this->ch, constant($const), $value);
		}

		// finally if there's a new URL, set that
		if ( !empty($url) ) $this->url = $url;

		// return $this for chaining
		return $this;
	}
	
	/**
	 * Init mydefaultopt array with values
	 * 
	 */
	protected function initDefault() {
		if(defined('CERTDIR')) {
			//$this->mydefaultopt['CURLOPT_CAPATH'] = CERTDIR;
		}
		//$this->mydefaultopt['CURLOPT_CAPATH'] = dirname(__FILE__)."/CERTS2";
	}
	
	/**
	 * replace mydefaultopt array values by array_merge
	 * (use @link __unset() to unset one value)
	 * @param array $array (keys must contain CURLOPT_ part)
	 */
	public function replaceDefaults($array = array()) {
		array_merge($this->mydefaultopt,$array);
	}

	/**
	 * Replace single CURLOPT_$k values with $v
	 * @param string $k
	 * @param string $v
	 */
	public function replaceDefault($k,$v) {
		$this->mydefaultopt['CURLOPT_'.$k] = $v;
	}
	
	/**
	 * setMyDefault array values that was not setted in curlopt array
	 * @return void
	 */
	private function _setMyDefault() {
		foreach($this->mydefaultopt as $k => $v) {
			if(!isset($this->curlopt[$k]))
				curl_setopt($this->ch,constant($k),$v);
		}
	}
	
	/**
	 * Execute the cURL transfer.
	 *
	 * @return mixed
	 */
	public function exec ()
	{
		$this->_setMyDefault();
		$this->response = curl_exec($this->ch);
		return $this->response;
	}

	/**
	 * If the Curl object was added to a {@link CurlParallel}
	 * object, then you can use this function to get the
	 * returned data (whatever that is). Otherwise it's similar
	 * to {@link exec()} except it saves the output, instead of
	 * running the request repeatedly.
	 *
	 * @see $multi
	 * @return mixed
	 */
	public function fetch ()
	{
		$this->_setMyDefault();
		if ( $this->multi ) {
			if ( !$this->response ) {
				$this->response = curl_multi_getcontent($this->ch);
			}
		} else {
			if ( !$this->response ) {
				$this->response = curl_exec($this->ch);
			}
		}
		return $this->response;
	}
	
	/** 
	 * fetchObj return object StdClass with 2 properties: headers and content
	 * @return stdClass
	 */
  public function fetchObj() {
    $obj = new stdClass();
    $data = $this->fetch();
    if(isset($this->header)) {
      $obj = new HttpResponse($data,1);
    } else {
      $obj = new HttpResponse($data,0);
    }
    return $obj;
  }
	
	/**
	 * setHeaderArray
	 * @param array $headers
	 */
	public function setHeaderArray($headers = array()) {
		if(is_array($headers)) {
			foreach($headers as $k => $v) {
				$curl_header_array[] = "$k: $v";
			}
			if(isset($curl_header_array)) {
				$this->httpheader = $curl_header_array;
			}
		}
	}

	// @todo add method to get response object in form of $obj->header_array, $obj->response, $obj->encoded

	/**
	 * Fetch a JSON encoded value and return a JSON
	 * object. Requires the PHP JSON functions. Pass TRUE
	 * to return an associative array instead of an object.
	 *
	 * @param bool array optional. Return an array instead of an object.
	 * @return mixed an array or object (possibly null).
	 */
	public function fetch_json ( $array = false )
	{
		return json_decode($this->fetch(), $array);
	}

	/**
	 * return the url used in the last communication (or near to be used)
	 * @return string the url
	 */
	public function getUrl() {
	  return $this->url;
	}

	/**
	 * Close the cURL session and free the resource.
	 */
	public function close ()
	{
		curl_close($this->ch);
	}

	/**
	 * Return an error string from the last execute (if any).
	 *
	 * @return string
	 */
	public function error()
	{
		return curl_error($this->ch);
	}

	/**
	 * Return the error number from the last execute (if any).
	 *
	 * @return integer
	 */
	public function errno()
	{
		return curl_errno($this->ch);
	}

	public function error_is_ssl() {
		$SSL_ERRORS = array(CURLE_SSL_PEER_CERTIFICATE,CURLE_SSL_CONNECT_ERROR,
										CURLE_SSL_ENGINE_NOTFOUND,
										CURLE_SSL_ENGINE_SETFAILED, CURLE_SSL_CERTPROBLEM,
										CURLE_SSL_CIPHER, CURLE_SSL_CACERT,);
		$errno = $this->errno();
		if(in_array($errno, $SSL_ERRORS)) {
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Get cURL version information (and adds OOCurl version info)
	 *
	 * @return array
	 */
	public function version ()
	{
		$version = curl_version();

		$version['oocurl_version'] = self::VERSION;
		$version['oocurlparallel_version'] = CurlParallel::VERSION;

		return $version;
	}

	/**
	 * Get information about this transfer.
	 *
	 * Accepts any of the following as a parameter:
	 *  - Nothing, and returns an array of all info values
	 *  - A CURLINFO_* constant, and returns a string
	 *  - A string of the second half of a CURLINFO_* constant,
	 *     for example, the string 'effective_url' is equivalent
	 *     to the CURLINFO_EFFECTIVE_URL constant. Not case
	 *     sensitive.
	 *
	 * @param mixed $opt A string or constant (optional).
	 * @return mixed An array or string.
	 */
	public function info ( $opt = false )
	{
		if (false === $opt) {
			return curl_getinfo($this->ch);
		}

		if ( is_int($opt) || ctype_digit($opt) ) {
			return curl_getinfo($this->ch,$opt);
		}

		if (constant('CURLINFO_'.strtoupper($opt))) {
			return curl_getinfo($this->ch,constant('CURLINFO_'.strtoupper($opt)));
		}
	}

	/**
	 * Magic property setter.
	 *
	 * A sneaky way to access curl_setopt(). If the
	 * constant CURLOPT_$opt exists, then we try to set
	 * the option using curl_setopt() and return its
	 * success. If it doesn't exist, just return false.
	 *
	 * Also stores the variable in {@link $curlopt} so
	 * its value can be retrieved with {@link __get()}.
	 *
	 * @param string $opt The second half of the CURLOPT_* constant, not case sensitive
	 * @param mixed $value
	 * @return void
	 */
	public function __set ( $opt, $value )
	{
		$const = 'CURLOPT_'.strtoupper($opt);
		if ( defined($const) ) {
			if (curl_setopt($this->ch,
											constant($const),
											$value)) {
				$this->curlopt[$const] = $value;
			}
		}
		// if set postfields, then set post mode
		if($const == 'CURLOPT_POSTFIELDS') $this->post = TRUE;
	}

	/**
	 * Magic property getter.
	 *
	 * When options are set with {@link __set()}, they
	 * are also stored in {@link $curlopt} so that we
	 * can always find out what the options are now.
	 *
	 * The default cURL functions lack this ability.
	 *
	 * @param string $opt The second half of the CURLOPT_* constant, not case sensitive
	 * @return mixed The set value of CURLOPT_<var>$opt</var>, or NULL if it hasn't been set (ie: is still default).
	 */
	public function __get ( $opt )
	{
		return $this->curlopt['CURLOPT_'.strtoupper($opt)];
	}

	/**
	 * Magic property isset()
	 *
	 * Can tell if a CURLOPT_* value was set by using
	 * <code>
	 * isset($curl->*)
	 * </code>
	 *
	 * The default cURL functions lack this ability.
	 *
	 * @param string $opt The second half of the CURLOPT_* constant, not case sensitive
	 * @return bool
	 */
	public function __isset ( $opt )
	{
		return isset($this->curlopt['CURLOPT_'.strtoupper($opt)]);
	}

	/**
	 * Magic property unset()
	 *
	 * Unfortunately, there is no way, short of writing an
	 * extremely long, but mostly NULL-filled array, to
	 * implement a decent version of
	 * <code>
	 * unset($curl->option);
	 * </code>
	 *
	 * @todo Consider implementing an array of all the CURLOPT_*
	 *       constants and their default values.
	 * @param string $opt The second half of the CURLOPT_* constant, not case sensitive
	 * @return void
	 */
	public function __unset ( $opt )
	{
		// Since we really can't reset a CURLOPT_* to its
		// default value without knowing the default value,
		// just do nothing.
		// Ok, but at least unset this internal
		unset($this->curlopt['CURLOPT_'.$opt]);
		unset($this->mydefaultopt['CURLOPT_'.$opt]);
	}

	/**
	 * Grants access to {@link Curl::$ch $ch} to a {@link CurlParallel} object.
	 *
	 * @param CurlParallel $mh The CurlParallel object that needs {@link Curl::$ch $ch}.
	 */
	public function grant ( CurlParallel $mh )
	{
		$mh->accept($this->ch,$this);
		$this->multi = true;
	}

	/**
	 * Removes access to {@link Curl::$ch $ch} from a {@link CurlParallel} object.
	 *
	 * @param CurlParallel $mh The CurlParallel object that no longer needs {@link Curl::$ch $ch}.
	 */
	public function revoke ( CurlParallel $mh )
	{
		$mh->release($this->ch,$this);
		$this->multi = false;
	}
	
	/**
	 * execute callback - called from curl parallel
	 * Daniele Cruciani
	 */
	public function callback() {
	  if(count($this->callbackfun)) {
	    if(count($this->callbackargs)) {
	      call_user_func_array($this->callbackfun,$this->callbackargs);
	    } else {
	      call_user_func($this->callbackfun);
	    }
	  }
	}

	/**
	 * call back setter
	 * callback will be called with parameters setted by @setCallbackArgs
	 * @param unknown_type $v
	 */
	public function setCallbackFun($v = NULL) {
	  if($v!=NULL) $this->callbackfun = $v;
	}
	
	/**
	 * set parameter for callback
	 * @param unknown_type $args
	 */
	public function setCallbackArgs($args=NULL) {
	  if(is_array($args)) $this->callbackargs = $args;
	  else if($args != NULL) $this->callbackargs = array($args);
	}
	
}


/* Copyright (c) 2008 James Socol

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

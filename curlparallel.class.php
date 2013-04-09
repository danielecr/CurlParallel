<?php

/**
 * CurlParallel
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

/**
 * Implements parallel-processing for cURL requests.
 *
 * The PHP cURL library allows two or more requests to run in
 * parallel (at the same time). If you have multiple requests
 * that may have high latency but can then be processed quickly
 * in series (one after the other), then running them at the
 * same time may save time, overall.
 *
 * You must create individual {@link Curl} objects first, add them to
 * the CurlParallel object, execute the CurlParallel object,
 * then get the data from the individual {@link Curl} objects. (Yes,
 * it's annoying, but it's limited by the PHP cURL library.)
 *
 * For example:
 *
 * <code>
 * $a = new Curl("http://www.yahoo.com/");
 * $b = new Curl("http://www.microsoft.com/");
 *
 * $m = new CurlParallel($a, $b);
 *
 * $m->exec(); // Now we play the waiting game.
 *
 * printf("Yahoo is %n characters.\n", strlen($a->fetch()));
 * printf("Microsoft is %n characters.\n", strlen($a->fetch()));
 * </code>
 *
 * You can add any number of {@link Curl} objects to the
 * CurlParallel object's constructor (including 0), or you
 * can add with the {@link add()} method:
 *
 * <code>
 * $m = new CurlParallel;
 *
 * $a = new Curl("http://www.yahoo.com/");
 * $b = new Curl("http://www.microsoft.com/");
 *
 * $m->add($a);
 * $m->add($b);
 *
 * $m->exec(); // Now we play the waiting game.
 *
 * printf("Yahoo is %n characters.\n", strlen($a->fetch()));
 * printf("Microsoft is %n characters.\n", strlen($a->fetch()));
 * </code>
 *
 * @package CurlParallel
 * @author Daniele Cruciani <daniele@smartango.com>
 * @version 1.0
 * @since 1.0
 * @copyright Copyright (c) 2013, Daniele Cruciani
 * @license http://www.opensource.org/licenses/mit-license.php
 */
class CurlParallel
{
  /**
	 * Store the cURL master resource.
	 * @var resource
	 */
	protected $mh;

	/**
	 * Store the resource handles that were
	 * added to the session.
	 * @var array
	 */
	protected $ch = array();

	protected $curlObjs = array();
	
	/**
	 * Store the version number of this class.
	 */
	const VERSION = '0.3.0';

	/**
	 * Initialize the multisession handler.
	 *
	 * @uses add()
	 * @param Curl $curl,... {@link Curl} objects to add to the Parallelizer.
	 * @return CurlParallel
	 */
	public function __construct ()
	{
		$this->mh = curl_multi_init();

		foreach ( func_get_args() as $ch ) {
			$this->add($ch);
		}

		return $this;
	}

	/**
	 * On destruction, frees resources.
	 */
	public function __destruct ()
	{
		$this->close();
	}

	/**
	 * Close the current session and free resources.
	 */
	public function close ()
	{
		foreach ( $this->ch as $ch ) {
			curl_multi_remove_handle($this->mh, $ch);
		}
		curl_multi_close($this->mh);
	}

	/**
	 * Add a {@link Curl} object to the Parallelizer.
	 *
	 * Will throw a catchable fatal error if passed a non-Curl object.
	 *
	 * @uses Curl::grant()
	 * @uses CurlParallel::accept()
	 * @param Curl $ch Curl object.
	 */
	public function add ( Curl $ch )
	{
		// get the protected resource
		$ch->grant($this);
	}

	/**
	 * Remove a {@link Curl} object from the Parallelizer.
	 *
	 * @param Curl $ch Curl object.
	 * @uses Curl::revoke()
	 * @uses CurlParallel::release()
	 */
	public function remove ( Curl $ch )
	{
		$ch->revoke($this);
	}

	/**
	 * Execute the parallel cURL requests.
	 */
	public function exec ()
	{
	  
		do {
			curl_multi_exec($this->mh, $running);
		} while ($running > 0);
		/*
		do {
			$mrc = curl_multi_exec($this->mh, $running);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		*/
	}

	/**
	 * Accept a resource handle from a {@link Curl} object and
	 * add it to the master.
	 *
	 * @param resource $ch A resource returned by curl_init().
	 */
	public function accept ( $ch, $curlObj = NULL )
	{
		$this->ch[] = $ch;
		$this->curlObjs[] = $curlObj;
		curl_multi_add_handle($this->mh, $ch);
	}

	/**
	 * Accept a resource handle from a {@link Curl} object and
	 * remove it from the master.
	 *
	 * @param resource $ch A resource returned by curl_init().
	 */
	public function release ( $ch, $curlObj = NULL )
	{
	  if ( false !== $key = array_search($this->ch, $ch) ) {
			unset($this->ch[$key]);
			unset($this->curlObjs[$key]);
			curl_multi_remove_handle($this->mh, $ch);
		}
	}
	
	/**
	 * Run and callback to Curl Object caller
	 * 
	 */
	public function RunAll() {
	  do {
	    $status = curl_multi_exec($this->mh, $active);
	    $info = curl_multi_info_read($this->mh);
	    if (false !== $info) {
	      //var_dump($info);
	      $handler = $info['handle'];
	      if ( false !== $key = array_search($handler,$this->ch) ) {
	        if($this->curlObjs[$key] instanceof Curl) {
	          $this->curlObjs[$key]->callback();
	        }
	      }
	    }
	  } while ($status === CURLM_CALL_MULTI_PERFORM || $active);
	}
}

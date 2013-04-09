<?php

/**
 * Sender and iSenderConsumer
 *
 * This interface your object oriented code with Curl and CurlParallel classes
 * Define a iSenderConsumer interface 
 *
 * @package CurlParallel
 * @author Daniele Cruciani <daniele@smartango.com>
 * @version 0.5
 * @copyright Copyright (c) 2013, Daniele Cruciani
 * @license http://www.opensource.org/licenses/mit-license.php
 */


class Sender {
  /**
   * Curl Parallel object
   * @var CurlParallel
   */
  private $cm;

  function __construct() {
    $this->cm = new CurlParallel();
    set_exception_handler(array('Sender', 'ExceptionHandler'));
  }

  /**
   * log execution
   * @param string $string
   * @param integer $type (0: notice, 1: warning, 2: error) or whatever you want
   */
  private function log($string,$type=0) {
    $datetime_string = strftime('c'); 
    // dump on screen (where? console maybe)
    print $datetime_string. " - " .$string . "\n";
  }
  
  /**
   * 
   * @param string $url
   * @param iSenderConsumer $caller
   */
  public function addRecipient($url, iSenderConsumer $caller) {

    $curlo = new Curl($url);
    $curlo->header = 1;
    $caller->setCurlParams($curlo);

    $curlo->setCallbackFun(array($this,'executedCurl'));
    $curlo->setCallbackArgs(array($curlo,$caller));

    $this->cm->add($curlo);
    //$curlres = $curlo->fetch();

    //ParseNotification::markAsSent($rids);
  }

  public function execute() {
    $this->cm->RunAll();
  }

  /**
   * consume the response
   * @param Curl $curlo
   * @param SenderConsumer $caller
   */
  public function executedCurl(Curl $curlo, iSenderConsumer $caller) {
    $response = $curlo->fetchObj();
    $caller->consumeCurlResponse($response);
  }

  public static function ExceptionHandler(Exception $e) {
    if(get_class($e)=='ESApiException') {
      if($e->object!= NULL && is_numeric($e->object)) {
        print "error";
      }
    }
    //ErrorLogger::log($e);
    $output = Output::getInstance();
    if($output) {
      $output->setError($e);
      //print "ERROR\n";
      $output->output();
    } else {
      if(get_class($e)!='ErrorException') {
        self::printError($e);
      }
    }
  }
}

interface iSenderConsumer {
   public function setCurlParams(Curl $curlo);
   public function consumeCurlResonse(stdClass $object);
}

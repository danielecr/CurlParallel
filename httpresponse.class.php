<?php

/**
 * Store the response and give some info about it
 * @author Daniele Cruciani <daniele@smartango.com>
 *
 */
class HttpResponse {
  
  const RESPONSE_SIGNATURE = '/^HTTP\/([0-9]+\.[0-9]+)\s+([0-9]+).*/';
  
  public $content = '';
  
  public $raw_headers = '';
  
  public $header_first_row = '';
  
  public $headers = array();
  
  private $responseCode = 0;
  
  private $has_header = 0;
  
  /**
   * store the response data after processing
   * @param string $data
   * @param int $has_header 0 has no header, 1 it has, 2 guess
   */
  public function __construct($data,$has_header=2) {
    if($has_header == 2) {
      $pieces = preg_split('/\r\n\r\n/',$data);
      if(preg_match(self::RESPONSE_SIGNATURE,$pieces[0])) {
        $has_header = 1;
      }
    }
    if($has_header == 1) {
      $this->has_header = 1;
      $pieces = preg_split('/\r\n\r\n/',$data);
      //drupal_set_message(__FILE__.':'.__LINE__.' '.print_r($pieces,TRUE));
      $inHeader = TRUE;
      $this->content = '';
      foreach($pieces as $piece) {
        if($inHeader) {
          if($this->raw_headers == '') {
            $this->raw_headers = $piece;
          } else {
            $this->raw_headers .= "\r\n" .$piece;
          }
        } else {
          if($this->content == '') {
            $this->content = $piece;
          } else {
            $this->content .= "\r\n" .$piece;
          }
        }
        // notice (1xx) and redirect (3xx)
        if(!preg_match('/^HTTP[^\n]*[13][0-9]{2}/',$piece)) $inHeader = FALSE;
      }
      //$obj->row_headers = $pieces[0];
      //array_shift($pieces);
      //$obj->content = implode("\r\n",$pieces);
      $headers = preg_split("/\r\n/",$this->raw_headers);
      $this->header_first_row = $headers[0];
      $this->headers = array();
      foreach($headers as $i => $line) {
        if(preg_match('/^([^:]*):\ (.*)$/',$line,$matches)) {
          $this->headers[$matches[1]] = $matches[2];
        }
      }
    }
    if($has_header == 0) {
      $this->content = $data;
    }
  }
  
  /**
   * return the http response code
   * @return int http response code from header
   */
  public function getResponseCode() {
    if($this->responseCode>0) return $this->responseCode;
    if($this->header_first_row != '') {
      if(preg_match(self::RESPONSE_SIGNATURE, $this->header_first_row, $matches)) {
        $this->responseCode = $matches[2];
        return $this->responseCode;
      }
    }
    if($this->has_header) return 0;
    return 200;
    //if(!$this->has_header && $this->content)
  }
  
  
  
}
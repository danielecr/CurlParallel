<?php

require_once "sender.class.php";

class TestCurl implements iSenderConsumer {
  private $url_list = array();
  
  public function __construct(Sender $sender) {
    // read urls from a file, one by one
    $this->readUrls();
    $this->sender = $sender;
    foreach ($this->url_list as $url) {
      if($url == '') continue;
      print "$url enqueued\n";
      $curlo = $this->sender->addRecipient($url, $this);
      //print_r($curlo);
      // set parameters option for $curlo ... but even not
      //unset($curlo);
    }
  }
  
  public function readUrls() {
    $c = file_get_contents('urllist.url');
    //print $c;
    $this->url_list = explode("\n", $c);
  }
   
  public function consumeCurlResponse(HttpResponse $object,Curl $curlo = NULL) {
     // I just want to know if all goes right
     print date('c') . " - " .$object->header_first_row. ' - ' .$object->getResponseCode() . " with a content of length: " . strlen($object->content) .
       " requested url: ". $curlo->getUrl() ."\n";
     if($object->getResponseCode() != 200) {
       print $object->content;
       print $object->raw_headers;
     }
  }

}

$sender = new Sender();
$tc = new TestCurl($sender);
$sender->execute();
sleep(10);

//$co = new Curl('http://www.cellularmagazine.it');
//$o = $co->fetch();
//print_r($o);
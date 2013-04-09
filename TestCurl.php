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
      print "$url\n";
      $curlo = $this->sender->addRecipient($url, $this);
      //print_r($curlo);
      // set parameters option for $curlo ... but even not
      //unset($curlo);
    }
    $this->sender->execute();
  }
  
  public function readUrls() {
    $c = file_get_contents('urllist.url');
    //print $c;
    $this->url_list = explode("\n", $c);
  }
   
  public function consumeCurlResponse(stdClass $object,Curl $curlo = NULL) {
     // I just want to know if all goes right
     print date('c') . " - ".$object->header_first_row . " with a content of length: " . strlen($object->content) .
       "requested url: ". $curlo->getUrl() ."\n";
  }

}

//$sender = new Sender();
//$tc = new TestCurl($sender);
//sleep(30);

$co = new Curl('http://www.cellularmagazine.it');
$o = $co->fetch();
print_r($o);
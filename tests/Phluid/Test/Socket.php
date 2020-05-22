<?php
namespace Phluid\Test;
use Evenement\EventEmitter;

class Socket extends EventEmitter {
  
  public function listen( $port, $host = '127.0.0.1' ) {
    
  }
  
  public function getPort() {
    return 80;
  }
  
  public function shutdown() {
    
  }
  
}


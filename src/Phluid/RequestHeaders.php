<?php
namespace Phluid;
use Psr\Http\Message\ServerRequestInterface;

class RequestHeaders extends Headers {
  
  public static function fromHttpRequest( ServerRequestInterface $request ){
     return new RequestHeaders(
       $request->getMethod(),
       $request->getRequestTarget(),
       $request->getQueryParams(),
       $request->getProtocolVersion(),
       $request->getHeaders()
     );
  }
  
  public $method;
  public $path;
  public $version;
  public $query;
  
  function __construct( $method, $path, $query, $version = '1.1', $headers = array() ){
    
    $this->method = $method;
    $this->path = $path;
    $this->version = $version;
    $this->query = $query;
    
    parent::__construct( $headers );
        
  }
  
  public function __toString(){
    $method = str_pad( $this->method, strlen( $this->method ) - 4 );
    return $method . ' ' . $this->getUri();
  }
  
  public function getUri(){
    return $this->path . $this->getQuerystring();
  }
  
  public function getQuerystring( $prefix = '?' ){
    $query = http_build_query( $this->query );
    if ( $query != "" && $prefix ) {
      $query = $prefix . $query;
    }
    return $query;
  }
  
  
}

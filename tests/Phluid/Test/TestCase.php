<?php
namespace Phluid\Test;
use Phluid\App;
use React\Http\Io\EmptyBodyStream;
use React\Http\Io\HttpBodyStream;
use React\Http\Io\ServerRequest as HttpRequest;
use Phluid\Request;
use Phluid\Response;

class TestCase extends \PHPUnit\Framework\TestCase {
  
  function setUp(): void {
    
    $this->connection = new Connection();
    $this->app = new App();
    $this->app->get( '/', function( $request, $response, $next ){
      $response->renderText('Hello World');
    } );
    $this->http = new Server();
    $this->app->createServer( "127.0.0.1:4000", $this->http );
  }
  
  public function doRequest( $method = 'GET', $path = '/', $query = array(), $headers = array(), $action = false ){
    
    $request = $this->makeRequest( $method, $path, $query, $headers );
    $request->method = $method;
    $request->path = $path;
    $this->response = $response = new MockResponse( $request );
    $this->app->__invoke( $request, $response );
    if ( !$action ){
     $request->close(); 
    } else {
      $action( $request, $response );
    }
    return $response;
  }
  
  public function makeRequest( $method = 'GET', $path = '/', $query = array(), $headers = array() ){
    $request = new HttpRequest( $method, $path, $headers, "", "1.1", $query);
    $request = $request->withBody(new HttpBodyStream(new EmptyBodyStream(), 0));
    $this->request = new Request( $request );
    return $this->request;
  }
  
  public function send( $body = null ){
    if ( $body != null ) {
      while( strlen( $body ) > 0 ){
        $part = substr( $body, 0, 1024 );
        $body = substr( $body, 1024 );
        $this->request->emit( 'data', array( $part ) );
      }
    }
    $this->request->close();
    
  }
  
  public function sendFile( $file ){
    $handle = fopen( $file, 'r' );
    while( $string = fread( $handle, 1024 ) ){
      $this->request->emit( 'data', array( $string ) );
    }
    fclose( $handle );
    $this->request->close();
  }
  
  public function getBody(){
    return $this->response->data;
  }
  
  public function fileFixture( $file ){
    return realpath('.') . '/tests/files/' . $file;
  }
  
}

class MockResponse extends Response {
  
  public $data = "";
  private $capture = false;
  
  public function sendHeaders( $status_or_headers = 200, $headers = array() ){
    parent::sendHeaders( $status_or_headers, $headers );
    $this->capture = true;
  }
  
  public function write( $data ){
    parent::write( $data );
    if ( $this->capture ) $this->data .= $data;
  }
  
  public function end( $data = null ){
    parent::end( $data );
    if ( $this->capture ) $this->data .= $data;
  }
  
}
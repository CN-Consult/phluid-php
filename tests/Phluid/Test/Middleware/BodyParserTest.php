<?php

namespace Phluid\Test\Middleware;

use Phluid\Middleware\JsonBodyParser;
use Phluid\Middleware\MultipartBodyParser;
use Phluid\Middleware\FormBodyParser;

class BodyParserTest extends \Phluid\Test\TestCase {
  
  public function testJsonParsing(){
    
    $thing = new \stdClass();
    $thing->awesome = "YES";
    
    $parser = new JsonBodyParser( false );
    $this->app->inject( $parser );
    $that = $this;

    $body = json_encode( $thing );
    $response = $this->doRequest( 'POST', '/', array(), array(
      'Content-Type' => 'application/json',
      'Content-Length' => strlen( $body )
    ), function( $request ) use ( $body, $that){
      $that->send( $body );
    } );
        
    $this->assertSame( $thing->awesome, $this->request->body->awesome );
    
  }
  
  public function testFormParsing(){

    $parser = new FormBodyParser();
    $values = array( 'field' => 'value' );
    $body = http_build_query( $values );
    $this->app->inject( $parser );
    $that = $this;

    $this->doRequest( 'POST', '/', array(), array(
      'Content-Type' => 'application/x-www-form-urlencoded',
      'Content-Length' => strlen( $body )
    ), function() use ( $body, $that ){
      $that->send( $body );
    } );

    $this->assertSame( $values, $this->request->body );

  }
  
  public function testMultipartParsing(){
    
    $parser = new MultipartBodyParser( realpath( '.' ) . '/tests/uploads' );
    $this->app->inject( $parser );
    $test = $this;

    $this->app->inject( function( $request, $response, $next ) use ( $test ){

      $test->assertArrayHasKey( 'name', $request->body );
      $test->assertArrayHasKey( 'file', $request->body );

      $test->assertFileExists( (string) $request->body['file'] );
      $next();
      
    } );
    
    $response = $this->doRequest( 'POST', '/', array(), array(
      'Content-Type' => 'multipart/form-data; boundary=----WebKitFormBoundaryoOeNyQKwEVuvehNw'
    ), function() use ( $test ){
      $test->sendFile( realpath('.') . '/tests/files/multipart-body' );
    });
  }
  
  public function testMultipartAssocParsing(){
    
    $parser = new MultipartBodyParser( realpath( '.' ) . '/tests/uploads' );
    $this->app->inject( $parser );
      $test = $this;

    $this->app->inject( function( $request, $response, $next ) use ( $test ){

      $body = $request->body;
      $test->assertArrayHasKey( 'first', $body['name'] );
      $test->assertArrayHasKey( 'last', $body['name'] );

      $test->assertSame( "Sammy", (string) $body['name']['first'] );
      $test->assertSame( 'Collins', (string) $body['name']['last'] );

      $test->assertArrayHasKey( 0, $body['file']['for'] );
      $test->assertArrayHasKey( 1, $body['file']['for'] );

      $test->assertFileExists( (string) $body['file']['for'][0] );
      $test->assertFileExists( (string) $body['file']['for'][1] );
      $next();
    });
    
    $response = $this->doRequest( 'POST', '/', array(), array(
      'Content-Type' => 'multipart/form-data; boundary=----WebKitFormBoundaryAYD2hRdSJxpcdK2a'
    ), function() use ( $test ) {
      $test->sendFile( realpath('.') . '/tests/files/multipart-assoc' );
    } );
    
  }
  
  public function testMultipartSkipsParsing(){
    $parser = new MultipartBodyParser( realpath( '.' ) . '/tests/uploads' );
    $this->app->inject( $parser );
    $test = $this;

    $response = $this->doRequest( 'POST', '/', array(), array( 'Content-Type' => 'text/plain'), function() use ( $test ) {
      $test->send( "Hello" );
    } );
    $this->assertObjectNotHasAttribute( 'body', $this->request );
  }
  
  function setUp(){
    parent::setUp();
    $this->app->post( '/', function( $request, $response ){
      $response->renderText( "done" );
    } );
  }
}
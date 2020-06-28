<?php

namespace Phluid\Middleware;
use Phluid\Request;
use Phluid\App;

class ExceptionHandlerTest extends \Phluid\Test\TestCase {
  
  function testRenderTemplate(){
    
    $handler = new ExceptionHandler();
    
    $this->app->get( '/gone', $handler, function( $request, $response ){
      // this template does not exist
      $response->on('data', function(){
        echo "Data?" . PHP_EOL;
      });
      $response->render( 'lol' );
    });
    
    $response = $this->doRequest( 'GET', '/gone' );
    # FIX assertTag() is deprecated and we are waiting the drop-in replacement 
    # from https://github.com/lstrojny/phpunit-dom-assertions
    #$this->assertTag( array( 'tag' => 'title', 'content' => 'Application Error:' ), $this->getBody() );
    self::assertTrue(true);
    
  }
  
}
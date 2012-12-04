<?php

namespace Phluid\Test;
use Phluid\App;

require_once 'tests/helper.php';

class RouteTest extends TestCase {
  
  public function getIndex( $request, $response ){
    $response->renderText( 'hello world' );
  }
  
  public function testInvokingRoute(){
    $this->app->get( '/show/:person', function( $request, $response ){
      $response->renderText( $request->param('person') );
    });
    $response = $this->doRequest( 'GET', '/show/beau' );
    $this->assertSame( 'beau', $response->getBody() );
    
  }
  
  public function testRouteWithArrayCallback(){
    $this->app->get( '/hello', array( $this, 'getIndex' ) );
    $response = $this->doRequest( 'GET', '/hello' );
    $this->assertSame( 'hello world', $response->getBody() );
  }
  
  public function testInvokigRouteWithFilters(){
    $reverse = function( $request, $response, $next ){
      $params = $request->params;
      $params['person'] = strrev( $params['person'] );
      $request->params = $params;
      $next();
    };
    $this->app->get( '/show/:person', $reverse, function( $request, $response ){
      $response->renderText( $request->param('person') );
    });
    $response = $this->doRequest( 'GET', '/show/beau' );
    $this->assertSame( strrev('beau'), $response->getBody() );
  }
  
  public function testInvokingRouteWithRedirectFilter(){
    $redirect = function( $request, $response, $next ){
      $response->redirectTo('/somewhere');
    };
    $this->app->get( '/redirect', $redirect, function( $request, $response ){
    });
    $response = $this->doRequest( 'GET', '/redirect' );
    $this->assertSame( '/somewhere', $response->getHeader('location') );
    
  }

}
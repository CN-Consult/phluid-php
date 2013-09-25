<?php
namespace Phluid\Middleware\Sessions;
use Phluid\Middleware\Sessions\SessionStoreInterface;
use Predis\Async\Client as PredisClient;

class PredisStore implements SessionStoreInterface {
  
  private $buffer = array();
  private $client;
  private $connected = false;
  private $options;
  
  function __construct( PredisClient $client, $options = array() ){
    $this->client = $client;
    $this->connect();
    $this->options = array_merge( array(
      'namespace' => 'session'
    ), $options );
  }
  
  public function find( $sid, $fn ){
    $that = $this;
    $callback = function () use ( $sid, $fn, $that ) {
      $id = $that->namespaceId( $sid );
      $that->client->get( $id , function( $data, $client ) use ($fn){
        if ( $data ) $data = json_decode( $data, true );
        $fn( $data );
      });
    };
    $this->buffer( $callback );
  }
  
  public function save( $sid, $session, $fn ){
    $that = $this;
    $callback = function() use ( $sid, $session, $fn, $that ){
      $data = json_encode( $session );
      $id = $that->namespaceId( $sid );
      $that->client->set( $id, $data, function( $res, $client ) use ($fn){
        $fn();
      });
    };
    $this->buffer( $callback );
  }
  
  public function destroy( $sid, $fn ){
    $that = $this;
    $id = $this->namespaceId( $sid );
    $this->buffer( function() use ( $sid, $fn, $that ){
      $that->client->del( $id, function() use ( $fn ){
        $fn();
      });
    });
  }
  
  private function buffer( $fn ){
    if ( !$this->connected ) {
      array_push( $this->buffer, $fn );
    } else {
      $fn();
    }
  }
  
  private function emptyBuffer(){
    while( count( $this->buffer ) > 0 ){
      $callback = array_unshift( $this->buffer );
      $callback();
    }
  }
  
  private function connect(){
    $that = $this;
    $this->client->connect( function( $client ) use ( $that ) {
      $that->connected = true;
      $that->emptyBuffer();
    });
  }
  
  private function namespaceId( $sid ){
    return $this->options['namespace'] . '.' . $sid;
  }
  
}
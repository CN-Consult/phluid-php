<?php

namespace Phluid\Middleware;

class JsonBodyParser {
  
  private $array = true;
  private $json_decode_options; // JSON_BIGINT_AS_STRING option
  private $depth;
  
  function __construct( $as_assoc_array = true, $depth = 512, $json_decode_options = null ){
    $this->array = $as_assoc_array;
    $this->depth = $depth;
    $this->options = $json_decode_options;
  }
  
  //Just JSON
  function __invoke( $request, $response, $next ){
    $options=new \stdClass;
    $options->array=$this->array;
    $options->depth=$this->depth;
    if ( $request->getContentType() == 'application/json' ) {
      $body = "";
      $request->on( 'data', function( $data ) use ( &$body ){
        $body .= $data;
      } );
      $request->on( 'end', function() use ( &$body, $request, $next, $options ){
        $request->body = json_decode( $body, $options->array, $options->depth );
        $next();
      } );
    } else {
      $next();      
    }
  }
  
}
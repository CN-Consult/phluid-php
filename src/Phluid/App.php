<?php

namespace Phluid;

use Evenement\EventEmitterInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use Phluid\Middleware\Router;
use React\Http\StreamingServer;
use React\Promise\Promise;
use React\Socket\Server as SocketServer;
use Phluid\Middleware\Cascade;
use React\EventLoop\Factory as LoopFactory;
use Evenement\EventEmitter;

class App extends EventEmitter {
  
  private $router;
  private $middleware = array();
  private $settings;
  private $router_mounted = false;
  
  public $http;
  public $socket;
  public $loop;
  
  /**
   * Passes an array of settings to initialize Settings with.
   *
   * @param array $options the settings for the app
   * @return App
   * @author Beau Collins
   **/
  public function __construct( $options = array() ){
    
    $defaults = array(
      'view_path' => realpath('.') . '/views',
      'env' => getenv("PHLUID_ENV") ?: 'development'
    );
    $this->settings = new Settings( array_merge( $defaults, $options ) );
    $this->router = new Router();
    
  }


  /**
   * Sets the loop that should be used when creating and running the HTTP server.
   *
   * @param LoopInterface $_loop The loop to use
   */
  public function setLoop(LoopInterface $_loop)
  {
  	$this->loop = $_loop;
  }
  
  public function createServer( $uri, EventEmitterInterface $http = null ){
    if ( $http === null ) {

      if (!$this->loop) $this->loop = LoopFactory::create();
      $this->socket = new SocketServer( $uri, $this->loop );
      $this->http = new StreamingServer(function(ServerRequestInterface $httpRequest) {
	    return new Promise(function ($resolve, $reject) use ($httpRequest) {
	      $request = new Request($httpRequest);
	      $response = new Response($request);

	      $response->on("end", function() use ($resolve, $response){
	        $resolve($response->getHttpResponse());
	      });

	      $app = $this;
	      $app($request, $response);
	    });
      });

      $this->http->listen($this->socket);
    }

    return $this;
  }
  
  public function listen( $port, $host = '127.0.0.1', $_startLoop = true ){
    if ( !$this->http ) {
      $this->createServer($host . ":" . $port);
    }

    if ($_startLoop) $this->loop->run();
    return $this;
  }
  
  /**
   * Retrieve a setting
   *
   * @param string $key 
   * @return mixed
   * @author Beau Collins
   */
  public function __get( $key ){
    return $this->settings->__get( $key );
  }
  
  /**
   * Set a setting
   *
   * @param string $key the setting name
   * @param mixed $value value to set
   * @author Beau Collins
   */
  public function __set( $key, $value ){
    return $this->settings->__set( $key, $value );
  }
  
  /**
   * An app is just a specialized middleware
   *
   * @param string $request 
   * @return void
   * @author Beau Collins
   */
  public function __invoke( $request, $response, $next = null ){
    
    $response->setOptions( array(
      'view_path' => $this->view_path,
      'default_layout' => $this->default_layout
    ) );
    
    if ( $this->router_mounted === false ) $this->inject( $this->router );
    
    $this->emit( 'start', array( $request, $response) );
    
    $middlewares = $this->middleware;
    $cascade = new Cascade( $middlewares );
    $cascade( $request, $response, function( $request, $response, $next ){
      $this->emit( 'end', array( $request, $response ) );
      $next();
    } );
    
  }

  /**
   * Adds the given middleware to the app's middleware stack. Returns $this for
   * chainable calls.
   *
   * @param Middleware $middleware 
   * @return App
   * @author Beau Collins
   */
  public function inject( $middleware ){
    if ( $middleware === $this->router ) $this->router_mounted = true;
    array_push( $this->middleware, $middleware );
    return $this;
  }
  
  /**
   * Configures a route give the HTTP request method, calls Router::route
   * returns $this for chainable calls
   *
   * Example:
   *
   *  $app->on( 'GET', '/profile/:username', function( $req, $res, $next ){
   *    $res->renderText( "Hello {$req->param('username')}");
   *  });
   *
   * @param string $method GET, POST or other HTTP method
   * @param string $path the matching path, refer to Router::route for options
   * @param invocable $closure an invocable object/function that conforms to Middleware
   * @return App
   * @author Beau Collins
   */
  public function handle( $method, $path, $filters, $action = null ){
    return $this->route( new RequestMatcher( $method, $path ), $filters, $action );
  }
  
  /**
   * Chainable call to the router's route method
   *
   * @param invocable $matcher 
   * @param invocable or array $filters 
   * @param invocable $action 
   * @return App
   * @author Beau Collins
   */
  public function route( $matcher, $filters, $action = null ){
    $this->router->route( $matcher, $filters, $action );
    return $this;
  }
  
  /**
   * Adds a route matching a "GET" request to the given $path. Returns $this so
   * it is chainable.
   *
   * @param string $path 
   * @param invocable or array $filters compatible function/invocable
   * @param invocable $closure compatible function/invocable
   * @return App
   * @author Beau Collins
   */
  public function get( $path, $filters, $action = null ){
    return $this->handle( 'GET', $path, $filters, $action );
  }
  
  /**
   * Adds a route matching a "POST" request to the given $path. Returns $this so
   * it is chainable.
   *
   * @param string $path 
   * @param invocable or array $filters compatible function/invocable
   * @param invocable $closure compatible function/invocable
   * @return App
   * @author Beau Collins
   */
  public function post( $path, $filters, $action = null ){
    return $this->handle( 'POST', $path, $filters, $action );
  }
  
  public function configure( $env, $callback = null ){
    if( func_num_args() == 2 ){
      $env = is_array( $env ) ? $env : [$env];
    } else {
      // no environment specific so always run
      $callback( $this );
      return;
    }
    
    if( in_array( $this->env, $env ) ){
      $callback( $this );
    }
    
  }
    
}

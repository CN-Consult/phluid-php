<?php

require_once( realpath( '../../' ) . '/vendor/autoload.php' );

$app = require( 'App.php' );

if (is_dir("/vagrant")) $host = "0.0.0.0";
else $host = "127.0.0.1";

$app->listen( 4000, $host );

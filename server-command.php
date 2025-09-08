<?php

if ( ! class_exists( 'FP_CLI' ) ) {
	return;
}

$fpcli_server_autoloader = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $fpcli_server_autoloader ) ) {
	require_once $fpcli_server_autoloader;
}

FP_CLI::add_command(
	'server',
	'Server_Command',
	array(
		'before_invoke' => function () {
			$min_version = '5.4';
			if ( version_compare( PHP_VERSION, $min_version, '<' ) ) {
				FP_CLI::error( "The `fp server` command requires PHP {$min_version} or newer." );
			}
		},
	)
);

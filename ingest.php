<?php
require 'vendor/autoload.php';

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \Monolog\Formatter\LineFormatter;
use \Bramus\Monolog\Formatter\ColoredLineFormatter;
use \Noodlehaus\Config;

/**
 * Setup our logs
 */
$log = new Logger('ingest');

// the STDOUT logger is simplified and coloured
$stdout = new StreamHandler(STDOUT, Logger::INFO);
$stdout->setFormatter(new ColoredLineFormatter(null, "[%datetime%] %message%\n", 'H:i:s'));
$log->pushHandler($stdout);

// the main log file is used as-is
$log->pushHandler(new StreamHandler('log/main.log'));

$log->info('Welcome to Ingester');


/**
 * Read in our config files
 */
$log->info('Reading configuration');

$files = array('config/main.json', 'config/user.json');
$configFiles = array();
foreach ($files as $file) {
	if (file_exists($file)) {
		$configFiles[] = $file;
	}
}

if (count($configFiles) > 0) {
	$conf = new Config($configFiles);
	$log->debug('Found and loaded ' . count($configFiles) . ' config files');
}
else {
	$log->alert('No configuration files found, exiting');
	die;
}





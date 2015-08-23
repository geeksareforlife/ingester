<?php
require 'vendor/autoload.php';

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \Monolog\Formatter\LineFormatter;
use \Bramus\Monolog\Formatter\ColoredLineFormatter;
use \Noodlehaus\Config;

require 'classes/image.php';

define("DS", DIRECTORY_SEPARATOR);

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

/**
 * Set the timezone
 */
$timezone = $conf->get('timezone');
if (!date_default_timezone_set($timezone)) {
	$log->alert('timezone not valid');
	die;
}

/**
 * Sequence number
 */
$sequenceFile = 'sequence.int';
$sequence = $conf->get('sequence_start');

if ($sequence == "") {
	if (file_exists($sequenceFile)) {
		$sequence = intval(file_get_contents($sequenceFile));
	} else {
		$sequence = 0;
	}
	$useSequenceFile = true;
} else {
	$sequence = intval($sequence);
	$useSequenceFile = false;
}

/**
 * Let's see what files we have.
 */
$source = $conf->get('source');
if (!is_dir($source)) {
	$log->alert('Source is not a directory, exiting');
	die;
}

// Get our image configuration
$imageConf = array();

$imageConf['datetype'] = $conf->get('datetype');
if ($imageConf['datetype'] !== 'file' && $imageConf['datetype'] !== 'image') {
	$log->warning('datetype not valid, using "file"');
	$imageConf['datetype'] = 'file';
}

$imageConf['folder_pattern'] = $conf->get('patterns.folder');
$imageConf['file_pattern'] = $conf->get('patterns.file');
$imageConf['destination'] = $conf->get('destination');


$log->info('Opening ' . $source);

$files = scandir($source);


foreach ($files as $file) {
	$image = Image::imageFactory($source . DS . $file, $log);
	if ($image === false) {
		$log->debug($file . ' is not supported, skipping');
		continue;
	}

	// load the conf
	if (!$image->loadConf($imageConf)) {
		$log->error('Failed to load conf for ' . $file);
		continue;
	}

	$sequence = $image->setSequence($sequence);

	$image->copy();

}

/**
 * Update the sequence
 */
if ($useSequenceFile) {
	file_put_contents($sequenceFile, $sequence);
}
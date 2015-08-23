<?php


class Image
{
	public static $supportedTypes = array('jpg', 'jpeg', 'cr2');


	private $fileInfo;

	private $destination;

	private $datetype = "";

	private $patterns = array();

	private $log;

	private $sequence;

	private $sequenceSize = 4;


	public function __construct($file, &$log)
	{
		$this->fileInfo = new SplFileInfo($file);
		
		$this->log = $log;
	}

	public static function imageFactory($file, &$log)
	{
		if (is_dir($file)) {
			return false;
		}

		$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

		if (!in_array($extension, Image::$supportedTypes)) {
			return false;
		}

		return new Image($file, $log);
	}


	public function loadConf($conf)
	{
		$status = true;

		if (!isset($conf['datetype'])) {
			$status = false;
		} else {
			$this->datetype = $conf['datetype'];
		}

		if (!isset($conf['folder_pattern'])) {
			$status = false;
		} else {
			$this->patterns['folder'] = $conf['folder_pattern'];
		}

		if (!isset($conf['file_pattern'])) {
			$status = false;
		} else {
			$this->patterns['file'] = $conf['file_pattern'];
		}

		if (!isset($conf['destination']) || !is_dir($conf['destination'])) {
			$status = false;
		} else {
			$this->destination = $conf['destination'];
		}

		return $status;
	}

	public function setSequence($sequence) {

		$this->sequence = str_pad($sequence, $this->sequenceSize, "0", STR_PAD_LEFT);

		$sequence++;

		if (strlen($sequence) > $this->sequenceSize) {
			$sequence = 0;
		}
		return $sequence;
	}

	public function copy()
	{
		$new_path = $this->destination . DS . $this->expandTags($this->patterns['folder']);
		if (!$this->createDirectory($new_path)) {
			$this->log->error('Failed to create path ' . $new_path);
		}
		$new_file = $this->expandTags($this->patterns['file']) . '.' . $this->fileInfo->getExtension();

		if(copy($this->fileInfo->getRealPath(), $new_path . DS . $new_file)) {
			$this->log->info('Copied ' . $this->fileInfo->getFilename() . ' to ' . $new_path . DS . $new_file);
			if (!touch($new_path . DS . $new_file, $this->fileInfo->getMTime())) {
				$this->error('Failed to set modification time for ' . $new_file);
			}
		}
		else {
			$this->log->error('Failed to copy ' . $this->fileInfo->getFilename());
		}

		//$this->log->info($this->fileInfo->getFileName() . ': ' . $new_path . ' : ' . $new_file);
	}

	private function expandTags($string) {

		// Replace time based tags
		$modTime = $this->fileInfo->getMTime();

		$string = str_replace('{year}', date('Y', $modTime), $string);
		$string = str_replace('{month}', date('m', $modTime), $string);
		$string = str_replace('{day}', date('d', $modTime), $string);
		$string = str_replace('{hour12}', date('g', $modTime), $string);
		$string = str_replace('{hour24}', date('H', $modTime), $string);
		$string = str_replace('{minute}', date('i', $modTime), $string);
		$string = str_replace('{second}', date('s', $modTime), $string);
		$string = str_replace('{ampm}', date('a', $modTime), $string);

		// Replace sequence number
		$string = str_replace('{seq}', $this->sequence, $string);		

		return $string;
	}

	private function createDirectory($path) {
		if (is_dir($path)) {
			return true;
		} elseif (mkdir($path, 0755, true)) {
			return true;
		} else {
			return false;
		}
	}
}
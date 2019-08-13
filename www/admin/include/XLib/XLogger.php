<?php
//-------------------------------------------------------------
class TXLogger
{
	var $Initted = false;
	var $CombinedLog = false;
	var $SeperateLog = true;
	var $levelNames = array();
	var $MaxLogLevel = -1;
	var $LogFileDir = '';
	var $LogFileName = '';
	var $MainLabel = '';
	var $TimeZone = false;
	var $TimeStampFormat = '';
	var $LineDelimitter = '';
	var $ArchiveFileSize = false;
	var $ArchiveDir = '';
	var $LastError = '';
	//--------------------------------------------------------
	function __construct()
	{
		
	}
	//--------------------------------------------------------
	function Init($logFileName, $combinedLog = false, $seperateLog = true, $mainLabel = false /*''*/, $timeZone = false /*'UTC'*/, $timeStampFormat = false /*'m/d/Y G:i'*/, $archiveFileSize = false /*disabled*/, $archiveDirectory = false /*$LogFileDir/archive*/, $lineDelimitter = false /*"\n"*/)
	{
		if ($mainLabel === false)
			$mainLabel = '';
		if ($archiveFileSize !== false)
		{
			if (!is_numeric($archiveFileSize))
			{
				$this->LastError = "TXLogger::Init invalid archive file size specified: $archiveFileSize";
				return false;
			}
			if (PHP_INT_SIZE < 8 /*32 bit*/ && $archiveFileSize >= 2000000000) 
			{
				$this->LastError = "TXLogger::Init invalid archive file size. Maximum 2GB allowed for 32-bit systems: $archiveFileSize";
				return false;
			}
		}
		if ($timeZone === false)
			$timeZone = 'UTC';
		if ($timeStampFormat === false)
			$timeStampFormat = 'm/d/Y G:i';
		if ($lineDelimitter === false)
			$lineDelimitter = "\n";
		if ($combinedLog !== true && $seperateLog !== true)
			$combinedLog = true; // default to at least output one log file
		$fileparts = pathinfo($logFileName);
		$this->LogFileDir  = XEnsureBackslash($fileparts['dirname']);
		$this->LogFileName = $fileparts['basename'];
		$this->ArchiveFileSize = $archiveFileSize;
		$this->ArchiveDir = XEnsureBackslash( ($archiveDirectory !== false ? $archiveDirectory : $this->LogFileDir."archive") );
		$this->CombinedLog = ($combinedLog === true ? true : false);
		$this->SeperateLog = ($seperateLog === true ? true : false);
		$this->MainLabel = $mainLabel;
		$this->TimeZone = new DateTimeZone($timeZone);
		if ($this->TimeZone === false)
		{
			$this->LastError = "TXLogger::Init invalid time zone specified: $timeZone";
			return false;
		}
		$this->TimeStampFormat = $timeStampFormat;
		$this->LineDelimitter = $lineDelimitter;
		$this->Initted = true;
		return true;
	}
	//--------------------------------------------------------
	function AddLevel($level, $name)
	{
		$this->levelNames[$level] = $name;
	}
	//--------------------------------------------------------
	function TestLogsWritable()
	{
		$r = true;
		$rMessage = "";
		if ($this->ArchiveFileSize !== false && !is_writeable($this->ArchiveDir))
		{
			$r = false;
			$rMessage .= "Log file archive directory not writable '$this->ArchiveDir'.\n";
		}
		foreach ($this->levelNames as $lvl)
		{
			$filename = $this->LogFileDir;
			$filename .= $lvl.'_';
			$filename .= $this->LogFileName;
			if (is_writable($filename) !== true)
			{
				$r = false;
				$rMessage .= "Log file not writable '$filename'.\n";
			}
			
		}
		return array($r, $rMessage);
	}
	//--------------------------------------------------------
	function Log($mess, $level, $label = "", $levelName = false)
	{
		if (!$this->Initted)
		{
			$this->LastError = "TXLogger::Log not initted";
			return false;
		}
		if ($this->maxLogLevel != -1 && $level > $this->maxLogLevel)
			return true; // skip too high of log level 		 
		
		if ($levelName === false)
			$levelName = (isset($this->levelNames[$level]) ? $this->levelNames[$level] : '');
		$levelName = preg_replace('/[^a-zA-Z0-9\_\-]/', '', $levelName);

		if ($label == '') 
			$label = $this->MainLabel;		
		if ($label != '')
			$label .= ' ';

		if ($this->TimeStampFormat === '')
			$dateText = '';
		else
		{
			$dt = new DateTime('now',  $this->TimeZone);
			if ($dt === false)
			{
				$this->LastError = "TXLogger::Log get date time failed";
				return false;
			}
			$dateText = $dt->format($this->TimeStampFormat).' '; // traily space only needed if timestamp isn't going to be blank
		}
		
		$filename = $this->LogFileDir;
		if ($levelName != '')
			$filename .= $levelName.'_';
		$filename .= $this->LogFileName;
	
		$fh = @fopen($filename, 'c'); // write only, create if not exist
		if (!$fh) 
		{
			$this->LastError = "TXLogger::Log Could not open log file '$filename' level label '$levelLabel', message '$mess'";
			return false;
		}
		if (-1 === fseek($fh, 0, SEEK_END)) // position at end of file
		{
			$this->LastError = "TXLogger::Log seek to end of file failed '$filename'";
			return false;
		}
		$size = @ftell($fh); // just use size before appending to avoid extra seek or flushing. ftell doesn't work with append fopen mode
		if ($size === false)
		{
			$this->LastError = "TXLogger::Log Get file position failed '$filename'";
			return false;
		}
		if (!@fwrite($fh, "$dateText$label$mess$this->LineDelimitter"))
		{
			$this->LastError = "TXLogger::Log Could not write to log file '$filename'";
			return false;
		}
		@fclose($fh);
		if ($this->ArchiveFileSize !== false && $size > $this->ArchiveFileSize)
		{
			if (!$this->ArchiveFile($filename))
				return false;
		}
		return true;
	}
	//--------------------------------------------------------
	function ArchiveFile($filename)
	{
		$fileparts = pathinfo($filename);
		$dt = new DateTime('now',  $this->TimeZone);
		if ($dt === false)
		{
			$this->LastError = "TXLogger::ArchiveFile get date time failed";
			return false;
		}
		$compFilename = $this->ArchiveDir.$fileparts['filename']."_".$dt->Format("m-d-Y_H-i-s").($fileparts['extension'] != '' ? '.'.$fileparts['extension'] : '').'.gz';
		$tempFilename = @tempnam($this->ArchiveDir, $fileparts['basename'].".tmp_");
		if ($tempFilename === false)
		{
			$this->LastError = "TXLogger::ArchiveFile get temp file name failed. Archive Dir: $this->ArchiveDir, base name: ".$fileparts['basename'];
			return false;
		}
		if (!@rename($filename, $tempFilename)) // move the whole file quickly so other instances can't write while we are reading/compressing it
		{
			$this->LastError = "TXLogger::ArchiveFile rename to temporary archive file failed. From: '$filename', To: '$tempFilename'";
			return false;
		}
		$logdata = file_get_contents($tempFilename);
		if ($logdata === false)
		{
			$this->LastError = "TXLogger::ArchiveFile get file contents of moved log file failed. Moved file name: '$tempFilename', original file name: '$filename'";
			return false;
		}
		$compdata = gzencode($logdata, 9 /*max compression*/);
		if ($compdata === false)
		{
			$this->LastError = "TXLogger::ArchiveFile gzencode read contents of moved log file failed. Moved file name: '$tempFilename', original file name: '$filename'";
			return false;
		}
		if (!file_put_contents($compFilename, $compdata))
		{
			$this->LastError = "TXLogger::ArchiveFile write compressed log data to file failed. Compressed file name: '$compFilename', Moved file name: '$tempFilename', original file name: '$filename'";
			return false;
		}
		if (!unlink($tempFilename))
		{
			$this->LastError = "TXLogger::ArchiveFile deleted temporary moved file failed. Compressed file name: '$compFilename', Moved file name: '$tempFilename', original file name: '$filename'";
			return false;
		}
		return true;
	}
	//--------------------------------------------------------
	function LogLevel($level, $mess, $label = '', $nocombine = false, $levelName = false)
	{
		if ($this->SeperateLog)
			if (!$this->Log($mess, $level, $label, $levelName))
				return false;
		if ($level != XLOG_LEVEL_ALL && $this->CombinedLog && $nocombine == false)
		{
			$levelName = (isset($this->levelNames[$level]) ? $this->levelNames[$level] : '');
			if ($label != '' && $levelName != '')
				$label .= ' ';
			$label .= $levelName;
			if (!$this->Log($mess, XLOG_LEVEL_ALL, $label))
				return false;
		}
		return true;
	}
	//--------------------------------------------------------
}// class XLogger extends base
//-------------------------------------------------------------
?>
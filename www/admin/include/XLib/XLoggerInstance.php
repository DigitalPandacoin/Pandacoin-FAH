<?php
//-------------------------------------------------------------
/*
 * Required define's:
 * 
 * 		XLOG_FILENAME [string] (path and file name)
 * 
 * 
 * Optional defines:
 * 
 * 		XLOG_MAX_LEVEL [int]  (default: -1/no limit)
 * 		XLOG_COMBINED  [bool] (default: false)
 * 
 * Creates global instantiation of TXLogger as $XLogInstance
 * 
 */
//-------------------------------------------------------------
if (!defined('XLOG_FILENAME'))
	die("XLoggerInstance.php XLOG_FILENAME is not defined:<br/>\n".nl2br(XStackTrace()));
//-------------------------------------------------------------
define('XLOGGER_INSTANCE', 1);
//-------------------------------------------------------------
define('XLOG_LEVEL_ALL',    0);
define('XLOG_LEVEL_ERROR',	1);
define('XLOG_LEVEL_WARN',	2);
define('XLOG_LEVEL_USER',	3);
define('XLOG_LEVEL_NOTIFY',	4);
define('XLOG_LEVEL_DEBUG',	5);
//-------------------------------------------------------------
$XLogInstance = new TXLogger() or die("Create object failed");
//-------------------------------------------------------------
$XLogInstance->Init(XLOG_FILENAME, XDefined('XLOG_COMBINED'), XDefined('XLOG_SEPERATE'), XDefined('XLOG_LABEL'), XDefined('XLOG_TIMEZONE'), XDefined('XLOG_TIMESTAMP_FORMAT'), XDefined('XLOG_ARCHIVE_FILESIZE'), XDefined('XLOG_ARCHIVE_DIR'), XDefined('XLOG_LINE_DELIMITTER'));
//-------------------------------------------------------------
if (defined('XLOG_MAX_LEVEL'))
	$XLogInstance->maxLogLevel = XLOG_MAX_LEVEL; 
//-------------------------------------------------------------
$XLogInstance->AddLevel(XLOG_LEVEL_ALL, 	"ALL");
$XLogInstance->AddLevel(XLOG_LEVEL_ERROR, 	"ERROR");
$XLogInstance->AddLevel(XLOG_LEVEL_WARN, 	"WARN");
$XLogInstance->AddLevel(XLOG_LEVEL_USER, 	"USER");
$XLogInstance->AddLevel(XLOG_LEVEL_NOTIFY, 	"NOTIFY");
$XLogInstance->AddLevel(XLOG_LEVEL_DEBUG, 	"DEBUG");
//-------------------------------------------------------------
function XLogAll($mess, $label = "")
{
	global $XLogInstance;
	if (!$XLogInstance->LogLevel(XLOG_LEVEL_ALL, $mess, $label))
		die ("XLogAll XLogger LogLevel failed: $XLogInstance->LastError");
}
//-------------------------------------------------------------
function XLogError($mess, $label = "")
{
	global $XLogInstance;
	if (!$XLogInstance->LogLevel(XLOG_LEVEL_ERROR, $mess, $label))
		die ("XLogError XLogger LogLevel failed: $XLogInstance->LastError");
}
//-------------------------------------------------------------
function XLogWarn($mess, $label = "")
{
	global $XLogInstance;
	if (!$XLogInstance->LogLevel(XLOG_LEVEL_WARN, $mess, $label))
		die ("XLogWarn XLogger LogLevel failed: $XLogInstance->LastError");
}
//-------------------------------------------------------------
function XLogUser($mess, $label = "")
{
	global $XLogInstance;
	if (!$XLogInstance->LogLevel(XLOG_LEVEL_USER, $mess, $label))
		die ("XLogUser XLogger LogLevel failed: $XLogInstance->LastError");
}
//-------------------------------------------------------------
function XLogNotify($mess, $label = "")
{
	global $XLogInstance;
	if (!$XLogInstance->LogLevel(XLOG_LEVEL_NOTIFY, $mess, $label))
		die ("XLogNotify XLogger LogLevel failed: $XLogInstance->LastError");
}
//-------------------------------------------------------------
function XLogDebug($mess, $label = "")
{
	global $XLogInstance;
	if (!$XLogInstance->LogLevel(XLOG_LEVEL_DEBUG, $mess, $label))
		die ("XLogDebug XLogger LogLevel failed: $XLogInstance->LastError");
}
//-------------------------------------------------------------
function XLogSpecial($mess, $label = "")
{
	global $XLogInstance;
	if (!$XLogInstance->LogLevel(XLOG_LEVEL_ALL, $mess, '' /*main label*/, true /*nocombine*/, $label /*levelName*/))
		die ("XLogDebug XLogger LogLevel failed: $XLogInstance->LastError");
}
//-------------------------------------------------------------
?>
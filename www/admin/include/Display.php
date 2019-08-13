<?php
/*
 *	www/include/Display.php
 * 
 * 
* 
*/
//---------------
define('DEFAULT_DISPLAY_DATETIME_FORMAT', 'm-d-Y g:i a');
define('DEFAULT_DISPLAY_DATE_FORMAT', 'm-d-Y');
define('DEFAULT_DISPLAY_TIME_FORMAT', 'g:i a');
//---------------
define('CFG_DISPLAY_DATETIME_FORMAT', 'display_datetime_format');
define('CFG_DISPLAY_DATE_FORMAT', 'display_date_format');
define('CFG_DISPLAY_TIME_FORMAT', 'display_time_format');
//---------------
class Display
{
	var $formatDateTime = false;
	var $formatDate = false;
	var $formatTime = false;
	//---------------
	function Init()
	{
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		$dspfmt = $Config->Get(CFG_DISPLAY_DATETIME_FORMAT);
		if ($dspfmt === false && !$Config->Set(CFG_DISPLAY_DATETIME_FORMAT, DEFAULT_DISPLAY_DATETIME_FORMAT))
		{
			XLogError("Display::Init Config Set display datetime format failed");
			return false;
		}
		//------------------
		$dspfmt = $Config->Get(CFG_DISPLAY_DATE_FORMAT);
		if ($dspfmt === false && !$Config->Set(CFG_DISPLAY_DATE_FORMAT, DEFAULT_DISPLAY_DATE_FORMAT))
		{
			XLogError("Display::Init Config Set display date format failed");
			return false;
		}
		//------------------
		$dspfmt = $Config->Get(CFG_DISPLAY_TIME_FORMAT);
		if ($dspfmt === false && !$Config->Set(CFG_DISPLAY_TIME_FORMAT, DEFAULT_DISPLAY_TIME_FORMAT))
		{
			XLogError("Display::Init Config Set display time format failed");
			return false;
		}
		//------------------
		return true;
	}
	//---------------
	function getDateTimeFormat()
	{
		//------------------
		if ($this->formatDateTime === false)
		{
			$Config = new Config() or die("Create object failed");
			$this->formatDateTime = $Config->Get(CFG_DISPLAY_DATETIME_FORMAT);
		}
		//------------------
		return $this->formatDateTime;
	}
	//---------------
	function getDateFormat()
	{
		//------------------
		if ($this->formatDate === false)
		{
			$Config = new Config() or die("Create object failed");
			$this->formatDate = $Config->Get(CFG_DISPLAY_DATE_FORMAT);
		}
		//------------------
		return $this->formatDate;
	}
	//---------------
	function getTimeFormat()
	{
		//------------------
		if ($this->formatTime === false)
		{
			$Config = new Config() or die("Create object failed");
			$this->formatTime = $Config->Get(CFG_DISPLAY_TIME_FORMAT);
		}
		//------------------
		return $this->formatTime;
	}
	//---------------
	function localDateTimeString($strUTC, $format = false, $default = "<date error>")
	{
		global $Login;
		//------------------
		if ($format === false)
			 $format = $this->getDateTimeFormat();; 
		//------------------
		$strLocal = $Login->getLocalTimeString($strUTC, $format);
		if ($strLocal === false)
			$strLocal = $default;
		//------------------
		return $strLocal;
	}
	//---------------
	function htmlLocalDateTime($strUTC, $format = false, $default = "&lt;date error&gt;")
	{
		//------------------
		return XEncodeHTML($this->localDateTimeString($strUTC, $format, $default));
	}
	//---------------
	function localDateString($strUTC, $default = "<date error>")
	{
		//------------------
		return $this->localDateTimeString($strUTC, $this->getDateFormat(), $default);
	}
	//---------------
	function htmlLocalDate($strUTC, $default = "&lt;date error&gt;")
	{
		//------------------
		return XEncodeHTML($this->localDateString($strUTC, $default));
	}
	//---------------
} // class Display
//---------------
?>
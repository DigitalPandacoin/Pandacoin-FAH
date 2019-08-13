<?php
//---------------------------------------------------------------------
define('XGeneralFunctionsPHP', true);
//---------------------------------------------------------------------
// htmlspecialchars();
// addslashes();
//---------------------------------------------------------------------
function Xnl2br($s)
{
	// cant seem to use str_replace to have array and replace with \n at same time
	//return str_replace(array("\r\n", "\n", "\r"), "<BR>\n", $s);
	return nl2br($s); // replaces with '<br />\n'
}
//---------------------------------------------------------------------
function BoolYN($value)
{
	return ($value ? "Y" : "N");
}
//---------------------------------------------------------------------
function BoolTF($value)
{
	return ($value ? "T" : "F");
}
//---------------------------------------------------------------------
function XMaskContains($mask, $test)
{
	//-----------------------------------
	if (!is_numeric($mask) || !is_numeric($test))
		return false;
	//-----------------------------------
	return (($mask & $test) != 0 ? true : false);
}
//---------------------------------------------------------------------
function XArray($a, $i, $def = "")
{
	//-----------------------------------
	return (isset($a[$i]) ? $a[$i] : $def);
}
//---------------------------------------------------------------------
function XVarDump(&$Var, $flatten = true)
{
	//-----------------------------------
	ob_start();
	var_dump($Var);
	$s = ob_get_contents();
	ob_end_clean();
	//-----------------------------------
	if ($flatten === true)
	{
		$lines = explode("\n", $s);
		$s = '';
		foreach ($lines as $l)
			$s .= trim($l)." ";
	}
	//-----------------------------------
	return $s;
}
//---------------------------------------------------------------------
function XStackTrace()
{
	//-----------------------------------
	$s = "";
	$t = debug_backtrace();
	//-----------------------------------
	foreach ($t as $c)
	{
		$l = XArray($c, 'line');
		$cls = XArray($c, 'class');
		$f = XArray($c, 'function');
		$s .= basename($c['file'])."\t\tLine: $l $cls::$f\n";
	}
	//-----------------------------------
	return $s;
}
//---------------------------------------------------------------------
function XPost($Name, $Def = "")
{
	//-----------------------------------
	if (get_magic_quotes_gpc())
		return (isset($_POST[$Name]) ? stripslashes($_POST[$Name]) : $Def);
	else
		return (isset($_POST[$Name]) ? $_POST[$Name] : $Def);
	//-----------------------------------
}
//---------------------------------------------------------------------
function XGet($Name, $Def = "")
{
	//-----------------------------------
	if (get_magic_quotes_gpc())
		return (isset($_GET[$Name]) ? stripslashes($_GET[$Name]) : $Def);
	else
		return (isset($_GET[$Name]) ? $_GET[$Name] : $Def);
	//-----------------------------------
}
//---------------------------------------------------------------------
function XGetPost($Name, $Def = "")
{
	//-----------------------------------
	$p = XPost($Name, false);
	if ($p !== false) return $p;
	//-----------------------------------
	return XGet($Name, $Def);
}
//---------------------------------------------------------------------
function XServer($Name, $Def = "")
{
	//-----------------------------------
	return (isset($_SERVER[$Name]) ? $_SERVER[$Name] : $Def);
}
//---------------------------------------------------------------------
function XSession($Name, $Def = "")
{
	//-----------------------------------
	return (isset($_SESSION) && isset($_SESSION[$Name]) ? $_SESSION[$Name] : $Def);
}
//---------------------------------------------------------------------
function XFiles($filename, $part, $Def = "")
{
	//-----------------------------------
	return (isset($_FILES) && isset($_FILES[$filename]) && isset($_FILES[$filename][$part]) ? $_FILES[$filename][$part] : $Def);
}
//---------------------------------------------------------------------
function XRand($Min = null, $Max = null) 
{
	//-----------------------------------
	if (isset($Min) && isset($Max))
	{
		if ($Min >= $Max) return $Min;
		else              return mt_rand($Min, $Max);
	}
	//-----------------------------------
	return mt_rand();
}
//---------------------------------------------------------------------
function XTestSecurityToken()
{
	//-----------------------------------
	if ( !isset($_SESSION['securityToken']) || !isset($_POST['securityToken']) )
		return false;
	//-----------------------------------
	if ( $_SESSION['securityToken'] !== $_POST['securityToken'] )
		return false;
	//-----------------------------------
	return true;
}
//-------------------------------------------------------
function XQuickWriteFile($tFileName, $tText, $tAppend = false)
{
	//-----------------------------------
	if ($tAppend) $Attr = 'a+';
	else          $Attr = 'w+';
	//-----------------------------------
	$fh = fopen($tFileName, $Attr);
	if (!$fh) 
		return false;
	//-----------------------------------
	fwrite($fh, $tText);
	fclose($fh);
	//-----------------------------------
	return true;
}
//-------------------------------------------------------
function XListContains(&$List, &$Item)
{
	//-----------------------------------
	$cnt = sizeof($List);
	for ($i = 0;$i < $cnt;$i++)
		if ($Item == $List[$i])
			return true;
	//-----------------------------------
	return false;
}
//-------------------------------------------------------
function XEnsureBackslash($str)
{
	//-----------------------------------
	if (strrpos($str, "/") != strlen($str) - 1)
		$str .= "/";
	//-----------------------------------
	return $str;
}
//-------------------------------------------------------
function XEncodeHTML($txt, $doubleEncode = false)
{
	//-----------------------------------
	return htmlspecialchars($txt, ENT_QUOTES, 'UTF-8', $doubleEncode);
}
//------------------------
function XEncodeHTMLJS($txt, $doubleEncode = false)
{
	//-----------------------------------
	return htmlspecialchars(addcslashes($txt, "\"'"), ENT_QUOTES, 'UTF-8', $doubleEncode);
}
//------------------------
function XDefined($constName, $default = false)
{
	return (defined($constName) ? constant($constName) : $default);
}
//------------------------
// returns difference in signed $units, with $overflow (def XDateTimeDiff_MAX 1 week max for seconds) on over flow
// before and later can be a DateTime object or a string
define('XDateTimeDiff_MAX', 604800); // 1 week worth of seconds
function XDateTimeDiff($before, $later = false/*now*/, $strTimeZone = false/*UTC*/, $units = false/*seconds (s,i/n,h,d,m,y)*/, $overflow = false/*XDateTimeDiff_MAX*/) 
{
	//-----------------------------------
	if ($units === false) $units = 's';
	if ($later === false) $later = 'now';
	if ($strTimeZone === false) $strTimeZone = 'UTC';
	if ($overflow === false) $overflow = XDateTimeDiff_MAX;
	if ($units === 'n') $units = 'i'; // alias i and n for minutes
	//-----------------------------------
	try{
		$timeZone = new DateTimeZone($strTimeZone);
	} catch (Exception $e) {
		XLogError("XDateTimeDiff create DateTimeZone strTimeZone: ".XVarDump($strTimeZone).",  exception: ".$e->getMessage());
		return false;
	}
	//-----------------------------------
	if ($timeZone === false){
		XLogError("XDateTimeDiff validate DateTimeZone failed, strTimeZone: ".XVarDump($strTimeZone));
		return false;
	}
	//-----------------------------------
	if (!is_string($before))
		$dtBefore = $before;
	else
	{
		try{
			$dtBefore = new DateTime($before, $timeZone);
		} catch (Exception $e) {
			XLogError("XDateTimeDiff create DateTime strBefore: ".XVarDump($strBefore).",  exception: ".$e->getMessage());
			return false;
		}
	}
	//-----------------------------------
	if ($dtBefore === false){
		XLogError("XDateTimeDiff validate DateTime failed, strBefore: ".XVarDump($strBefore));
		return false;
	}
	//-----------------------------------
	if (!is_string($later))
		$dtLater = $later;
	else
	{
		try{
			$dtLater = new DateTime($later, $timeZone);
		} catch (Exception $e) {
			XLogError("XDateTimeDiff create DateTime strLater: ".XVarDump($strLater).",  exception: ".$e->getMessage());
			return false;
		}
	}
	//-----------------------------------
	if ($dtLater === false){
		XLogError("XDateTimeDiff validate DateTime failed, strLater: ".XVarDump($strLater));
		return false;
	}
	//-----------------------------------
	$diff = $dtLater->diff($dtBefore);
	if ($diff === false){
		XLogError("XDateTimeDiff diff failed");
		return false;
	}
	//-----------------------------------
	$dy = ($diff->y !== false && $diff->y !== 0 ? $diff->y : 0);
	$dm = ($diff->m !== false && $diff->m !== 0 ? $diff->m : 0);
	$dd = ($diff->d !== false && $diff->d !== 0  && $diff->d !== -99999 /*PHP < 5.4.20/5.5.4*/ ? $diff->d : 0);
	$dh = ($diff->h !== false && $diff->h !== 0 ? $diff->h : 0);
	$di = ($diff->i !== false && $diff->i !== 0 ? $diff->i : 0);
	$ds = ($diff->s !== false && $diff->s !== 0 ? $diff->s : 0);
	//-----------------------------------
	if ($units === 's' && ($dy != 0 || $dm != 0 || $dd > 7))
		return $overflow;
	else if ($units === 'i' && ($dy > 1 || ($dy == 1 && $dm > 2) || $dm > 14 || $dd > 425))
		return $overflow;
	//-----------------------------------
	$val = 0.0;
	//-----------------------------------
	if ($units === 's') // seconds
	{
		$val += ($dd * 86400.0);
		$val += ($dh * 3600.0);
		$val += ($di * 60.0);
		$val += $ds;
	}
	else if ($units === 'i') // minutes
	{
		$val += ($dy * 525600.0);
		$val += ($dm * 43800.0);
		$val += ($dd * 1440.0);
		$val += ($dh * 60.0);
		$val += $di;
		if ($ds != 0) $val += ($ds / 60);
	}
	else if ($units === 'h') // hours
	{
		$val += ($dy * 8760.0);
		$val += ($dm * 730.001);
		$val += ($dd * 24.0);
		$val += $dh;
		if ($di != 0) $val += ($di / 60.0);
		if ($ds != 0) $val += ($ds / 3600.0); // (60 * 60)
	}
	else if ($units === 'd') // days
	{
		$val += ($dy * 365.25);
		$val += ($dm * 30.4167);
		$val += $dd;
		if ($dh != 0) $val += ($dh / 24.0);
		if ($di != 0) $val += ($di / 1440.0); // (60 * 24)
		if ($ds != 0) $val += ($ds / 86400.0); // (60 * 60 * 24)
	}
	else if ($units === 'm') // months
	{
		$val += ($dy * 12);
		$val += $dm;
		if ($dd != 0) $val += ($dd / 30.4167);
		if ($dh != 0) $val += ($dh / 730.0008); // (24 * 30.4167)
		if ($dh != 0) $val += ($di / 43800.048); // (60 * 24 * 30.4167)
		if ($dh != 0) $val += ($ds / 2628002.88); // (60 * 60 * 24 * 30.4167)
	}
	else if ($units === 'y') // years
	{
		$val += $dy;
		if ($dm != 0) $val += ($dm / 12.0);
		if ($dd != 0) $val += ($dd / 365.25);
		if ($dh != 0) $val += ($dh / 8766.0); // (24 * 365.25)
		if ($dh != 0) $val += ($di / 525960.0);  // (60 * 24 * 365.25)
	}
	//-----------------------------------
	//XLogDebug("XDateTimeDiff before: '$strBefore', later: '$strLater', tz: ' $strTimeZone', units: '$units', overflow: '$overflow', diff: ".XVarDump($diff));
	//-----------------------------------
	return $val;
}
//------------------------
?>

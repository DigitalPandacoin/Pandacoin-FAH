<?php
//-------------------------------------------------------------
// 
//
//-------------------------------------------------------------
if (!defined('XLOGGER_INSTANCE'))
	die('XSession - XLoggerInstance required');
//------------------------------------------------------------------------------
//##############################################################################
//------------------------------------------------------------------------------
class TXMySqlDBResult
{
	//---------------------------------------------------------
	function __construct($tHandle, $tPdo = false)
	{
		$this->pdo = $tPdo;
		$this->HasRowCount = false;
		if (!$tHandle) $this->Initted = false;
		else
		{
			$this->Handle = $tHandle;
			$this->Initted = true;
		}
	}
	//---------------------------------------------------------
	function Release()
	{
		if ($this->Initted)
		{
			if ($this->pdo)
				$this->Handle = NULL;
			else
				@mysql_free_result($this->Handle);
		}
		$this->HasRowCount = false;
		$this->Initted = false;
	}
	//---------------------------------------------------------
	function RowCount()
	{
		if (!$this->Initted)
			return 0;
		if (!$this->HasRowCount)
		{
			if ($this->pdo)
				$this->tRowCount = $this->Handle->rowCount();
			else
				$this->tRowCount = @mysql_num_rows($this->Handle);
		}
		return $this->tRowCount;
	}
	//---------------------------------------------------------
	function GetRow()
	{
		if (!$this->Initted)
		{
			XLogError("TXMySqlDBResult::GetRow not initted");
			return false;
		}
		if (!$this->pdo)
			return @mysql_fetch_row($this->Handle);
		return $this->Handle->fetch(PDO::FETCH_NUM);
	}
	//---------------------------------------------------------
	function GetRowArray()
	{
		if (!$this->Initted)
		{
			XLogError("TXMySqlDBResult::GetRowArray not initted");
			return false;
		}
		if (!$this->pdo)
			return @mysql_fetch_assoc($this->Handle);
		return $this->Handle->fetch(PDO::FETCH_ASSOC);
	}
	//---------------------------------------------------------
} // class TXMySQLDBResult
//------------------------------------------------------------------------------
//##############################################################################
//------------------------------------------------------------------------------
class TXMySqlDB
{
	//---------------------------------------------------------
	function __construct()
	{
		$this->pdo = false;
		$this->Connected = false;
		$this->Error = false;
		$this->DBSelected = false;
	}
	//---------------------------------------------------------
	function sanitize($val)
	{
		if (!$this->Connected)
			return $val;
		if ($this->pdo)
		{
			//XLogDebug('TMySqlDB::sanitize - PDO Support detected, using quote');
			return substr($this->Connection->quote($val), 1, -1);
		}
		else 
		{
			XLogDebug('TMySqlDB::sanitize - falling back on mysql_real_escape_string, instead of PDO quote');
			try
			{
				$r = mysql_real_escape_string(addslashes($val)); // was XDBSanitize
			}
			catch (Exception $e)
			{
				XLogWarning('TMySqlDB::sanitize - exception: '.XVarDump($e));
			}
			return $r;
		}
	}
	//---------------------------------------------------------
	function errorString()
	{
		if ($this->pdo)
			return $this->Error;
		return @mysql_error();
	}
	//---------------------------------------------------------
	function Connect($tHost, $tUser, $tPass, $tDatabase = "")
	{
		if ($this->Connected)
			$this->Disconnect();
		if (defined('PDO::ATTR_DRIVER_NAME'))
		{
			$this->pdo = true;
			//XLogDebug('TMySqlDB::Connect - PDO Support detected');
			try
			{
				$this->Connection = new PDO("mysql:host=$tHost;dbname=$tDatabase" , $tUser, $tPass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
			}
			catch (PDOException $e)
			{
				$this->Error = $e->getMessage();
				XLogError('TXMySqlDB::Connect create pdo connection failed: '.$e->getMessage());
				return false;
			}
			$this->DBSelected = true;
		}
		else
		{
			if (!function_exists('mysql_connect')) 
			{
				XLogError('TXMySqlDB::Connect - undefined function: mysql_connect().  Please install PDO or the MySQL Connector for PHP'); 
				return false;
			}
			XLogDebug('TMySqlDB::Connect - falling back on mysql_*, instead of PDO');
			if ($this->Connected)
				$this->Disconnect();
			$this->Connection = @mysql_connect($tHost, $tUser, $tPass);
			if (!$this->Connection)
				return false;
		}
		$this->Connected = true;
		return true;
	}
	//---------------------------------------------------------
	function Disconnect()
	{
		if ($this->Connected && $this->Connection)
		{
			if (!$this->pdo)
				$this->Connection = NULL;
			else			
				@mysql_close($this->Connection);
		}
		$this->Connected = false;
	}
	//---------------------------------------------------------
	function SelectDatabase($tDatabase)
	{
		if (!$this->pdo)
		{
			if (!$this->Connected)
				return false;
			if (@mysql_select_db($tDatabase)) $this->DBSelected = true;
			else                              $this->DBSelected = false;
		}
		return $this->DBSelected;
	}	
	//---------------------------------------------------------
	function Execute($tQuery, $MustSelectDB = true)
	{
		if (!$this->Connected)
		{
			XLogError('XMySqlDB::Execute - Not connected.');
			return false;
		}
		if ($MustSelectDB &&  !$this->DBSelected)
		{
			XLogError('XMySqlDB::Execute - No database selected.');
			return false;
		}
		if (!$this->pdo)
			return @mysql_query($tQuery);
		$r = false;
		try
		{
			$r = $this->Connection->query($tQuery);
		}
		catch (PDOException $e)
		{
			$this->Error = $e->getMessage();
			XLogError('XMySqlDB::Execute - query failed: '.$e->getMessage());
			return false;
		}		
		return $r;	
	}
	//---------------------------------------------------------
	function Query($tQuery)
	{
		if (!$this->Connected || !$this->DBSelected)
		{
			XLogError('XMySqlDB::Query - Not connected or no database selected');
			return false;
		}
		if (!$this->pdo)
		{
			$r = @mysql_query($tQuery, $this->Connection);
			if (!$r) return false;
			$rr =  new TXMySqlDBResult($r, false/*pdo*/) or die('XMySqlDB::Query - Create object failed');
			return $rr; // doesn't work:  return new MyClass() or die("sfd");
		}
		$r = false;
		try
		{
			$r = $this->Connection->query($tQuery);
		}
		catch (PDOException $e)
		{
			$this->Error = $e->getMessage();
			XLogError('XMySqlDB::Query - query failed: '.$e->getMessage());
			return false;
		}
		if (!$r) return false;
		$rr =  new TXMySqlDBResult($r, true/*pdo*/) or die('XMySqlDB::Query - Create object failed');
		return $rr;
	}
	//---------------------------------------------------------
} // class TXMySQLDB
//------------------------------------------------------------------------------
?>

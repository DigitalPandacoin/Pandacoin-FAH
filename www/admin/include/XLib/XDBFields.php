<?php
//-------------------------------------------------------------
/* 
 *	include/XDBFields.php
 * 
 * Examples:
$dbF = new TXDBFields();
$dbF->Add(XDB_USER_ID, 'INT UNSIGNED NOT NULL');
$dbF->Add(XDB_USER_USER	'VARCHAR(25) NOT NULL');

"CREATE TABLE IF NOT EXISTS myTable ".$dbF->GetFieldDeclarations();
"DROP TABLE IF EXISTS myTable"

$dbF->ClearValues();
$dbF->SetValue('id', '1');
"UPDATE myTable SET ".$xdbContentFields->GetValueAssignments()." WHERE id='$id'"
"INSERT INTO myTable ".$xdbContentFields->GetNameListString()." VALUES ".$xdbContentFields->GetValueListString();
"ALTER TABLE ".XDB_CONTENT_TABLE." AUTO_INCREMENT = 1"; // sets auto inc to highest index +1 (does not set to 1)
*/
//-------------------------------------------------------------
class TXDBField
{
	var $Index;
	var $Name;
	var $Types;
	var $MaxSize = 0;
	var $Value = false;
	var $ValueSet = false;
	function __construct($tIdx, $tName, $tTypes, $tMaxSize = 0)
	{
		$this->Index = $tIdx;
		$this->Name = $tName;
		$this->Types = $tTypes;
		$this->MaxSize = $tMaxSize;
	}
	function sqlValue()
	{
		if ($this->ValueSet === false || is_null($this->Value))
			return "NULL";
		if (is_bool($this->Value))
			return ($this->Value ? "1" : "0");
		if (is_numeric($this->Value))
			return "".$this->Value;
		return "'".$this->Value."'";
	}
} // class TXDBField
//-------------------------------------------------------
class TXDBFields
{
	var $tableVersion = 0;
	var $tableName = "";
	var $Fields = array();
	function __construct($tTableName, $tTableVersion = 0)
	{
		$this->tableName = $tTableName;
		$this->tableVersion = $tTableVersion;
	}
	function Add($tName, $tTypes, $tMaxSize = 0)
	{
		$this->Fields[] = new TXDBField(sizeof($this->Fields), $tName, $tTypes, $tMaxSize) or die("Create object failed");
	}
	function ClearValues()
	{
		foreach ($this->Fields as $f)
		{
			$f->ValueSet = false;
			$f->Value = NULL;
		}
	}
	function SetValues()
	{
		foreach ($this->Fields as $f)
		{
			$f->ValueSet = true;
			$f->Value = NULL;
		}
	}
	function ClearValue($tName)
	{
		$f = $this->GetByName($tName);
		if ($f === false)
		{
			die("XDBFields.php - ClearValue GetByName, failed Name: $tName");
		}
		$f->Value = NULL;
		$f->ValueSet = false;
		return true;
	}
	function SetValue($tName, $tValue = NULL)
	{
		$f = $this->GetByName($tName);
		if ($f === false)
		{
			die("XDBFields.php - SetValue GetByName, failed Name: $tName");
		}
		$f->Value = $tValue;
		$f->ValueSet = true;
		return true;
	}
	function GetByName($tName)
	{
		foreach ($this->Fields as $f)
			if ($tName == $f->Name)
				return $f;
		return false;
	}
	function GetMaxSize($tName)
	{
		foreach ($this->Fields as $f)
			if ($tName == $f->Name)
				return $f->MaxSize;
		return false;
	}
	function GetNameArray($SkipNotSet = false)
	{
		$a = array();
		if (!$SkipNotSet)
			foreach ($this->Fields as $f)
				$a[] = $f->Name;
		else
			foreach ($this->Fields as $f)
				if ($f->ValueSet) $a[] = $f->Name;
		return $a;
	}
	function GetNameListString($SkipNotSet = false) // for use with INSERT
	{
		return implode(',',$this->GetNameArray($SkipNotSet));
	}
	function GetValueArray($SkipNotSet = false)
	{
		$a = array();
		foreach ($this->Fields as $f)
		{
			if (is_object($f->Value))
			{
				die("XDBFields - GetValueArray non object error");
			}
			if ($f->ValueSet) $a[] = $f->sqlValue();
			elseif (!$SkipNotSet) $a[] = "''";
		}
		return $a;
	}
	function GetValueListString($SkipNotSet = false) // for use with UPDATE
	{
		return implode(',',$this->GetValueArray($SkipNotSet));
	}
	function GetValueAssignments($SkipNotSet = false)
	{
		$a = array();
		foreach ($this->Fields as $f)
			if ($SkipNotSet === false || $f->ValueSet)
				$a[] = $f->Name."=".$f->sqlValue();
		return implode(',', $a);
	}
	function GetFieldDeclarations()
	{
		$a = array();
		foreach ($this->Fields as $f)
			$a[] = $f->Name." ".$f->Types;
		return '('.implode(',', $a).')';
	}
	function scriptCreateTable($ifNotExists = true)
	{
		return "CREATE TABLE ".($ifNotExists === true ? "IF NOT EXISTS " : "").$this->tableName." ".$this->GetFieldDeclarations();
	}
	function scriptDropTable($ifNotExists = true)
	{
		return "DROP TABLE ".($ifNotExists == true ? "IF EXISTS " : "").$this->tableName;
	}
	function scriptInsert()
	{
		return "INSERT INTO ".$this->tableName." (".$this->GetNameListString(true/*SkipNotSet*/).") VALUES (".$this->GetValueListString(true/*SkipNotSet*/).")";
	}
	function scriptUpdate($where = false, $SkipNotSet = true)
	{
		return "UPDATE ".$this->tableName." SET ".$this->GetValueAssignments($SkipNotSet).($where !== false ? " WHERE ".$where : "");
	}
	function scriptSelect($where = false, $orderby = false, $limit = false, $SkipNotSet = true)
	{
		return "SELECT ".$this->GetNameListString(true/*SkipNotSet*/)." FROM ".$this->tableName.($where !== false ? " WHERE $where" : "").($orderby !== false ? " ORDER BY $orderby" : "").($limit !== false ? " LIMIT $limit" : "");
	}
	function scriptDelete($where = false)
	{
		return "DELETE FROM ".$this->tableName.($where !== false ? " WHERE $where" : "");
	}
} // class TXDBFields
//-------------------------------------------------------------
?>

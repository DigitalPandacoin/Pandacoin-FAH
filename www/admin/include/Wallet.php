<?php
/*
 *	www/include/Wallet.php
 * 
 * 
* 
*/
//---------------
define('RPC_DEBUG_FAIL', true);
define('RPC_DEBUG_SUCCESS', false);
define('RPC_DEBUG_CREDENTIALS', false);
//---------------
define('DEF_CFG_WALLET_MIN_CONF', 3);
define('DEF_CFG_WALLET_EST_FEE', 5);
define('DEF_CFG_WALLET_EST_CONT_FEE', 2);
//---------------
define('CFG_WALLET_ACTIVE_ACCOUNT',	'wallet-active-account');
define('CFG_WALLET_MIN_CONF',		'wallet-min-conf');
define('CFG_WALLET_EST_FEE',		'wallet-est-fee');
define('CFG_WALLET_EST_CONT_FEE',	'wallet-est-cont-fee');
//---------------
define('WALLET_AUTO_MODE_NONE', 		0);
define('WALLET_AUTO_MODE_FLAT', 		1);
define('WALLET_AUTO_MODE_PERCENT', 		2);
//---------------
define('WALLET_AUTO_FLAG_NONE', 		0);
define('WALLET_AUTO_FLAG_REQUIRED', 	1);
//------------------------ User defined special log type
function XLogRPC($mess)
{
	XLogSpecial($mess, 'RPC'); // from XLoggerInstance.php
}
//---------------
require_once('jsonRPCClient.php');
//------------------
class Wallet
{
	var $clientSession = false;
	var $account = false;
	var $minConf = DEF_CFG_WALLET_MIN_CONF; // default
	var $estfee  = DEF_CFG_WALLET_EST_FEE; // default
	var $estcontfee  = DEF_CFG_WALLET_EST_CONT_FEE; // default
	//------------------
	function Init()
	{
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		$this->minConf = $Config->Get(CFG_WALLET_MIN_CONF);
		if ($this->minConf === false)
		{
			$this->minConf = DEF_CFG_WALLET_MIN_CONF; // default
			if (!$Config->Set(CFG_WALLET_MIN_CONF, $this->minConf))
			{
				XLogError("Wallet::Init Config Set minConf failed");
				return false;
			}
		}
		//------------------
		$this->estfee = $Config->Get(CFG_WALLET_EST_FEE);
		if ($this->estfee === false)
		{
			$this->estfee = DEF_CFG_WALLET_EST_FEE; // default
			if (!$Config->Set(CFG_WALLET_EST_FEE, $this->estfee))
			{
				XLogError("Wallet::Init Config Set estfee failed");
				return false;
			}
		}
		//------------------
		$this->estcontfee = $Config->Get(CFG_WALLET_EST_CONT_FEE);
		if ($this->estcontfee === false)
		{
			$this->estcontfee = DEF_CFG_WALLET_EST_CONT_FEE; // default
			if (!$Config->Set(CFG_WALLET_EST_CONT_FEE, $this->estcontfee))
			{
				XLogError("Wallet::Init Config Set estcontfee failed");
				return false;
			}
		}
		//------------------
		$this->loadActiveAccount();
		//------------------
		return true;
	}
	//------------------
	function isValidAddressQuick($address)
	{        
		if (!function_exists("bcmul"))
		{
			XLogError("Wallet::isValidAddressQuick Required PHP dependency BC Math not found.");
			return false;
		}
		if (strlen($address) > C_MAX_WALLET_ADDRESS_LENGTH/*DatabaseDefines.php*/)
			return false;
		$addr = $this->decodeBase58($address);
		if (strlen($addr) != 50)
		  return false;
		$check = substr($addr, 0, strlen($addr) - 8);
		$check = pack("H*", $check);
		$check = strtoupper(hash("sha256", hash("sha256", $check, true)));
		$check = substr($check, 0, 8);
		return $check == substr($addr, strlen($addr) - 8);
	}
	//------------------
	function bcDecToHex($dec)
	{
		$hexchars = "0123456789ABCDEF";
		$val = "";
		while (bccomp($dec, 0) == 1)
		{
			$dv = (string) bcdiv($dec, "16", 0);
			$rem = (integer) bcmod($dec, "16");
			$dec = $dv;
			$val = $val.$hexchars[$rem];
		}
		return strrev($val);
	}
	//------------------
	function decodeBase58($base58)
	{
		$origbase58 = $base58;    
		$base58chars = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz"; 
		$val = "0";
		for ($i = 0; $i < strlen($base58); $i++)
		{
		  $current = (string) strpos($base58chars, $base58[$i]);
		  $val = (string) bcmul($val, "58", 0);
		  $val = (string) bcadd($val, $current, 0);
		}
		$val = $this->bcDecToHex($val);
		//leading zeros
		for ($i = 0; $i < strlen($origbase58) && $origbase58[$i] == "1"; $i++)
		  $val = "00" . $val;
		if (strlen($val) % 2 != 0)
		  $val = "0" . $val;
		return $val;
	}
	//------------------
	function JSONtoAmount($value) 
	{
		// see https://en.bitcoin.it/wiki/Proper_Money_Handling_(JSON-RPC)#PHP
		return round($value * 1e8);
	}	
	//------------------
	function getClient()
	{
		//------------------
		if ($this->clientSession === false)
			$this->clientSession = new jsonRPCClient('http://'.WALLET_RPC_USER.':'.WALLET_RPC_PASS.'@'.WALLET_RPC_HOST.':'.WALLET_RPC_PORT.'/'); 
		//------------------
		return $this->clientSession;
	}
	//------------------
	function execWallet($command, $paramArray = array(), $enableDebug = false)
	{
		//------------------
		$client = $this->getClient();
		//------------------
		try
		{
			//------------------
			if ( $enableDebug  || RPC_DEBUG_SUCCESS || RPC_DEBUG_FAIL)
				$client->debug = true;
			//------------------
			$result = $client->__call($command, $paramArray);
			//------------------
			XLogRPC("Request: $command ".XVarDump($paramArray)." ## Result: ".XVarDump($result));
			//------------------
		}
		catch (exception $e)
		{
			//------------------
			$UnableConnectString = "exception 'Exception' with message 'Unable to connect to";
			//------------------
			if (!RPC_DEBUG_CREDENTIALS)
				if (strncmp($e, $UnableConnectString, strlen($UnableConnectString)) == 0)
					$e = "Unable to connect to RPC server"; // scrub credentials from log
			//------------------
			XLogNotify("Wallet::execWallet command: '$command', params: '".XVarDump($paramArray, false/*flatten*/)."', debug: '$client->debug', failed with exception: $e");
			//------------------
			return false;
		}
		//------------------
		if ($enableDebug || RPC_DEBUG_SUCCESS)
			XLogNotify("execWallet success debug: $client->debug");
		//------------------
		return $result;		
	}
	//------------------
	function getInfo()
	{
		//------------------
		return $this->execWallet("getinfo");
	}
	//------------------
	function getBalance($account = false, $minconf = false)
	{
		//------------------
		if ($account === false)
			$account = $this->getActiveAccount();
		//------------------
		if ($minconf === false)
			$minconf = $this->minConf;
		//------------------
		if ($account === false)
			$params = array((int)$minconf);
		else
		{
			$account .= ""; // ensure string type
			$params = array($account, (int)$minconf);
		}
		//------------------
		$result = $this->execWallet("getbalance", $params);
		//------------------
		return $result; // $this->JSONtoAmount($result);
	}
	//------------------
	function listAccounts()
	{
		//------------------
		return $this->execWallet("listaccounts");
	}
	//------------------
	function send($fromAccount, $toAddress, $amount, $minconf = false, $comment = false)
	{
		//------------------
		if ($fromAccount === false)
			$fromAccount = $this->getActiveAccount();
		//------------------
		if ($minconf === false)
			$minconf = $this->minConf;
		//------------------
		if (!is_numeric($amount))
		{
			XLogError("Wallet::send validate amount is numeric failed. Amount: ".XVarDump($amount));
			return false;
		}
		//------------------
		$amount += 0.0; // ensure float
		$fromAccount .= ""; // ensure string type
		$toAddress .= "";// ensure string type
		$params = array($fromAccount, $toAddress, $amount, (int)$minconf);
		//------------------
		if ($comment !== false)
			$params[] = $comment;
		//------------------
		$result = $this->execWallet("sendfrom", $params, true/*enableDebug*/);
		//------------------
		return $result;
	}
	//------------------
	function sendMany($fromAccount, $addressValues, $minconf = false, $comment = false)
	{
		//------------------
		if ($fromAccount === false)
			$fromAccount = $this->getActiveAccount();
		//------------------
		if ($minconf === false)
			$minconf = $this->minConf;
		//------------------
		if (sizeof($addressValues) == 0)
		{
			XLogError("Wallet::sendMany addressValues is empty");
			return false;
		}
		//------------------
		$fromAccount .= ""; // ensure string type
		$params = array($fromAccount, $addressValues, (int)$minconf);
		//------------------
		if ($comment !== false)
			$params[] = $comment;
		//------------------
		$result = $this->execWallet("sendmany", $params, true/*enableDebug*/);
		//------------------
		return $result;
	}
	//------------------
	function getNewAddress($account = false)
	{
		//------------------
		if ($account === false)
			$account = $this->getActiveAccount();
		//------------------
		if ($account === false)
			$params = array();
		else
		{
			$account .= ""; // ensure string type
			$params = array($account);
		}
		//------------------
		$result = $this->execWallet("getnewaddress", $params);
		//------------------
		return $result; 
	}
	//------------------
	function getAccountAddress($account = false)
	{
		//------------------
		if ($account === false)
			$account = $this->getActiveAccount();
		//------------------
		$account .= ""; // ensure string type
		$params = array($account);
		//------------------
		$result = $this->execWallet("getaddressesbyaccount", $params);
		//------------------
		return $result; 
	}
	//------------------
	function move($fromAccount, $toAccount, $amount, $minconf = false, $comment = false)
	{
		//------------------
		if ($minconf === false)
			$minconf = $this->minConf;
		//------------------
		if (!is_numeric($amount))
		{
			XLogError("Wallet::move validate amount is numeric failed. Amount: ".XVarDump($amount));
			return false;
		}
		//------------------
		$amount += 0.0; // ensure float
		$fromAccount .= ""; // ensure string type
		$toAccount .= "";// ensure string type
		$params = array($fromAccount, $toAccount, $amount, (int)$minconf);
		//------------------
		if ($comment !== false)
			$params[] = $comment;
		//------------------
		$result = $this->execWallet("move", $params);
		//------------------
		return $result; 
	}
	//------------------
	function setFee($fee)
	{
		//------------------
		if (!is_numeric($fee))
		{
			XLogError("Wallet::setFee validate that fee is numberic failed: '$fee'");
			return false;
		}
		//------------------
		$params = array((double)$fee);
		//------------------
		$result = $this->execWallet("settxfee", $params);
		//------------------
		return $result; 
	}
	//------------------
	function getFee()
	{
		//------------------
		$result = $this->getInfo();
		if ($result === false)
		{
			XLogError("Wallet::getFee getInfo failed");
			return false;
		}
		//------------------
		if (!isset($result["paytxfee"]))
		{
			XLogError("Wallet::getFee paytxfee not found in result");
			return false;
		}
		//------------------
		return $result["paytxfee"];
	}
	//------------------
	function getTotalFees()
	{
		//------------------
		$result = $this->getInfo();
		if ($result === false)
		{
			XLogError("Wallet::getTotalFees getInfo failed");
			return false;
		}
		//------------------
		if (!isset($result["paytxfee"]))
		{
			XLogError("Wallet::getTotalFees paytxfee not found in result");
			return false;
		}
		//------------------
		$rlFee = (isset($result["relayfee"]) ? $result["relayfee"] : "0.0");
		//------------------
		$txFee = $result["paytxfee"];
		$totFee = bcadd($txFee, $rlFee, 8);
		//------------------
		return $totFee;
	}
	//------------------
	function getEstFee()
	{
		//------------------
		return $this->estfee;
	}
	//------------------
	function setEstFee($estFee)
	{
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		if (!is_numeric($estFee))
		{
			XLogError("Wallet::setEstFee validate estfee is numeric failed");
			return false;
		}
		//------------------
		$this->estfee = $estFee;
		if (!$Config->Set(CFG_WALLET_EST_FEE, $this->estfee))
		{
			XLogError("Wallet::setEstFee Config Set estfee failed");
			return false;
		}
		//------------------
		return true;
	}
	//------------------
	function getEstContFee()
	{
		//------------------
		return $this->estcontfee;
	}
	//------------------
	function setEsContFee($estContFee)
	{
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		if (!is_numeric($estContFee))
		{
			XLogError("Wallet::setEsContFee validate estContFee is numeric failed");
			return false;
		}
		//------------------
		$this->estcontfee = $estContFee;
		if (!$Config->Set(CFG_WALLET_EST_CONT_FEE, $this->estcontfee))
		{
			XLogError("Wallet::setEsContFee Config Set estcontfee failed");
			return false;
		}
		//------------------
		return true;
	}
	//------------------
	function loadActiveAccount()
	{
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		$this->account = $Config->Get(CFG_WALLET_ACTIVE_ACCOUNT, ""/*default blank*/);
		//------------------
		return $this->account;
	}
	//------------------
	function getActiveAccount()
	{
		//------------------
		if ($this->account === false)
			return $this->loadActiveAccount();
		//------------------
		return $this->account;
	}
	//------------------
	function setActiveAccount($account)
	{
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		if (!$Config->Set(CFG_WALLET_ACTIVE_ACCOUNT, $account))
		{
			XLogError("Wallet::setActiveAccount Config set failed");
			return false;
		}
		//------------------
		$this->account = $account;
		//------------------
		return true;
	}
	//------------------
	function getMinConf()
	{
		//------------------
		return $this->minConf;
	}
	//------------------
	function setMinConf($minConf)
	{
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		if (!$Config->Set(CFG_WALLET_MIN_CONF, $minConf))
		{
			XLogError("Wallet::setMinConf Config Set minConf failed");
			return false;
		}
		//------------------
		$this->minConf = $minConf;
		//------------------
		return true;
	}
	//------------------
 	function validateAddress($address)
	{        
		//------------------
		$address .= ""; // ensure string type
		$params = array($address);
		//------------------
		$result = $this->execWallet("validateaddress", $params);
		//------------------
		if ($result === false)
		{
			XLogError("Wallet::validateAddress execWallet failed");
			return false;
		}
		//------------------
		if (!isset($result["isvalid"]))
		{
			XLogError("Wallet::validateAddress isvalid not found in result");
			return false;
		}
		//------------------
		return ($result["isvalid"] == true ? $address : "");
	}
	//------------------
 	function isValidAddress($address)
	{        
		//------------------
		if (!$this->isValidAddressQuick($address))
			return "";
		//------------------
		$result = $this->validateAddress($address);
		//------------------
		return $result;
	}
	//------------------
}// class Wallet
?>

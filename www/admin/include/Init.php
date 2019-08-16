<?php
//--------------------------- Wallet
define('WALLET_RPC_HOST', "localhost"); 
define('WALLET_RPC_PORT', ""); // Pandacoin default 22555
define('WALLET_RPC_USER', ""); // @CHANGEME
define('WALLET_RPC_PASS', ""); // @CHANGEME
//--------------------------- Database
define('XDATABASE_HOST', "localhost");
define('XDATABASE_NAME', ""); // @CHANGEME
define('XDATABASE_USER', "root"); // @CHANGEME
define('XDATABASE_PASS', ""); // @CHANGEME
//--------------------------- Branding
define('BRAND_FOLDING_TEAM_ID', 234317); // @CUSTOMIZE folding team ID
define('BRAND_ADDRESS_LINK', 'https://chainz.cryptoid.info/pnd/address.dws?'); // @CUSTOMIZE block explorer link for address (address is appended)
define('BRAND_TX_LINK', 'https://chainz.cryptoid.info/pnd/tx.dws?'); // @CUSTOMIZE block explorer link for transaction (txid is appended)
define('BRAND_UNIT', 'PND'); // @CUSTOMIZE currency unit
define('BRAND_COPYRIGHT', 'Pandacoin F@H (c) 2019'); // @CUSTOMIZE used in footer template
define('BRAND_TX_COMMENT', "Pandacoin F@H round "); // @CUSTOMIZE comment added to transaction (round_id and round_start_date are appended)
define('BRAND_DB_PREFIX', "pndfah_"); // @CUSTOMIZE 
define('X_TITLE', "Pandacoin F@H"); // @CUSTOMIZE 
define('X_SESSION_NAME', 'Pandacoinfah'); // @CUSTOMIZE used for session id
//---------------------------
define('MYSQL_REAL_ESCAPE', true);
define('XIS_DEBUG', true);
//---------------------------
if (!defined('XDATABASE_HOST') || !defined('XDATABASE_NAME') || !defined('XDATABASE_USER') || !defined('XDATABASE_PASS'))
{
	echo "<div style='color : red;'>Database credentials not specified in include/Init.php. XDATABASE_HOST, XDATABASE_NAME, XDATABASE_USER, XDATABASE_PASS are required.</div>\r";
	exit;
}
if (!defined('WALLET_RPC_HOST') || !defined('WALLET_RPC_USER') || !defined('WALLET_RPC_PASS'))
{
	echo "<div style='color : red;'>Coin wallet RPC credentials not specified in include/Init.php. WALLET_RPC_HOST, WALLET_RPC_USER, WALLET_RPC_USER are required.</div>\r";
	exit;
}
//---------------------------
define('XLOG_MAX_LEVEL', 4); // 0 All, 1 Error, 2 Warn, 3 User, 4 Notify, 5 Debug
//---------------------------
define('XLOG_FILENAME', './log/log.txt');
define('XLOG_TIMEZONE', 'America/Chicago');
define('XLOG_COMBINED', true); // creates ALL log file with log entries from all levels combined
define('XLOG_SEPERATE', true); // creaes seperate log files for each debug level
//define('XLOG_LABEL', ''); // main label to put on every log entry after the timestamp if any, default none (level labels are automatic)
//define('XLOG_TIMESTAMP_FORMAT', 'm/d/Y G:i'); // default 'm/d/Y G:i'
define('XLOG_ARCHIVE_FILESIZE', 2500000); // bytes
//define('XLOG_ARCHIVE_DIR', './log/archive'); // defaults to 'archive' subdirectory of XLOG_FILENAME's directory
//define('XLOG_LINE_DELIMITTER', '/n'); // defaults to /n
//------------------------
define('XINCLUDE_DIR', './include/XLib/');
require('./include/XLib/XInit.php');
//------------------------
require('./include/Version.php');
require('./include/DatabaseDefines.php');
require('./include/Config.php');
require('./include/Template.php');
require('./include/Workers.php');
require('./include/Wallet.php');
require('./include/Rounds.php');
require('./include/Payouts.php');
require('./include/Stats.php');
require('./include/Automation.php');
require('./include/Privileges.php');
require('./include/Contributions.php');
require('./include/Ads.php');
require('./include/Display.php');
require('./include/FahClient.php');
require('./include/Team.php');
//------------------------
//XLogDebug("Init.php magic quotes gpc ".get_magic_quotes_gpc()." runtime ".get_magic_quotes_runtime());
//---------------------------------
?>

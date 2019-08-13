<?php
//---------------
/*
 *	include/DatabaseDefines.php
 * 
 *  
 * 
*/
//---------------
define('DB_TABLE_PREFIX', BRAND_DB_PREFIX);
define('C_MAX_NAME_TEXT_LENGTH', 	 80);
define('C_MAX_TITLE_TEXT_LENGTH', 	120);
define('C_MAX_COMMENT_TEXT_LENGTH', 200);
define('C_MAX_DATE_TEXT_LENGTH', 	120);
define('C_MAX_CONFIG_VALUE_LENGTH', 120);
define('C_MAX_WALLET_ADDRESS_LENGTH', 40); //  26-35, but extra big
define('C_MAX_TRANSACTION_ID_LENGTH', 80); // 64, but extra big
define('MYSQL_DATETIME_FORMAT', "Y-m-d H:i:s");
define('C_MAX_AD_LINK_TEXT_LENGTH', 	120);
define('C_MAX_AD_TEXT_LENGTH', 	 		120);
define('C_MAX_AD_IMAGE_TEXT_LENGTH', 	80);

//--------------- tables
define('DB_CONFIG',   				DB_TABLE_PREFIX.'config');
define('DB_TEAM',   				DB_TABLE_PREFIX.'team');
define('DB_TEAM_USERS',   			DB_TABLE_PREFIX.'tusers');
define('DB_WORKERS',   				DB_TABLE_PREFIX.'workers');
define('DB_ROUND',   				DB_TABLE_PREFIX.'round');
define('DB_PAYOUT',   				DB_TABLE_PREFIX.'payout');
define('DB_STATS',   				DB_TABLE_PREFIX.'stats');
define('DB_CONTRIBUTION',			DB_TABLE_PREFIX.'contribs');
define('DB_ADS',					DB_TABLE_PREFIX.'ads');
//--------------- DB_CONFIG
define('DB_CFG_VER', 	1);
define('DB_CFG_NAME', 	'name');
define('DB_CFG_VALUE', 	'value');
//--------------- DB_TEAM
define('DB_TEAM_VER', 				1);
define('DB_TEAM_ID', 				'id');
define('DB_TEAM_DATE', 				'date');
define('DB_TEAM_RANK', 				'rank');
define('DB_TEAM_TEAMS', 			'teams');
define('DB_TEAM_CREDIT', 			'credit');
define('DB_TEAM_WUS', 				'wus');
define('DB_TEAM_ACTIVE50', 			'active_50');
define('DB_TEAM_ACTIVEDONORS', 		'active_donors');
//--------------- DB_TEAM_USERS
define('DB_TEAM_USERS_VER', 				1);
define('DB_TEAM_USERS_ID', 				'id');
define('DB_TEAM_USERS_DATE',			'dt');
define('DB_TEAM_USERS_USER_ID',			'uid');
define('DB_TEAM_USERS_POINTS',			'pt');
define('DB_TEAM_USERS_WUS', 			'wu');
//--------------- DB_WORKERS
define('DB_WORKER_VER', 		2);
define('DB_WORKER_ID', 			'id');
define('DB_WORKER_USER_NAME', 	'name');
define('DB_WORKER_DATE_CREATED', 	'created');
define('DB_WORKER_DATE_UPDATED',	'updated');
define('DB_WORKER_UPDATED_BY',		'updatedby');
define('DB_WORKER_PAYOUT_ADDRESS',	'payaddr');
define('DB_WORKER_VALID_ADDRESS',	'valaddr');
define('DB_WORKER_DISABLED',		'disabled');
define('DB_WORKER_ACTIVITY',		'activity');
//--------------- DB_ROUND
define('DB_ROUND_VER', 				2);
define('DB_ROUND_ID', 				'id');
define('DB_ROUND_COMMENT', 			'comment');
define('DB_ROUND_DATE_STARTED', 	'started');
define('DB_ROUND_DATE_FAH_STATS_REQUESTED', 	'fahstatsreq');
define('DB_ROUND_DATE_FAH_STATS_DONE', 	'fahstatsdone');
define('DB_ROUND_DATE_STATS_REQUESTED', 	'statsreq');
define('DB_ROUND_DATE_STATS_DONE', 	'statsdone');
define('DB_ROUND_DATE_CONT_REQUESTED', 'contreq');
define('DB_ROUND_DATE_CONT_DONE', 		'contdone');
define('DB_ROUND_DATE_PAY_REQUESTED','payreq');
define('DB_ROUND_DATE_PAID', 		'paid');
define('DB_ROUND_STATS_MODE', 		'statsmode');
define('DB_ROUND_TEAM_ID',			'teamid');
define('DB_ROUND_PAY_MODE', 		'paymode');
define('DB_ROUND_PAY_RATE', 		'payrate');
define('DB_ROUND_TOTAL_WORK', 		'totwork');
define('DB_ROUND_TOTAL_PAY', 		'totpay');
define('DB_ROUND_APPROVED', 		'approved');
define('DB_ROUND_FUNDED', 			'funded');
//--------------- DB_PAYOUT
define('DB_PAYOUT_VER', 				1);
define('DB_PAYOUT_ID', 				'id');
define('DB_PAYOUT_DATE_CREATED',	'dtcreated');
define('DB_PAYOUT_DATE_PAID',		'dtpaid');
define('DB_PAYOUT_ROUND', 			'ridx');
define('DB_PAYOUT_WORKER', 			'widx');
define('DB_PAYOUT_ADDRESS', 		'addr');
define('DB_PAYOUT_PAY', 		    'pay');
define('DB_PAYOUT_TXID', 		    'tx');
//--------------- DB_CONTRIBUTION 
define('DB_CONTRIBUTION_VER', 		3);
define('DB_CONTRIBUTION_ID', 		'id');
define('DB_CONTRIBUTION_DATE_CREATED',	'dtcreated');
define('DB_CONTRIBUTION_DATE_DONE',	'dtdone');
define('DB_CONTRIBUTION_OUTCOME',	'outcome');
define('DB_CONTRIBUTION_ROUND', 	'ridx');
define('DB_CONTRIBUTION_NUMBER', 	'num');
define('DB_CONTRIBUTION_NAME', 		'name');
define('DB_CONTRIBUTION_MODE', 		'mode');
define('DB_CONTRIBUTION_ACCOUNT',	'acc');
define('DB_CONTRIBUTION_VALUE',	    'value');
define('DB_CONTRIBUTION_FLAGS',	    'flags');
define('DB_CONTRIBUTION_TXID',	    'txid');
define('DB_CONTRIBUTION_AD',		'ad');
//--------------- DB_ADS
define('DB_ADS_VER',		1);
define('DB_ADS_ID', 		'id');
define('DB_ADS_MODE',	'admode');
define('DB_ADS_TEXT',	'adtext');
define('DB_ADS_LINK',	'adlink');
define('DB_ADS_IMAGE',	'adimage');
define('DB_ADS_STYLE',	'adstyle');
//--------------- DB_STATS
define('DB_STATS_VER', 				2);
define('DB_STATS_ID', 				'id');
define('DB_STATS_DATE_CREATED',		'dtcreated');
define('DB_STATS_DATE_POLLED',		'dtpoll');
define('DB_STATS_MODE', 			'mode');
define('DB_STATS_ROUND', 			'ridx');
define('DB_STATS_PAYOUT', 			'pidx');
define('DB_STATS_WORKER', 			'widx');
define('DB_STATS_WORKER_USER_ID',	'wuid');
define('DB_STATS_WORK', 			'work');
define('DB_STATS_TEAM', 			'team');
define('DB_STATS_TEAM_RANK', 		'teamrank');
define('DB_STATS_RANK', 			'rank');
define('DB_STATS_WEEK_POINTS', 		'weekpoints');
define('DB_STATS_AVG_POINTS', 		'avgpoints');
define('DB_STATS_TOTAL_POINTS', 	'totpoints');
define('DB_STATS_WUS', 				'wus'); // work units 
//--------------- DB_CONFIG fields class
$dbConfigFields = new TXDBFields(DB_CONFIG, DB_CFG_VER);
$dbConfigFields->Add(DB_CFG_NAME,	'varchar('.C_MAX_NAME_TEXT_LENGTH.') NOT NULL PRIMARY KEY');
$dbConfigFields->Add(DB_CFG_VALUE,	'varchar('.C_MAX_CONFIG_VALUE_LENGTH.')');
//--------------- DB_TEAM fields class
$dbTeamFields = new TXDBFields(DB_TEAM, DB_TEAM_VER);
$dbTeamFields->Add(DB_TEAM_ID, 				'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
$dbTeamFields->Add(DB_TEAM_DATE,			'DATETIME');
$dbTeamFields->Add(DB_TEAM_RANK,			'INT UNSIGNED');
$dbTeamFields->Add(DB_TEAM_TEAMS,			'INT UNSIGNED');
$dbTeamFields->Add(DB_TEAM_CREDIT,			'INT UNSIGNED');
$dbTeamFields->Add(DB_TEAM_WUS,				'INT UNSIGNED');
$dbTeamFields->Add(DB_TEAM_ACTIVE50,		'INT UNSIGNED');
$dbTeamFields->Add(DB_TEAM_ACTIVEDONORS,	'INT UNSIGNED');
//--------------- DB_TEAM_USERS fields class
$dbTeamUsersFields = new TXDBFields(DB_TEAM_USERS, DB_TEAM_USERS_VER);
$dbTeamUsersFields->Add(DB_TEAM_USERS_ID, 				'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
$dbTeamUsersFields->Add(DB_TEAM_USERS_DATE,				'DATETIME');
$dbTeamUsersFields->Add(DB_TEAM_USERS_USER_ID, 			'INT UNSIGNED');
$dbTeamUsersFields->Add(DB_TEAM_USERS_POINTS,			'INT UNSIGNED');
$dbTeamUsersFields->Add(DB_TEAM_USERS_WUS,				'INT UNSIGNED');
//--------------- DB_WORKERS fields class
$dbWorkerFields = new TXDBFields(DB_WORKERS, DB_WORKER_VER);
$dbWorkerFields->Add(DB_WORKER_ID, 	'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
$dbWorkerFields->Add(DB_WORKER_USER_NAME,	'varchar('.C_MAX_NAME_TEXT_LENGTH.')');
$dbWorkerFields->Add(DB_WORKER_DATE_CREATED,	'varchar('.C_MAX_DATE_TEXT_LENGTH.') NOT NULL', C_MAX_DATE_TEXT_LENGTH);
$dbWorkerFields->Add(DB_WORKER_DATE_UPDATED,	'varchar('.C_MAX_DATE_TEXT_LENGTH.')', C_MAX_DATE_TEXT_LENGTH);
$dbWorkerFields->Add(DB_WORKER_UPDATED_BY,		'varchar('.(C_MAX_NAME_TEXT_LENGTH + 5).')'); // extra room to prepend user's ID
$dbWorkerFields->Add(DB_WORKER_PAYOUT_ADDRESS,	'varchar('.C_MAX_WALLET_ADDRESS_LENGTH.')');
$dbWorkerFields->Add(DB_WORKER_VALID_ADDRESS,	'TINYINT(1)');
$dbWorkerFields->Add(DB_WORKER_DISABLED,		'TINYINT(1)');
$dbWorkerFields->Add(DB_WORKER_ACTIVITY,		'INT');
//--------------- DB_ROUND fields class
$dbRoundFields = new TXDBFields(DB_ROUND, DB_ROUND_VER);
$dbRoundFields->Add(DB_ROUND_ID, 	'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
$dbRoundFields->Add(DB_ROUND_COMMENT,	'varchar('.C_MAX_COMMENT_TEXT_LENGTH.')', C_MAX_COMMENT_TEXT_LENGTH);
$dbRoundFields->Add(DB_ROUND_DATE_STARTED,	'varchar('.C_MAX_DATE_TEXT_LENGTH.') NOT NULL', C_MAX_DATE_TEXT_LENGTH);
$dbRoundFields->Add(DB_ROUND_DATE_FAH_STATS_REQUESTED,	'varchar('.C_MAX_DATE_TEXT_LENGTH.')', C_MAX_DATE_TEXT_LENGTH);
$dbRoundFields->Add(DB_ROUND_DATE_FAH_STATS_DONE,	'varchar('.C_MAX_DATE_TEXT_LENGTH.')', C_MAX_DATE_TEXT_LENGTH);
$dbRoundFields->Add(DB_ROUND_DATE_STATS_REQUESTED,	'varchar('.C_MAX_DATE_TEXT_LENGTH.')', C_MAX_DATE_TEXT_LENGTH);
$dbRoundFields->Add(DB_ROUND_DATE_STATS_DONE,	'varchar('.C_MAX_DATE_TEXT_LENGTH.')', C_MAX_DATE_TEXT_LENGTH);
$dbRoundFields->Add(DB_ROUND_DATE_CONT_REQUESTED,	'varchar('.C_MAX_DATE_TEXT_LENGTH.')', C_MAX_DATE_TEXT_LENGTH);
$dbRoundFields->Add(DB_ROUND_DATE_CONT_DONE,	'varchar('.C_MAX_DATE_TEXT_LENGTH.')', C_MAX_DATE_TEXT_LENGTH);
$dbRoundFields->Add(DB_ROUND_DATE_PAY_REQUESTED,	'varchar('.C_MAX_DATE_TEXT_LENGTH.')', C_MAX_DATE_TEXT_LENGTH);
$dbRoundFields->Add(DB_ROUND_DATE_PAID,	'varchar('.C_MAX_DATE_TEXT_LENGTH.')', C_MAX_DATE_TEXT_LENGTH);
$dbRoundFields->Add(DB_ROUND_STATS_MODE,	'INT UNSIGNED');
$dbRoundFields->Add(DB_ROUND_TEAM_ID,	'INT UNSIGNED');
$dbRoundFields->Add(DB_ROUND_PAY_MODE,	'INT UNSIGNED');
$dbRoundFields->Add(DB_ROUND_PAY_RATE,	'DOUBLE');
$dbRoundFields->Add(DB_ROUND_TOTAL_WORK,	'INT UNSIGNED');
$dbRoundFields->Add(DB_ROUND_TOTAL_PAY,	'DOUBLE');
$dbRoundFields->Add(DB_ROUND_APPROVED,	'TINYINT(1)');
$dbRoundFields->Add(DB_ROUND_FUNDED,	'TINYINT(1)');
//--------------- DB_PAYOUT fields class
$dbPayoutFields = new TXDBFields(DB_PAYOUT, DB_PAYOUT_VER);
$dbPayoutFields->Add(DB_PAYOUT_ID, 	'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
$dbPayoutFields->Add(DB_PAYOUT_DATE_CREATED,	'varchar('.C_MAX_DATE_TEXT_LENGTH.') NOT NULL', C_MAX_DATE_TEXT_LENGTH);
$dbPayoutFields->Add(DB_PAYOUT_DATE_PAID,	'varchar('.C_MAX_DATE_TEXT_LENGTH.')', C_MAX_DATE_TEXT_LENGTH);
$dbPayoutFields->Add(DB_PAYOUT_ROUND,	'INT UNSIGNED');
$dbPayoutFields->Add(DB_PAYOUT_WORKER,	'INT UNSIGNED');
$dbPayoutFields->Add(DB_PAYOUT_ADDRESS,	'varchar('.C_MAX_WALLET_ADDRESS_LENGTH.')');
$dbPayoutFields->Add(DB_PAYOUT_PAY,	'DOUBLE');
$dbPayoutFields->Add(DB_PAYOUT_TXID,	'varchar('.C_MAX_TRANSACTION_ID_LENGTH.')');
//--------------- DB_CONTRIBUTION fields class
$dbContributionFields = new TXDBFields(DB_CONTRIBUTION, DB_CONTRIBUTION_VER);
$dbContributionFields->Add(DB_CONTRIBUTION_ID, 		'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
$dbContributionFields->Add(DB_CONTRIBUTION_DATE_CREATED,	'varchar('.C_MAX_DATE_TEXT_LENGTH.')', C_MAX_DATE_TEXT_LENGTH);
$dbContributionFields->Add(DB_CONTRIBUTION_DATE_DONE,	'varchar('.C_MAX_DATE_TEXT_LENGTH.')', C_MAX_DATE_TEXT_LENGTH);
$dbContributionFields->Add(DB_CONTRIBUTION_OUTCOME,	'INT UNSIGNED');
$dbContributionFields->Add(DB_CONTRIBUTION_NUMBER,		'INT UNSIGNED');
$dbContributionFields->Add(DB_CONTRIBUTION_NAME,		'varchar('.C_MAX_TITLE_TEXT_LENGTH.')', C_MAX_TITLE_TEXT_LENGTH);
$dbContributionFields->Add(DB_CONTRIBUTION_ROUND,		'INT'); // -1 denotes default
$dbContributionFields->Add(DB_CONTRIBUTION_MODE,		'INT UNSIGNED');
$dbContributionFields->Add(DB_CONTRIBUTION_ACCOUNT,	'varchar('.C_MAX_WALLET_ADDRESS_LENGTH.')', C_MAX_WALLET_ADDRESS_LENGTH);
$dbContributionFields->Add(DB_CONTRIBUTION_VALUE,		'DOUBLE');
$dbContributionFields->Add(DB_CONTRIBUTION_FLAGS,		'INT UNSIGNED');
$dbContributionFields->Add(DB_CONTRIBUTION_TXID,	'varchar('.C_MAX_TRANSACTION_ID_LENGTH.')');
$dbContributionFields->Add(DB_CONTRIBUTION_AD,		'INT UNSIGNED');
//--------------- DB_ADS fields class
$dbAds = new TXDBFields(DB_ADS, DB_ADS_VER);
$dbAds->Add(DB_ADS_ID, 		'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
$dbAds->Add(DB_ADS_MODE,	'INT UNSIGNED');
$dbAds->Add(DB_ADS_TEXT,	'varchar('.C_MAX_AD_TEXT_LENGTH.')', C_MAX_AD_TEXT_LENGTH);
$dbAds->Add(DB_ADS_LINK,	'varchar('.C_MAX_AD_LINK_TEXT_LENGTH.')', C_MAX_AD_LINK_TEXT_LENGTH);
$dbAds->Add(DB_ADS_IMAGE,	'varchar('.C_MAX_AD_IMAGE_TEXT_LENGTH.')', C_MAX_AD_IMAGE_TEXT_LENGTH);
$dbAds->Add(DB_ADS_STYLE,	'varchar('.C_MAX_TITLE_TEXT_LENGTH.')', C_MAX_TITLE_TEXT_LENGTH);
//--------------- DB_STATS fields class
$dbStatsFields = new TXDBFields(DB_STATS, DB_STATS_VER);
$dbStatsFields->Add(DB_STATS_ID, 	'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
$dbStatsFields->Add(DB_STATS_DATE_CREATED,	'varchar('.C_MAX_DATE_TEXT_LENGTH.') NOT NULL', C_MAX_DATE_TEXT_LENGTH);
$dbStatsFields->Add(DB_STATS_DATE_POLLED,	'varchar('.C_MAX_DATE_TEXT_LENGTH.')', C_MAX_DATE_TEXT_LENGTH);
$dbStatsFields->Add(DB_STATS_MODE,	'INT UNSIGNED');
$dbStatsFields->Add(DB_STATS_ROUND,	'INT UNSIGNED');
$dbStatsFields->Add(DB_STATS_PAYOUT,'INT UNSIGNED');
$dbStatsFields->Add(DB_STATS_WORKER,	'INT UNSIGNED');
$dbStatsFields->Add(DB_STATS_WORKER_USER_ID,	'INT UNSIGNED');
$dbStatsFields->Add(DB_STATS_WORK,	'INT UNSIGNED');
$dbStatsFields->Add(DB_STATS_TEAM,	'INT UNSIGNED');
$dbStatsFields->Add(DB_STATS_TEAM_RANK,	'INT UNSIGNED');
$dbStatsFields->Add(DB_STATS_RANK,	'INT UNSIGNED');
$dbStatsFields->Add(DB_STATS_WEEK_POINTS,	'INT UNSIGNED');
$dbStatsFields->Add(DB_STATS_AVG_POINTS,	'INT UNSIGNED');
$dbStatsFields->Add(DB_STATS_TOTAL_POINTS,	'INT UNSIGNED');
$dbStatsFields->Add(DB_STATS_WUS,	'INT UNSIGNED');
//---------------
?>

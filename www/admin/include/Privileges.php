<?php
//---------------------------------
/*
 * include/Privileges.php
 * 
 * 
*/
//---------------------------------
define('PRIV_ADMIN',			XUSER_PRIV_ADMIN); // defined in XLogin.php as 'U'
define('PRIV_ROUND_APPROVE',	'A');
define('PRIV_ROUND_ADD',		'r');
define('PRIV_DELETE_HISTORY',	'D');
define('PRIV_WORKER_ADD',		'w');
define('PRIV_WORKER_MANAGE',	'W');
define('PRIV_WALLET_MANAGE',	'R');
define('PRIV_AUTO_MANAGE',		'a');
define('PRIV_CONFIG_MANAGE',	'c');
//---------------------------------
$Privileges = array(	array(PRIV_ADMIN, 			'Manage admin users',	'Add, remove, and modify users as well as reset passwords.'),
						array(PRIV_ROUND_APPROVE,	'Round approval',		'Approve or unapprove active rounds.'),
						array(PRIV_ROUND_ADD,		'Round add',			'Manually add rounds.'),
						array(PRIV_DELETE_HISTORY,	'Delete history',		'Delete historic records of rounds, payouts, and stats.'),
						array(PRIV_WORKER_ADD,		'Add new workers',		'Add new workers.'),
						array(PRIV_WORKER_MANAGE,	'Manage workers',		'Add, update, and delete workers.'),
						array(PRIV_WALLET_MANAGE,	'Manage wallet',		'Adjust wallet fees, set active wallet account, and other wallet management.'),
						array(PRIV_AUTO_MANAGE,		'Manage automation',	'Start, stop, and manage automated system.'),
						array(PRIV_CONFIG_MANAGE,	'Manage configuration',	'Manage low level configurations.')
					);
//---------------------------------
?>
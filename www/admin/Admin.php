<?php
//---------------------------------
define('IS_ADMIN', true);
//---------------------------------
require('./include/Init.php');
//---------------------------------
$title = X_TITLE." - Administration";
//---------------------------------
echo htmlHeader($title);
echo htmlHeaderBar($title);
//---------------------------------
$fnMenuItems = array();
$fnMenuItems[] = "Admin Users";
$fnMenuItems[] = "Config";
$fnMenuItems[] = "Automation";
$fnMenuItems[] = "Wallet";
$fnMenuItems[] = "Contributions";
$fnMenuItems[] = "Workers";
$fnMenuItems[] = "Rounds";
$fnMenuItems[] = "Payouts";
$fnMenuItems[] = "Cont. Payouts";
$fnMenuItems[] = "Stats";
//---------------------------------
echo htmlMenu($fnMenuItems, "./Admin.php", SecToken());
//---------------------------------
echo "<!--content -->\n";
//---------------
$page = XGetPost('p');
if ($page == "")
	$page = 0;
//---------------
includeAdminPage($page);
//---------------------------------
echo "<!--content end-->\n";
//---------------------------------
echo htmlFooter();
//---------------------------------
?>

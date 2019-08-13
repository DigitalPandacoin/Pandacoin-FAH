<?php 
//---------------------------------
function htmlHeaderBar($title)
{
	global $Login;
	//---------------
	$html = "
	<!-- Start Header Bar -->\n
	<div class='logo'>\n
		<a href='../index.html'><img src='images/dfahlogo.png' id='home' alt='Home Icon' /></a>\n
	</div>\n
	<div class='header'>\n
	<div class='header-title'>$title</div>\n"; // DONE
	//---------------
	if (defined('IS_ADMIN') && IS_ADMIN === true)
		if (!defined('IS_LOGIN') || IS_LOGIN !== true) // Not on log in page Login.php
		{
			if (defined('VERSION_STRING'))
				$html .= "<div style='text-align: center;'>Version: ".XEncodeHTML(VERSION_STRING)."</div>\n";
			$html .= "<div style='float:right;margin:0px 20px'>$Login->UserName&nbsp;<a href='./Admin.php'>Logout</a></div>\n";
		}
	//---------------
	$html .= "</div><!-- End Header Bar -->\n";
	//---------------
	return $html;
}
//---------------------------------
// $menuItems string array
function htmlMenu($menuItems, $curpage, $params = false, $selPage = false)
{
	//---------------
	if ($selPage === false)
		$page = XGetPost('p');
	else
		$page = $selPage;
	//---------------
	$html = "<!-- Start Menu -->\n";
	$html .= "<ul id='menu' class='wrapper'>\n";
	//---------------
	$idx = 0;
	foreach ($menuItems as $item)
	{
		//---------------
		if ($page == $idx) // if select page
			$html .= "<li id='menu_active'><a href='$curpage?p=$idx".($params ? "&amp;$params" : "")."'>$item</a></li>\n";
		else // not selected page
			$html .= "<li><a href='$curpage?p=$idx".($params ? "&amp;$params" : "")."'>$item</a></li>\n";
		//---------------
		$idx++;
		//---------------
	} // foreach
	//---------------
	$html .= "</ul>\n";
	$html .= "<!-- End Menu -->\n";
	//---------------
	return $html;
}
//---------------------------------
// $events: date, [link], title, [detail]  (both title/detail are single line?)
function htmlEvents($title, $secondTitle, $events, $description = false)
{
	//---------------
	$html = "<!-- Start Events -->\n<h1>$title</h1>\n";
	//---------------
	if ($description !== false)
		$html .= "<div class='wrapper pad_bot1 under'>\n<p class='pad_bot2'>$description</p>\n";
	//---------------
	if ($secondTitle !== false)
		$html .= "<h2 class='pad_bot3'>$secondTitle</h2>\n";
	//---------------
	$html .= "<ul class='list1 pad_bot2'>\n";	
	//---------------
	foreach ($events as $event)
	{
		//---------------
		$html .= "<li>\n<span>${event['date']}</span>\n<div class='left'>\n";
		//---------------
		if ($event['link'] !== false)
			$html .= "<a href='$' class='link2'>${event['title']}</a><br/>";
		else
			$html .= "${event['title']}<br/>";
		//---------------
		if ($event['detail'] !== false)
			$html .= "${event['detail']}\n";
		//---------------
		$html .= "</div>\n</li>\n";
		//---------------
	} // foreach
	//---------------
	$html .= "</ul>\n";
	//---------------
	if ($description !== false)
		$html .= "</div>\n";
	//---------------
	$html .= "<!-- End Events -->\n";
	//---------------
	return $html;
}
//---------------------------------
function htmlButton($link, $text)
{
	//---------------
	return "<a href='$link' class='button'>$text</a>";
}
//---------------------------------
// $items: title, link
function hmtlLinkList($items)
{
	//---------------
	$html = "<!-- Start Link List -->\n<ul class='list2 pad_bot3'>";
	//---------------
	foreach ($items as $item)
		$html .= "<li><a href='${item['link']}'>${item['title']}</a></li>\n";
	//---------------
	$html .= "</ul>\n<!-- End Link List -->\n";
	//---------------
	return $html;
}
//---------------------------------
function htmlHeader($title)
{
	//---------------------------------
	$config = new Config();
	//---------------------------------
	//$html = "<!DOCTYPE HTML>\n<html lang='en'>\n";
	$html = "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>\n";
	$html .= "<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>\n";
	$html .= "<head>\n";
	//---------------------------------
	$html .= "\t\t<title>$title</title>\n";
	$html .= "<meta name='robots' content='noindex'/>\n";
	//---------------------------------
	$debug = (defined('XIS_DEBUG') && XIS_DEBUG === true);
	$admin = (defined('IS_ADMIN') && IS_ADMIN === true);
	//---------------------------------
	if ($debug)
		$html .= "<meta http-equiv='Pragma' content='no-cache' />\n\t<meta http-equiv='Expires' content='-1' />\n";
	//---------------------------------
	if ($debug)
		$cachekill = "?t=".time();
	else
		$cachekill = "";
	//---------------------------------
	$html .= "<link href='Style.css$cachekill' rel='stylesheet' type='text/css' />\n";
	//---------------------------------
	$html .= '
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			'; // DONE
	//---------------------------------
	$html .= '
	</head>
<body id="page1">
		<div class="main">
			'; // DONE
	//---------------------------------
	return $html;
} // htmlHeader
//---------------------------------
function htmlFooter()
{
	//---------------------------------
	$html = '
			<!--footer -->
			<div id="footer">
				'.BRAND_COPYRIGHT.' <br/>
				<!-- {%FOOTER_LINK} -->
			</div>
			<!--footer end-->
		</div>
	</body>
</html>'; // DONE
	//---------------------------------
	return $html;
}
//---------------------------------
function includeAdminPage($page)
{
	//---------------
	switch ($page)
	{
		case 0: require_once('./include/pages/admin/Users.php'); break;
		case 1: require_once('./include/pages/admin/Config.php'); break;
		case 2: require_once('./include/pages/admin/Automation.php'); break;
		case 3: require_once('./include/pages/admin/Wallet.php'); break;
		case 4: require_once('./include/pages/admin/Contributions.php'); break;
		case 5: require_once('./include/pages/admin/Workers.php'); break;
		case 6: require_once('./include/pages/admin/Rounds.php'); break;
		case 7: require_once('./include/pages/admin/Payouts.php'); break;
		case 8: require_once('./include/pages/admin/ContPayouts.php'); break;
		case 9: require_once('./include/pages/admin/Stats.php'); break;
	}
	//---------------------------------
}
//---------------------------------
?>

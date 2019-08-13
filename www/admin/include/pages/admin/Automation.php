<?php
//---------------------------------
/*
 * include/pages/admin/Automation.php
 * 
 * 
*/
//---------------------------------
global $Login;
$Automation = new Automation() or die("Create object failed");
$Config = new Config() or die("Create object failed");
$Display = new Display() or die("Create object failed");
$pagenum = XGetPost('p');
$action = XPost('action');
//---------------------------------
function getRow($canManage, $idx, $name, $active, $defRate, $strLastRan, $strCurRate)
{
	//------------------------
	$col = array();
	//------------------------
	$col[] = $name; // Name
	$col[] = ($active ? "Active" : "Idle"); // Active
	//------------------------
	if ($canManage)
	{
		//------------------------
		$action = ($active ? "Stop" : "Start");
		//------------------------
		$btnExec = "<button style='padding:3px;margin:3px;' name='action' value='Exec_$idx' type='submit'>Execute</button>";
		$btnSS = "<button style='padding:3px;margin:3px;' name='action' value='".$action."_$idx' type='submit'>$action</button>";
		$col[] = $btnExec." ".$btnSS; // Control
		//------------------------
	}
	else
	{
		//------------------------
		$col[] = "&nbsp;"; // Control
		//------------------------
	}
	//------------------------
	if ($idx != 0)
	{
		$col[] = ($defRate === false ? "&nbsp;" : "<div style='padding:1px;text-align:center;'><input style='padding: 1px; width: 35px;' id='Rate_$idx' type='text' name='Rate_$idx' value='".($active ? $strCurRate."' maxlength='5' disabled" : $defRate."' maxlength='3'")."/> minutes</div>"); // Rate
	}
	else  // handled specially
	{
		//------------------------
		$weekdays = array( 	array('*', '(any)'),
							array('0', 'Sunday'),
							array('1', 'Monday'),
							array('2', 'Tuesday'),
							array('3', 'Wednesday'),
							array('4', 'Thursday'),
							array('5', 'Friday'),
							array('6', 'Saturday')
						);
		//------------------------
		$Automation = new Automation() or die("Create object failed");
		$tz = $Automation->getTimeZoneName();
		if ($tz === false)
			$tz = "?";
		//------------------------
		$str = "<div style='padding:1px;text-align:center;'><div style='margin: 1px;'>\n";
		$str .= "<input style='padding: 1px; width: 25px;' id='Hour_$idx' type='text' name='Hour_$idx' value='";
		$str .= ($active ? $strCurRate[0] : $defRate[0]);
		$str .="' maxlength='2'/>:<input style='padding: 1px; width: 25px;' id='Min_$idx' type='text' name='Min_$idx' value='";
		$str .= ($active ? $strCurRate[1] : $defRate[1]);
		$str .="' maxlength='2'/></div><div>24&nbsp;hour&nbsp;-&nbsp;$tz</div>";
		//------------------------
		$str .= "<div>Day&nbsp;<select id='Weekday_$idx' name='Weekday_$idx'".($active || !$canManage ? " disabled" : "").">\n";
		//------------------------
		foreach ($weekdays as $day)
		{
			$str .= "<option value='$day[0]'";
			if ( ($active && $strCurRate[2] == $day[0]) || (!$active && $defRate[2] == $day[0]) )
				$str .= " selected";
			$str .= ">$day[1]</option>\n";
		}
		//------------------------
		$str .= "</select></div>\n";
		//------------------------
		$col[] = $str;
		//------------------------
	}
	//------------------------
	$col[] = ($strLastRan === false ? "&nbsp;" : $strLastRan); // Last Ran
	//------------------------
	$out = "<tr class='admin-row'>";
	//------------------------
	foreach ($col as $c)
		$out .= "<td>$c</td>\n";
	//------------------------
	$out .= "</tr>\n";
	//------------------------
	return $out;
}
//---------------------------------
?>
<div id="content"><!-- Start Container 1 -->
	<div class="under pad_bot1"><!-- Start Container 2 -->
	<br/>
	<div class="line1 wrapper pad_bot2"><!-- Start Container 3 -->
		<div class="col1"  style="width: 720px;"><!-- Start Left Column -->
		<div>
			<h1>Automation:</h1>
			<p>
			Folding @ Home automation.
			</p>
			<br/>
		</div>
		<?php
			//------------------------
			if ($action == "Start_0")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_AUTO_MANAGE))
				{
					//------------------------
					$hour = XPost('Hour_0');
					$min = XPost('Min_0');
					$weekday = XPost('Weekday_0');
					//------------------------
					if (!is_numeric($hour) || !is_numeric($min) || $hour < 0 || $hour >= 24 || $min < 0 || $min >= 60 || ($weekday != '*' && !is_numeric($weekday)))
					{
						XLogNotify("Admin Automation hour/min is invalid, hour: ".XVarDump($hour).", min: ".XVarDump($min).", weekday: ".XVarDump($weekday));
						echo "Start automation failed. Rate 'at time' is invalid.<br/>";
					}
					else
					{
						//------------------------
						$hour = (int)$hour; // try to trim preceding zeros, ect
						$min = (int)$min; // try to trim preceding zeros, ect
						if ($weekday != '*')
							$weekday = (int)$weekday; // try to trim preceding zeros, ect
						//------------------------
						if (!$Automation->StartNewRoundPage($min, $hour, $weekday))
						{
							XLogNotify("Admin Automation Automation StartNewRoundPage failed new round");
							echo "Start new round automation failed.<br/>";
						}
						else
						{
							XLogNotify("Admin Automation started new round Automation");
							echo "Start new round automation succeded.<br/>";
						}
						//------------------------
					}
					//------------------------
				}
				else
				{
					XLogNotify("Admin Automation Automation start new round doesn't have proper privileges: $Login->UserName");
					echo "Start new round automation failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			else if ($action == "Stop_0")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_AUTO_MANAGE))
				{
					//------------------------
					if (!$Automation->StopNewRoundPage())
					{
						XLogNotify("Admin Automation Automation StopNewRoundPage failed new round");
						echo "Stop new round automation failed.<br/>";
					}
					else
					{
						XLogNotify("Admin Automation stopped new round Automation");
						echo "Stop new round automation succeded.<br/>";
					}
					//------------------------
				}
				else
				{
					XLogNotify("Admin Automation Automation stop new round doesn't have proper privileges: $Login->UserName");
					echo "Stop new round automation failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			else if ($action == "Exec_0")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_AUTO_MANAGE))
				{
					//------------------------
					$result = $Automation->ExecuteNewRoundPage();
					if ($result === false)
					{
						XLogNotify("Admin Automation Automation ExecuteNewRoundPage failed new round");
						echo "Execute new round failed.<br/>";
					}
					else
					{
						$value = $result[0];
						$output = $result[1];
						XLogNotify("Admin Automation executed new round result value: ".XVarDump($value).", output: ".XVarDump($output));
						echo "Execute new round ".($value === 0 ? "succeded" : "failed");
						if (sizeof($output) == 0)
							echo " with no output.<br/>\n";
						else
						{
							echo " with output:<br/>\n";
							echo "<div class='admin-rawtext'>\n";
							echo join("<br/>", $output);
							echo "</div>\n";
						}
					}
					//------------------------
				}
				else
				{
					XLogNotify("Admin Automation Automation execute new round doesn't have proper privileges: $Login->UserName");
					echo "Execute new round automation failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			else if ($action == "Start_1")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_AUTO_MANAGE))
				{
					//------------------------
					$rate = XPost('Rate_1');
					if (!is_numeric($rate))
					{
						XLogNotify("Admin Automation rate is invalid");
						echo "Start automation failed. Rate is invalid.<br/>";
					}
					else 
					{
						//------------------------
						if (!$Automation->StartRoundCheckPage($rate))
						{
							XLogNotify("Admin Automation Automation StartRoundCheckPage failed");
							echo "Start monitor round automation failed.<br/>";
						}
						else
						{
							XLogNotify("Admin Automation started monitor round Automation");
							echo "Start monitor round automation succeded.<br/>";
						}
						//------------------------
					}
					//------------------------
				}
				else
				{
					XLogNotify("Admin Automation Automation start monitor round doesn't have proper privileges: $Login->UserName");
					echo "Start monitor round automation failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			else if ($action == "Stop_1")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_AUTO_MANAGE))
				{
					//------------------------
					if (!$Automation->StopRoundCheckPage())
					{
						XLogNotify("Admin Automation Automation StopRoundCheckPage failed monitor round");
						echo "Stop monitor round automation failed.<br/>";
					}
					else
					{
						XLogNotify("Admin Automation stopped monitor round Automation");
						echo "Stop monitor round automation succeded.<br/>";
					}
					//------------------------
				}
				else
				{
					XLogNotify("Admin Automation Automation stop monitor round doesn't have proper privileges: $Login->UserName");
					echo "Stop monitor round automation failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			else if ($action == "Exec_1")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_AUTO_MANAGE))
				{
					//------------------------
					$result = $Automation->ExecuteRoundCheckPage();
					if ($result === false)
					{
						XLogNotify("Admin Automation Automation ExecuteRoundCheckPage failed monitor round");
						echo "Execute monitor round failed.<br/>";
					}
					else
					{
						$value = $result[0];
						$output = $result[1];
						XLogNotify("Admin Automation executed monitor round result value: ".XVarDump($value).", output: ".XVarDump($output));
						echo "Execute monitor round ".($value === 0 ? "succeded" : "failed");
						if (sizeof($output) == 0)
							echo " with no output.<br/>\n";
						else
						{
							echo " with output:<br/>\n";
							echo "<div class='admin-rawtext'>\n";
							echo join("<br/>", $output);
							echo "</div>\n";
						}
					}
					//------------------------
				}
				else
				{
					XLogNotify("Admin Automation Automation execute monitor round doesn't have proper privileges: $Login->UserName");
					echo "Execute monitor round automation failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			else if ($action == "Start_2")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_AUTO_MANAGE))
				{
					//------------------------
					$rate = XPost('Rate_2');
					if (!is_numeric($rate))
					{
						XLogNotify("Admin Automation rate is invalid");
						echo "Start automation failed. Rate is invalid.<br/>";
					}
					else 
					{
						//------------------------
						if (!$Automation->StartStatCheckPage($rate))
						{
							XLogNotify("Admin Automation Automation StartStatCheckPage failed stats");
							echo "Start stats automation failed.<br/>";
						}
						else
						{
							XLogNotify("Admin Automation started stats Automation");
							echo "Start stats automation succeded.<br/>";
						}
						//------------------------
					}
					//------------------------
				}
				else
				{
					XLogNotify("Admin Automation Automation start stats automation doesn't have proper privileges: $Login->UserName");
					echo "Start stats automation failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			else if ($action == "Stop_2")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_AUTO_MANAGE))
				{
					//------------------------
					if (!$Automation->StopStatCheckPage())
					{
						XLogNotify("Admin Automation Automation StopStatCheckPage failed stats");
						echo "Stop stats automation failed.<br/>";
					}
					else
					{
						XLogNotify("Admin Automation stopped stats Automation");
						echo "Stop stats automation succeded.<br/>";
					}
					//------------------------
				}
				else
				{
					XLogNotify("Admin Automation Automation stop stats automation doesn't have proper privileges: $Login->UserName");
					echo "Stop stats automation failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			else if ($action == "Exec_2")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_AUTO_MANAGE))
				{
					//------------------------
					$result = $Automation->ExecuteStatCheckPage();
					if ($result === false)
					{
						XLogNotify("Admin Automation Automation ExecuteStatCheckPage failed stats");
						echo "Execute stats failed.<br/>";
					}
					else
					{
						$value = $result[0];
						$output = $result[1];
						XLogNotify("Admin Automation executed stats result value: ".XVarDump($value).", output: ".XVarDump($output));
						echo "Execute stats ".($value === 0 ? "succeded" : "failed");
						if (sizeof($output) == 0)
							echo " with no output.<br/>\n";
						else
						{
							echo " with output:<br/>\n";
							echo "<div class='admin-rawtext'>\n";
							echo join("<br/>", $output);
							echo "</div>\n";
						}
					}
					//------------------------
				}
				else
				{
					XLogNotify("Admin Automation Automation execute stats automation doesn't have proper privileges: $Login->UserName");
					echo "Execute stats automation failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			else if ($action == "Start_3")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_AUTO_MANAGE))
				{
					//------------------------
					if (!$Automation->StartFahStatsPage())
					{
						XLogNotify("Admin Automation Automation StartFahStatsPage failed Fah stats");
						echo "Start Fah stats automation failed.<br/>";
					}
					else
					{
						XLogNotify("Admin Automation started Fah stats Automation");
						echo "Start Fah stats automation succeded.<br/>";
					}
					//------------------------
				}
				else
				{
					XLogNotify("Admin Automation Automation start Fah stats automation doesn't have proper privileges: $Login->UserName");
					echo "Start Fah stats automation failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			else if ($action == "Stop_3")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_AUTO_MANAGE))
				{
					//------------------------
					if (!$Automation->StopFahStatsPage())
					{
						XLogNotify("Admin Automation Automation StopFahStatsPage failed Fah stats");
						echo "Stop Fah stats automation failed.<br/>";
					}
					else
					{
						XLogNotify("Admin Automation stopped Fah stats Automation");
						echo "Stop Fah stats automation succeded.<br/>";
					}
					//------------------------
				}
				else
				{
					XLogNotify("Admin Automation Automation stop Fah stats automation doesn't have proper privileges: $Login->UserName");
					echo "Stop Fah stats automation failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			//------------------------
			else if ($action == "Exec_4")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_AUTO_MANAGE))
				{
					//------------------------
					$result = $Automation->ExecutePublicData();
					if ($result === false)
					{
						XLogNotify("Admin Automation Automation ExecutePublicData failed public data");
						echo "Execute public data failed.<br/>";
					}
					else
					{
						$value = $result[0];
						$output = $result[1];
						XLogNotify("Admin Automation executed public data result value: ".XVarDump($value).", output: ".XVarDump($output));
						echo "Execute public data ".($value === 0 ? "succeded" : "failed");
						if (sizeof($output) == 0)
							echo " with no output.<br/>\n";
						else
						{
							echo " with output:<br/>\n";
							echo "<div class='admin-rawtext'>\n";
							echo join("<br/>", $output);
							echo "</div>\n";
						}
					}
					//------------------------
				}
				else
				{
					XLogNotify("Admin Automation Automation execute public data automation doesn't have proper privileges: $Login->UserName");
					echo "Execute public data automation failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			else if ($action == "Start_4")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_AUTO_MANAGE))
				{
					//------------------------
					$rate = XPost('Rate_4');
					if (!is_numeric($rate))
					{
						XLogNotify("Admin Automation rate is invalid");
						echo "Start automation failed. Rate is invalid.<br/>";
					}
					else 
					{
						//------------------------
						if (!$Automation->StartPublicData($rate))
						{
							XLogNotify("Admin Automation Automation StartPublicData failed public data");
							echo "Start public data automation failed.<br/>";
						}
						else
						{
							XLogNotify("Admin Automation started public data Automation");
							echo "Start public data automation succeded.<br/>";
						}
						//------------------------
					}
					//------------------------
				}
				else
				{
					XLogNotify("Admin Automation Automation start public data automation doesn't have proper privileges: $Login->UserName");
					echo "Start public data automation failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			else if ($action == "Stop_4")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_AUTO_MANAGE))
				{
					//------------------------
					if (!$Automation->StopPublicData())
					{
						XLogNotify("Admin Automation Automation StopPublicData failed public data");
						echo "Stop public data automation failed.<br/>";
					}
					else
					{
						XLogNotify("Admin Automation stopped public data Automation");
						echo "Stop public data automation succeded.<br/>";
					}
					//------------------------
				}
				else
				{
					XLogNotify("Admin Automation Automation stop public data automation doesn't have proper privileges: $Login->UserName");
					echo "Stop public data automation failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			//------------------------
		?>
		<form action="./Admin.php" method="post" enctype="multipart/form-data">
		<fieldset class="loginBox">
			<?php PrintSecTokenInput(); ?>
			<input type="hidden" name="p" value="<?php echo $pagenum; ?>"/><!-- Page -->
			<table class='admin-table'>
			<tr><th style='width:57px;'>Name</th><th style='width:45px;'>Active</th><th style='width:85px;'>Control</th><th style='width:100px;'>Rate</th><th style='width:100px;'>Last ran</th></tr>
			<?php
				//------------------------
				$pages = array(AUTOMATION_PAGE_NEWROUND, AUTOMATION_PAGE_ROUND, AUTOMATION_PAGE_STAT, AUTOMATION_PAGE_FAH_STATS, AUTOMATION_PAGE_PUBLIC_DATA);
				$pagesActive = $Automation->hasCrontabs($pages);
				if ($pagesActive === false)
				{
					XLogNotify("Admin Automation Automation hasCrontabs failed");
					echo "<tr class='admin-row'>Error<td><td>Error</td><td>Error</td><td>Error</td><td>Error</td><td>Error</td></tr>\n";
				}
				else
				{
					//------------------------	
					$canManage = $Login->HasPrivilege(PRIV_AUTO_MANAGE);
					//------------------------	
					$rateH = $Config->Get(CFG_AUTO_NEWROUND_RATE_H, 11);
					$rateM = $Config->Get(CFG_AUTO_NEWROUND_RATE_M, "00");
					$rateW = $Config->Get(CFG_AUTO_NEWROUND_RATE_W, 6 /*Saturday*/);
					//------------------------
					$autos = array();
					//------------------------
					$autos[] = array(	'idx'	=> 0,
										'name'	=> "Round Automation",
										'last' 	=> CFG_AUTO_LAST_NEWROUND,
										'cur'	=> array($rateH, $rateM, $rateW), // handled specially
										'start'	=> false, 
										'drate' => array( 11 /*hour*/, '00' /*min*/, 6 /*saturday*/) // handled specially
										);
					//------------------------
					$autos[] = array(	'idx'	=> 1,
										'name'	=> "Round Monitor",
										'last' 	=> CFG_AUTO_LAST_ROUND,
										'cur'	=> CFG_AUTO_ROUND_CURRENT_RATE,
										'start'	=> CFG_AUTO_ROUND_START_RATE,
										'drate' => 2
										);
					//------------------------
					$autos[] = array(	'idx'	=> 2,
										'name'	=> "Stat Polling",
										'last' 	=> CFG_AUTO_LAST_STAT,
										'cur'	=> CFG_AUTO_STATS_CURRENT_RATE,
										'start'	=> CFG_AUTO_STATS_START_RATE,
										'drate' => 1
										);
					//------------------------
					$autos[] = array(	'idx'	=> 3,
										'name'	=> "F@H Stat Polling",
										'last' 	=> CFG_AUTO_LAST_FAH_STATS,
										'cur'	=> false,
										'start'	=> false,
										'drate' => false
										);
					//------------------------
					$autos[] = array(	'idx'	=> 4,
										'name'	=> "Public Data Update",
										'last' 	=> CFG_AUTO_LAST_PUBDATA,
										'cur'	=> CFG_AUTO_PUBDATA_CURRENT_RATE,
										'start'	=> CFG_AUTO_PUBDATA_START_RATE,
										'drate' => 5
										);
					//------------------------
					foreach ($autos as $auto)
					{
						//------------------------
						if ($auto['cur'] !== false && $auto['start'] !== false)
						{
							//------------------------
							$curRate = $Config->Get($auto['cur']);
							$startRate = $Config->Get($auto['start']);
							//------------------------
							if ($curRate === false || $startRate === false)
								$strCurRate = false;
							else 
								$strCurRate = ($curRate != $startRate ? "$curRate ($startRate)" : $startRate);
							//------------------------
						}
						else if ($auto['idx'] == 0) // handled specially
							$strCurRate = $auto['cur'];
						else
							$strCurRate = false;
						//------------------------
						$lastRan = $Config->Get($auto['last']);
						if ($lastRan !== false)
							$lastRan = $Display->htmlLocalDateTime($lastRan);
						//------------------------
						echo getRow($canManage, $auto['idx'], $auto['name'], $pagesActive[$auto['idx']], $auto['drate'], $lastRan, $strCurRate);
						//------------------------
					}

					//------------------------	
				}
				echo "</table>\n";		
				//------------------------	
			?>
			</fieldset>
		</form>
		<br/>
		<div>
			<h2>Raw crontabs:</h2>
			<div>(minute hour day month week-day command)</div>
		</div>		
		<div class='admin-rawtext'>
		<?php
			//------------------------	
			$crontabsRaw = $Automation->getCrontabsRaw();
			if ($crontabsRaw === false)
			{
				XLogNotify("Admin Automation Automation getCrontabsRaw failed");
				echo "Failed to get raw cron tabs.";
			}
			else if (sizeof($crontabsRaw) == 0)
				echo "No crontabs.";
			else
				echo join("<br/>", $crontabsRaw);
			//------------------------	
		?>
		</div>
		</div><!-- End Left Column -->
	</div><!-- End Container 3 -->
	</div><!-- End Container 2 -->
</div><!-- End Container 1 -->
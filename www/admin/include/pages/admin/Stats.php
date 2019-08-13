<?php
//---------------------------------
/*
 * include/pages/admin/Stats.php
 * 
 * 
*/
//---------------------------------
global $Login; 
//---------------------------------
$Stats = new Stats() or die("Create object failed");
$Workers = new Workers() or die("Create object failed");
$Rounds  = new Rounds() or die("Create object failed");
$Payouts = new Payouts() or die("Create object failed");
$Display = new Display() or die("Create object failed");
$Config = new Config() or die("Create object failed");
//---------------------------------
$pagenum = XGetPost('p');
$sidx = XGetPost("idx");
if ($sidx == "")
	$sidx = false;
$action = XPost('action');
$mode = XGetPost("mode");
if ($mode == "")
	$mode = false;
$widx = XGetPost("widx");
if ($widx == "")
	$widx = false;
$ridx = XGetPost("ridx");
if ($ridx == "")
	$ridx = false;
$limit = XGetPost("lmt");
if ($limit == false || $limit == "" || !is_numeric($limit))
	$limit = 500;
else if ($limit == 0)
	$limit = false;
$selRoundStr = "";
$selWorkerStr = "";
$selStat = false;
$dridx = XPost('dridx', false);
//---------------------------------
$defRoundStatsMode = $Config->Get(CFG_ROUND_STATS_MODE, DEFAULT_ROUND_STATS_MODE); 
$defTeamID = $Config->Get(CFG_ROUND_TEAM_ID, DEFAULT_ROUND_TEAM_ID);
//---------------------------------
?>
<div id="content"><!-- Start Container 1 -->
	<div class="under pad_bot1"><!-- Start Container 2 -->
	<br/>
	<div class="line1 wrapper pad_bot2"><!-- Start Container 3 -->
		<div class="col1" style="width: 98%;"><!-- Start Left Column -->
		<div>
			<h1>Worker stats:</h1>
			<p>
			Folding @ Home stats list.
			</p>
			<br/>
		</div>
		<?php
			//------------------------	
			if ($action == "Add")
			{
				//------------------------	
				$wpnt = XGetPost("wpnt");
				if ($wpnt == "")
					$wpnt = false;
				$tpnt = XGetPost("tpnt");
				if ($tpnt == "")
					$tpnt = false;
				$wus = XGetPost("wus");
				if ($wus == "")
					$wus = false;
				$dupcnt = XGetPost("dcnt");
				if ($dupcnt == "")
					$dupcnt = false;
				$teamid = XGetPost("team");
				if ($teamid == "")
					$teamid = false;
				//------------------------	
				XLogNotify("User $Login->User is adding stat for round: $ridx, worker idx: $widx, mode: $mode, dup count: $dupcnt, work/wk points: $wpnt, tot points: $tpnt, wus: $wus");
				//------------------------	
				if ($ridx === false || !is_numeric($ridx) && $widx === false || !is_numeric($widx) || $mode === false || !is_numeric($mode) || $teamid === false || !is_numeric($teamid) || $tpnt == false || !is_numeric($tpnt) || $wus === false || !is_numeric($wus) || $dupcnt === false || !is_numeric($dupcnt) || $dupcnt > 100)
				{
					XLogNotify("Stats admin page - stats add failed to validate parameters");
					echo "Failed to add stat, invalid parmeters provided.<br/><br/>\n";
				}
				else if ($Login->HasPrivilege('D')) // delete history
				{
					
					$worker = $Workers->loadWorker($widx);
					if ($worker === false)
					{
						XLogNotify("Stats admin page - stats add failed to find worker $widx");
						echo "Failed to add stat, worker index not found.<br/><br/>\n";
					}
					else
					{
						$dleft = $dupcnt;
						$ok = true;
						while ($dleft > 0 && $ok === true)
						{
							if (!$Stats->addFahClientStat((int)$ridx, (int)$mode, (int)$widx, (int)$teamid, 0, (int)$tpnt, (int)$wus, false /*rank*/, false/*reload*/))
							{
								$ok = false;
								XLogNotify("Stats admin page - stats failed to addFahClientStat, round idx: $ridx, worker idx: $widx, dup count: $dupcnt, dup left: $dleft");
								echo "Failed to add stat.<br/><br/>\n";
							}
							$dleft--;
						}
						if ($ok === true)
						{
							XLogNotify("Stats admin page - stat added successfully. round idx: $ridx, worker idx: $widx, dup count: $dupcnt");
							echo "Successfully added $dupcnt duplicate stat(s).<br/><br/>\n";
						}
					}
				}
				else 
					echo "Deleting historical data required 'D' delete privileges.<br/><br/>\n";
			}
			else if ($action == "Delete" && $sidx !== false && is_numeric($sidx))
			{
				if ($Login->HasPrivilege('D')) // delete history
				{
					XLogNotify("User $Login->User is deleting stat: $sidx");
					if (!$Stats->deleteStat($sidx))
					{
						XLogNotify("Stats admin page - stats failed to delete Stat $sidx");
						echo "Failed to delete stat.<br/><br/>\n";
					}
					else
					{
						echo "Stat successfully deleted.<br/><br/>\n";
						$sidx = false;
					}
				}
				else 
					echo "Deleting historical data required 'D' delete privileges.<br/><br/>\n";
			}
			else if ($action == "Update Points" && $sidx !== false && is_numeric($sidx))
			{
				if ($Login->HasPrivilege('D')) // delete history
				{
					$stat = $Stats->loadStat($sidx);
					if ($stat === false)
					{
						XLogNotify("Stats admin page - stats failed find Stat $sidx to update points");
						echo "Failed to update stat points.<br/><br/>\n";
					}
					else
					{
						$wp = XPost('selwp');
						$tp = XPost('seltp');
						if ($wp === false || $tp === false || !is_numeric($wp) || !is_numeric($tp))
						{
							XLogWarn("Stats admin page - stats failed validate points for Stat $sidx to update points");
							echo "Failed to update stat points, points invalid.<br/><br/>\n";
						}
						else
						{
							XLogNotify("User $Login->User is updating stat: $sidx's points from (".$stat->weekPoints."/".$stat->totPoints.") to ($wp/$tp)");
							$stat->weekPoints = $wp;
							$stat->totPoints = $tp;
							if (!$stat->Update())
							{
								XLogError("Stats admin page - stat failed to update for Stat $sidx to update points");
								echo "Failed to update stat points update failed.<br/><br/>\n";
							}
							else
							{
								echo "Stat successfully updated stat points.<br/><br/>\n";
							}
						}
					}
				}
				else 
					echo "Update historical data required 'D' delete privileges.<br/><br/>\n";
			}
			else if ($action == "Delete All From Round")
			{
				if ($Login->HasPrivilege('D')) // delete history
				{
					if ($dridx !== false && is_numeric($dridx))
					{
						XLogNotify("User $Login->User is deleting all stats from round: $dridx");
						if (!$Stats->deleteAllRound($dridx))
						{
							XLogNotify("Stats admin page - stats failed to delete all from round $dridx");
							echo "Failed to delete all from round $dridx.<br/><br/>\n";
						}
						else
						{
							echo "Stats successfully deleted.<br/><br/>\n";
							$sidx = false;
						}
						
					}
					else "Deleting all from round, validate round index failed.<br/><br/>\n";
				}
				else 
					echo "Deleting historical data required 'D' delete privileges.<br/><br/>\n";
			}
			//------------------------	
			$Columns = array(	array(	"40px",		"ID"),
								array(	"195px",	"Date"),
								array(	"150px",	"Round"),
								array(	"405px",	"Worker"), // 180
								array(	"120px",	"Work")
								);
			//------------------------	
			echo "<table class='admin-table' style='width:970px;'>\n<thead class='admin-scrolltable-header'>\n<tr style='width:100%;'>\n";
			//------------------------	
			foreach ($Columns as $col)
				echo "\t<th".($col[0] !== false ? " style='width:$col[0];'" : "").">$col[1]</th>\n";
			//------------------------	
			echo "</tr></thead>\n<tbody class='admin-scrolltable-body'>\n";
			//------------------------	
			$slist = $Stats->getStats($limit);
			if ($slist === false)
				XLogError("Stats admin page - getStats failed");
			if ($slist === false || sizeof($slist) == 0)
			{
				//------------------------
				echo "<tr class='admin-row'>";
				foreach ($Columns as $col)
					echo "<td".($col[0] !== false ? " style='width:".$col[0].";'" : "").">&nbsp;</td>";
				echo "</tr>\n";
				//------------------------
			}
			else
			{
				//------------------------
				$rowIdx = 0;
				foreach ($slist as $s)
				{
					//------------------------
					$strRound = $Rounds->getRoundDate($s->roundIdx);
					if ($strRound === false || $strRound == "")
						$strRound = $s->roundIdx;
					else
						$strRound = "($s->roundIdx) ".$Display->htmlLocalDate($strRound); // just the date
					//------------------------
					$worker = $Workers->getWorker($s->workerIdx);
					if ($worker === false)
						$strWorker = "($s->workerIdx) [not found]";
					else
						$strWorker = "($s->workerIdx) ".($worker->address == "" ? $worker->uname : $worker->address);
					//------------------------
					if ($s->dtPolled !== false && $s->dtPolled != "")
						$strPolled = $Display->htmlLocalDateTime($s->dtPolled);
					else
						$strPolled = "(queued)".$Display->htmlLocalDateTime($s->dtCreated);
					//------------------------
					$strWork = $s->work();
					if ($strWork === false) 
						$strWork = "";
					//------------------------
					if ($sidx !== false && $sidx == $s->id)
					{
						$rowClass = 'admin-row-sel';
						$selRoundStr = $strRound;
						$selStat = $s;
						$selWorker = $worker;
						$selWork = $strWork;
					}
					else $rowClass = 'admin-row';
					//------------------------
					echo "<tr class='$rowClass' onclick=\"document.location.href='./Admin.php?p=$pagenum&amp;idx=$s->id&amp;lmt=".($limit === false ? 0 : $limit)."&amp;".SecToken()."';\">\n";
					//------------------------
					$colData = array(	$s->id,
										$strPolled,
										$strRound,
										$strWorker,
										$strWork
									);
					//------------------------
					for ($i = 0;$i < sizeof($colData);$i++)
					{
						$style = "";
						if ($Columns[$i][0] !== false) // $rowIdx == 0 && 
							$style .= "width:".$Columns[$i][0].";";
						if ($Columns[$i][1] == "Work")
							$style .= "text-align:right;";
						if ($Columns[$i][1] == "Worker" || $Columns[$i][1] == "Date" || $Columns[$i][1] == "Round")
							$style .= "overflow: hidden;white-space:nowrap;text-overflow:ellipsis;";
							
						if ($Columns[$i][1] == "Worker")
							$style .= "display: block;";
							
						if ($style != "")
							$style = " style='$style'";
						echo "<td$style>".$colData[$i]."</td>";
					}
					//------------------------
					echo "</tr>\n";
					//------------------------
					$rowIdx++;
					//------------------------
				} // foreach
				//------------------------
			}
			//------------------------
			echo "</tbody></table>\n";		
			//------------------------	
		?>
		</div><!-- End Left Column -->
		<?php
		//---------------------------
		if ($sidx !== false && $selStat !== false)
		{
		?>
		<div class="col1 pad_left1" style="width:800px;"><!-- Start Right Column -->
		<div class="admin-edit" style="margin: auto;">
		<form action="./Admin.php" method="post" enctype="multipart/form-data">
			<fieldset class="loginBox">
				<?php 
					//---------------------------
					echo "Limit: <input id='lmt' type='text' name='lmt' value='".($limit === false ? 0 : $limit)."' maxlength='6'/>\n";
					echo "<input type='submit' name='action' value='Update Limit' /><br/>\n";
					//---------------------------
				?>
				<br/>
				<div style="font-weight:bold;font-size:120%;">Stat Details:</div>
				<br/>
				<?php PrintSecTokenInput(); ?>
				<input type="hidden" name="idx" value="<?php echo $sidx; ?>" />
				<input type="hidden" name="p" value="<?php echo $pagenum; ?>"/><!-- Page -->
				<?php 
					//---------------------------
					echo "<table class='admin-table'>\n<tr><th style='width:120px;'>Attribute</th><th >Value</th></tr>\n";
					//---------------------------
					if ($selWorker === false)
						$selWorkerStr = "($selStat->workerIdx) [not found]";
					else
					{
						if ($selWorker->address != $selWorker->uname)
							$selWorkerStr = "($selStat->workerIdx) ".$selWorker->uname." (".($selWorker->address == "" ? "not set" : "<a href='".BRAND_ADDRESS_LINK.XEncodeHTML($selWorker->address)."'>".XEncodeHTML($selWorker->address)."</a>").")";
						else
							$selWorkerStr = "($selStat->workerIdx) <a href='".BRAND_ADDRESS_LINK.XEncodeHTML($selWorker->address)."'>".XEncodeHTML($selWorker->address)."</a>";
					}
					//------------------------
					$attributes = array(	'ID' => $sidx,
											'Created' => $Display->htmlLocalDateTime($selStat->dtCreated),
											'Polled' => $Display->htmlLocalDateTime($selStat->dtPolled, false/*default format*/, "(queued)"/*default string*/),
											'Round' => $selRoundStr,
											'Mode' => $selStat->mode,
											'Worker' => $selWorkerStr,
											'Work' => $selWork
									);
					//---------------------------
					if ($selStat->payoutIdx === false)
						$attributes['Payout'] = "(not paid or queued)";
					else
					{
						$payout = $Payouts->getPayout($selStat->payoutIdx);
						if ($payout === false)
						{
							$attributes['Payout'] = "($selStat->payoutIdx) &lt;Error getting details&gt;";
						}
						else if ($payout->txid !== false && $payout->txid != "")
						{
							$attributes['Payout'] = "($selStat->payoutIdx) Paid at ".$Display->htmlLocalDateTime($payout->dtPaid);
							$attributes['Payment'] = "$payout->pay&nbsp;".BRAND_UNIT."&nbsp;to&nbsp;<a href='".BRAND_ADDRESS_LINK.XEncodeHTML($payout->address)."'>".XEncodeHTML($payout->address)."</a>";
							$attributes['Payment TxID'] = ($payout->txid != "" ? "<a href='".BRAND_TX_LINK.XEncodeHTML($payout->txid)."'>".XEncodeHTML($payout->txid)."</a>" : "&nbsp;");
						}
						else
						{
							$attributes['Payout'] = "($selStat->payoutIdx) Not yet paid";
							$attributes['Payment'] = "$payout->pay&nbsp;".BRAND_UNIT."&nbsp;to&nbsp;<a href='".BRAND_ADDRESS_LINK.XEncodeHTML($payout->address)."'>".XEncodeHTML($payout->address)."</a>";
						}
					}
					//---------------------------
					$attributes['Week Points'] = $selStat->weekPoints;
					$attributes['24hr Avg Points'] = $selStat->avgPoints;
					$attributes['Total Points'] = $selStat->totPoints;
					$attributes['Work Units'] = $selStat->wus;
					$attributes['Rank'] = $selStat->rank;
					$attributes['Team'] = $selStat->team;
					$attributes['Team Rank'] = $selStat->teamRank;
					//---------------------------
					foreach ($attributes as $name => $value)
						echo "<tr class='admin-row'><td>$name</td><td><div style='overflow: auto;'>$value</div></td></tr>\n";
					//---------------------------
					echo "</table>\n";
					//---------------------------
					if ($Login->HasPrivilege(PRIV_DELETE_HISTORY)) 
					{
						echo "<br/><div>Privileged:</div>\n<br/>";
						echo "<input type='submit' name='action' value='Delete' /><br/>\n";
						echo "<br/>\n";
						echo "Week Points: <input id='selwp' type='text' name='selwp' value='".$selStat->weekPoints."' maxlength='10'/><br/>\n";
						echo "Total Points: <input id='seltp' type='text' name='seltp' value='".$selStat->totPoints."' maxlength='10'/><br/>\n";
						echo "<input type='submit' name='action' value='Update Points' /><br/>\n";
						echo "<br/>\n";
					}
					//---------------------------
				?>
				<br/>
				<br/>
			</fieldset>
		</form>
		</div>
		<br/>
		</div><!-- End Right Column -->
		<?php
		//---------------------------
		} // if ($sidx !== false && $selStat !== false)
		//---------------------------
		if ($Login->HasPrivilege(PRIV_DELETE_HISTORY)) 
		{
			//---------------------------
			?>
			<div class="col1 pad_left1" style="width:800px;"><!-- Start Right Column -->
			<div class="admin-edit" style="margin: auto;">
			<form action="./Admin.php" method="post" enctype="multipart/form-data">
				<fieldset class="loginBox">
					<?php
						//---------------------------
						echo "Limit: <input id='lmt' type='text' name='lmt' value='".($limit === false ? 0 : $limit)."' maxlength='6'/>\n";
						echo "<input type='submit' name='action' value='Update Limit' /><br/>\n";
						//---------------------------
					?>
					<br/>
					<div>Privileged:</div><br/>
					<br/>
					<?php PrintSecTokenInput(); ?>
					<input type="hidden" name="idx" value="<?php echo $sidx; ?>" />
					<input type="hidden" name="p" value="<?php echo $pagenum; ?>"/><!-- Page -->
			<?php
			//---------------------------
			echo "<input type='submit' name='action' value='Delete All From Round' />\n";
			echo "Round index: <input id='dridx' type='text' name='dridx' value='' maxlength='5'/><br/>\n";
			echo "<br/>\n";
			echo "Round index: <input id='ridx' type='text' name='ridx' value='' maxlength='5'/><br/>\n";
			echo "Team: <input id='team' type='text' name='team' value='$defTeamID' maxlength='5'/><br/>\n";
			echo "Mode: <input id='mode' type='text' name='mode' value='$defRoundStatsMode' maxlength='5'/><br/>\n";
			echo "Worker index: <input id='widx' type='text' name='widx' value='' maxlength='5'/><br/>\n";
			echo "Work/Week Points: <input id='wpnt' type='text' name='wpnt' value='0' maxlength='10'/><br/>\n";
			echo "Total Points: <input id='tpnt' type='text' name='tpnt' value='0' maxlength='10'/><br/>\n";
			echo "Work Units: <input id='wus' type='text' name='wus' value='0' maxlength='10'/><br/>\n";
			echo "<br/>\n";
			echo "Duplicate count: <input id='dcnt' type='text' name='dcnt' value='1' maxlength='5'/><br/>\n";
			echo "<input type='submit' name='action' value='Add' /><br/>\n";
			echo "<br/>\n";
			echo "<br/>\n";
			//---------------------------
			?>
				</fieldset>
			</form>
			</div>
			<br/>
			</div><!-- End Right Column -->
			<?php
			//---------------------------
		} // if ($Login->HasPrivilege(PRIV_DELETE_HISTORY)) 
		//---------------------------
		?>
	</div><!-- End Container 3 -->
	</div><!-- End Container 2 -->
</div><!-- End Container 1 -->

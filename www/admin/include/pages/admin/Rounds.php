<?php
//---------------------------------
/*
 * include/pages/admin/Rounds.php
 * 
 * 
*/
//---------------------------------
global $Login;
$Rounds = new Rounds() or die("Create object failed");
$Config = new Config() or die("Create object failed");
$Contributions = new Contributions() or die("Create object failed");
$Payouts = new Payouts() or die("Create object failed");
$Stats = new Stats() or die("Create object failed");
$Display = new Display() or die("Create object failed");
$Workers = new Workers() or die("Create object failed");
$pagenum = XGetPost('p');
$action = XPost('action');
//---------------------------------
$rAppr = "";
$rActive = false;
$rFunded = "";
$rStarted = "";
$rState = ROUND_STATE_NONE;
$rStatus = "";
$rStats = "";
$rPayment = "";
$rTeamId = "";
$rStatsMode = ROUND_STATS_MODE_NONE;
$rPayMode = ROUND_PAY_MODE_NONE;
$rPayRate = "";
$rTotWork = "";
$rTotPay = "";
//------------------------
$teamId = XGetPost('teamid');
if ($teamId == "")
	$teamId = $Config->Get(CFG_ROUND_TEAM_ID, DEFAULT_ROUND_TEAM_ID);
//------------------------
$payMode = XPost('paymode', false);

if ($payMode === false ||  !is_numeric($payMode))
	$payMode = $Config->Get(CFG_ROUND_PAY_MODE, DEFAULT_ROUND_PAY_MODE);
else
{
	if (XPost('smht', false) !== false)
		$payMode |= ROUND_PAY_MODE_HIDDEN_TEST;
	if (XPost('smdr', false) !== false)
		$payMode |= ROUND_PAY_MODE_FLAG_DRYRUN;
	if (XPost('smnw', false) !== false)
		$payMode |= ROUND_PAY_MODE_FLAG_NAMED_WORKERS_ONLY;
	if (XPost('smuk', false) !== false)
		$payMode |= ROUND_PAY_MODE_FLAG_INC_UNKOWN_WORKERS;
}
//------------------------
$payRate = XPost('payrate');
if ($payRate == "")
	$payRate = $Config->Get(CFG_ROUND_PAY_RATE, DEFAULT_ROUND_PAY_RATE);
$ridx = XGetPost('ridx');
if ($ridx == "")
	$ridx = 0;
$rid = XGetPost('rid');
if ($rid == "")
	$rid = false;
//------------------------
$startAuto = XPost('stauto', false);
//------------------------
$statsModeFahWorkers = XPost('smfw', false);
$statsModeScore = XPost('smscore', false);
if ($statsModeScore !== false && is_numeric($statsModeScore))
{
	$statsMode = $statsModeScore;
	if ($statsModeFahWorkers !== false)
		$statsMode |= ROUND_STATS_MODE_FAH_WORKER;
} 
else $statsMode = $Config->Get(CFG_ROUND_STATS_MODE, DEFAULT_ROUND_STATS_MODE); 
//------------------------
$rListSort = XGetPost('rsort', -1);
$wListSort = XGetPost('wsort', 1);
//------------------------
$rLimit = XGetPost('lmt', false);
if ($rLimit === false || $rLimit == "" || !is_numeric($rLimit))
	$rLimit = 10;
else if ($rLimit == 0)
	$rLimit = false;
//------------------------
?>
<div id="content"><!-- Start Container 1 -->
	<div class="under pad_bot1"><!-- Start Container 2 -->
	<br/>
	<div class="line1 wrapper pad_bot2"><!-- Start Container 3 -->
		<div style="width:940px;"><!-- Start Left Column -->
		<div>
			<h1>Rounds:</h1>
			<p>
			Folding @ Home round list.
			</p>
			<br/>
		</div>
		<?php
			//------------------------
			if ($action == "Add")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_ROUND_ADD)) 
				{
					//------------------------
					if (!is_numeric($teamId) || !is_numeric($statsMode) || !is_numeric($payMode) || !is_numeric($payRate))
					{
						XLogWarn("Admin Rounds Add Round validate parameters failed. Team ID: ".XVarDump($teamId).", stats Mode: ".XVarDump($statsMode).", pay Mode: ".XVarDump($payMode).", pay rate: ".XVarDump($payRate));
						echo "Add round failed. Invalid params.<br/>";
					}
					if (XMaskContains($payMode, ROUND_PAY_MODE_FLAG_INC_UNKOWN_WORKERS) && !XMaskContains($payMode, ROUND_PAY_MODE_FLAG_DRYRUN))
					{
						XLogWarn("Admin Rounds Add Round validate pay mode combindation failed. Team ID: ".XVarDump($teamId).", stats Mode: ".XVarDump($statsMode).", pay Mode: ".XVarDump($payMode).", pay rate: ".XVarDump($payRate));
						echo "Add round failed. Unkown workers requires Dry Run mode because they cannot be paid.<br/>";
					}
					else
					{
						//------------------------
						if (!$Rounds->addRound($teamId, $statsMode, $payMode, $payRate, $startAuto))
						{
							$ridx = 0;
							XLogWarn("Admin Rounds Add Round addRound failed");
							echo "Add round failed.<br/>";
						}
						else
						{
							//$widx = $maxidx+1;
							XLogNotify("Round admin page - adding round (Stats Mode: $statsMode, Pay Mode: $payMode Rate: $payRate)");
							echo "Added round successfully.<br/><br/>\n";
						}
						//------------------------
					}
					//------------------------
				}
				else
					echo "Manually adding rounds require round add privileges.<br/><br/>\n";
				//------------------------
			}
			else if ($rid !== false && ($action == "Approve" || $action == "Unapprove"))
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_ROUND_APPROVE))
				{
					//------------------------
					$r = $Rounds->getRound($rid);
					if ($r === false)
					{
						XLogNotify("Round admin page - $action getRound id $rid failed");
						echo "Round not found.<br/><br/>\n";
					}
					else if (!$r->isActive())
					{
						XLogNotify("Round admin page - $action round id $rid is not active");
						echo "Round is no longer active.<br/><br/>\n";
					}
					else
					{
						$r->approved = ($action == "Approve" ? true : false);
						if (!$r->Update())
						{
							XLogNotify("Round admin page - $action update round failed");
							echo "Round update failed.<br/><br/>\n";
						}
						else
						{
							XLogNotify("Round admin page - round $rid set to $action by $Login->UserName");
							echo "Round successfully $action.<br/><br/>\n";
						}
					}
					//------------------------
				}
				else
					echo "Approving/Unapproving rounds require approval privileges.<br/><br/>\n";
				//------------------------
			}
			else if ($rid !== false && ($action == "Unmark Hidden" || $action == "Mark Hidden"))
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_DELETE_HISTORY))
				{
					//------------------------
					$r = $Rounds->getRound($rid);
					if ($r === false)
					{
						XLogNotify("Round admin page - $action getRound id $rid failed");
						echo "Round not found.<br/><br/>\n";
					}
					else
					{
						if ($action == "Mark Hidden")
							$r->payMode = $r->payMode | ROUND_PAY_MODE_HIDDEN_TEST;
						else if (XMaskContains($r->payMode, ROUND_PAY_MODE_HIDDEN_TEST))
							$r->payMode = $r->payMode ^ ROUND_PAY_MODE_HIDDEN_TEST;
						if (!$r->Update())
						{
							XLogNotify("Round admin page - $action update round failed");
							echo "Round update failed.<br/><br/>\n";
						}
						else
						{
							XLogNotify("Round admin page - round $rid set to $action by $Login->UserName");
							echo "Round successfully $action.<br/><br/>\n";
						}
					}
					//------------------------
				}
				else
					echo "Marking/Unmarking rounds as hidden require delete privileges.<br/><br/>\n";
				//------------------------
			}
			else if ($rid !== false && $action == "Upate Comment")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_DELETE_HISTORY))
				{
					//------------------------
					$comment = XPost('comment', "");
					$comment = ($comment == "" ? false : $comment);
					//------------------------
					$r = $Rounds->getRound($rid);
					if ($r === false)
					{
						XLogNotify("Round admin page - $action getRound id $rid failed");
						echo "Round not found.<br/><br/>\n";
					}
					else
					{
						if (!$r->setComment($comment))
						{
							XLogNotify("Round admin page - $action update round failed");
							echo "Round update failed.<br/><br/>\n";
						}
						else
						{
							XLogNotify("Round admin page - round $rid $action by $Login->UserName to ".XVarDump($comment));
							echo "Round successfully $action.<br/><br/>\n";
						}
					}
					//------------------------
				}
				else
					echo "Setting round comments require delete privileges.<br/><br/>\n";
				//------------------------
			}
			else if ($rid !== false && $action == "Delete")
			{
				if ($Login->HasPrivilege(PRIV_DELETE_HISTORY))
				{
					XLogNotify("User $Login->User is deleting round: $rid");
					if (!$Rounds->deleteRound($rid))
					{
						XLogNotify("Round admin page - rounds failed to deleteRound $rid");
						echo "Failed to delete round.<br/><br/>\n";
					}
					else
					{
						echo "Round successfully deleted.<br/><br/>\n";
						$ridx = 0;
						$rid = false;
					}
				}
				else
					echo "Deleting historical data required delete history privileges.<br/><br/>\n";
			}
			else if ($rid !== false && $action == "Set State")
			{
				if ($Login->HasPrivilege(PRIV_DELETE_HISTORY))
				{
					//------------------------
					if (XPost('stateconf', false) !== false)
					{
						//------------------------
						$state = XPost('state', "");
						$state = ($state == "" ? false : $state);
						//------------------------
						$r = $Rounds->getRound($rid);
						if ($r === false)
						{
							XLogNotify("Round admin page - $action getRound id $rid failed");
							echo "Round not found.<br/><br/>\n";
						}
						else
						{
							if (!$r->forceState($state))
							{
								XLogNotify("Round admin page - $action round forceState to ".XVarDump($state)." failed");
								echo "Round force state failed.<br/><br/>\n";
							}
							else
							{
								XLogNotify("Round admin page - round $rid $action by $Login->UserName to ".XVarDump($state));
								echo "Round successfully $action.<br/><br/>\n";
							}
						}
						//------------------------
					}
					else
						echo "Manually setting round state requires the confirmation box to be checked.<br/><br/>\n";
					//------------------------
				}
				else
					echo "Manually setting round state requires delete history privileges.<br/><br/>\n";
			}
			else if ($rid !== false && $action == "Reparse FahClient Data From File")
			{
				if ($Login->HasPrivilege(PRIV_DELETE_HISTORY))
				{
					//------------------------
					$data = "";
					$FahClient = new FahClient() or die("Create object failed");
					if (!$FahClient->reparseDataFromFile($rid, "./reparse_fahclient_data.json"))
					{
						XLogNotify("Round admin page - $action FahClient reparseDataFromFile failed");
						echo "Round $action failed.<br/><br/>\n";
					}
					else
					{
						XLogNotify("Round admin page - round $rid $action by $Login->UserName to ".XVarDump($state));
						echo "Round successfully $action.<br/><br/>\n";
					}
					//------------------------
				}
				else
					echo "Reparsing round requires delete history privileges.<br/><br/>\n";
			}
			else if ($rid !== false && $action == "Reparse FahClient CheckStats")
			{
				if ($Login->HasPrivilege(PRIV_DELETE_HISTORY))
				{
					//------------------------
					$FahClient = new FahClient() or die("Create object failed");
					if (!$FahClient->checkStats($rid, true/*reparse*/))
					{
						XLogNotify("Round admin page - $action FahClient requestPayouts(reparse) failed");
						echo "Round $action failed.<br/><br/>\n";
					}
					else
					{
						XLogNotify("Round admin page - round $rid $action by $Login->UserName to ".XVarDump($state));
						echo "Round successfully $action.<br/><br/>\n";
					}
					//------------------------
				}
				else
					echo "Reparsing round requires delete history privileges.<br/><br/>\n";
			}
			
			
			
			else if ($rid !== false && $action == "Reparse Request Payouts")
			{
				if ($Login->HasPrivilege(PRIV_DELETE_HISTORY))
				{
					//------------------------
					$r = $Rounds->getRound($rid);
					if ($r === false)
					{
						XLogNotify("Round admin page - $action getRound id $rid failed");
						echo "Round not found.<br/><br/>\n";
					}
					else
					{
						if (!$r->requestPayouts(true/*reparse*/))
						{
							XLogNotify("Round admin page - $action round requestPayouts(reparse) failed");
							echo "Round $action failed.<br/><br/>\n";
						}
						else
						{
							XLogNotify("Round admin page - round $rid $action by $Login->UserName to ".XVarDump($state));
							echo "Round successfully $action.<br/><br/>\n";
						}
					}
					//------------------------
				}
				else
					echo "Reparsing round requires delete history privileges.<br/><br/>\n";
			}
			else if ($rid !== false && $action == "Reparse Send Payout")
			{
				if ($Login->HasPrivilege(PRIV_DELETE_HISTORY))
				{
					//------------------------
					$r = $Rounds->getRound($rid);
					if ($r === false)
					{
						XLogNotify("Round admin page - $action getRound id $rid failed");
						echo "Round not found.<br/><br/>\n";
					}
					else
					{
						if (!$r->sendPayout(true/*reparse*/))
						{
							XLogNotify("Round admin page - $action round sendPayout failed");
							echo "Round $action failed.<br/><br/>\n";
						}
						else
						{
							XLogNotify("Round admin page - round $rid $action by $Login->UserName to ".XVarDump($state));
							echo "Round successfully $action.<br/><br/>\n";
						}
					}
					//------------------------
				}
				else
					echo "Reparsing round requires delete history privileges.<br/><br/>\n";
			}
			//------------------------
			# Round List Columns (<Width/Col style>, <Header Title>, <Field for sorting or false>)
			$Columns = array(	array(	"35px",		"ID",			DB_ROUND_ID),
								array(	"180px",	"Started",		DB_ROUND_DATE_STARTED),
								array(	"100px",	"Status",		false),
								array(	"120px",	"Rate",			false),
								array(	"120px",	"Total Work",	DB_ROUND_TOTAL_WORK),
								array(	"200px",	"Total Pay",	DB_ROUND_TOTAL_PAY),
								array(	"50px",		"Apvd",			DB_ROUND_APPROVED),
								array(	"50px",		"Fund",			DB_ROUND_FUNDED)
								);
			//------------------------	
			echo "<table class='admin-table'>\n<thead class='admin-scrolltable-header'>\n<tr style='width:100%;'>\n";
			//------------------------	
			$colIdx = 1;
			foreach ($Columns as $col)
			{
				if ($colIdx == $rListSort)
					$sort = $colIdx * -1;
				else
					$sort = $colIdx;
				echo "\t<th".($col[0] !== false ? " style='width:$col[0];'" : "");
				if ($col[2] !== false)
					echo " onclick=\"document.location.href='./Admin.php?p=$pagenum&amp;ridx=$ridx&amp;lmt=$rLimit&amp;rsort=$sort&amp;wsort=$wListSort&amp;rid=".($rid === false ? "" : $rid)."&amp;".SecToken()."';\"";
				echo ">$col[1]</th>\n";
				$colIdx++;
			}
			//------------------------	
			echo "</tr></thead>\n<tbody class='admin-scrolltable-body'>\n";
			//------------------------	
			$scidx = ($rListSort < 0 ? ($rListSort * -1) : $rListSort);
			if ($scidx === false || $scidx < 1 || $scidx >= sizeof($Columns))
				$scidx = 1;
			$sort = $Columns[$scidx - 1][2];
			if ($rListSort < 0)
				$sort .= " DESC";				
			//------------------------	
			//echo "Sort: '$sort'<br/>"; // debug sorting
			//------------------------	
			$rlist = $Rounds->getRounds(false/*OnlyDone*/, $rLimit, $sort);
			if ($rlist === false)
				XLogError("Rounds admin page - getRounds failed");
			if ($rlist === false || sizeof($rlist) == 0)
			{
				//------------------------
				echo "<tr class='admin-row'>";
				$colIdx = 0;
				foreach ($Columns as $col)
				{
					echo "<td".($col[0] !== false ? " style='width:".$col[0].";'" : "")." onclick=\"document.location.href='./Admin.php?p=$pagenum&amp;ridx=$ridx&amp;rsort=$colIdx&amp;wsort=$wListSort&amp;rid=$rid&amp;".SecToken()."';>&nbsp;</td>";
					$colIdx++;
				}
				echo "</tr>\n";
				//------------------------
			}
			else
			{
				//------------------------
				$rowIdx = 0;
				foreach ($rlist as $r)
				{
					//------------------------
					if ( ($r->payMode & ROUND_PAY_MODE_MASK) == ROUND_PAY_MODE_RATE) 
						$payModeName = "Rate ".sprintf("%0.f", $r->payRate);
					else if ( ($r->payMode & ROUND_PAY_MODE_MASK) == ROUND_PAY_MODE_PERCENT) 
						$payModeName = "Percentage";
					else $payModeName = "None";
					//-----------
					if (XMaskContains($r->payMode, ROUND_PAY_MODE_FLAG_DRYRUN)) $payModeName .= " (Dry Run/No Pay)";
					if (XMaskContains($r->payMode, ROUND_PAY_MODE_HIDDEN_TEST)) $payModeName .= " (hidden test)";
					//------------------------
					if ($ridx == $rowIdx)
					{
						$rowClass = 'admin-row-sel';
						$rid = $r->id;
						$rComment = $r->getComment();
						$rComment = ($rComment === false ? "&lt;error&gt;" : XEncodeHTML($rComment));
						$rActive = $r->isActive();
						$rState = $r->state;
						$rStatus = XEncodeHTML($r->stateText());
						$rAppr = $r->approved;
						$rFunded = $r->funded;
						$rStarted = $Display->htmlLocalDateTime($r->dtStarted);
						$rStats = ($r->dtStatsDone != "" ? "(recorded) ".$Display->htmlLocalDateTime($r->dtStatsDone) : "(queued) ".$Display->htmlLocalDateTime($r->dtStatsRequested));
						$rTeamId = ($r->teamId === false ? "" : $r->teamId);
						$rPayment =  ($r->dtPaid != "" ? "(paid) ".$Display->htmlLocalDateTime($r->dtPaid) : ($r->dtPayRequested != "" ? "(queued) ".$Display->htmlLocalDateTime($r->dtPayRequested) : ""));
						$rStatsMode = $r->statsMode;
						$rPayMode = $r->payMode;
						$rPayModeName = $payModeName;
						$rPayRate = sprintf("%0.f", $r->payRate);
						$rTotWork = $r->totalWork;
						$rTotPay = sprintf("%0.f", $r->totalPay);
					}
					else $rowClass = 'admin-row';
					//------------------------
					echo "<tr class='$rowClass' onclick=\"document.location.href='./Admin.php?p=$pagenum&amp;ridx=$rowIdx&amp;lmt=$rLimit&amp;rsort=$rListSort&amp;wsort=$wListSort&amp;rid=$r->id&amp;".SecToken()."';\">\n";
					//------------------------
					$colData = array(	$r->id,
										$Display->htmlLocalDateTime($r->dtStarted),
										XEncodeHTML($r->stateText()),
										$payModeName,
										$r->totalWork,
										sprintf("%0.f", $r->totalPay),
										($r->approved ? "Yes" : "No"),
										($r->funded ? "Yes" : "No")
									);
					//------------------------
					for ($i = 0;$i < sizeof($colData);$i++)
					{
						$style = "";
						if ($rowIdx == 0 && $Columns[$i][0] !== false)
							$style .= "width:".$Columns[$i][0].";";
						if ($Columns[$i][1] == "Status" || $Columns[$i][1] == "Rate" || $Columns[$i][1] == "Total Work" || $Columns[$i][1] == "Total Pay")
							$style .= "text-align:right;";
						if ($style != "")
							$style = " style='$style'";
						echo "<td$style>".$colData[$i]."</td>";
					}
					//------------------------
					echo "</tr>\n";
					//------------------------
					$rowIdx++;
					//------------------------
				}
				//------------------------
			}
			//------------------------
			echo "</tbody></table>\n";		
			//------------------------	
		?>
		</div><!-- End Left Column -->
		<?php
		if ($rid !== false)
		{
		?>
		<div><!--  pad_left1 left  marg_left1 margin: auto;  Start Right Column -->
		<div class="admin-edit" style="width:930px;padding: 5px 3px;">
		<form action="./Admin.php" method="post" enctype="multipart/form-data">
			<fieldset class="loginBox">
				Round List Limit: <input id='lmt' type='text' name='lmt' value='<?php echo ($rLimit === false ? 0 : $rLimit); ?>' maxlength='6'/>
				<input type='submit' name='action' value='Update Limit' /><br/>
				<br/>
				<h2>Selected round:</h2><br/>
				<div style="width:100%;height:760px;">
				<div style="width:100%;">
				<div style="width:410px;"><!-- float:left;-->
				<h3>Round Details</h3>
				<table class='admin-table' style="width:400px;">
				<thead class='admin-scrolltable-header'>
					<tr style='width:100%;'><th style='width: 100px;'>Attribute</th><th style='width: 280px;'>Value</th></tr>
				</thead>
				<tbody class='admin-scrolltable-body'  style="float:left; height:100%;">
				<?php 
					//------------------------
					$attributes = array( 	array("ID", "$rid"),
											array("Comment", $rComment),
											array("Active", ($rActive === true ? "Yes" : "No")),
											array("Started", $rStarted),
											array("Status", $rStatus),
											array("Stats Mode", $rStatsMode), 
											array("Stats", $rStats),
											array("Payment", $rPayment),
											array("Team", $rTeamId),
											array("Pay Mode", $rPayModeName),
											array("Total Work", $rTotWork),
											array("Total Pay", $rTotPay),
											array("Approved", ($rAppr === true ? "Yes" : "No")),
											array("Funded", ($rFunded === true  ? "Yes" : "No")));
					//------------------------
					$rowIdx = 0;
					foreach ($attributes as $attr)
					{
						echo "<tr class='admin-row'><td".($rowIdx == 0 ? " style='width:100px;'" : "").">$attr[0]</td><td style='text-align: right;".($rowIdx == 0 ? " width:280px;" : "")."'>$attr[1]</td></tr>\n";
						$rowIdx++;
					}
					//------------------------
				?>	
				</tbody></table>
				</div>
				<br/>
				<div style="width: 500px; "> <!-- float:right;margin: auto; margin-right: 17px; -->
				<h3>Round Contributions</h3>
				<table class='admin-table' style=" width:500px;"> <!-- float:right; margin: auto; -->
				<thead class='admin-scrolltable-header'>
					<tr style='width:100%;'><th style='width: 150px;'>Rate</th><th style='width: 100px;'>Account</th><th style='width: 220px;'>Outcome</th><th style='width: 100px;'>Txid</th></tr>
				</thead>
				<tbody class='admin-scrolltable-body'  style="height:100px;">
				<?php 
					//------------------------
					$contList = $Contributions->findRoundContributions($rid);
					$rows = array();
					if ($contList === false)
						$rows[] = array("Error", "Error", "Error", "Error");
					else if (sizeof($contList) == 0)
						$rows[] = array("&nbsp;", "&nbsp;", "&nbsp;", "&nbsp;");
					else
					{
						//------------------------
						foreach ($contList as $cont)
						{
							//------------------------
							if ($cont->mode == CONT_MODE_NONE)
								$strRate = "Disabled";
							else if ($cont->mode == CONT_MODE_FLAT)
								$strRate = $cont->value." per round".(XMaskContains($cont->flags, CONT_FLAG_REQUIRED) ? "(required)" : "");
							else if ($cont->mode == CONT_MODE_PERCENT)
								$strRate = $cont->value."% match".(XMaskContains($cont->flags, CONT_FLAG_REQUIRED) ? "(required)" : "");
							else if ($cont->mode == CONT_MODE_ALL)
								$strRate = "(all)";
							else if ($cont->mode == CONT_MODE_EACH)
								$strRate = $cont->value." each".(XMaskContains($cont->flags, CONT_FLAG_REQUIRED) ? "(required)" : "");
							else
								$strRate = "Invalid Mode";
							//------------------------
							if ($cont->outcome == CONT_OUTCOME_NONE)
								$strOutcome = "None";
							else if ($cont->outcome == CONT_OUTCOME_PAID)
								$strOutcome = "Paid ".($cont->dtDone == "" ? "(no done date recorded)" : $Display->htmlLocalDateTime($cont->dtDone));
							else if ($cont->outcome == CONT_OUTCOME_NOFUNDS_WAITING)
								$strOutcome = "Waiting for funds";
							else if ($cont->outcome == CONT_OUTCOME_NOFUNDS_SKIPPED)
								$strOutcome = "Skipped, lack of funds";
							else if ($cont->outcome == CONT_OUTCOME_FAILED_WAITING)
								$strOutcome = "Failed, waiting to retry";
							else if ($cont->outcome == CONT_OUTCOME_FAILED_SKIPPING)
								$strOutcome = "Failed, skipped";
							else if ($cont->outcome == CONT_OUTCOME_FAILED_WONTFIX)
								$strOutcome = "Failed, won't fix, skipped";
							else
								$strOutcome = "Invalid outcome";
							//------------------------
							if ($cont->txid != "")
								$strTxid = "<a href='".BRAND_TX_LINK.XEncodeHTML($cont->txid)."'>".XEncodeHTML(substr($cont->txid, 0, 7))."...</a>";
							else
								$strTxid = "&nbsp;";
							//------------------------
							$rows[] = array($strRate, ($cont->account === false ? "" : $cont->account), $strOutcome, $strTxid);
							//------------------------
						}
						//------------------------
					}
					//------------------------
					$rowIdx = 0;
					foreach ($rows as $row)
					{
						echo "<tr class='admin-row'><td".($rowIdx == 0 ? " style='width:150px;'" : "").">$row[0]</td><td style='text-align: right;".($rowIdx == 0 ? " width:100px;" : "")."'>$row[1]</td><td style='text-align: right;".($rowIdx == 0 ? " width:220px;" : "")."'>$row[2]</td><td style='text-align: right;".($rowIdx == 0 ? " max-width:100px;text-overflow:ellipse;width:100px;" : "")."'>$row[3]</td></tr>\n";
						$rowIdx++;
					}
					//------------------------
				?>	
				</tbody></table>	
				</div>
				</div>
				<br/>
				<div style="width:900px;margin-top:15px;float:left;">
				<h3>Worker and Payout Details</h3>
				<table class='admin-table' > <!-- float:right; margin: auto; -->
				<?php 
					//------------------------
					//			Columns 	(<Header Title>, <style>)
					$Columns = array( 	array("Worker", "width:440px;"),
										array("Points", "width:105px;"),
										array("Pay",	"width:320px;")
										);
					//------------------------
					echo "<thead class='admin-scrolltable-header'>\n";
					echo "<tr style='width:100%;'>";
					//------------------------
					$colIdx = 1;
					foreach ($Columns as $col)
					{
						echo "<th";
						if ($col[1] !== false)
							echo " style='$col[1]'";
						if ($colIdx == $wListSort)
							$sort = $colIdx * -1;
						else
							$sort = $colIdx;
						echo " onclick=\"document.location.href='./Admin.php?p=$pagenum&amp;ridx=$ridx&amp;lmt=$rLimit&amp;rsort=$rListSort&amp;wsort=$sort&amp;rid=".($rid === false ? "" : $rid)."&amp;".SecToken()."';\"";
						echo ">$col[0]</th>";
						$colIdx++;
					}
					//------------------------
					echo "</tr></thead><tbody class='admin-scrolltable-body'  style='height:200px;'>";
					//------------------------
					$rows = array();
					//------------------------
					$cid = $wListSort;
					if ($cid < 0)
						$cid *= -1;
					//------------------------
					if ($cid == 2)
						$sort = DB_STATS_WORK;
					else if ($cid == 3)
						$sort = DB_STATS_PAYOUT;
					else // $wListSort == 1
						$sort = DB_STATS_WORKER;
					//------------------------
					if ($wListSort < 0)
						$sort .= " DESC";
					//------------------------
					//echo "Sort: $sort<br/>"; // debug sorting
					//------------------------
					$payoutList = $Payouts->findRoundPayouts($rid);
					$statList = $Stats->findRoundStats($rid, $sort);
					$statNotZeroCount = 0;
					//------------------------
					if ($payoutList === false || $statList === false)
						$rows[] = array("Error", "Error", "Error");
					else if (sizeof($statList) == 0)
						$rows[] = array("&nbsp;", "&nbsp;", "&nbsp;");
					else
					{
						//------------------------
						foreach ($statList as $stat)
						{
							//------------------------
							$payout = false;
							foreach ($payoutList as $p)
								if ($p->id == $stat->payoutIdx)
								{
									$payout = $p;
									break;
								}
							//------------------------
							if ($payout === false)
								$widx = $stat->workerIdx;
							else
								$widx = $payout->workerIdx;
							//------------------------
							$worker = $Workers->getWorker($widx);
							if ($worker === false)
								$strWorker = "($widx) [not found]";
							else
							{
								$strWorker = "(<a href='./Admin.php?p=4&amp;idx=$widx&amp;".SecToken()."'>$widx</a>) ";
								if ($worker->address != $worker->uname)
									$strWorker .= $worker->uname." (".($worker->address == "" ? "not set" : "<a href='".BRAND_ADDRESS_LINK.XEncodeHTML($worker->address)."'>".XEncodeHTML($worker->address)."</a>").")";
								else
									$strWorker .= "<a href='".BRAND_ADDRESS_LINK.XEncodeHTML($worker->address)."'>".XEncodeHTML($worker->address)."</a>";
							}
							//------------------------
							$work =  $stat->work();
							if ($work === false)
								$work = "error";
							else if ($work > 0)
								$statNotZeroCount++;
							//------------------------
							$strPoints = (($stat->dtPolled !== false && $stat->dtPolled != "") ? $work : "not polled");
							$strPoints .= " (<a href='./Admin.php?p=9&amp;idx=$stat->id&amp;".SecToken()."'>$stat->id</a>)";
							//------------------------
							if ($payout === false)
								$strPayout = "";
							else
							{
								$strPayout = "$payout->pay ";
								if ($payout->dtPaid != "")
									$strPayout .= "(paid tx:&nbsp;".($payout->txid == "" ? "&nbsp;" : "<a href='".BRAND_TX_LINK.XEncodeHTML($payout->txid)."'>".XEncodeHTML(substr($payout->txid, 0, 7))."...</a>)");
								else
									$strPayout .= "(not paid)";
								$strPayout .= "(<a href='./Admin.php?p=7&amp;idx=$payout->id&amp;".SecToken()."'>$payout->id</a>)";
							}
							//------------------------
							$rows[] = array(	$strWorker,
												$strPoints,
												$strPayout,
												);
							//------------------------
						}
						//------------------------
					}
					//------------------------
					$rowIdx = 0;
					foreach ($rows as $row)
					{
						echo "<tr class='admin-row'><td".($rowIdx == 0 ? " style='width:440px;'" : "").">$row[0]</td><td style='text-align: right;".($rowIdx == 0 ? " width:105px;" : "")."'>$row[1]</td><td style='text-align: right;".($rowIdx == 0 ? " width:320px;" : "")."'>$row[2]</td></tr>\n";
						$rowIdx++;
					}
					//------------------------
				?>	
				</tbody></table>	
				</div>
				</div>
				<?php PrintSecTokenInput(); ?>
				<input type="hidden" name="p" value="<?php echo $pagenum; ?>"/><!-- Page -->
				<input type="hidden" name="ridx" value="<?php echo $ridx; ?>"/>
				<input type="hidden" name="rid" value="<?php echo ($rid === false ? "" : $rid); ?>"/>
				<input type="hidden" name='rsort' value='<?php echo $rListSort; ?>' />
				<input type="hidden" name='wsort' value='<?php echo $wListSort; ?>' />
				<?php 
					//------------------------
					echo "Stat count: ".($statList === false ? "(error)" : "".sizeof($statList))." ($statNotZeroCount non-zero), Payout count: ".($payoutList === false ? "(error)" : "".sizeof($payoutList))."<br/>\n";
					//------------------------
					$hasDeletePriv = $Login->HasPrivilege(PRIV_DELETE_HISTORY);
					//------------------------
					echo "<br/><div style='float:left;'>Privileged Actions:</div><br/>\n";
					//------------------------
					echo "<label class='loginLabel' for='comment'>Comment</label><br/>\n";
					echo "<input style='margin-top: 8px; width:220px;' id='comment' type='text' name='comment' value='$rComment' maxlength='".C_MAX_COMMENT_TEXT_LENGTH."' ".(!$hasDeletePriv ? "disabled='disabled' " : "")."/>\n";
					if ($hasDeletePriv)
						echo "<input type='submit' name='action' value='Upate Comment' />\n";
					echo "<br/><br/>\n";
					//------------------------
					echo "<label class='loginLabel' for='state'>State</label><br/>\n";
					echo "<input style='margin-top: 8px; width:220px;' id='state' type='text' name='state' value='$rState' maxlength='2' ".(!$hasDeletePriv ? "disabled='disabled' " : "")."/>\n";
					echo "<input type='submit' name='action' value='Set State' />\n";
					echo "<input id='stateconf' type='checkbox' name='stateconf' />Confirm debug set round<br/>";
					//------------------------
					if ($rActive === true && $Login->HasPrivilege(PRIV_ROUND_APPROVE)) 
					{
						if ($rAppr === true)
							echo "<br/><input type='submit' name='action' value='Unapprove' /><br/>\n";
						else
							echo "<br/><input type='submit' name='action' value='Approve' /><br/>\n";
						$hadPrevious = true;
						echo "<br/>";
					}
					//------------------------
					if ($hasDeletePriv) 
					{
						echo "<input type='submit' name='action' value='".(XMaskContains($rPayMode, ROUND_PAY_MODE_HIDDEN_TEST) ? "Unmark Hidden" : "Mark Hidden")."' /><br>\n";
						echo "<br/><input type='submit' name='action' value='Delete' /><br/>\n";
						echo "<br/>Reparsing:<br/>\n";
						echo "&nbsp;&nbsp;<input type='submit' name='action' value='Reparse FahClient Data From File' /> expected at ./(admin)/reparse_fahclient_data.json<br/>\n";
						echo "&nbsp;&nbsp;<input type='submit' name='action' value='Reparse FahClient CheckStats' /><br/>\n";
						echo "&nbsp;&nbsp;<input type='submit' name='action' value='Reparse Request Payouts' /><br/>\n";
						echo "&nbsp;&nbsp;<input type='submit' name='action' value='Reparse Send Payout' /><br/>\n";
					}
					//------------------------
				?>
				<br/>
			</fieldset>
		</form>
		</div>
		<br/>
		</div><!-- End Right Column -->
		<?php
		} // if ($rid !== false)
		?>
		<br/>
		<br/>
		<?php 
			if ($Login->HasPrivilege(PRIV_ROUND_ADD)) 
			{
		?>
		<div class="pad_left1 marg_left1"><!-- left margin: auto;  Start Right Column -->
		<div class="admin-edit" style="width:300px;">
		<form action="./Admin.php" method="post" enctype="multipart/form-data">
			<fieldset class="loginBox">
				<legend style='font-weight:bold;font-size:120%;'>New round:</legend>
				<br/>
				<?php PrintSecTokenInput(); ?>
				<input type="hidden" name="p" value="<?php echo $pagenum; ?>"/><!-- Page -->
				<input type="hidden" name="ridx" value="<?php echo $ridx; ?>"/>
				<input type="hidden" name="rid" value="<?php echo ($rid === false ? "" : $rid); ?>"/>
				<input type="hidden" name='rsort' value='<?php echo $rListSort; ?>' />
				<input type="hidden" name='wsort' value='<?php echo $wListSort; ?>' />
					<div>
						Team ID:&nbsp;<input style="width:100px;" id="teamid" type="text" name="teamid" value="<?php echo XEncodeHTML($teamId);?>" maxlength="12" />
					</div>
					<br/>
					<div>
						<input id='smht' type='checkbox' name='smht'<?php if (XMaskContains($payMode, ROUND_PAY_MODE_HIDDEN_TEST)) echo " checked='checked'";?> />Test, hidden from public<br/>
						<input id='smdr' type='checkbox' name='smdr'<?php if (XMaskContains($payMode, ROUND_PAY_MODE_FLAG_DRYRUN)) echo " checked='checked'";?> />Faked dry run (no Payout)<br/>
						<br/>
						<input id='smnw' type='checkbox' name='smnw'<?php if (XMaskContains($payMode, ROUND_PAY_MODE_FLAG_NAMED_WORKERS_ONLY)) echo " checked='checked'";?> />Named workers only (name not address)<br/>
						<input id='smuk' type='checkbox' name='smuk'<?php if (XMaskContains($payMode, ROUND_PAY_MODE_FLAG_INC_UNKOWN_WORKERS)) echo " checked='checked'";?> />Include unknown workers (no address)<br/>
						<br/>
						<input id='smfw' type='checkbox' name='smfw'<?php if (XMaskContains($statsMode, ROUND_STATS_MODE_FAH_WORKER)) echo " checked='checked'";?> />F@H Workers List<br/>
						<div>
							<input id='smscore' type='radio' name='smscore' value='<?php echo ROUND_STATS_MODE_EO_WEEK_SCORE;  ?>' <?php if (XMaskContains($statsMode, ROUND_STATS_MODE_EO_WEEK_SCORE)) echo " checked='checked'";?> />EO Week Scores<br/>
							<input id='smscore' type='radio' name='smscore' value='<?php echo ROUND_STATS_MODE_EO_TOTAL_SCORE;  ?>' <?php if (XMaskContains($statsMode, ROUND_STATS_MODE_EO_TOTAL_SCORE)) echo " checked='checked'";?> />EO Total Scores<br/>
							<input id='smscore' type='radio' name='smscore' value='<?php echo ROUND_STATS_MODE_EO_TOTAL_LESS_WEEK_SCORE;  ?>' <?php if (XMaskContains($statsMode, ROUND_STATS_MODE_EO_TOTAL_LESS_WEEK_SCORE)) echo " checked='checked'";?> />EO Total Less Week Scores<br/>
							<input id='smscore' type='radio' name='smscore' value='<?php echo ROUND_STATS_MODE_FAH_SCORE; ?>' <?php if (XMaskContains($statsMode, ROUND_STATS_MODE_FAH_SCORE)) echo " checked='checked'";?> />F@H Scores<br/>
						</div>
					</div>
					<br/>
					<div>
						<input id='paymode' type='radio' name='paymode' value='<?php echo ROUND_PAY_MODE_PERCENT; ?>' <?php if ( ($payMode & ROUND_PAY_MODE_MASK) == ROUND_PAY_MODE_NONE || ($payMode & ROUND_PAY_MODE_MASK) == ROUND_PAY_MODE_PERCENT) echo " checked='checked'";?> />Percentage<br/>
						<div>
							<input id='paymode' type='radio' name='paymode' value='<?php echo ROUND_PAY_MODE_RATE; ?>' <?php if ( ($payMode & ROUND_PAY_MODE_MASK) == ROUND_PAY_MODE_RATE) echo " checked='checked'";?> />Rate:&nbsp;<input style="width:120px;" id="rate" type="text" name="payrate" value="<?php echo XEncodeHTML($payRate);?>" maxlength="40" />
						</div>
					</div>
					<br/>
					<input id='stauto' type='checkbox' name='stauto' checked='checked'/>Start Round Automation<br/>
					<br/>
					<input type="submit" name="action" value="Add" />&nbsp;
				<br/>
				<br/>
			</fieldset>
		</form>
		</div>
		<br/>
		</div><!-- End Right Column -->
		<?php 
			} // if ($Login->HasPrivilege(PRIV_ROUND_ADD)) 
		?>
	</div><!-- End Container 3 -->
	</div><!-- End Container 2 -->
</div><!-- End Container 1 -->

<?php
//---------------------------------
/*
 * include/pages/admin/ContPayouts.php
 * 
 * 
*/
//---------------------------------
global $Login;
$Contributions = new Contributions() or die("Create object failed");
$Display = new Display() or die("Create object failed");
$Wallet = new Wallet() or die("Create object failed");
$Wallet->Init();
$pagenum = XGetPost('p');
$ridx = XGetPost('ridx', false);
$action = XGetPost('action', false);
$showConts = XGetPost('sc', false);
if ($showConts !== false)
	$showConts = true;
$dridx = XGetPost("dridx");
if ($dridx == "")
	$dridx = false;
$selID = false;
$selCont = false;
//---------------------------------
function getContributionRow($Display, $idx, $roundIdx, $number, $name, $mode, $value, $account, $flags, $adID, $outcome, $dtDone, $txid)
{
	//------------------------
	$number = XEncodeHTML($number);
	$name = XEncodeHTML($name);
	$value = XEncodeHTML($value);
	$account = XEncodeHTML($account);
	$adID = XEncodeHTML($adID);
	$txid = XEncodeHTML($txid);
	//------------------------
	if (is_Numeric($mode) && $mode == CONT_MODE_NONE)
		$modeText = "Disabled";
	else if (is_Numeric($mode) && $mode == CONT_MODE_FLAT)
		$modeText = "Flat Rate";
	else if (is_Numeric($mode) && $mode == CONT_MODE_PERCENT)
		$modeText = "Percentage";
	else if (is_Numeric($mode) && $mode == CONT_MODE_ALL)
		$modeText = "All";
	else 
		$modeText = "(Unkown ".XEncodeHTML($mode).")";
	//------------------------
	$flagsText = "";
	if (XMaskContains($flags, WALLET_AUTO_FLAG_REQUIRED))
		$flagsText .= " (Required)";
	//------------------------
	if ($outcome == CONT_OUTCOME_NONE)
		$strOutcome = "None";
	else if ($outcome == CONT_OUTCOME_PAID)
		$strOutcome = "Paid ".($dtDone == "" ? "(no done date recorded)" : $Display->htmlLocalDateTime($dtDone));
	else if ($outcome == CONT_OUTCOME_NOFUNDS_WAITING)
		$strOutcome = "Waiting for funds";
	else if ($outcome == CONT_OUTCOME_NOFUNDS_SKIPPED)
		$strOutcome = "Skipped, lack of funds";
	else if ($outcome == CONT_OUTCOME_FAILED_WAITING)
		$strOutcome = "Failed, waiting to retry";
	else if ($outcome == CONT_OUTCOME_FAILED_SKIPPING)
		$strOutcome = "Failed, skipped";
	else if ($outcome == CONT_OUTCOME_FAILED_WONTFIX)
		$strOutcome = "Failed, won't fix, skipped";
	else
		$strOutcome = "Invalid outcome";
	//------------------------
	if ($txid !== "0" && $txid !== "" && $txid !== false)
		$txidText = "$strOutcome <a href='".BRAND_TX_LINK.XEncodeHTML($txid)."'>".XEncodeHTML(substr($txid, 0, 7))."...</a>";
	else
		$txidText = "$strOutcome $txid";
	//------------------------
	return array($idx, $roundIdx, $number, $name, $modeText.$flagsText, $value, $account, $txidText);
}
//---------------------------------
?>
<div id="content"><!-- Start Container 1 -->
	<div class="under pad_bot1"><!-- Start Container 2 -->
	<br/>
	<div class="line1 wrapper pad_bot2"><!-- Start Container 3 -->
		<div class="col1" style="float:none; width:780px;"><!-- Start Left Column -->
		<div>
			<h1>Contributions:</h1>
			<p>
			Folding @ Home Contribution Configuration.
			</p>
			<br/>
		</div>
		</div> <!-- End Left Column -->
		<div class="col1 pad_left1" style="float:none;width:80%;"><!-- Start Right Column -->
		<div class="admin-edit" style="width:1000px;">
		<form action="./Admin.php" method="post" enctype="multipart/form-data">
			<fieldset class="loginBox">
				<legend style="font-weight:bold;font-size:120%;">Configure Wallet:</legend>
				<br/>
				<?php 
					PrintSecTokenInput(); 
					if ($showConts !== false)
						echo "<input type='hidden' name='sc' value='1' />";
				
				?>
				<input type="hidden" name="p" value="<?php echo $pagenum; ?>"/><!-- Page -->
				<div>
					<?php
							//------------------------	
							$contList = $Contributions->loadContributions( ($showConts ? false : true) );
							//------------------------	
							$canDelete = $Login->HasPrivilege(PRIV_DELETE_HISTORY);
							//---------------------------------
							$wrappingStyle = "overflow:hidden;text-overflow:ellipse;white-space:nowrap;";
							//------------------------	
							$cols = array(	array("Idx", 40, $wrappingStyle."text-align:right;"),
											array("Rnd", 40, $wrappingStyle."text-align:right;"),
											array("Num", 40, $wrappingStyle."text-align:right;"),
											array("Name", 200, $wrappingStyle),
											array("Mode", 100, $wrappingStyle),
											array("Value", 70, $wrappingStyle."text-align:right;"),
											array("Account", 150, $wrappingStyle),
											array("Outcome", 320, $wrappingStyle."text-align:right;"),
											);
							//------------------------	
							if ($contList === false)
								XLogError("Admin Contributions Payouts failed to getContributions (a) (default)");
							else
							{
								//---------------------------------
								$rowIdx = 0;
								if ($ridx !== false)
									foreach ($contList as $cont)
									{
										if ($ridx == $rowIdx)
										{
											$selID = $cont->id;
											$selCont = $cont;
										}
										$rowIdx++;
									}
								//---------------------------------
								if ($selCont !== false)
								{
									//---------------------------------
									XLogNotify("action : ".XVarDump($action));
									if ($action == "Clear Payment")
									{
										//---------------------------------
										if ($canDelete)
										{
											//---------------------------------
											$selCont->dtDone = "";
											$selCont->outcome = CONT_OUTCOME_NONE;
											$selCont->txid = false;
											if (!$selCont->Update())
											{
												XLogError("Admin Contribution Payout Clear Payment Update failed");
												echo "<div>Clear Contribution Payout failed.</div>\n";
											}
											else
												echo "<div>Cleared Contribution Payout successfully.</div>\n";
											//---------------------------------
										}
										else
										{
											XLogNotify("Admin Contribution Payout action '$action' doesn't have proper privileges: $Login->UserName");
											echo "Contribution Payout $action failed. Action requires proper privileges.<br/>";
										}
										//---------------------------------
										$selCont = $Contributions->loadContribution($selCont->id);
										//---------------------------------
									}
									else if ($action == "Delete" && $selCont->id != -1)
									{
										//---------------------------------
										if ($canDelete)
										{
											//---------------------------------
											if (!$Contributions->deleteContribution($selCont->id))
											{
												XLogError("Admin Contribution Payout deleteContribution failed");
												echo "<div>Delete Contribution Payout failed.</div>\n";
											}
											else
												echo "<div>Contribution Payout deleted successfully.</div>\n";
											//---------------------------------
										}
										else
										{
											XLogNotify("Admin Contribution Payout action '$action' doesn't have proper privileges: $Login->UserName");
											echo "Contribution Payout $action failed. Action requires proper privileges.<br/>";
										}
										//---------------------------------
										$contList = $Contributions->loadContributions( ($showConts ? false : true) );
										$selID  = false;
										$selCont = false;
										$ridx = false;
										//---------------------------------
									}
								} // if ($selCont !== false)
								//---------------------------------
								if ($action == "Delete All From Round")
								{
									//---------------------------------
									if ($canDelete)
									{
										//---------------------------------
										if ($dridx !== false && is_numeric($dridx))
										{
											//---------------------------------
											if (!$Contributions->deleteAllRound($dridx))
											{
												XLogError("Admin Contribution Payout deleteAllRound failed");
												echo "<div>Delete all contributions from round failed.</div>\n";
											}
											else
											{
												echo "<div>Deleted all contributions from round successfully.</div>\n";
											}
											//---------------------------------
										}
										else "Delete all contributions from round, validate round index failed.<br/><br/>\n";
										//---------------------------------
									}
									else
									{
										XLogNotify("Admin Contribution Payout action '$action' doesn't have proper privileges: $Login->UserName");
										echo "Contribution Payout $action failed. Action requires proper privileges.<br/>";
									}
									//---------------------------------
									$contList = $Contributions->loadContributions( ($showConts ? false : true) );
									$selID  = false;
									$selCont = false;
									$ridx = false;
									//---------------------------------
								}
								//---------------------------------
							} // if ($contList === false) // else
							//---------------------------------
							if ($contList === false)
								XLogError("Admin Contributions Payouts failed to getContributions (a) (default)");
							else
							{
								//---------------------------------
								echo "<table class='admin-table' style='width: 1000px;'>\n";
								echo "<thead class='admin-scrolltable-header'><tr>";
								$rowIdx = 0;
								foreach ($cols as $col)
								{
									echo "<th style='".$wrappingStyle."min-width:".$col[1]."px;max-width:".$col[1]."px;width:".$col[1]."px;'>";
									echo $col[0]."</th>";
									//echo "<th><span style='".$wrappingStyle."min-width:".$col[1]."px;max-width:".$col[1]."px;width:".$col[1]."px;'>$col[0]</span></th>";
									$rowIdx++;
								}
								echo "</tr></thead>\n";
								//------------------------	
								echo "<tbody class='admin-scrolltable-body' style='height: 400px;'>\n";
								//------------------------	
								$rows = array();
								foreach ($contList as $cont)
									$rows[] = array($cont->id, getContributionRow($Display, $cont->id, $cont->roundIdx, $cont->number, $cont->name, $cont->mode, $cont->value, $cont->account, 
																			$cont->flags, $cont->ad, $cont->outcome, $cont->dtDone, $cont->txid));
								//---------------------------------
								$rowIdx = 0;
								foreach ($rows as $row)
								{
									$colIdx = 0;
									$rId = $row[0];
									if ($ridx !== false && $ridx == $rowIdx)
										$rowClass = 'admin-row-sel';
									else 
										$rowClass = 'admin-row';
									echo "<tr class='$rowClass' onclick=\"document.location.href='./Admin.php?p=$pagenum&amp;ridx=$rowIdx&amp;id=$rId&amp;".($showConts === false ? "" : "sc=1&amp;").SecToken()."';\">";
									foreach ($row[1] as $col)
									{
										if ($cols[$colIdx][2] !== false)
											$style = $cols[$colIdx][2];
										else 
											$style = "";
										if ($rowIdx == 0)
											$style .= "min-width:".$cols[$colIdx][1]."px;max-width:".$cols[$colIdx][1]."px;width:".$cols[$colIdx][1]."px;";
										if ($style != "")
											$style = " style='".$style."' ";
										echo "<td$style>$col</td>";
										$colIdx++;
									}
									echo "</tr>";
									$rowIdx++;
								}
								//------------------------
							}
							//---------------------------------
							echo "</tbody></table><br/>\n";
							//---------------------------------
							if ($showConts === false)
								echo "<input id='showconfs' type='checkbox' name='showconfs' ";
							else
								echo "<input id='showconfs' type='checkbox' name='showconfs' checked='checked' ";
							//------------------------	
							echo "onclick=\"document.location.href='./Admin.php?p=$pagenum&amp;ridx=$ridx&amp;".($selCont === false ? "" : "id=".$selCont->id."&amp;").($showConts === false ? "sc=1&amp;" : "").SecToken()."';\"/>Show Scheduled Contributions<br/>";
							//------------------------	
							if ($selCont !== false)
							{
								$selContData = getContributionRow($Display, $selCont->id, $selCont->roundIdx, $selCont->number, $selCont->name, $selCont->mode, $selCont->value, $selCont->account, 
																			$selCont->flags, $selCont->ad, $selCont->outcome, $selCont->dtDone, $selCont->txid);
								echo "<div class='admin-edit'>\n";
								echo "<table>\n<tr><th>Name</th><th>Value</th></tr>\n";
								for ($i = 0;$i < sizeof($cols);$i++)
									echo "<tr class='admin-row'><td>".$cols[$i][0]."</td><td>".$selContData[$i]."</td></tr>\n";
								echo "</table>\n";
								echo "</div>\n";
								if ($canDelete)
								{
									echo "";
									echo "<br/><input type='submit' name='action' value='Clear Payment' /><br/>\n";
									echo "<br/><input type='submit' name='action' value='Delete' /><br/>\n";

								}
								echo "<input type='hidden' name='ridx' value='$ridx'>";
							} // if ($selCont !== false)
							//------------------------	
							if ($canDelete)
							{
								echo "<br/><input type='submit' name='action' value='Delete All From Round' />\n";
								echo "Round index: <input id='dridx' type='text' name='dridx' value='' maxlength='5'/><br/>\n";
							}
							//------------------------	
					?>
				</div>
			</fieldset>
		</form>
		</div>
		<br/>
		</div><!-- End Right Column -->
	</div><!-- End Container 3 -->
	</div><!-- End Container 2 -->
</div><!-- End Container 1 -->

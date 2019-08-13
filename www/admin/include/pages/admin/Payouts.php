<?php
//---------------------------------
/*
 * include/pages/admin/Payouts.php
 * 
 * 
*/
//---------------------------------
global $Login;
$Payouts = new Payouts() or die("Create object failed");
$Workers = new Workers() or die("Create object failed");
$Rounds  = new Rounds() or die("Create object failed");
$Display = new Display() or die("Create object failed");
$pagenum = XGetPost('p');
$action = XPost('action');
$pidx = XGetPost('idx');
$selPayout = false;
if ($pidx == "")
	$pidx = false;	
$selRoundStr = "";
$selWorkerStr = "";
$dridx = XPost('dridx', false);
//---------------------------------
?>
<div id="content"><!-- Start Container 1 -->
	<div class="under pad_bot1"><!-- Start Container 2 -->
	<br/>
	<div class="line1 wrapper pad_bot2"><!-- Start Container 3 -->
		<div class="col1" style="width: 98%;"><!-- Start Left Column -->
		<div>
			<h1>Payouts:</h1>
			<p>
			Folding @ Home Payout list.
			</p>
			<br/>
		</div>
		<?php
			//------------------------	
			if ($action == "Delete" && $pidx !== false)
			{
				if ($Login->HasPrivilege('D')) // delete history
				{
					XLogNotify("User $Login->User is deleting payout: $pidx");
					if (!$Payouts->deletePayout($pidx))
					{
						XLogNotify("Payouts admin page - Payouts failed to deletePayout $pidx");
						echo "Failed to delete payout.<br/><br/>\n";
					}
					else
					{
						echo "Payout successfully deleted.<br/><br/>\n";
						$pidx = false;
					}
				}
				else 
					echo "Deleting historical data required 'D' delete privileges.<br/><br/>\n";
			}
			else if ($action == "Delete All From Round")
			{
				//------------------------	
				if ($Login->HasPrivilege('D')) // delete history
				{
					if ($dridx !== false && is_numeric($dridx))
					{
						XLogNotify("User $Login->User is deleting all payouts from round: $dridx");
						if (!$Payouts->deleteAllRound($dridx))
						{
							XLogNotify("Payout admin page - payouts failed to delete all from round $dridx");
							echo "Failed to delete all from round $dridx.<br/><br/>\n";
						}
						else
						{
							echo "Payouts successfully deleted.<br/><br/>\n";
							$pidx = false;
						}
						
					}
					else "Deleting all from round, validate round index failed.<br/><br/>\n";
				}
				else 
					echo "Deleting historical data required 'D' delete privileges.<br/><br/>\n";
				//------------------------	
			}
			//------------------------	
			$Columns = array(	array(	40,		"ID"),
								array(	195,	"Date"),
								array(	150,	"Round"),
								array(	320,	"Worker"),
								array(	80,	"Payment"),
								array(	105,	"Tx ID")
								);
			//------------------------	
			echo "<table class='admin-table' style='width:950px;'>\n<thead class='admin-scrolltable-header'>\n<tr>\n";
			//------------------------	
			foreach ($Columns as $col)
				echo "\t<th style='width:".($col[0] !== false ? "$col[0]px" : "100%").";'>$col[1]</th>\n";
			//------------------------	
			echo "</tr></thead>\n<tbody class='admin-scrolltable-body'>\n";
			//------------------------	
			$plist = $Payouts->getPayouts();
			if ($plist === false)
				XLogError("Payouts admin page - getPayouts failed");
			if ($plist === false || sizeof($plist) == 0)
			{
				//------------------------
				echo "<tr class='admin-row'>";
				foreach ($Columns as $col)
					echo "<td style='width:".$col[0]."px;'>&nbsp;</td>";
				echo "</tr>\n";
				//------------------------
			}
			else
			{
				//------------------------
				$rowIdx = 0;
				foreach ($plist as $p)
				{
					//------------------------
					$strRound = $Rounds->getRoundDate($p->roundIdx);
					if ($strRound === false || $strRound == "")
						$strRound = $p->roundIdx;
					else
						$strRound = "($p->roundIdx) ".$Display->htmlLocalDate($strRound); // just the date
					//------------------------
					$worker = $Workers->getWorker($p->workerIdx);
					if ($worker === false)
						$strWorker = "($p->workerIdx) [not found]";
					else
						$strWorker = "($p->workerIdx) ".($worker->address == "" ? $worker->uname : $worker->address);
					//------------------------
					if ($pidx !== false && $pidx == $p->id)
					{
						$rowClass = 'admin-row-sel';
						$selRoundStr = $strRound;
						$selPayout = $p;
						$selWorker = $worker;
					}
					else $rowClass = 'admin-row';
					//------------------------
					echo "<tr class='$rowClass' onclick=\"document.location.href='./Admin.php?p=$pagenum&amp;idx=$p->id&amp;".SecToken()."';\">\n";
					//------------------------
					$colData = array(	$p->id,
										($p->dtPaid !== false && $p->dtPaid != "" ? $Display->htmlLocalDateTime($p->dtPaid) : "(queued)".$Display->htmlLocalDateTime($p->dtcreated)),
										$strRound,
										$strWorker,
										$p->pay,
										(strlen($p->txid) > 8 ? XEncodeHTML(substr($p->txid, 0, 8))."..." : XEncodeHTML($p->txid))
										);
					//------------------------
					for ($i = 0;$i < sizeof($colData);$i++)
					{
						$style = "";
						if ($Columns[$i][0] !== false) // $rowIdx == 0 && 
							$style .= "width:".$Columns[$i][0]."px;";
						if ($Columns[$i][1] == "Payment")
							$style .= "text-align:right;";
						if ($Columns[$i][1] == "Worker" || $Columns[$i][1] == "Date")
							$style .= "display: block;overflow: hidden;white-space:nowrap;";
						if ($style != "")
							$style = " style='$style'";
						echo "<td$style>$colData[$i]</td>";
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
		//</div>
		?>
		</div><!-- End Left Column -->
		<?php
		//---------------------------
		if ($pidx !== false && $selPayout !== false)
		{
		?>
		<div class="col1 pad_left1" style="width:865px;"><!-- Start Right Column -->
		<div class="admin-edit" style="margin-right: 40px;" >
		<form action="./Admin.php" method="post" enctype="multipart/form-data">
			<fieldset class="loginBox">
				<legend style="font-weight:bold;font-size:120%;">Payout Details:</legend>
				<br/>
				<?php PrintSecTokenInput(); ?>
				<input type="hidden" name="idx" value="<?php echo $pidx; ?>" />
				<input type="hidden" name="p" value="<?php echo $pagenum; ?>"/><!-- Page -->
				<table class='admin-table'>
				<tr><th style='width:120px;'>Attribute</th><th >Value</th></tr>
				<?php
					//---------------------------
					if ($selWorker === false)
						$selWorkerStr = "($selPayout->workerIdx) [not found]";
					else
					{
						if ($selWorker->address != $selWorker->uname)
							$selWorkerStr = "($selPayout->workerIdx) ".$selWorker->uname." (".($selWorker->address == "" ? "not set" : "<a href='".BRAND_ADDRESS_LINK.XEncodeHTML($selWorker->address)."'>".XEncodeHTML($selWorker->address)."</a>").")";
						else
							$selWorkerStr = "($selPayout->workerIdx) <a href='".BRAND_ADDRESS_LINK.XEncodeHTML($selWorker->address)."'>".XEncodeHTML($selWorker->address)."</a>";
					}
					//------------------------
					$attributes = array(	'ID' => $selPayout->id,
											'Created' => $Display->htmlLocalDateTime($p->dtcreated),
											'Round' => $selRoundStr,
											'Worker' => $selWorkerStr,
											'Payout' => ($p->dtPaid === false || $p->dtPaid == "" ? "Not yet paid" : "Paid at ".$Display->htmlLocalDateTime($p->dtPaid)),
											'Payment' => "$selPayout->pay&nbsp;".BRAND_UNIT."&nbsp;to&nbsp;<a href='".BRAND_ADDRESS_LINK.XEncodeHTML($selPayout->address)."'>".XEncodeHTML($selPayout->address)."</a>",
											'Payment TxID' => ($selPayout->txid != "" ? "<a href='".BRAND_TX_LINK.XEncodeHTML($selPayout->txid)."'>".XEncodeHTML($selPayout->txid)."</a>" : "&nbsp;")
											);
					//---------------------------style='text-overflow: none;'
					foreach ($attributes as $name => $value)
						echo "<tr class='admin-row'><td>$name</td><td><div style='overflow: auto;'>$value</div></td></tr>\n";
					//---------------------------
					echo "</table>\n";
					//---------------------------
					if ($Login->HasPrivilege('D')) // delete history
						echo "<br/><div>Privileged:</div><br/><input type='submit' name='action' value='Delete' /><br/>\n";
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
	} // if selected
	else
	{
		//---------------------------
		if ($Login->HasPrivilege(PRIV_DELETE_HISTORY)) 
		{
			//---------------------------
			?>
			<div class="col1 pad_left1" style="width:800px;"><!-- Start Right Column -->
			<div class="admin-edit" style="margin: auto;">
			<form action="./Admin.php" method="post" enctype="multipart/form-data">
				<fieldset class="loginBox">
					<div>Privileged:</div><br/>
					<br/>
					<?php PrintSecTokenInput(); ?>
					<input type="hidden" name="idx" value="<?php echo $pidx; ?>" />
					<input type="hidden" name="p" value="<?php echo $pagenum; ?>"/><!-- Page -->
			<?php
			//---------------------------
			echo "<input type='submit' name='action' value='Delete All From Round' />\n";
			echo "Round index: <input id='dridx' type='text' name='dridx' value='' maxlength='5'/><br/>\n";
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
	}
	//----------------------------------
	?>
	</div><!-- End Container 3 -->
	</div><!-- End Container 2 -->
</div><!-- End Container 1 -->

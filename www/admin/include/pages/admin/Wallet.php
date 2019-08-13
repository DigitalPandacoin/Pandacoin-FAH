<?php
//---------------------------------
/*
 * include/pages/admin/Wallet.php
 * 
 * 
*/
//---------------------------------
global $Login;
$Wallet = new Wallet() or die("Create object failed");
$Wallet->Init();
$pagenum = XGetPost('p');
$action = XGetPost('action');
$actionAA1 = XGetPost('actionAA_1');
$actionAA2 = XGetPost('actionAA_2');
$accIdx = XGetPost('acidx');
if ($accIdx == "")
	$accIdx = 0;
$selAccName = false;
$actAccName = false;
$activeAccAddress = false;
//---------------------------------
?>
<div id="content"><!-- Start Container 1 -->
	<div class="under pad_bot1"><!-- Start Container 2 -->
	<br/>
	<div class="line1 wrapper pad_bot2"><!-- Start Container 3 -->
		<div class="col1" style="float:none; width:740px;"><!-- Start Left Column -->
		<div>
			<h1>Wallet:</h1>
			<p>
			Folding @ Home Wallet Configuration.
			</p>
			<br/>
		</div>
		<?php
		//---------------------------------
		$canManage = $Login->HasPrivilege(PRIV_WALLET_MANAGE);
		//---------------------------------
		if ($action == 'Set Account')
		{
			//---------------------------------
			if ($canManage)
			{
				//---------------------------------
				$newAccName = XPost('accName');
				if (!$Wallet->setActiveAccount($newAccName))
				{
					XLogError("Admin Wallet wallet set active account failed.");
					echo "<div>Set payout account name failed.</div>\n";
				}
				else
				{
					echo "<div>Payout account name set.</div>\n";
					$selAccName = $newAccName;
				}
				//---------------------------------
			}
			else
			{
				XLogNotify("Admin Wallet action '$action' doesn't have proper privileges: $Login->UserName");
				echo "Wallet $action failed. Action requires proper privileges.<br/>";
			}
			//---------------------------------
		}
		else if ($action == 'Get Info')
		{
			//---------------------------------
			$detailList = $Wallet->getInfo();
			if ($detailList === false)
			{
				echo "<div>Request info from wallet client failed.</div>\n";
			}
			else
			{
				//---------------------------------
				echo "<table class='admin-table'>\n";
				echo "<tr><th style='width:120px;'>Name</th><th style='width:120px;'>Value</th></tr>\n";
				//---------------------------------
				if (sizeof($detailList) == 0)
					echo "<tr><td style='width:120px;'>&nbsp;</td><td style='width:80px;'>&nbsp;</td></tr>\n";
				else
				{
					//---------------------------------
					foreach ($detailList as $name => $val)
					{
						echo "<tr class='admin-row'>\n";
						echo "<td style='width:120px;'>$name</td><td style='width:80px; text-align: right;'>$val</td></tr>\n";
					}
					//---------------------------------
				}
				//---------------------------------
				echo "</table>\n";		
				//---------------------------------
			}
			//---------------------------------
		}
		else if ($action == 'List Accounts')
		{
			//---------------------------------
			$accountList = $Wallet->listAccounts();
			if ($accountList === false)
				echo "<div>Request account list from wallet client failed.</div>\n";
			else
			{
				//---------------------------------
				echo "<table class='admin-table'>\n";
				echo "<tr><th style='width:120px;'>Account Name</th><th style='width:240px;'>Address</th><th style='width:60px;'>Balance</th></tr>\n";
				//---------------------------------
				if (sizeof($accountList) == 0)
					echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>\n";
				else
				{
					//---------------------------------
					$idx = 0;
					foreach ($accountList as $acc => $bal)
					{
						//---------------------------------
						if ($accIdx == $idx)
						{
							$selAccName = $acc;
							$rowClass = 'admin-row-sel';
						}
						else
							$rowClass = 'admin-row';
						//---------------------------------
						$address = $Wallet->getAccountAddress($acc);
						//---------------------------------
						if ($address === false)
							$address = "(error)";
						else if (sizeof($address) == 0)
							$address = "(none)";
						else
							$address = "<a href='".BRAND_ADDRESS_LINK.XEncodeHTML($address[0])."'>".XEncodeHTML($address[0])."</a>";
						//---------------------------------
						echo "<tr class='$rowClass' onclick=\"document.location.href='./Admin.php?p=$pagenum&amp;action=List%20Accounts&amp;acidx=$idx&amp;".SecToken()."';\">\n";
						echo "<td><div style='overflow: auto;'>".($acc == "" ? "(default)" : $acc)."</div></td><td><div style='overflow: auto;'>$address</div></td><td style='text-align: right;'><div style='overflow: auto;'>$bal</div></td></tr>\n";
						//---------------------------------
						$idx++;
						//---------------------------------
					}
					//---------------------------------
				}
				//---------------------------------
				echo "</table>\n";		
				//---------------------------------
			}
			//---------------------------------
		}
		else if ($action == "Set Fee")
		{
			//---------------------------------
			if ($canManage)
			{
				//---------------------------------
				$txfee = XPost('txfee');
				//---------------------------------
				if (!is_numeric($txfee))
					echo "<div>Validate value to set fee failed.</div>\n";
				else
				{
					if (!$Wallet->setFee($txfee))
						echo "<div>Set fee failed.</div>\n";
					else 
						echo "<div>Fee set successfully.</div>\n";
				}
				//---------------------------------
			}
			else
			{
				XLogNotify("Admin Wallet action '$action' doesn't have proper privileges: $Login->UserName");
				echo "Wallet $action failed. Action requires proper privileges.<br/>";
			}
			//---------------------------------
		}
		else if ($action == "Set Est Fee")
		{
			//---------------------------------
			if ($canManage)
			{
				//---------------------------------
				$estfee = XPost('estfee');
				//---------------------------------
				if (!is_numeric($estfee))
					echo "<div>Validate value to set est. fee failed.</div>\n";
				else
				{
					if (!$Wallet->setEstFee($estfee))
						echo "<div>Set est. fee failed.</div>\n";
					else 
						echo "<div>Est fee set successfully.</div>\n";
				}
				//---------------------------------
			}
			else
			{
				XLogNotify("Admin Wallet action '$action' doesn't have proper privileges: $Login->UserName");
				echo "Wallet $action failed. Action requires proper privileges.<br/>";
			}
			//---------------------------------
		}
		else if ($action == "Set Min Conf")
		{
			//---------------------------------
			if ($canManage)
			{
				//---------------------------------
				$minConf = XPost('minconf');
				//---------------------------------
				if (!is_numeric($minConf))
					echo "<div>Validate value to set minimum confirmations failed.</div>\n";
				else
				{
					if (!$Wallet->setMinConf($minConf))
						echo "<div>Set minimum confirmations failed.</div>\n";
					else 
						echo "<div>Minimum confirmations set successfully.</div>\n";
				}
				//---------------------------------
			}
			else
			{
				XLogNotify("Admin Wallet action '$action' doesn't have proper privileges: $Login->UserName");
				echo "Wallet $action failed. Action requires proper privileges.<br/>";
			}
			//---------------------------------
		}
		//---------------------------------
		$estfee = $Wallet->getEstFee();
		//---------------------------------
		$minConf = $Wallet->getMinConf();
		//---------------------------------
		$actAccName = $Wallet->getActiveAccount();
		if ($actAccName === false)
			$actAccName = "";
		//---------------------------------
		$payoutAccBalance = $Wallet->getBalance();
		//---------------------------------
		$addresses = $Wallet->getAccountAddress();
		if ($addresses !== false && sizeof($addresses) > 0)
			$activeAccAddress = $addresses[0];
		//---------------------------------
		$txfee = $Wallet->getFee();
		//---------------------------------
		if ($payoutAccBalance === false)
			$payoutAccBalance = "(error)";
		//---------------------------------
		if ($txfee === false)
		{
			$txfee = "(error)";
			echo "<div style='color:red;'>Wallet RPC connectivity failed!</div>\n";
		}
		//---------------------------------
		?>
		</div> <!-- End Left Column -->
		<div class="col1 pad_left1" style="float:none;width:80%;"><!-- Start Right Column -->
		<div class="admin-edit" style="width:80%;">
		<form action="./Admin.php" method="post" enctype="multipart/form-data">
			<fieldset class="loginBox">
				<legend style="font-weight:bold;font-size:120%;">Configure Wallet:</legend>
				<br/>
				<?php PrintSecTokenInput(); ?>
				<input type="hidden" name="p" value="<?php echo $pagenum; ?>"/><!-- Page -->
				<input type="hidden" name="acidx" value="<?php echo $accIdx ?>" />
				<div>
					<label class="loginLabel" for="accName">Payout Account:</label><br/>
					<div style='width:220px;background:#AAAAAA; padding: 1px 1px; margin: 1px 1px;'><?php echo ($actAccName == "" ? "(default)" : $actAccName); ?></div>
					<?php 
						if ($canManage)
						{
							echo "<input style='width:220px;' id='accName' type='text' name='accName' value='".XEncodeHTML( ($selAccName !== false ? $selAccName : $actAccName) )."' maxlength='".C_MAX_NAME_TEXT_LENGTH."' />\n";
							echo "<br/><input style='margin-top:10px;' type='submit' name='action' value='Set Account' />\n";
						}
					?>
				</div>
				<br/>
				<div>Payout address:</div>
				<div style="background:#AAAAAA; padding: 1px 1px; width:400px; overflow: auto;"><?php echo ($activeAccAddress === false ? "(error)" : "<a href='".BRAND_ADDRESS_LINK.XEncodeHTML($activeAccAddress)."'>".XEncodeHTML($activeAccAddress)."</a>"); ?></div>
				<br/>
				<div>
					<label class="loginLabel" for="accBal">Payout Account Balance:</label><br/>
					<input style="width:220px;" id="accBal" type="text" name="accBal" value="<?php echo XEncodeHTML(sprintf("%0.8f", $payoutAccBalance));?>" readonly="readonly" />
				</div>
				<br/>
				<div>
					<label class="loginLabel" for="estfee">Estimated Fee Buffer:</label><br/>
					<?php 
						echo "<input style='width:220px;' id='estfee' type='text' name='estfee' value='$estfee' maxlength='8' ";
						if ($canManage)
						{
							echo "/>\n<br/>";
							echo "<input style='margin-top:10px;' type='submit' name='action' value='Set Est Fee' />\n";
						}
						else 
							echo "readonly='readonly' />&nbsp;per KB\n<br/>";
					?>
				</div>
				<br/>
				<div>
					<label class="loginLabel" for="txfee">Transaction Fee:</label><br/>
					<?php 
						echo "<input style='width:220px;' id='txfee' type='text' name='txfee' value='$txfee' maxlength='8' ";
						if ($canManage)
						{
							echo "/>&nbsp;per KB\n<br/>";
							echo "<input style='margin-top:10px;' type='submit' name='action' value='Set Fee' />\n";
						}
						else 
							echo "readonly='readonly' />&nbsp;per KB\n<br/>";
					?>
				</div>
				<br/>
				<div>
					<label class="loginLabel" for="txfee">Minimum Confirmations:</label><br/>
					<?php 
						echo "<input style='width:120px;' id='minconf' type='text' name='minconf' value='$minConf' maxlength='3' ";
						if ($canManage)
						{
							echo "/>\n<br/>";
							echo "<input style='margin-top:10px;' type='submit' name='action' value='Set Min Conf' />\n";
						}
						else 
							echo "readonly='readonly' />\n<br/>";
					?>
				</div>
				<br/>
				<legend style="font-weight:bold;font-size:120%;">Inspect Wallet:</legend>
				<br/>
				<input type="submit" name="action" value="Get Info" />&nbsp;
				<input type="submit" name="action" value="List Accounts" />&nbsp;
				<br/>
				<br/>
			</fieldset>
		</form>
		</div>
		<br/>
		</div><!-- End Right Column -->
	</div><!-- End Container 3 -->
	</div><!-- End Container 2 -->
</div><!-- End Container 1 -->

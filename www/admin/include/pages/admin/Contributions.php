<?php
//---------------------------------
/*
 * include/pages/admin/Contributions.php
 * 
 * 
*/
//---------------------------------
global $Login;
$Contributions = new Contributions() or die("Create object failed");
$Ads = new Ads() or die("Create object failed");
$Wallet = new Wallet() or die("Create object failed");
$Wallet->Init();
$pagenum = XGetPost('p');
$action = XGetPost('action');
//---------------------------------
//---------------------------------
function getContributionDropDown($idx, $selNumber, $selName, $selMode, $selValue, $selAccount, $selAccBalance, $selFlags, $selAdID, $readonly = false)
{
	//------------------------
	$selNumber = XEncodeHTML($selNumber);
	$selName = XEncodeHTML($selName);
	$selValue = XEncodeHTML($selValue);
	$selAccount = XEncodeHTML($selAccount);
	$selAdID = XEncodeHTML($selAdID);
	//------------------------
	$out = "<div style='width:420px;'>Automated Account $idx</div><table class='admin-table' style='width:420px;'>\n";
	//------------------------	
	$out .= "<tr class='admin-row'><td><label class='loginLabel' for='aan_$idx'>Name</label>\n</td><td>";
	$out .= "<input style='width:100%;' id='aan_$idx' type='text' name='aan_$idx' value='$selName' maxlength='45'".($readonly !== false ? " readonly='readonly'" : "")." /></td></tr>\n";
	//------------------------	
	$out .= "<tr class='admin-row'><td><label class='loginLabel' for='aanm_$idx'>Order</label>\n</td><td>";
	$out .= "<input style='width:100%;' id='aan_$idx' type='text' name='aanm_$idx' value='$selNumber' maxlength='45'".($readonly !== false ? " readonly='readonly'" : "")." /></td></tr>\n";
	//------------------------	
	$out .= "<tr class='admin-row'><td><label class='loginLabel' for='aaad_$idx'>Ad ID</label>\n</td><td>";
	$out .= "<input style='width:60px;' id='aaad_$idx' type='text' name='aaad_$idx' value='$selAdID' maxlength='8'".($readonly !== false ? " readonly='readonly'" : "")." /></td></tr>\n";
	//------------------------	
	$out .= "<tr class='admin-row'><td style='width:80px;'>Mode</td><td><select name='aam_$idx' id='aam_$idx' ".($readonly !== false ? " disabled" : "").">\n";
	//---------
	$out .= "<option value='".CONT_MODE_NONE."'".($selMode == CONT_MODE_NONE ? " selected" : "").">Disabled</option>\n";
	$out .= "<option value='".CONT_MODE_FLAT."'".($selMode == CONT_MODE_FLAT ? " selected" : "").">Flat Rate</option>\n";
	$out .= "<option value='".CONT_MODE_PERCENT."'".($selMode == CONT_MODE_PERCENT ? " selected" : "").">Percentage</option>\n";
	$out .= "<option value='".CONT_MODE_ALL."'".($selMode == CONT_MODE_ALL ? " selected" : "").">All</option>\n";
	$out .= "<option value='".CONT_MODE_EACH."'".($selMode == CONT_MODE_EACH ? " selected" : "").">Each</option>\n";
	//---------
	$out .= "</select></td></tr>\n";
	//------------------------	
	$out .= "<tr class='admin-row'><td><label class='loginLabel' for='aav_$idx'>Value</label>\n</td><td>";
	$out .= "<input style='width:60px;' id='aav_$idx' type='text' name='aav_$idx' value='$selValue' maxlength='8'".($readonly !== false ? " readonly='readonly'" : "")." /></td></tr>\n";
	//------------------------	
	$out .= "<tr class='admin-row'><td><label class='loginLabel' for='aaa_$idx'>Account</label>\n</td><td>";
	$out .= "<input style='width:100%;' id='aaa_$idx' type='text' name='aaa_$idx' value='$selAccount' maxlength='45'".($readonly !== false ? " readonly='readonly'" : "")." /></td></tr>\n";
	//------------------------	
	if ($selAccBalance === false)
		$selAccBalance = '<error>';
	else if ($selAccBalance != "")
		$selAccBalance = XEncodeHTML(sprintf("%0.8f", $selAccBalance));
	$out .= "<tr class='admin-row'><td><label class='loginLabel' for='aabal_$idx'>Account Balance</label>\n</td><td>$selAccBalance</td></tr>\n";
	//------------------------	
	$f1 = XMaskContains($selFlags, WALLET_AUTO_FLAG_REQUIRED);
	$out .= "<tr class='admin-row'><td>Required Payout</td><td><input id='aaf1_$idx' type='checkbox' name='aaf1_$idx'".($f1 !== false ? " checked='checked'" : "").($readonly !== false ? " readonly='readonly'" : "")." />&nbsp;required</td></tr>\n";
	//------------------------	
	if ($readonly === false)
		$out .= "<tr><td style='text-align:center;'><input style='margin: 2px;' type='submit' name='actionC_$idx' value='Update' />&nbsp;&nbsp;<input style='margin: 2px;' type='submit' name='actionC_$idx' value='Remove' /></td></tr>\n";
	//------------------------	colspan='2'
	$out .= "</table>";
	return $out;
}
//---------------------------------
function getAdDropDown($idx, $selAdMode, $selAdStyle, $selAdText, $selAdLink, $selAdImage, $readonly = false)
{
	//------------------------
	$selAdStyle = XEncodeHTML($selAdStyle);
	$selAdText = XEncodeHTML($selAdText);
	$selAdLink = XEncodeHTML($selAdLink);
	$selAdImage = XEncodeHTML($selAdImage);
	//------------------------	Ad Mode
	$amShowAlways = XMaskContains($selAdMode, AD_MODE_SHOW_ALWAYS);
	$amShowTitle  = XMaskContains($selAdMode, AD_MODE_SHOW_TITLE);
	$amShowValue  = XMaskContains($selAdMode, AD_MODE_SHOW_VALUE);
	$amShowText   = XMaskContains($selAdMode, AD_MODE_SHOW_TEXT);
	$amShowImage  = XMaskContains($selAdMode, AD_MODE_SHOW_IMAGE);
	$amLinkTitle  = XMaskContains($selAdMode, AD_MODE_LINK_TITLE);
	$amLinkText   = XMaskContains($selAdMode, AD_MODE_LINK_TEXT);
	$amLinkImage  = XMaskContains($selAdMode, AD_MODE_LINK_IMAGE);
	//------------------------	
	$out = "<div style='width:420px;'>Add $idx</div><table class='admin-table' style='width:420px;'>\n";
	//------------------------	
	$out .= "<tr class='admin-row'><td>Ad Show Title</td><td><input id='aaam0_$idx' type='checkbox' name='aaam0_$idx'".($amShowAlways !== false ? " checked='checked'" : "").($readonly !== false ? " readonly='readonly'" : "")." />&nbsp;show always</td></tr>\n";
	$out .= "<tr class='admin-row'><td>Ad Show Title</td><td><input id='aaam1_$idx' type='checkbox' name='aaam1_$idx'".($amShowTitle !== false ? " checked='checked'" : "").($readonly !== false ? " readonly='readonly'" : "")." />&nbsp;show title</td></tr>\n";
	$out .= "<tr class='admin-row'><td>Ad Show Value</td><td><input id='aaam2_$idx' type='checkbox' name='aaam2_$idx'".($amShowValue !== false ? " checked='checked'" : "").($readonly !== false ? " readonly='readonly'" : "")." />&nbsp;show value</td></tr>\n";
	$out .= "<tr class='admin-row'><td>Ad Show Text</td><td><input  id='aaam3_$idx' type='checkbox' name='aaam3_$idx'".($amShowText !== false ? " checked='checked'" : "").($readonly !== false ? " readonly='readonly'" : "")." />&nbsp;show text</td></tr>\n";
	$out .= "<tr class='admin-row'><td>Ad Show Image</td><td><input id='aaam4_$idx' type='checkbox' name='aaam4_$idx'".($amShowImage !== false ? " checked='checked'" : "").($readonly !== false ? " readonly='readonly'" : "")." />&nbsp;show image</td></tr>\n";

	$out .= "<tr class='admin-row'><td>Ad Link Title</td><td><input id='aaam5_$idx' type='checkbox' name='aaam5_$idx'".($amLinkTitle !== false ? " checked='checked'" : "").($readonly !== false ? " readonly='readonly'" : "")." />&nbsp;link title</td></tr>\n";
	$out .= "<tr class='admin-row'><td>Ad Link Text</td><td><input  id='aaam6_$idx' type='checkbox' name='aaam6_$idx'".($amLinkText !== false ? " checked='checked'" : "").($readonly !== false ? " readonly='readonly'" : "")." />&nbsp;link text</td></tr>\n";
	$out .= "<tr class='admin-row'><td>Ad Link Image</td><td><input id='aaam7_$idx' type='checkbox' name='aaam7_$idx'".($amLinkImage !== false ? " checked='checked'" : "").($readonly !== false ? " readonly='readonly'" : "")." />&nbsp;link image</td></tr>\n";
	//------	
	//$out .= "<tr class='admin-row'><td><label class='loginLabel' for='aaam_$idx'>Ad Mode</label>\n</td><td>";
	//$out .= "<input style='width:100%;' id='aaam_$idx' type='text' name='aaam_$idx' value='$selAdMode' maxlength='45'".($readonly !== false ? " readonly='readonly'" : "")." /></td></tr>\n";
	//------------------------	Ad Style
	$out .= "<tr class='admin-row'><td><label class='loginLabel' for='aaai_$idx'>Ad Style</label>\n</td><td>";
	$out .= "<input style='width:100%;' id='aaas_$idx' type='text' name='aaas_$idx' value='$selAdStyle' maxlength='".C_MAX_TITLE_TEXT_LENGTH."'".($readonly !== false ? " readonly='readonly'" : "")." /></td></tr>\n";
	//------------------------	Ad Text
	$out .= "<tr class='admin-row'><td><label class='loginLabel' for='aaat_$idx'>Ad Text</label>\n</td><td>";
	$out .= "<input style='width:100%;' id='aaat_$idx' type='text' name='aaat_$idx' value='$selAdText' maxlength='".C_MAX_AD_TEXT_LENGTH."'".($readonly !== false ? " readonly='readonly'" : "")." /></td></tr>\n";
	//------------------------	Ad Link
	$out .= "<tr class='admin-row'><td><label class='loginLabel' for='aaal_$idx'>Ad Link</label>\n</td><td>";
	$out .= "<input style='width:100%;' id='aaal_$idx' type='text' name='aaal_$idx' value='$selAdLink' maxlength='".C_MAX_AD_LINK_TEXT_LENGTH."'".($readonly !== false ? " readonly='readonly'" : "")." /></td></tr>\n";
	//------------------------	Ad Image
	$out .= "<tr class='admin-row'><td><label class='loginLabel' for='aaai_$idx'>Ad Image</label>\n</td><td>";
	$out .= "<input style='width:100%;' id='aaai_$idx' type='text' name='aaai_$idx' value='$selAdImage' maxlength='".C_MAX_AD_IMAGE_TEXT_LENGTH."'".($readonly !== false ? " readonly='readonly'" : "")." /></td></tr>\n";
	//------------------------	
	if ($readonly === false)
		$out .= "<tr><td style='text-align:center;'><input style='margin: 2px;' type='submit' name='actionA_$idx' value='Update' />&nbsp;&nbsp;<input style='margin: 2px;' type='submit' name='actionA_$idx' value='Remove' /></td></tr>\n";
	//------------------------	colspan='2'
	$out .= "</table>";
	return $out;
}
//---------------------------------
?>
<div id="content"><!-- Start Container 1 -->
	<div class="under pad_bot1"><!-- Start Container 2 -->
	<br/>
	<div class="line1 wrapper pad_bot2"><!-- Start Container 3 -->
		<div class="col1" style="float:none; width:740px;"><!-- Start Left Column -->
		<div>
			<h1>Contributions:</h1>
			<p>
			Folding @ Home Contribution Configuration.
			</p>
			<br/>
		</div>
		<?php
		//---------------------------------
		$adList = $Ads->getAds();
		$contList = $Contributions->findRoundContributions(-1 /*roundIdx*/);
		$canManage = $Login->HasPrivilege(PRIV_WALLET_MANAGE);
		//---------------------------------
		XLogNotify("action : ".XVarDump($action));
		if ($action == "Add Contribution")
		{
			//---------------------------------
			if ($canManage)
			{
				//---------------------------------
				if (!$Contributions->addContribution(-1 /*not round related*/, 50 /*number for order*/, "new contribution", CONT_MODE_NONE, "", 0.0, CONT_FLAG_NONE))
				{
					XLogError("Admin Wallet Contributions addContribution failed");
					echo "<div>Add new contribution failed.</div>\n";
				}
				else
					echo "<div>New contribution added successfully.</div>\n";
				//---------------------------------
			}
			else
			{
				XLogNotify("Admin Wallet action '$action' doesn't have proper privileges: $Login->UserName");
				echo "Wallet $action failed. Action requires proper privileges.<br/>";
			}
			//---------------------------------
			$contList = $Contributions->findRoundContributions(-1 /*roundIdx*/);
			//---------------------------------
		}
		else if ($action == "Add Ad")
		{
			//---------------------------------
			if ($canManage)
			{
				//---------------------------------
				if (!$Ads->addAd())
				{
					XLogError("Admin Wallet Ads addAd failed");
					echo "<div>Add new ad failed.</div>\n";
				}
				else
					echo "<div>New ad added successfully.</div>\n";
				//---------------------------------
			}
			else
			{
				XLogNotify("Admin Wallet action '$action' doesn't have proper privileges: $Login->UserName");
				echo "Wallet $action failed. Action requires proper privileges.<br/>";
			}
			//---------------------------------
			$adList = $Ads->getAds();
			//---------------------------------
		}
		else
		{
			//---------------------------------
			if ($contList === false)
				XLogError("Admin Ads failed to getAds");
			else
			{
				//---------------------------------
				foreach ($contList as $cont)
					if ($cont->id !== false)
					{
						//---------------------------------
						$id = $cont->id;
						$actionC =  XGetPost('actionC_'.$id);
						//XLogDebug("cont actionC_$id : ".XVarDump($actionC).", canManage: ".XVarDump($canManage));
						//---------------------------------
						if ($actionC == "Update")
						{
							//---------------------------------
							if ($canManage)
							{
								//---------------------------------
								$name = XPost("aan_$id");
								$number = XPost("aanm_$id");
								$mode = XPost("aam_$id");
								$value = XPost("aav_$id");
								$account = XPost("aaa_$id");
								$adID = XPost("aaad_$id");
								//---------------------------------
								$flags = WALLET_AUTO_FLAG_NONE;
								if (XPost("aaf1_$id", false) !== false)
									$flags |= WALLET_AUTO_FLAG_REQUIRED;
								//---------------------------------
								if (!is_numeric($mode) || !is_numeric($value))
									echo "<div>Validate value to update auto account failed.</div>\n";
								else
								{
									//---------------------------------
									$cont = $Contributions->getContribution($id);
									if ($cont === false)
									{
										XLogError("Admin Contributions Contribution Update id $id not found");
										echo "<div>Auto account updated contribution not found.</div>\n";
									}
									else
									{
										$cont->name = $name;
										$cont->number = $number;
										$cont->mode = $mode;
										$cont->account = $account;
										$cont->value = $value;
										$cont->flags = $flags;
										$cont->ad = $adID;
										if (!$cont->Update())
										{
											XLogError("Admin Contributions Contribution Update id $id failed");
											echo "<div>Update contribution failed.</div>\n";
										}
										else 
										{
											XLogError("Admin Contributions Contribution Update id $id success");
											echo "<div>Contribution updated successfully.</div>\n";
										}
									}
									//---------------------------------
								}
								//---------------------------------
							} // if ($canManage)
							else
							{
								XLogNotify("Admin Contributions action update contribution doesn't have proper privileges: $Login->UserName");
								echo "Wallet update contribution. Action requires proper privileges.<br/>";
							}
							//---------------------------------
							$contList = $Contributions->findRoundContributions(-1 /*roundIdx*/);
							//---------------------------------
						} // "Update"
						else if ($actionC == "Remove")
						{
							//---------------------------------
							if ($canManage)
							{
								//---------------------------------
								$cont = $Contributions->getContribution($id);
								if ($cont === false)
								{
									XLogError("Admin Contributions Delete Contribution index $id not found.");
									echo "<div>Delete contribution not found.</div>\n";
								}
								else 
								{
									if (!$Contributions->deleteContribution($id))
									{
										XLogError("Admin Contributions delete Contribution id $id failed");
										echo "<div>Delete contribution failed.</div>\n";
									}
									else 
									{
										XLogError("Admin Contributions delete Contribution id $id success");
										echo "<div>Deleted contribution successfully.</div>\n";
									}
								}
								//---------------------------------
							} // if ($canManage)
							else
							{
								XLogNotify("Admin Contributions action delete contribution doesn't have proper privileges: $Login->UserName");
								echo "Wallet delete contribution failed. Action requires proper privileges.<br/>";
							}
							//---------------------------------
							$contList = $Contributions->findRoundContributions(-1 /*roundIdx*/);
							//---------------------------------
						} // else if ($actionAA == "Remove")
						//---------------------------------
					} // foreach if
				//---------------------------------
			} // "// else // if ($contList === false)
			//---------------------------------
			if ($adList === false)
				XLogError("Admin Ads failed to getAds");
			else
			{
				//---------------------------------
				foreach ($adList as $ad)
					if ($ad->id !== false)
					{
						//---------------------------------
						$id = $ad->id;
						$actionA =  XGetPost('actionA_'.$id);
						//---------------------------------
						//XLogDebug("ad actionA_$id : ".XVarDump($actionA));
						if ($actionA == "Update")
						{
							//---------------------------------
							if ($canManage)
							{
								//---------------------------------
								$adStyle = XPost("aaas_$id");
								$adText =  XPost("aaat_$id");
								$adLink =  XPost("aaal_$id");
								$adImage = XPost("aaai_$id");
								//$adMode =  XPost("aaam_$id");
								$amShowAlways = XPost("aaam0_$id", false);
								$amShowTitle  = XPost("aaam1_$id", false);
								$amShowValue  = XPost("aaam2_$id", false);
								$amShowText   = XPost("aaam3_$id", false);
								$amShowImage  = XPost("aaam4_$id", false); //aaam0-4

								$amLinkTitle = XPost("aaam5_$id", false);
								$amLinkText  = XPost("aaam6_$id", false);
								$amLinkImage = XPost("aaam7_$id", false);
								//---------------------------------
								$adMode = 0;
								if ($amShowAlways !== false)
									$adMode |= AD_MODE_SHOW_ALWAYS;
								if ($amShowTitle !== false)
									$adMode |= AD_MODE_SHOW_TITLE;
								if ($amShowValue !== false)
									$adMode |= AD_MODE_SHOW_VALUE;
								if ($amShowText !== false)
									$adMode |= AD_MODE_SHOW_TEXT;
								if ($amShowImage !== false)
									$adMode |= AD_MODE_SHOW_IMAGE;
								
								if ($amLinkTitle !== false)
									$adMode |= AD_MODE_LINK_TITLE;
								if ($amLinkText !== false)
									$adMode |= AD_MODE_LINK_TEXT;
								if ($amLinkImage !== false)
									$adMode |= AD_MODE_LINK_IMAGE;
								//---------------------------------
								$ad = $Ads->getAd($id);
								if ($ad === false)
								{
									XLogError("Admin Contribution Update Ad id $id not found");
									echo "<div>Contribution update ad not found.</div>\n";
								}
								else
								{
									$ad->mode = $adMode;
									$ad->text = $adText;
									$ad->link = $adLink;
									$ad->image = $adImage;
									$ad->style = $adStyle;
									if (!$ad->Update())
									{
										XLogError("Admin Contributions Ad Update id $id failed");
										echo "<div>Update Ad failed.</div>\n";
									}
									else 
									{
										XLogError("Admin Contribution Update Ad id $id not found. Mode ".XVarDump($adMode).", amShowAlways: ".XVarDump($amShowAlways));
										echo "<div>Ad updated successfully.</div>\n";
									}
								}
								//---------------------------------
							} // if ($canManage)
							else
							{
								XLogNotify("Admin Contributions action update contribution doesn't have proper privileges: $Login->UserName");
								echo "Wallet update contribution. Action requires proper privileges.<br/>";
							}
							//---------------------------------
							$adList = $Ads->getAds();
							//---------------------------------
						} // "Update"
						else if ($actionA == "Remove")
						{
							//---------------------------------
							if ($canManage)
							{
								//---------------------------------
								$ad = $Ads->getAd($id);
								if ($ad === false)
								{
									XLogError("Admin Contribution index $id not found.");
									echo "<div>Delete contribution not found.</div>\n";
								}
								else 
								{
									if (!$Ads->deleteAd($id))
									{
										XLogError("Admin Contribution delete Ad id $id failed");
										echo "<div>Delete contribution failed.</div>\n";
									}
									else 
										echo "<div>Deleted contribution successfully.</div>\n";
								}
								//---------------------------------
							} // if ($canManage)
							else
							{
								XLogNotify("Admin Contributions action delete contribution doesn't have proper privileges: $Login->UserName");
								echo "Wallet delete contribution failed. Action requires proper privileges.<br/>";
							}
							//---------------------------------
							$adList = $Ads->getAds();							
							//---------------------------------
						} // else if ($actionC == "Remove")
						//---------------------------------
					} // foreach ($adList as $ad) // if ($ad->number !== false)
				//---------------------------------
			} // if ($adList === false) // else
			//---------------------------------
		} // if ($action == "Add Contribution")
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
				<div>
					<?php
							//------------------------	
							echo "<table class='admin-table' style='width: 460px;'>\n";
							echo "<thead class='admin-scrolltable-header'>\n<tr>\n";
							echo "<input style='margin-top:10px;' type='submit' name='action' value='Add Contribution' />\n";
							echo "</tr></thead>\n";
							//------------------------	
							echo "<tbody class='admin-scrolltable-body' style='height: 400px;'>\n";
							//------------------------	
							if ($contList === false)
								XLogError("Admin Wallet Contributions failed to findRoundContributions (default)");
							else
							{
								//---------------------------------
								foreach ($contList as $cont)
									echo "<tr><td>".getContributionDropDown($cont->id, $cont->number, $cont->name, $cont->mode, $cont->value, $cont->account, 
																			$Wallet->getBalance($cont->account), $cont->flags, $cont->ad,
																			(!$canManage ? true/*readOnly*/ : false))."</td></tr>";
								//---------------------------------
							}
							//---------------------------------
							echo "</tbody></table>\n";
							//------------------------	
							echo "<table class='admin-table' style='width: 460px;'>\n";
							echo "<thead class='admin-scrolltable-header'>\n<tr>\n";
							echo "<input style='margin-top:10px;' type='submit' name='action' value='Add Ad' />\n";
							echo "</tr></thead>\n";
							//------------------------	
							echo "<tbody class='admin-scrolltable-body' style='height: 480px;'>\n";
							if ($adList === false)
								XLogError("Admin Wallet Contributions failed to findRoundContributions (default)");
							else
							{
								//---------------------------------
								foreach ($adList as $ad)
									echo "<tr><td>".getAdDropDown($ad->id, $ad->mode, $ad->style, $ad->text, $ad->link, $ad->image,
																			(!$canManage ? true/*readOnly*/ : false))."</td></tr>";
								//---------------------------------
							}
							//------------------------	
							echo "</tbody></table>\n";
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

<?

IncludeModuleLangFile(__FILE__);
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");

if(!$USER->IsAdmin()) return;

$module_id = "feedback_error";
$strWarning = "";

$dbSites = CSite::GetList($by="sort", $order="asc", array("ACTIVE" => "Y"));
$arSites = array();
while($arSite = $dbSites->Fetch()) {
	$arSites[$arSite["LID"]] = $arSite;
}

$defEmail = COption::GetOptionString("main", "email_from", "error@".str_replace("www.","",$_SERVER["SERVER_NAME"]));
$defIP = COption::GetOptionInt($module_id, "limit_ip", 30);


$arAllOptions = array(
	"main" => Array(
		Array("email_from", GetMessage("FEEDBACK_ERROR_OPTIONS_EMAIL_FROM"), $defEmail, Array("text", 30)),
		Array("email_to", GetMessage("FEEDBACK_ERROR_OPTIONS_EMAIL_TO"), $defEmail, Array("text", 30)),
		Array("limit_ip", GetMessage("FEEDBACK_ERROR_OPTIONS_LIMIT_IP"), $defIP, Array("text", 30)),
	),
);
$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
	array("DIV" => "edit2", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "feedback_comments_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS")),
);

//Restore defaults
if ($USER->IsAdmin() && $_SERVER["REQUEST_METHOD"]=="GET" && strlen($RestoreDefaults)>0 && check_bitrix_sessid())
{
	COption::RemoveOption("feedback_error");
}
$tabControl = new CAdminTabControl("tabControl", $aTabs);


function __AdmSettingsDrawRowCustom($module_id, $Option, $site_id = false)
{
	$arControllerOption = CControllerClient::GetInstalledOptions($module_id);
	if(!is_array($Option)):
	?>
		<tr class="heading">
			<td colspan="2"><?=$Option?></td>
		</tr>
	<?
	elseif(isset($Option["note"])):
	?>
		<tr>
			<td colspan="2" align="center">
				<?echo BeginNote('align="center"');?>
				<?=$Option["note"]?>
				<?echo EndNote();?>
			</td>
		</tr>
	<?
	else:
		if ($Option[0] != "")
		{
			$val = COption::GetOptionString($module_id, $Option[0], $Option[2], $site_id);
		}
		else
		{
			$val = $Option[2];
		}
	if ($site_id !== false)
		$Option[0] .= "_".$site_id;
		$type = $Option[3];
		$disabled = array_key_exists(4, $Option) && $Option[4] == 'Y' ? ' disabled' : '';
		$sup_text = array_key_exists(5, $Option) ? $Option[5] : '';
	?>
		<tr>
			<td<?if($type[0]=="multiselectbox" || $type[0]=="textarea" || $type[0]=="statictext" || $type[0]=="statichtml") echo ' class="adm-detail-valign-top"'?> width="50%"><?
				if($type[0]=="checkbox")
					echo "<label for='".htmlspecialcharsbx($Option[0])."'>".$Option[1]."</label>";
				else
					echo $Option[1];
				if (strlen($sup_text) > 0)
				{
					?><span class="required"><sup><?=$sup_text?></sup></span><?
				}
					?></td>
			<td width="50%"><?
			if($type[0]=="checkbox"):
				?><input type="checkbox" <?if(isset($arControllerOption[$Option[0]]))echo ' disabled title="'.GetMessage("MAIN_ADMIN_SET_CONTROLLER_ALT").'"';?> id="<?echo htmlspecialcharsbx($Option[0])?>" name="<?echo htmlspecialcharsbx($Option[0])?>" value="Y"<?if($val=="Y")echo" checked";?><?=$disabled?><?if($type[2]<>'') echo " ".$type[2]?>><?
			elseif($type[0]=="text" || $type[0]=="password"):
				?><input type="<?echo $type[0]?>"<?if(isset($arControllerOption[$Option[0]]))echo ' disabled title="'.GetMessage("MAIN_ADMIN_SET_CONTROLLER_ALT").'"';?> size="<?echo $type[1]?>" maxlength="255" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0])?>"<?=$disabled?><?=($type[0]=="password" || $type["noautocomplete"]? ' autocomplete="off"':'')?>><?
			elseif($type[0]=="selectbox"):
				$arr = $type[1];
				if(!is_array($arr))
					$arr = array();
				?><select name="<?echo htmlspecialcharsbx($Option[0])?>" <?if(isset($arControllerOption[$Option[0]]))echo ' disabled title="'.GetMessage("MAIN_ADMIN_SET_CONTROLLER_ALT").'"';?> <?=$disabled?>><?
					foreach($arr as $key => $v):
						?><option value="<?echo $key?>"<?if($val==$key)echo" selected"?>><?echo htmlspecialcharsbx($v)?></option><?
					endforeach;
					?></select><?
			elseif($type[0]=="multiselectbox"):
				$arr = $type[1];
				if(!is_array($arr))
					$arr = array();
				$arr_val = explode(",",$val);
				?><select size="5" <?if(isset($arControllerOption[$Option[0]]))echo ' disabled title="'.GetMessage("MAIN_ADMIN_SET_CONTROLLER_ALT").'"';?> multiple name="<?echo htmlspecialcharsbx($Option[0])?>[]"<?=$disabled?>><?
					foreach($arr as $key => $v):
						?><option value="<?echo $key?>"<?if(in_array($key, $arr_val)) echo " selected"?>><?echo htmlspecialcharsbx($v)?></option><?
					endforeach;
				?></select><?
			elseif($type[0]=="textarea"):
				?><textarea <?if(isset($arControllerOption[$Option[0]]))echo ' disabled title="'.GetMessage("MAIN_ADMIN_SET_CONTROLLER_ALT").'"';?> rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0])?>"<?=$disabled?>><?echo htmlspecialcharsbx($val)?></textarea><?
			elseif($type[0]=="statictext"):
				echo htmlspecialcharsbx($val);
			elseif($type[0]=="statichtml"):
				echo $val;
			endif;
			?></td>
		</tr>
	<?
	endif;
}

function __AdmSettingsSaveOptionCustom($module_id, $arOption, $site_id = false)
{
	if(!is_array($arOption) || isset($arOption["note"]))
		return false;

	if($arOption[3][0] == "statictext" || $arOption[3][0] == "statichtml")
		return false;

	$arControllerOption = CControllerClient::GetInstalledOptions($module_id);

	if(isset($arControllerOption[$arOption[0]]))
		return false;

	$name = $arOption[0];
	$val = $_REQUEST[$name.($site_id === false ? "" : "_".$site_id)];

	//disabled
	if(!isset($_REQUEST[$name]))
	{
		if($arOption[3][0] == 'checkbox')
			$val = 'N';
		else
			return false;
	}

	if($arOption[3][0] == "checkbox" && $val != "Y")
		$val = "N";
	if($arOption[3][0] == "multiselectbox")
		$val = @implode(",", $val);

	COption::SetOptionString($module_id, $name, $val, $arOption[1], $site_id);
	return null;
}

function __AdmSettingsDrawRowFile($module_id, $Option, $site_id = false)
{
	$bFileman = CModule::IncludeModule('fileman');

	$arControllerOption = CControllerClient::GetInstalledOptions($module_id);
	$val = COption::GetOptionString($module_id, $Option[0], $Option[2], $site_id);

	if ($site_id !== false)
		$Option[0] .= "_".$site_id;

	$type = $Option[3];
	$Option[0] = $Option[0];
	?>
		<tr>
			<td valign="top" width="50%"><?

				echo $Option[1];

				if (strlen($sup_text) > 0)
				{
					?><span class="required"><sup><?=$sup_text?></sup></span><?
				}
					?></td>
			<td valign="middle" width="50%"><?
			if($type[0]=="file"):
				if($bFileman):
					echo CMedialib::InputFile(
						$Option[0], $val,
						array(
							"IMAGE" => "N",
							"PATH" => "Y",
							"FILE_SIZE" => "Y",
							"DIMENSIONS" => "Y",
							"IMAGE_POPUP"=>"Y",
							"MAX_SIZE" => array("W" => 200, "H"=>200)
							), //info
						false, //file
						array(), //server
						array(), //media lib
						false, //descr
						false //delete
					);

					$Module = CModule::CreateModuleObject("fileman");
					if(CheckVersion("9.5.4", $Module->MODULE_VERSION)):
						$arFile = CFile::_GetImgParams($val);
						echo CFile::ShowImage($arFile["SRC"], 200, 200, "border=0", "", true);
					endif;
				else:

					$arFile = CFile::_GetImgParams($val);
					echo CFile::InputFile($Option[0], 20, "", false, 0, "IMAGE", "", 0);
					echo "<br>";
					echo CFile::ShowImage($arFile["SRC"], 200, 200, "border=0", "", true);
				endif;
			endif;
			?></td>
		</tr>
	<?
}

function ShowParamsHTMLByArray($arParams, $site_id = false)
{
	foreach($arParams as $Option)
	{
		if($Option[3][0] == "file")
			__AdmSettingsDrawRowFile("feedback_error", $Option, $site_id);
		else
			__AdmSettingsDrawRowCustom("feedback_error", $Option, $site_id);
	}
}

//Save options
if($REQUEST_METHOD=="POST" && strlen($Update.$Apply.$RestoreDefaults)>0 && check_bitrix_sessid())
{
	if(strlen($RestoreDefaults)>0)
	{
		COption::RemoveOption("feedback_error");
	}
	else
	{
		if (!$strError)
		{
			foreach($arSites as $arSite)
			{
				if (strlen($_POST["logo"."_".$arSite["ID"]]) > 0)
				{
					$file_name = $_POST["logo"."_".$arSite["ID"]];
					$ABS_FILE_NAME = $_SERVER["DOCUMENT_ROOT"].$file_name;

					if (is_file($ABS_FILE_NAME) && file_exists($ABS_FILE_NAME))
					{
						if(!CFile::IsImage($file_name))
						{
							$strError = GetMessage("FEEDBACK_ERROR_NO_IMG").": ".'['.$arSite["ID"].'] '.$arSite["NAME"];
							break;
						}
					}
					else
					{
						$strError = GetMessage("FEEDBACK_ERROR_NO_FILE").": ".'['.$arSite["ID"].'] '.$arSite["NAME"];
						break;
					}
				}
			}
		}

		if(!$strError)
		{
			foreach($arAllOptions as $aOptGroup)
			{
				foreach($aOptGroup as $option)
				{
					if($option[0] == "logo" && strlen($_POST["logo"])==0)
						continue;
					__AdmSettingsSaveOption($module_id, $option);
				}
			}

			foreach($arSites as $arSite)
			{
				foreach($arAllOptions as $aOptGroup)
				{
					foreach($aOptGroup as $option)
					{
						$option[2] = "";

						__AdmSettingsSaveOptionCustom($module_id, $option, $arSite["ID"]);
					}
				}
			}
		}
	}
	if(!$strError)
	{
		if(strlen($Update)>0 && strlen($_REQUEST["back_url_settings"])>0)
			LocalRedirect($_REQUEST["back_url_settings"]);
		else
			LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
	}
	else
	{
		$APPLICATION->ThrowException($strError);
	}
}


if ($e = $APPLICATION->GetException())
	$message = new CAdminMessage(GetMessage("FEEDBACK_ERROR_SAVING"), $e);

if($message)
	echo $message->Show();
?>

<form method="post" name="feedback_error_option_form" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialchars($mid)?>&amp;lang=<?echo LANG?>">
<?
$tabControl->Begin();
$tabControl->BeginNextTab();
?>
<tr>
	<td colspan="2">
	</td>
</tr>
<tr>
	<td colspan="2">
<?
$tabIndex = 0;

$aSettingTabs = array();

// main tab
$aSettingTabs[] = array("DIV" => "opt_site_main_".$tabIndex, "TAB" => GetMessage("FEEDBACK_ERROR_MAIN_TAB_SET_MAIN_TITLE"), 'TITLE' => GetMessage("FEEDBACK_ERROR_MAIN_TAB_SET_MAIN_DESCRIPTION"));

// tabs on the sites
foreach($arSites as $arSite)
	$aSettingTabs[] = array("DIV" => "opt_site_".$arSite["ID"]."_".$tabIndex, "TAB" => '['.$arSite["ID"].'] '.htmlspecialcharsbx($arSite["NAME"]), 'TITLE' => GetMessage("FEEDBACK_ERROR_MAIN_TAB_SET_SITE_DESCRIPTION").' ['.$arSite["ID"].'] '.htmlspecialcharsbx($arSite["NAME"]));
?>
<tr>
	<td colspan="2">
	<?
	$siteTabControl = new CAdminViewTabControl("siteTabControl_".$tabIndex, $aSettingTabs);
	$siteTabControl->Begin();

	$suffix = "_main_".$tabIndex;
	$siteTabControl->BeginNextTab();
	?>

	<table cellpadding="0" cellspacing="0" border="0" class="edit-table" width="100%" id="site_settings<?=$suffix?>">

	<?
		ShowParamsHTMLByArray($arAllOptions["main"]);
	?>
	</table>

	<?

	// output tabs
	foreach($arSites as $arSite)
	{
		$settings = $arAllOptions["main"];

		foreach($settings as $ind => $data)
		{
			$settings[$ind][2] = "";
		}

		$suffix = "_".$arSite['ID']."_".$tabIndex;
		$siteTabControl->BeginNextTab(); ?>

		<table cellpadding="0" cellspacing="0" border="0" class="edit-table" width="100%" id="site_settings<?=$suffix?>">

		<? ShowParamsHTMLByArray($settings, $arSite['ID']); ?>

		</table>

	<? }

	$siteTabControl->End();

	?>
	</td>
</tr>
<?$tabControl->BeginNextTab();?>
<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
<?$tabControl->Buttons();?>
<script language="JavaScript">
function RestoreDefaults()
{
	if(confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>'))
		window.location = "<?echo $APPLICATION->GetCurPage()?>?RestoreDefaults=Y&lang=<?echo LANG?>&mid=<?echo urlencode($mid)?>&<?=bitrix_sessid_get()?>";
}
</script>
<div align="left">
	<input type="hidden" name="Update" value="Y">
	<input type="submit" <?if(!$USER->IsAdmin())echo " disabled ";?> name="Update" value="<?echo GetMessage("MAIN_SAVE")?>">
	<input type="reset" <?if(!$USER->IsAdmin())echo " disabled ";?> name="reset" value="<?echo GetMessage("MAIN_RESET")?>">
	<input type="button" <?if(!$USER->IsAdmin())echo " disabled ";?> type="button" title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="RestoreDefaults();" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
</div>
<?$tabControl->End();?>
<?=bitrix_sessid_post();?>
</form>
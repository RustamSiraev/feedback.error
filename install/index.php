<?

global $MESS;
$PathInstall = str_replace("\\", "/", __FILE__);
$PathInstall = substr($PathInstall, 0, strlen($PathInstall)-strlen("/index.php"));
IncludeModuleLangFile(__FILE__);

Class feedback_error extends CModule
{
        var $MODULE_ID = "feedback.error";
        var $MODULE_VERSION;
        var $MODULE_VERSION_DATE;
        var $MODULE_NAME;
        var $MODULE_DESCRIPTION;
        var $MODULE_CSS;

        function feedback_error()
        {
                $arModuleVersion = array();

                $path = str_replace("\\", "/", __FILE__);
                $path = substr($path, 0, strlen($path) - strlen("/index.php"));
                include($path."/version.php");

                if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
                {
                        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
                        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
                }
                else
                {
                        $this->MODULE_VERSION = "1.0.0";
                        $this->MODULE_VERSION_DATE = "2021-03-11 14:00:00";
                }

                $this->MODULE_NAME = GetMessage("FEEDBACK_ERROR_MODULE_NAME");
                $this->MODULE_DESCRIPTION = GetMessage("FEEDBACK_ERROR_MODULE_DESCRIPTION");

                $this->PARTNER_NAME = "RustamSiraev";
                $this->PARTNER_URI = "https://www.qtakbash.ru/";
        }
        function DoInstall()
        {
                global $DB, $APPLICATION, $step;
                $step = IntVal($step);
                $this->InstallFiles();
                $this->InstallDB();
                $this->InstallEvents();

                $GLOBALS["errors"] = $this->errors;
                $APPLICATION->IncludeAdminFile(GetMessage("FEEDBACK_ERROR_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/local/modules/feedback.error/install/step1.php");
        }
        function DoUninstall()
        {
                global $DB, $APPLICATION, $step;
                $step = IntVal($step);
                $this->UnInstallDB();
                $this->UnInstallEvents();
                $this->UnInstallFiles();
                $APPLICATION->IncludeAdminFile(GetMessage("FEEDBACK_ERROR_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/local/modules/feedback.error/install/unstep1.php");
        }
        function InstallDB()
        {
                global $DB, $DBType, $APPLICATION;
                $this->errors = false;

                if(CModule::IncludeModule("iblock"))
                {
                        $arLang = array();
                        $l = CLanguage::GetList($lby="sort", $lorder="asc");
                        while($ar = $l->ExtractFields("l_"))
                                $arIBTLang[]=$ar;

                        for($i=0; $i<count($arIBTLang); $i++)
                        {
                                if($arIBTLang[$i]["LID"]=="ru")
                                        $NAME = GetMessage("FEEDBACK_ERROR_IBLOCK_TYPE_NAME");
                                else
                                        $NAME = GetMessage("FEEDBACK_ERROR_IBLOCK_TYPE_NAME_EN");

                                $arLang[$arIBTLang[$i]["LID"]] = array("NAME" => $NAME);
                        }
                        $arFields = array(
                                "ID" => GetMessage("FEEDBACK_ERROR_IBLOCK_TYPE_NAME_EN"),
                                "LANG" => $arLang,
                                "SECTIONS" => "Y"
                        );

                        $obBlocktype = new CIBlockType;
                        if(!CIBlockType::GetByID(GetMessage("FEEDBACK_ERROR_IBLOCK_TYPE_NAME_EN"))->Fetch())
                                $IBLOCK_TYPE_ID = $obBlocktype->Add($arFields);

                        COption::SetOptionString("altasib_error", "ERROR_IBLOCK_BASE_CODE", GetMessage("FEEDBACK_ERROR_IBLOCK_TYPE_NAME_EN"));

                        if(!$IBLOCK_TYPE_ID)
                                $this->errors .= $obBlocktype->LAST_ERROR;

                        $arSites = Array();
                        $obSites = CSite::GetList($by="sort", $order="asc", array("ACTIVE" => "Y"));
                        while($arSite = $obSites->Fetch())
                        {
                                $iblockCode = GetMessage("FEEDBACK_ERROR_IBLOCK_TYPE_NAME_EN").'_'.$arSite['LID'];
                                $arIB = CIBlock::GetList(false,Array("CODE"=>$iblockCode))->Fetch();
                                if(!$arIB)
                                {
                                        $ib = new CIBlock;
                                        $arFields = Array(
                                                "NAME" => GetMessage("FEEDBACK_ERROR_IBLOCK_TYPE_NAME")." [".$arSite["LID"]."] ".$arSite["NAME"],
                                                "CODE" => $iblockCode,
                                                "LIST_PAGE_URL" =>"",
                                                "DETAIL_PAGE_URL" =>"",
                                                "SITE_ID" => array($arSite["LID"]),
                                                "IBLOCK_TYPE_ID" => GetMessage("FEEDBACK_ERROR_IBLOCK_TYPE_NAME_EN"),
                                                "INDEX_ELEMENT" => "N",
                                                "INDEX_SECTION" => "N",
                                        );
                                        $IBLOCK_ID = $ib->Add($arFields);

                                        if(!$IBLOCK_ID)
                                                $this->errors .= $ib->LAST_ERROR;
                                        else
                                        {
                                                CIBlock::SetPermission($IBLOCK_ID, Array("2"=>"R"));
                                                $arFields = Array(
                                                        "NAME" => GetMessage("FEEDBACK_ERROR_IBLOCK_URL_PROPERTY_NAME"),
                                                        "ACTIVE" => "Y",
                                                        "SORT" => "100",
                                                        "CODE" => "URL_ERROR",
                                                        "PROPERTY_TYPE" => "S",
                                                        "IBLOCK_ID" => $IBLOCK_ID,
                                                );
                                                $ibp = new CIBlockProperty;
                                                $PropID = $ibp->Add($arFields);

                                                if(!$PropID)
                                                        $this->errors .= $ibp->LAST_ERROR;

                                                $arFields = Array(
                                                        "NAME" => "IP",
                                                        "ACTIVE" => "Y",
                                                        "SORT" => "100",
                                                        "CODE" => "IP_ADDRESS",
                                                        "PROPERTY_TYPE" => "S",
                                                        "IBLOCK_ID" => $IBLOCK_ID,
                                                );
                                                $ibp = new CIBlockProperty;
                                                $PropID = $ibp->Add($arFields);

                                                if(!$PropID)
                                                        $this->errors .= $ibp->LAST_ERROR;
                                        }
                                }
                        }
                }
                if(empty($this->errors))
                {
                        RegisterModule("feedback.error");
                        RegisterModuleDependences("main","OnProlog","feedback.error","ErrorSendMD","ErrorSendOnProlog", "100");
                        RegisterModuleDependences("main","OnBeforeEndBufferContent","feedback.error","ErrorSendMD","ErrorSendOnBeforeEndBufferContent", "100");
                }
        }

        function UnInstallDB($arParams = array())
        {
                global $DB, $DBType, $APPLICATION;
                $this->errors = false;

                UnRegisterModuleDependences("main", "OnProlog", "feedback.error", "ErrorSendMD", "ErrorSendOnProlog");
                UnRegisterModuleDependences("main", "OnBeforeEndBufferContent", "feedback.error", "ErrorSendMD", "ErrorSendOnBeforeEndBufferContent");
                COption::RemoveOption("feedback_error");
                UnRegisterModule("feedback.error");
                return true;
        }
        function InstallEvents()
        {
                $rsET = CEventType::GetList(Array("TYPE_ID" => "FEEDBACK_ERROR_MAIL"));
                if(!$arET = $rsET->Fetch())
                {
                        $arSites = Array();
                        $obSites = CSite::GetList($by="sort", $order="desc", Array());
                        while($arSite = $obSites->Fetch())
                        {
                                $arSites[] = $arSite["ID"];
                        }

                        $et = new CEventType;
                        $ID = $et->Add(array(
                                "LID"                => "ru",
                                "EVENT_NAME"        => "FEEDBACK_ERROR_MAIL",
                                "NAME"                        => GetMessage("FEEDBACK_ERROR_EVENT_NAME"),
                                "DESCRIPTION"        => GetMessage("FEEDBACK_ERROR_EVENT_DESC")
                        ));
                        if(!$ID)
                                echo $et->LAST_ERROR;

                        $emess = new CEventMessage;
                        $arMessage = Array(
                                "ACTIVE"                =>        "Y",
                                "LID"                        =>        $arSites,
                                "EVENT_NAME"        =>        "FEEDBACK_ERROR_MAIL",
                                "EMAIL_FROM"        =>        "#EMAIL_FROM#",
                                "EMAIL_TO"                =>        "#EMAIL_TO#",
                                "SUBJECT"                =>        GetMessage("FEEDBACK_ERROR_EVENT_NAME"),
                                "BODY_TYPE"                =>        "html",
                                "MESSAGE"                =>        GetMessage("FEEDBACK_ERROR_EVENT_MESSAGE")
                        );
                        if(!$emess->Add($arMessage))
                                echo $emess->LAST_ERROR;
                }
        }

        function UnInstallEvents()
        {
                global $DB;
                $DB->Query("DELETE FROM b_event_type WHERE EVENT_NAME in ('FEEDBACK_ERROR_MAIL')");
                $DB->Query("DELETE FROM b_event_message WHERE EVENT_NAME in ('FEEDBACK_ERROR_MAIL')");
        }

        function InstallFiles()
        {
                CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/local/modules/feedback.error/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/feedback.error", true, true);
                return true;
        }

        function UnInstallFiles()
        {
                DeleteDirFilesEx("/bitrix/js/feedback.error");
                return true;
        }
}
?>

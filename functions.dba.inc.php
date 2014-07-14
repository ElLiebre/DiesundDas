<?php

/*
v 1.4
- expects PDO DB Object
- image upload functionality not working yet, see line 305 and on

v 1.2 - some time around 2009
- Beim formfieldtype select können die Daten jetzt aus anderen Tabellen in der gleichen Datenbank kommen. -> relationreplace relationreplace_table relationreplace_id relationreplace_text

v 1.1 - 09.09.2008
- Added basic search functionality
- Added JPEG upload

Planned Features:
- delete rows
- checkinput_method: FLOAT,INT
- enum(y, n) fields

// ------------------ Usage ------------------ //
* REQUIRES: functions.misc.inc.php
* to use, call handleDBA($DBPDO, $CDBA);
- $DBPDO -> active MySQL PDO connection
- $CDBA -> configuration array:

$CDBA = array(
    'db_name' => 'dbname',
    'db_table' => 'dbtable',
    'db_table_pkey' => 'prefix_id',
    // 'db_field_timestamp_add' => 'prefix_timestamp_add',
    // 'db_field_timestamp_edit' => 'prefix_timestamp_edit',
    'db_field_order' => 'prefix_date',
    'db_field_order_method' => 'DESC',
    'db_show_rowcount' => true,
    // 'db_show_addtime' => true,
    // 'db_show_edittime' => true,

    'form_submit_width' => 300,
    'show_navigation' => true,
    'allow_delete' => false, // muss noch implementiert werden!

    'img_upload_enabled' => false, // NUR JPEG Dateien!
    // 'img_upload_max_images' => 2,
    // 'img_upload_directory' => '/uploaddir/', // relative path

    'search_enable' => true,

    'listtable_options' => array(
        'maxrows' => 40,
        'edit_enabled' => true,
        'edit_title' => 'bearb.',
        'edit_width' => 45,
        'show_enabled' => true,
        'show_title' => 'zeigen',
        'show_width' => 50,
    ),

    'db_table_fields' => array(
        'field1name' => array(
            'listtable_options' => array(
                'show' => true,
                'width' => 130,
                'title' => 'field1listtitle',
            ),
            'relationreplace' => true, // kommen die Daten für dieses select aus einer anderen Tabelle? (Dieser Block kommt nur bei formfieldtype = select zum tragen)
            'relationreplace_table' => 'partner', // aus welcher Tabelle?
            'relationreplace_id' => 'pa_id', // Primärschlüssel
            'relationreplace_text' => 'pa_name', // Textschlüssel

            'searchable' => false,
            'formfieldtitle' => 'Titel',
            'formfieldtype' => 'select', // select / text / textarea
            'maxlength' => 16, // only needed if formfieldtype == text
            'select_options' => array( // only needed if formfieldtype == select
                '|empty',
                'option1|Option 1',
                'option2|Option 2',
            ),
            'formfieldwidth' => 170,
            'formfieldheight' => 50, // only needed if formfieldytpe == textarea

            'checkinput' => false,
            'checkinput_method' => 'strlen', // strlen/email/select
            'checkinput_method_strlen' => 3,
        ),
    ),
);
*/

function DBAshowSearchForm($CDBA) {
    global $FORM;

    $sH = '';

    $aOptions = array();
    foreach ($CDBA["db_table_fields"] as $sKey => $aValue) {
        if ($aValue["searchable"]) $aOptions[] = $sKey.'|'.$aValue["formfieldtitle"];
        else continue;
    }

    if (count($aOptions)) {
        $sH .= 'Suche:<br />';
        $FORM->sFormmethod = 'GET';
        $sH .= $FORM->openForm();
        $sH .= $FORM->makeSelect('f', $aOptions, getFormField('f', ''));
        $sH .= ' ';

        $aOptions = array(
            'c|enthält',
            'i|ist gleich',
            'nc|enthält nicht',
            'n|ist nicht',
        );

        $sH .= $FORM->makeSelect('c', $aOptions, getFormField('c', ''));
        $sH .= ' ';
        $sH .= $FORM->makeText('t', getFormField('t', ''), 150);
        $sH .= ' ';
        $sH .= $FORM->makeSubmit('', 'los...', 50);
        $sH .= $FORM->closeForm();
        $sH .= '<br />';

        return $sH;
    }
}

function handleDBA($DBDBA, $CDBA) {
    $sH = '';

    if (isset($CDBA["show_navigation"]) && $CDBA["show_navigation"]) $sH .= '<a href="'.$_SERVER["PHP_SELF"].'">Datens&auml;tze anzeigen</a> &middot; <a href="'.$_SERVER["PHP_SELF"].'?action=add">Datensatz hinzuf&uuml;gen</a><br /><br />';

    // check for relationreplace
    $aRelationreplace = array();
    foreach ($CDBA["db_table_fields"] as $sKey => $mValue) {
        if (isset($mValue["relationreplace"]) && $mValue["relationreplace"]) {
            $sQ = "SELECT ".$mValue["relationreplace_id"].", ".$mValue["relationreplace_text"]." FROM ".$mValue["relationreplace_table"];
            //$sH .= $sQ;

            $hResult = $DBDBA->query($sQ);
            //echo debug($DBDBA->error());
            $iRows = $DBDBA->numRows($hResult);

            if ($iRows > 0) {
                while ($aRow = $DBDBA->fetchArray($hResult)) $aRelationreplace[$sKey][$aRow[$mValue["relationreplace_id"]]] = $aRow[$mValue["relationreplace_text"]];
            }
        }
    }
    //if (count($aRelationreplace) > 0) $sH .= debug($aRelationreplace);
    // check for relationreplace end

    if (isset($_REQUEST["action"]) && ($_REQUEST["action"] == 'edit' || $_REQUEST["action"] == 'doedit' || $_REQUEST["action"] == 'show'|| $_REQUEST["action"] == 'add'|| $_REQUEST["action"] == 'doadd')) {

        $sQSelect = DBAGenerateFormQuery($CDBA, $_REQUEST["id"]);
        //debug($sQSelect);

        if ($_REQUEST["action"] == 'edit' || $_REQUEST["action"] == 'doedit') {
            $sErr = '';
            if ($_REQUEST["action"] == 'doedit') {
                $sErr .= DBAcheckFormData($CDBA);
                if ($sErr == '') {
                    $hQUpdate = DBAprepareUpdateQuery($CDBA, $DBDBA);
                    //debug($sQUpdate);

                    $hQUpdate->execute();

                    $sH .= '<div style="border: 2px solid black; padding: 10px;">Der Datensatz wurde aktualisiert ('.showClienttime().').</div><br />';
                } else $sH .= '<div style="border: 2px solid red; padding: 10px;">'.cutStringend($sErr, 6).'</div><br />';
            } elseif (isset($_REQUEST["subaction"]) && $_REQUEST["subaction"] == "upload" && $iBild <= $iMaxbilder) $sH .= '<div style="border: 2px solid black; padding: 10px;">'.DBAhandleUpload($CDBA).'</div><br />';

            if (isset($_GET["justadded"])) $sH .= '<div style="border: 2px solid black; padding: 10px;">Der Datensatz wurde hinzugef&uuml;gt ('.showClienttime().'), Sie k&ouml;nnen ihn jetzt hier weiter bearbeiten.</div><br />';

            $hResult = $DBDBA->query($sQSelect);
            $aRow = $hResult->fetch();
            //debug($aRow);

            $sH .= DBAgenerateForm($CDBA, $DBDBA, 'edit', $aRow);

            if (isset($CDBA["db_show_addtime"]) && $CDBA["db_show_addtime"] && $CDBA["db_field_timestamp_add"] != '') $sH .= 'Der Datensatz wurde am '.date("d.m.Y H:i", $aRow[$CDBA["db_field_timestamp_add"]]).' hinzugef&uuml;gt.<br />';
            if (isset($CDBA["db_show_edittime"]) && $CDBA["db_show_edittime"] && $CDBA["db_field_timestamp_edit"] != '' && $aRow[$CDBA["db_field_timestamp_edit"]] != '') $sH .= 'Der Datensatz wurde am '.date("d.m.Y H:i", $aRow[$CDBA["db_field_timestamp_edit"]]).' das letzte Mal bearbeitet.<br />';
        } elseif ($_REQUEST["action"] == 'show') {
            $hResult = $DBDBA->query($sQSelect);
            $aRow = $DBDBA->fetchArray($hResult);
            //debug($aRow);

            $sH .= DBAgenerateForm($CDBA, $DBDBA, 'show', $aRow);

            if (isset($CDBA["db_show_addtime"]) && $CDBA["db_show_addtime"] && $CDBA["db_field_timestamp_add"] != '') $sH .= 'Der Datensatz wurde am '.date("d.m.Y H:i", $aRow[$CDBA["db_field_timestamp_add"]]).' hinzugef&uuml;gt.<br />';
            if (isset($CDBA["db_show_edittime"]) && $CDBA["db_show_edittime"] && $CDBA["db_field_timestamp_edit"] != '' && $aRow[$CDBA["db_field_timestamp_edit"]] != '') $sH .= 'Der Datensatz wurde am '.date("d.m.Y H:i", $aRow[$CDBA["db_field_timestamp_edit"]]).' das letzte Mal bearbeitet.<br />';
        } elseif ($_REQUEST["action"] == 'add' || $_REQUEST["action"] == 'doadd') {
            $sErr = '';
            if ($_REQUEST["action"] == 'doadd') {
                $sErr .= DBAcheckFormData($CDBA);
                if ($sErr == '') {
                    $hQInsert = DBAprepareInsertQuery($CDBA, $DBDBA);

                    $hQInsert->execute();

                    $iId = $DBDBA->lastInsertId();
                    header('Location: '.$_SERVER["PHP_SELF"].'?action=edit&id='.$iId.'&justadded');

                    exit;
                } else $sH .= '<div style="border: 2px solid red; padding: 10px;">'.cutStringend($sErr, 6).'</div><br />';
            }
            $sH .= DBAgenerateForm($CDBA, $DBDBA, 'add');
        }
    } else { // no action set, show listtable
        if ($CDBA["search_enable"]) $sH .= DBAshowSearchForm($CDBA);

        $CDBAList = DBAgenerateListtableConfig($CDBA);
        //debug($CDBAList);

        $sQ = "SELECT ".$CDBAList["select_fields"]." FROM ".$CDBA["db_table"];
        $sQ .= DBAgenerateSerachClause($CDBA);

        $aGetvars = array();
        if ($CDBA["search_enable"] && isset($_GET["f"]) && $_GET["f"] != '') {
            $aGetvars = array(
                'f' => $_GET["f"],
                'c' => $_GET["c"],
                't' => $_GET["t"],
            );
        }
        if (isset($CDBA["db_field_order"]) && $CDBA["db_field_order"] != '') {
            $sQ .= " ORDER BY ".$CDBA["db_field_order"];
            if (isset($CDBA["db_field_order_method"]) && $CDBA["db_field_order_method"] != '') $sQ .= " ".$CDBA["db_field_order_method"];
        }
        //debug($sQ);

        $hResult = $DBDBA->query($sQ);
        $iRows = $hResult->rowCount();
        //debug($iRows);

        if ($iRows == 0) $sH .= 'Es liegen keine Daten vor die angezeigt werden könnten.';
        else {
            if (isset($CDBA["listtable_options"]["maxrows"]) && $CDBA["listtable_options"]["maxrows"] > 0 && $CDBA["listtable_options"]["maxrows"] < $iRows) {
                $iPages = ceil($iRows / $CDBA["listtable_options"]["maxrows"]);

                if (isset($_GET["page"])) {
                    $iPage = $_GET["page"] * 1;
                    if ($iPage > $iPages) $iPage = $iPages;
                    if ($iPage == 0) $iPage = 1;
                } else {
                    $iPage = 1;
                } // endif
                //debug($iPage);
                //debug($iPages);

                $sH .= showPagesnav($iPages, $iPage, $aGetvars).'<br /><br />';
                $sQ = DBAgenerateListtableQuery($CDBA, $CDBAList["select_fields"], $iPage);
                //debug($sQ);

                $hResult = $DBDBA->query($sQ);
            }
            while ($aRow = $hResult->fetch()) $aData[] = $aRow;
            //debug($aData);
            //debug($aRelationreplace);

            if (count($aRelationreplace) > 0) {
                foreach ($aRelationreplace as $sKey => $mValue) {
                    //$sH .= $sKey;
                    //debug($mValue);

                    foreach ($aData as $sDataKey => $aDataValue) {
                        $aDataTMP[$sDataKey] = $aDataValue;
                        $aDataTMP[$sDataKey][$sKey] = $mValue[$aDataValue[$sKey]];
                    }
                    //debug($aDataTMP);
                }
                $aData = $aDataTMP;

                unset($aDataTMP);
            }
            $sH .= makeListtable($CDBAList["table"], $aData);

            if (isset($CDBA["db_show_rowcount"]) && $CDBA["db_show_rowcount"]) $sH .= '<br />'.$iRows.' Datens&auml;tze insgesamt.';
        }
    }

    return $sH;
}

function DBAprepareInsertQuery($CDBA, $DBDBA) {
    $aData = array();
    foreach ($CDBA["db_table_fields"] as $sKey => $aValue) {
        $aData[$sKey] = $_POST[$sKey];
    }
    if (isset($CDBA["db_field_timestamp_add"]) && $CDBA["db_field_timestamp_add"] != '') $aData[$CDBA["db_field_timestamp_add"]] = time();

    $sQ = buildPSInsertQuery( $aData, $CDBA["db_table"] );
    //echo debug($sQ);
    $hResult = $DBDBA->prepare( $sQ );
    foreach ( $aData as $sKey => $sValue ) $hResult->bindValue( ':'.$sKey, $sValue );

    return $hResult;
}

function DBAprepareUpdateQuery($CDBA, $DBDBA) {

    $aData = array();
    foreach ($CDBA["db_table_fields"] as $sKey => $aValue) {
        $aData[$sKey] = $_POST[$sKey];
    }
    $aData[$CDBA["db_table_pkey"]] = $_POST["id"];
    if (isset($CDBA["db_field_timestamp_edit"]) && $CDBA["db_field_timestamp_edit"] != '') $aData[$CDBA["db_field_timestamp_edit"]] = time();

    $sQ = buildPSUpdateQuery( $aData, $CDBA["db_table"], $CDBA["db_table_pkey"] );
    //echo $sQ."\n";
    //echo debug($aData, true);

    $hResult = $DBDBA->prepare( $sQ );
    foreach ( $aData as $sKey => $sValue ) $hResult->bindValue( ':'.$sKey, $sValue );

    return $hResult;
}

function DBAcheckFormData($CDBA) {
    $sErr = '';
    foreach ($CDBA["db_table_fields"] as $sKey => $aValue) {
        if (isset($aValue["checkinput"]) && $aValue["checkinput"]) {
            if ($aValue["checkinput_method"] == 'strlen') {
                if (strlen($_POST[$sKey]) < $aValue["checkinput_method_strlen"]) $sErr .= $aValue["formfieldtitle"].': Bitte geben Sie mindestens '.$aValue["checkinput_method_strlen"].' Zeichen an.<br />';
            } elseif ($aValue["checkinput_method"] == 'email') {
                if (!validateEmail($_POST[$sKey])) $sErr .= $aValue["formfieldtitle"].': Bitte geben Sie eine g&uuml;ltige E-Mail Adresse an.<br />';
            } elseif ($aValue["checkinput_method"] == 'select') {
                if ($_POST[$sKey] == '') $sErr .= $aValue["formfieldtitle"].': Bitte wählen Sie eine der Optionen aus.<br />';
            }
        }
    }

    return $sErr;
}

function DBAgenerateForm($CDBA, $DBDBA, $sType, $aData = array()) {
    global $FORM;

    $sH = '';
    $bReadonly = false;

    $sH .= $FORM->openForm();

    if ($sType == 'edit') {
        $sH .= 'Datensatz bearbeiten<br /><br />';
        $sH .= $FORM->makeHidden('id', $aData[$CDBA["db_table_pkey"]]);
        $sH .= $FORM->makeHidden('action', 'doedit');
    } elseif ($sType == 'add') $sH .= 'Datensatz hinzuf&uuml;gen<br /><br />'.$FORM->makeHidden('action', 'doadd');
    elseif ($sType == 'show') {
        $sH .= 'Datensatz anzeigen<br /><br />';
        $bReadonly = true;
    }
    foreach ($CDBA["db_table_fields"] as $sKey => $aValue) {
        if ($sType == 'edit') $sFieldvalue = getFormfield($sKey, $aData[$sKey], true);
        elseif ($sType == 'show') $sFieldvalue = $aData[$sKey];
        elseif ($sType == 'add') $sFieldvalue = getFormfield($sKey, '');

        $sH .= $aValue["formfieldtitle"].'<br />';
        if ($aValue["formfieldtype"] == 'text') $sH .= $FORM->makeText($sKey, $sFieldvalue, $aValue["formfieldwidth"], $aValue["maxlength"], $bReadonly);
        if ($aValue["formfieldtype"] == 'textarea') $sH .= $FORM->makeTextarea($sKey, $sFieldvalue, $aValue["formfieldwidth"], $aValue["formfieldheight"], '', '', $bReadonly);
        if ($aValue["formfieldtype"] == 'select') {
            if (isset($aValue["relationreplace"]) && $aValue["relationreplace"]) {
                $sQ = "SELECT ".$aValue["relationreplace_id"].", ".$aValue["relationreplace_text"]." FROM ".$aValue["relationreplace_table"];
                $hResult = $DBDBA->query($sQ);
                //echo debug($DBDBA->error());
                $iRows = $DBDBA->numRows($hResult);

                if ($iRows > 0) {
                    while ($aRow = $DBDBA->fetchArray($hResult)) {
                        $aRelationreplace[$aRow[$aValue["relationreplace_id"]]] = $aRow[$aValue["relationreplace_text"]];
                    }
                    $aValue["select_options"] = $aRelationreplace;
                }
            }
            if ($bReadonly) $sH .= $FORM->makeText($sKey, $sFieldvalue, $aValue["formfieldwidth"], 0, $bReadonly);
            else $sH .= $FORM->makeSelect($sKey, $aValue["select_options"], $sFieldvalue, $aValue["formfieldwidth"]);
        }
        $sH .= '<br /><br />';
    }
    if (!$bReadonly) $sH .= $FORM->makeSubmit('', 'Submit', $CDBA["form_submit_width"]);

    $sH .= $FORM->closeForm();

    if ($CDBA["img_upload_enabled"]) {
        $iBild = 1;
        //debug($_SERVER["DOCUMENT_ROOT"].$CDBA["img_upload_directory"].$_REQUEST["id"].'_'.$iBild.'.jpg');
        for ($i = 0; $i < $CDBA["img_upload_max_images"]; $i++) {
            if (is_file ($_SERVER["DOCUMENT_ROOT"].$CDBA["img_upload_directory"].$_REQUEST["id"].'_'.$iBild.'.jpg') && getImageSize($_SERVER["DOCUMENT_ROOT"].$CDBA["img_upload_directory"].$_REQUEST["id"].'_'.$iBild.'.jpg')) {
                $sH .= '<img src="'.$CDBA["img_upload_directory"].$_REQUEST["id"].'_'.$iBild.'.jpg" alt="" /><br /><br />';
                $iBild++;
            } else break;
        }
        /*
        // Bild Upload Funktionalität
        if (isset($_REQUEST["subaction"]) && $_REQUEST["subaction"] == "upload" && $iBild <= $iMaxbilder) $sInfo .= handleUpload($iBild);

        if (is_file ($_SERVER["DOCUMENT_ROOT"].$CDBA["img_upload_directory"].$_REQUEST["id"].'_'.$iBild.'.jpg') && getImageSize($_SERVER["DOCUMENT_ROOT"].$CDBA["img_upload_directory"].$_REQUEST["id"].'_'.$iBild.'.jpg')) {

        $sBild .= '<img src="'.$CDBA["img_upload_directory"].$_REQUEST["id"].'_'.$iBild.'.jpg" alt="" /><br /><br />';
        $iBild++;

        }
        */
        if ($sType == 'edit' && $iBild <= $CDBA["img_upload_max_images"]) $sH .= DBAshowUploadForm($iBild);
    }

    return $sH;
}

function DBAhandleUpload($CDBA) {
    $sH = '';

    $aIData = getImageSize($_FILES["bild"]["tmp_name"]);
    if (is_uploaded_file($_FILES["bild"]["tmp_name"]) && is_array($aIData) && $aIData[2] = 2) {
        if (!is_file($_SERVER["DOCUMENT_ROOT"].$CDBA["img_upload_directory"].$_REQUEST["id"].'_'.$_REQUEST["number"].'.jpg') || !getImageSize($_SERVER["DOCUMENT_ROOT"].$CDBA["img_upload_directory"].$_REQUEST["id"].'_'.$_REQUEST["number"].'.jpg')) {
            copy($_FILES["bild"]["tmp_name"], $_SERVER["DOCUMENT_ROOT"].$CDBA["img_upload_directory"].$_REQUEST["id"].'_'.$_REQUEST["number"].'.jpg');
            $sH .= '<strong>Das Bild wurde erfolgreich hochgeladen.</strong>';
        } else $sH .= 'Es ist bereits en Bild mit dieser Nummer vorhanden.';
    } else $sH .= '<b>Fehler beim Upload oder falsches Bildformat (nur JPG).</b>';

    return $sH;
}

function DBAshowUploadForm($iBild) {
    global $FORM;

    $FORM->bUploadform = true;
    $sH = $FORM->openForm();
    $sH .= $FORM->makeHidden('id', $_REQUEST["id"]);
    $sH .= $FORM->makeHidden('action', 'edit');
    $sH .= $FORM->makeHidden('subaction', 'upload');
    $sH .= $FORM->makeHidden('number', $iBild);
    $sH .= 'Bitte Bild <b>('.$iBild.')</b> auswählen:<br />';
    $sH .= $FORM->makeUpload('bild', '', 340, 100).'<br />';
    $sH .= $FORM->makeSubmit('submit', 'Absenden', 340);
    $sH .= $FORM->closeForm();

    return $sH;
}

function DBAgenerateFormQuery($CDBA, $iId) {
    $sQ = "SELECT ";
    foreach ($CDBA["db_table_fields"] as $sKey => $aValue) $sQ .= $sKey.", ";
    $sQ .= $CDBA["db_table_pkey"];
    if (isset($CDBA["db_field_timestamp_add"]) && $CDBA["db_field_timestamp_add"] != '') $sQ .= ", ".$CDBA["db_field_timestamp_add"];
    if (isset($CDBA["db_field_timestamp_edit"]) && $CDBA["db_field_timestamp_edit"] != '') $sQ .= ", ".$CDBA["db_field_timestamp_edit"];
    $sQ .= " FROM ".$CDBA["db_table"];
    $sQ .= " WHERE ".$CDBA["db_table_pkey"]." = '".cED($iId)."'";

    return $sQ;
}

function DBAgenerateSerachClause($CDBA) {
    $sQ = "";
    if ($CDBA["search_enable"]) {
        if (isset($_GET["f"]) && $_GET["f"] != '') {
            $sQ .= " WHERE ";
            $sQ .= cED($_GET["f"])." ";
            if ($_GET["c"] == 'c') $sQ .= "LIKE '%".cED($_GET["t"])."%'";
            elseif ($_GET["c"] == 'i') $sQ .= "= '".cED($_GET["t"])."'";
            elseif ($_GET["c"] == 'nc') $sQ .= "NOT LIKE '%".cED($_GET["t"])."%'";
            elseif ($_GET["c"] == 'n') $sQ .= "!= '".cED($_GET["t"])."'";
        }
    }

    return $sQ;
}

function DBAgenerateListtableQuery($CDBA, $sFields, $iPage) {
    $sQ = "SELECT ".$sFields." FROM ".$CDBA["db_table"];
    $sQ .= DBAgenerateSerachClause($CDBA);
    if (isset($CDBA["db_field_order"]) && $CDBA["db_field_order"] != '') {
        $sQ .= " ORDER BY ".$CDBA["db_field_order"];
        if (isset($CDBA["db_field_order_method"]) && $CDBA["db_field_order_method"] != '') $sQ .= " ".$CDBA["db_field_order_method"];
    }
    if (isset($CDBA["listtable_options"]["maxrows"]) && $CDBA["listtable_options"]["maxrows"] != '') {
        $iLimitstart = $iPage * $CDBA["listtable_options"]["maxrows"] - $CDBA["listtable_options"]["maxrows"];
        $sQ .= " LIMIT ".$iLimitstart.",".$CDBA["listtable_options"]["maxrows"];
    }

    return $sQ;
}

function DBAgenerateListtableConfig($CDBA) {
    $CDBAListtable = array();
    $CDBAList["select_fields"] = '';

    foreach ($CDBA["db_table_fields"] as $sKey => $aValue) {
        if ($aValue["listtable_options"]["show"]) {
            $CDBAList["table"][] = array('title' => $aValue["listtable_options"]["title"], 'key' => $sKey, 'width' => $aValue["listtable_options"]["width"], 'linked' => false);
            $CDBAList["select_fields"] .= $sKey.', ';
        }
    }
    $CDBAList["select_fields"] .= $CDBA["db_table_pkey"];

    if (isset($CDBA["listtable_options"]["show_enabled"]) && $CDBA["listtable_options"]["show_enabled"]) {
        $CDBAList["table"][] = array(
            'title' => $CDBA["listtable_options"]["show_title"],
            'key' => $CDBA["db_table_pkey"],
            'width' => $CDBA["listtable_options"]["show_width"],
            'linked' => true,
            'ltarget' => $_SERVER["PHP_SELF"],
            'lkeyname' => 'id',
            'lgetvars' => array('action' => 'show'),
        );
    }
    if (isset($CDBA["listtable_options"]["edit_enabled"]) && $CDBA["listtable_options"]["edit_enabled"]) {
        $CDBAList["table"][] = array(
            'title' => $CDBA["listtable_options"]["edit_title"],
            'key' => $CDBA["db_table_pkey"],
            'width' => $CDBA["listtable_options"]["edit_width"],
            'linked' => true,
            'ltarget' => $_SERVER["PHP_SELF"],
            'lkeyname' => 'id',
            'lgetvars' => array('action' => 'edit'),
        );
    }

    return $CDBAList;
}

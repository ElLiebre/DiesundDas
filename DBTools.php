<?php

namespace HaaseIT;

class DBTools
{

    public static function buildInsertQuery($aData, $sTable, $bKeepAT = false)
    {
        $sFields = '';
        $sValues = '';
        foreach ($aData as $sKey => $sValue) {
            $sFields .= $sKey . ", ";
            $sValues .= "'" . Tools::cED($sValue, $bKeepAT) . "', ";
        }
        $sQ = "INSERT INTO " . $sTable . " (" . Tools::cutStringend($sFields, 2) . ") ";
        $sQ .= "VALUES (" . Tools::cutStringend($sValues, 2) . ")";
        return $sQ;
    }

    public static function buildPSInsertQuery($aData, $sTable)
    {
        $sFields = '';
        $sValues = '';
        foreach ($aData as $sKey => $sValue) {
            $sFields .= $sKey . ', ';
            $sValues .= ":" . $sKey . ", ";
        }
        $sQ = "INSERT INTO " . $sTable . " (" . Tools::cutStringend($sFields, 2) . ") VALUES (" . Tools::cutStringend($sValues, 2) . ")";
        return $sQ;
    }

    public static function buildUpdateQuery($aData, $sTable, $sPKey = '', $sPValue = '', $bKeepAT = false)
    {
        $sQ = "UPDATE " . $sTable . " SET ";
        foreach ($aData as $sKey => $sValue) {
            $sQ .= $sKey . " = '" . Tools::cED($sValue, $bKeepAT) . "', ";
        }
        $sQ = Tools::cutStringend($sQ, 2);
        if ($sPKey == '') {
            $sQ .= ' ';
        } else {
            $sQ .= " WHERE " . $sPKey . " = '" . Tools::cED($sPValue, $bKeepAT) . "'";
        }
        return $sQ;
    }

    public static function buildPSUpdateQuery($aData, $sTable, $sPKey = '')
    {
        $sQ = "UPDATE " . $sTable . " SET ";
        foreach ($aData as $sKey => $sValue) {
            if ($sPKey != '' && $sKey == $sPKey) {
                continue;
            }
            $sQ .= $sKey . " = :" . $sKey . ", ";
        }
        $sQ = Tools::cutStringend($sQ, 2);
        if ($sPKey == '') {
            $sQ .= ' ';
        } else {
            $sQ .= " WHERE " . $sPKey . " = :" . $sPKey;
        }
        return $sQ;
    }
}

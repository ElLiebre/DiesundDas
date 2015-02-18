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
            $sValues .= "'" . self::cED($sValue, $bKeepAT) . "', ";
        }
        $sQ = "INSERT INTO " . $sTable . " (" . self::cutStringend($sFields, 2) . ") ";
        $sQ .= "VALUES (" . self::cutStringend($sValues, 2) . ")";
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
        $sQ = "INSERT INTO " . $sTable . " (" . self::cutStringend($sFields, 2) . ") VALUES (" . self::cutStringend($sValues, 2) . ")";
        return $sQ;
    }

    public static function buildUpdateQuery($aData, $sTable, $sPKey = '', $sPValue = '', $bKeepAT = false)
    {
        $sQ = "UPDATE " . $sTable . " SET ";
        foreach ($aData as $sKey => $sValue) {
            $sQ .= $sKey . " = '" . self::cED($sValue, $bKeepAT) . "', ";
        }
        $sQ = self::cutStringend($sQ, 2);
        if ($sPKey == '') {
            $sQ .= ' ';
        } else {
            $sQ .= " WHERE " . $sPKey . " = '" . self::cED($sPValue, $bKeepAT) . "'";
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
        $sQ = self::cutStringend($sQ, 2);
        if ($sPKey == '') {
            $sQ .= ' ';
        } else {
            $sQ .= " WHERE " . $sPKey . " = :" . $sPKey;
        }
        return $sQ;
    }
}

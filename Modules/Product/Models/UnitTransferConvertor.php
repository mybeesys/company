<?php

namespace Modules\Product\Models;

use Exception;

class UnitTransferConvertor{

    public static function getMainUnit($type, $id, $units){
        if($units == null){
            if($type == 'P')
                $units = UnitTransfer::where('product_id', '=', $id)->get();
            else
                $units = UnitTransfer::where('modifier_id', '=', $id)->get();
            $units = $units->toArray();
        }
        $mainUnit = array_filter($units, function($unit) {
            return $unit["unit2"] == null; // Keep only even numbers
        });
        $mainUnit = reset($mainUnit);
        return $mainUnit;
    }

    public static function convertUnit($type, $id, $fromId, $toId, $quantity, $units) {
        if($units == null){
            if($type == 'P')
                $units = UnitTransfer::where('product_id', '=', $id)->get();
            else
                $units = UnitTransfer::where('modifier_id', '=', $id)->get();
            $units = $units->toArray();
        }
        if($toId == null){
            $mainUnit = self::getMainUnit($type, $id, $units);
            $toId = $mainUnit["id"];
        }
        if ($fromId === $toId) {
            return $quantity;
        }
        
        
        $mapUnits = array_column($units, null, 'id');
        $path = self::findPath($mapUnits, $fromId, $toId);
        if (!$path) {
            return null; // No conversion path found
        }

        foreach ($path as $step) {
            $transfer = $mapUnits[$step]['transfer'];
            if ($transfer !== null) {
                $quantity *= $transfer;
            } else {
                $quantity /= self::getReverseTransfer($mapUnits, $step);
            }
        }

        return $quantity;
    }

    private static function findPath($units, $fromId, $toId, $visited = [])
    {
        if ($fromId === $toId) {
            return [];
        }
        $visited[] = $fromId;
        try{
        $unit = $units[$fromId];
        }
        catch(Exception $e){
            dd($units);
        }
        if ($unit['unit2'] && !in_array($unit['unit2'], $visited)) {
            $path = self::findPath($units, $unit['unit2'], $toId, $visited);
            if ($path !== null) {
                return array_merge([$fromId], $path);
            }
        }

        foreach ($units as $id => $u) {
            if ($u['unit2'] === $fromId && !in_array($id, $visited)) {
                $path = self::findPath($units, $id, $toId, $visited);
                if ($path !== null) {
                    return array_merge([$id], $path);
                }
            }
        }

        return null;
    }

    private static function getReverseTransfer($units, $unitId)
    {
        foreach ($units as $id => $unit) {
            if ($unit['unit2'] === $unitId) {
                return $unit['transfer'];
            }
        }
        return 1;
    }

}
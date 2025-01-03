<?php

namespace Modules\Product\Models;
class Barcode{
    public static function generateUPCA() {
        // Step 1: Generate 11 random digits
        $data = '';
        for ($i = 0; $i < 11; $i++) {
            $data .= mt_rand(0, 9);
        }
    
        // Step 2: Calculate the check digit
        $oddSum = 0;
        $evenSum = 0;
    
        for ($i = 0; $i < 11; $i++) {
            if ($i % 2 === 0) {
                $oddSum += $data[$i];
            } else {
                $evenSum += $data[$i];
            }
        }
    
        $total = ($oddSum * 3) + $evenSum;
        $checkDigit = (10 - ($total % 10)) % 10;
    
        // Step 3: Combine the 11 digits with the check digit
        return $data . $checkDigit;
    }
}
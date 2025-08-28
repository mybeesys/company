<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("DROP FUNCTION IF EXISTS get_main_unit");
        DB::statement("CREATE FUNCTION `get_main_unit`(p_type char(1), 
                p_id INT) RETURNS int
    DETERMINISTIC
BEGIN
    DECLARE result INT;
    IF p_type ='P' THEN
        SELECT COALESCE(MAX(id), -1) INTO result 
        FROM product_unit_transfer ut 
        WHERE ut.product_id = p_id AND ut.unit2 IS NULL;
    ELSE
        SELECT COALESCE(MAX(id), -1) INTO result 
        FROM product_unit_transfer ut 
        WHERE ut.modifier_id = p_id AND ut.unit2 IS NULL;
    END IF;
    RETURN result;
END");

        DB::statement("DROP FUNCTION IF EXISTS convert_quantity");
        DB::statement("CREATE FUNCTION `convert_quantity`(p_type char(1), 
                    p_id INT, 
                    from_id INT, 
                    to_id INT, 
                    quantity DECIMAL(10,4)) RETURNS decimal(10,4)
    DETERMINISTIC
BEGIN
    DECLARE main_unit_id INT;
    DECLARE result_quantity DECIMAL(10,4);
    DECLARE conversion_factor DECIMAL(10,4);
    
    IF from_id IS NULL OR from_id = -1 THEN
        RETURN quantity;
    END IF;
    
    IF p_type = 'P' THEN
        SET main_unit_id = get_main_unit('P', p_id);
    ELSE
        SET main_unit_id = get_main_unit('M', p_id);
    END IF;
    
    IF main_unit_id = -1 THEN
        RETURN quantity;
    END IF;
    
    IF to_id IS NULL OR to_id = -1 THEN
        SET to_id = main_unit_id;
    END IF;
    
    IF from_id = to_id THEN
        RETURN quantity;
    END IF;
    
    IF to_id = main_unit_id THEN
        SELECT transfer INTO conversion_factor 
        FROM product_unit_transfer 
        WHERE id = from_id;
        
        IF conversion_factor IS NOT NULL THEN
            SET result_quantity = quantity * conversion_factor;
        ELSE
            SET result_quantity = quantity;
        END IF;
    ELSEIF from_id = main_unit_id THEN
        SELECT transfer INTO conversion_factor 
        FROM product_unit_transfer 
        WHERE id = to_id;
        
        IF conversion_factor IS NOT NULL THEN
            SET result_quantity = quantity / conversion_factor;
        ELSE
            SET result_quantity = quantity;
        END IF;
    ELSE

        SELECT transfer INTO conversion_factor 
        FROM product_unit_transfer 
        WHERE id = from_id;
        
        IF conversion_factor IS NOT NULL THEN
            SET result_quantity = quantity * conversion_factor;
        ELSE
            SET result_quantity = quantity;
        END IF;
        

        SELECT transfer INTO conversion_factor 
        FROM product_unit_transfer 
        WHERE id = to_id;
        
        IF conversion_factor IS NOT NULL THEN
            SET result_quantity = result_quantity / conversion_factor;
        END IF;
    END IF;
    
    RETURN result_quantity;
END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};

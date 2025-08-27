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
                        select 
                            COALESCE(MAX(id), -1)  into result 
                        from 
                            product_unit_transfer ut 
                        where ut.product_id = p_id and ut.unit2 is null;
                    ELSE
                        select 
                            COALESCE(MAX(id), -1) into result 
                        from 
                            product_unit_transfer ut 
                        where ut.modifier_id = p_id and ut.unit2 is null;
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
                        SELECT COALESCE(MAX(id), -1) INTO main_unit_id 
                        FROM product_unit_transfer 
                        WHERE product_id = p_id AND unit2 IS NULL;
                    ELSE
                        SELECT COALESCE(MAX(id), -1) INTO main_unit_id 
                        FROM product_unit_transfer 
                        WHERE modifier_id = p_id AND unit2 IS NULL;
                    END IF;
                    
                    IF to_id IS NULL OR to_id = -1 THEN
                        SET to_id = main_unit_id;
                    END IF;
                    
                    IF from_id = to_id THEN
                        RETURN quantity;
                    END IF;
                    
                    IF to_id = main_unit_id THEN
                        WITH RECURSIVE conversion_path AS (
                            SELECT id, unit2, transfer, 1 as level
                            FROM product_unit_transfer
                            WHERE id = from_id
                            
                            UNION ALL
                            
                            SELECT ut.id, ut.unit2, cp.transfer * ut.transfer, cp.level + 1
                            FROM product_unit_transfer ut
                            INNER JOIN conversion_path cp ON ut.id = cp.unit2
                            WHERE ut.unit2 IS NOT NULL
                        )
                        SELECT transfer INTO conversion_factor
                        FROM conversion_path
                        WHERE unit2 IS NULL
                        ORDER BY level DESC
                        LIMIT 1;
                        
                        IF conversion_factor IS NOT NULL THEN
                            SET result_quantity = quantity * conversion_factor;
                        ELSE
                            SET result_quantity = quantity;
                        END IF;
                    ELSEIF from_id = main_unit_id THEN
                        WITH RECURSIVE conversion_path AS (
                            SELECT id, unit2, transfer, 1 as level
                            FROM product_unit_transfer
                            WHERE unit2 = to_id
                            
                            UNION ALL
                            
                            SELECT ut.id, ut.unit2, cp.transfer * ut.transfer, cp.level + 1
                            FROM product_unit_transfer ut
                            INNER JOIN conversion_path cp ON ut.id = cp.unit2
                        )
                        SELECT 1/transfer INTO conversion_factor
                        FROM conversion_path
                        WHERE id = from_id
                        ORDER BY level DESC
                        LIMIT 1;
                        
                        IF conversion_factor IS NOT NULL THEN
                            SET result_quantity = quantity * conversion_factor;
                        ELSE
                            SET result_quantity = quantity;
                        END IF;
                    ELSE
                        SET result_quantity = convert_quantity(p_type, p_id, from_id, main_unit_id, quantity);
                        SET result_quantity = convert_quantity(p_type, p_id, main_unit_id, to_id, result_quantity);
                    END IF;
                    
                    RETURN result_quantity;
                END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};

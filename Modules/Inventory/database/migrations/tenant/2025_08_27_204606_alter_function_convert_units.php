<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS get_main_unit');
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

        DB::statement('DROP FUNCTION IF EXISTS convert_quantity');
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
                    
                    if from_id is null then
                        return quantity;
                    end if;
                    
                    IF to_id IS NULL THEN
                        IF p_type = 'P' THEN
                            SET to_id = get_main_unit('P', p_id);
                        ELSE
                            SET to_id = get_main_unit('M', p_id);
                        END IF;
                    END IF;
                    
                    if to_id = -1 then
                        return quantity;
                    end if;
                    
                    IF from_id = to_id THEN
                        RETURN quantity;
                    END IF;
                    SELECT transfer INTO conversion_factor 
                    FROM product_unit_transfer 
                    WHERE (id = from_id AND unit2 = to_id) 
                       OR (id = to_id AND unit2 = from_id)
                    LIMIT 1;
                    
                    IF conversion_factor IS NOT NULL THEN
                        IF EXISTS (SELECT 1 FROM product_unit_transfer WHERE id = from_id AND unit2 = to_id) THEN
                            SET result_quantity = quantity / conversion_factor;
                        ELSE
                            SET result_quantity = quantity * conversion_factor;
                        END IF;
                    ELSE
                        SET result_quantity = quantity;
                    END IF;

                    RETURN result_quantity;
                END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS get_main_unit');
        DB::statement('DROP FUNCTION IF EXISTS convert_quantity');
    }
};

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
        DB::statement("DROP FUNCTION IF EXISTS convert_quantity");
        DB::statement("CREATE FUNCTION `convert_quantity`(p_type char(1), 
                    p_id INT, 
                    from_id INT, 
                    to_id INT, 
                    quantity DECIMAL(10,4)) RETURNS decimal(10,2)
                        DETERMINISTIC
                    BEGIN
                    DECLARE main_unit_id INT;
                    DECLARE result_quantity DECIMAL(10,4);

                    IF to_id IS NULL THEN
                        IF p_type = 'P' THEN
                            SET to_id = get_main_unit('P', p_id);
                        ELSE
                            SET to_id = get_main_unit('M', p_id);
                        END IF;
                    END IF;

                    IF from_id = to_id THEN
                        RETURN quantity;
                    END IF;

                    WITH RECURSIVE unit_path (unit_id, transfer_factor, depth) AS (
                        SELECT unit2, transfer, 1
                        FROM product_unit_transfer
                        WHERE id = from_id

                        UNION ALL

                        SELECT ut.unit2, up.transfer_factor * ut.transfer, depth + 1
                        FROM product_unit_transfer ut
                        JOIN unit_path up ON ut.id = up.unit_id
                        WHERE ut.unit2 IS NOT NULL
                    )
                    SELECT transfer_factor INTO result_quantity
                    FROM unit_path
                    WHERE unit_id = to_id
                    ORDER BY depth ASC
                    LIMIT 1;

                    IF result_quantity IS NULL THEN
                        SET result_quantity = NULL;
                    ELSE
                        SET result_quantity = quantity * result_quantity;
                    END IF;

                    RETURN result_quantity;
                END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

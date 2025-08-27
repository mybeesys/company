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
        DB::statement("CREATE FUNCTION `get_main_unit`(p_type char(1), p_id INT) RETURNS int
        DETERMINISTIC
        BEGIN
            DECLARE result INT;
            
            IF p_type ='P' THEN
                SELECT COALESCE(MAX(id), -1) INTO result 
                FROM product_unit_transfer 
                WHERE product_id = p_id 
                AND unit2 IS NULL 
                AND deleted_at IS NULL;
            ELSE
                SELECT COALESCE(MAX(id), -1) INTO result 
                FROM product_unit_transfer 
                WHERE modifier_id = p_id 
                AND unit2 IS NULL 
                AND deleted_at IS NULL;
            END IF;
            
            RETURN result;
        END");

        DB::statement("DROP FUNCTION IF EXISTS convert_quantity");
        DB::statement("CREATE FUNCTION `convert_quantity`(p_type char(1), p_id INT, from_id INT, to_id INT, quantity DECIMAL(10,4)) RETURNS decimal(10,2)
        DETERMINISTIC
        BEGIN
            DECLARE main_unit_id INT;
            DECLARE result_quantity DECIMAL(10,4);
            DECLARE conversion_factor DECIMAL(10,4);
            IF from_id IS NULL THEN
                RETURN quantity;
            END IF;
            IF to_id IS NULL THEN
                SET to_id = get_main_unit(p_type, p_id);
            END IF;
            
            IF to_id = -1 OR from_id = to_id THEN
                RETURN quantity;
            END IF;
            WITH RECURSIVE unit_path AS (
                SELECT 
                    ut.unit2, 
                    ut.transfer as factor,
                    1 as depth
                FROM product_unit_transfer ut
                WHERE ut.id = from_id
                AND ut.deleted_at IS NULL
                
                UNION ALL

                SELECT 
                    ut.unit2,
                    up.factor * ut.transfer,
                    up.depth + 1
                FROM product_unit_transfer ut
                INNER JOIN unit_path up ON ut.id = up.unit2
                WHERE ut.unit2 IS NOT NULL
                AND ut.deleted_at IS NULL
            )
            SELECT factor INTO conversion_factor
            FROM unit_path
            WHERE unit2 = to_id OR unit2 IS NULL
            ORDER BY depth DESC
            LIMIT 1;
            
            IF conversion_factor IS NULL THEN
                SET result_quantity = quantity;
            ELSE
                SET result_quantity = quantity * conversion_factor;
            END IF;
            
            RETURN result_quantity;
        END");

        DB::statement("CREATE OR REPLACE VIEW product_inventories AS
        SELECT 
            pp.id AS product_id,
            establishment_id,
            get_main_unit('P', pp.id) as unit_id, 
            COALESCE(SUM(op.qty), 0) as qty
        FROM product_products pp
        LEFT JOIN (
            SELECT 
                -1 * SUM(convert_quantity('P', ioi.product_id, ioi.unit_id, null, ioi.qyt)) AS qty, 
                ioi.product_id AS product_id,
                io1.establishment_id
            FROM transaction_sell_lines ioi 
            INNER JOIN transactions io1 ON io1.id = ioi.transaction_id
            WHERE io1.status ='approved'
            GROUP BY ioi.product_id, io1.establishment_id
            
            UNION ALL 
            
            SELECT
                SUM(convert_quantity('P', ioi.product_id, ioi.unit_id, null, ioi.qyt)) AS qty,
                ioi.product_id AS product_id,
                io1.establishment_id
            FROM transaction_purchases_lines ioi
            INNER JOIN transactions io1 ON io1.id = ioi.transaction_id
            WHERE io1.status ='approved'
            GROUP BY ioi.product_id, io1.establishment_id
        ) op ON op.product_id = pp.id
        GROUP BY pp.id, establishment_id");

        DB::statement("CREATE OR REPLACE VIEW modifier_inventories AS
        SELECT 
            pp.id AS modifier_id,
            establishment_id,
            get_main_unit('M', pp.id) as unit_id, 
            COALESCE(SUM(op.qty), 0) as qty
        FROM product_modifiers pp
        LEFT JOIN (
            SELECT 
                -1 * SUM(convert_quantity('M', ioi.modifier_id, ioi.unit_id, null, ioi.qyt)) AS qty, 
                ioi.modifier_id AS modifier_id,
                io1.establishment_id
            FROM transaction_sell_lines ioi 
            INNER JOIN transactions io1 ON io1.id = ioi.transaction_id
            WHERE io1.status ='approved'
            GROUP BY ioi.modifier_id, io1.establishment_id
            
            UNION ALL 
            
            SELECT
                SUM(convert_quantity('M', ioi.modifier_id, ioi.unit_id, null, ioi.qyt)) AS qty,
                ioi.modifier_id AS modifier_id,
                io1.establishment_id
            FROM transaction_purchases_lines ioi
            INNER JOIN transactions io1 ON io1.id = ioi.transaction_id
            WHERE io1.status ='approved'
            GROUP BY ioi.modifier_id, io1.establishment_id
        ) op ON op.modifier_id = pp.id
        GROUP BY pp.id, establishment_id");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};

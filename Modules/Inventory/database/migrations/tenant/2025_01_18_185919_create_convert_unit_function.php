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
        DB::statement("CREATE FUNCTION get_main_unit
                (p_type char(1), 
                p_id INT) 
                RETURNS int
                DETERMINISTIC
                BEGIN
                    DECLARE result INT;
                    IF p_type ='P' THEN
                        select 
                            id into result 
                        from 
                            product_unit_transfer ut 
                        where ut.product_id = p_id and ut.unit2 is null;
                    ELSE
                        select 
                            id into result 
                        from 
                            product_unit_transfer ut 
                        where ut.modifier_id = p_id and ut.unit2 is null;
                    END IF;
                    RETURN result;
                END;");
        DB::statement("DROP FUNCTION IF EXISTS convert_quantity");
        DB::statement("CREATE FUNCTION convert_quantity
                    (p_type char(1), 
                    p_id INT, 
                    from_id INT, 
                    to_id INT, 
                    quantity DECIMAL(10,4)) 
                    RETURNS decimal(10,2)
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
                        WHERE product_id = product_id AND id = from_id

                        UNION ALL

                        SELECT ut.unit2, up.transfer_factor * ut.transfer, depth + 1
                        FROM product_unit_transfer ut
                        JOIN unit_path up ON ut.id = up.unit_id
                        WHERE ut.product_id = product_id AND ut.unit2 IS NOT NULL
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
        

        DB::statement("CREATE OR REPLACE VIEW product_inventories AS
        SELECT 
        pp.id AS product_id,
        establishment_id,
        SUM(qty) qty
    	FROM
        product_products pp
        LEFT JOIN (SELECT 
					-1 * SUM(convert_quantity('P', ioi.product_id, ioi.unit_id, null, ioi.qyt)) AS qty, 
                    ioi.product_id AS product_id,
                    io1.establishment_id
					FROM 
					transaction_sell_lines ioi INNER JOIN 
					transactions io1 ON io1.id = ioi.transaction_id
					WHERE io1.type = 'rma' or io1.type ='sell' or io1.type = 'WASTE'
					GROUP BY ioi.product_id, io1.establishment_id
                    UNION ALL SELECT
					SUM(convert_quantity('P', ioi.product_id, ioi.unit_id, null, ioi.qyt)) AS qty,
					ioi.product_id AS product_id,
                    io1.establishment_id
					FROM
					transaction_sell_lines ioi
					INNER JOIN transactions io1 ON io1.id = ioi.transaction_id
					WHERE io1.type = 'purchases-order' or io1.type ='PO0'
					GROUP BY ioi.product_id, io1.establishment_id
                    UNION ALL SELECT
					-1 * SUM(convert_quantity('P', ioi.product_id, ioi.unit_id, null, ioi.qyt)) * SUM(iop.times) AS qty,
					ioi.product_id AS product_id,
                    io1.establishment_id
					FROM
					transaction_sell_lines ioi
					INNER JOIN transactions io1 ON io1.id = ioi.transaction_id
					INNER JOIN inventory_Op_preps iop ON iop.operation_id = io1.id
					WHERE io1.type = 'PREP'
					GROUP BY ioi.product_id, io1.establishment_id
                    UNION ALL SELECT
					SUM(iop.times) AS qty,
					iop.product_id AS product_id,
                    io1.establishment_id
					FROM
					inventory_Op_preps iop /*prep in*/
                    INNER JOIN transactions io1 ON io1.id = iop.operation_id
					GROUP BY iop.product_id, io1.establishment_id
                    UNION ALL SELECT
					-1 * SUM(convert_quantity('P', ioi.product_id, ioi.unit_id, null, ioi.qyt)) AS qty,
					ioi.product_id AS product_id,
                    io1.establishment_id
					FROM
					transaction_sell_lines ioi
					INNER JOIN transactions io1 ON io1.id = ioi.transaction_id
					WHERE io1.type = 'transfer'
					GROUP BY ioi.product_id, io1.establishment_id
                    -- UNION ALL SELECT
-- 					SUM(ioi.qyt) AS qty,
-- 					ioi.product_id AS product_id,
--                     iot.establishment_id
-- 					FROM
-- 					transaction_sell_lines ioi
-- 					INNER JOIN transactions io1 ON io1.id = ioi.transaction_id
-- 					WHERE io1.type = 'transfer'
-- 					GROUP BY ioi.product_id, iot.establishment_id
                    ) op ON op.product_id = pp.id
        group by pp.id, establishment_id"
        );


        DB::statement("CREATE OR REPLACE VIEW modifier_inventories AS
    	SELECT 
        pii.id AS modifier_id,
        establishment_id,
        SUM(qty) qty
    	FROM
        product_modifiers pii
        LEFT JOIN (SELECT 
					-1 * SUM(convert_quantity('M', ioi.modifier_id, ioi.unit_id, null, ioi.qyt)) AS qty, 
                    ioi.modifier_id AS modifier_id,
                    io1.establishment_id
					FROM 
					transaction_sell_lines ioi INNER JOIN 
					transactions io1 ON io1.id = ioi.transaction_id
					WHERE io1.type = 'rma' or io1.type ='sell' or io1.type = 'WASTE'
					GROUP BY ioi.modifier_id, io1.establishment_id
                    UNION ALL SELECT
					SUM(convert_quantity('M', ioi.modifier_id, ioi.unit_id, null, ioi.qyt)) AS qty,
					ioi.modifier_id AS modifier_id,
                    io1.establishment_id
					FROM
					transaction_sell_lines ioi
					INNER JOIN transactions io1 ON io1.id = ioi.transaction_id
					WHERE io1.type = 'purchases-order' or io1.type ='PO0'
					GROUP BY ioi.modifier_id, io1.establishment_id
                    UNION ALL SELECT
					-1 * SUM(convert_quantity('M', ioi.modifier_id, ioi.unit_id, null, ioi.qyt)) * SUM(iop.times) AS qty,
					ioi.modifier_id AS modifier_id,
                    io1.establishment_id
					FROM
					transaction_sell_lines ioi
					INNER JOIN transactions io1 ON io1.id = ioi.transaction_id
					INNER JOIN inventory_Op_preps iop ON iop.operation_id = io1.id
					WHERE io1.type = 'PREP'
					GROUP BY ioi.modifier_id, io1.establishment_id
                    UNION ALL SELECT
					SUM(iop.times) AS qty,
					iop.modifier_id AS modifier_id,
                    io1.establishment_id
					FROM
					inventory_Op_preps iop /*prep in*/
                    INNER JOIN transactions io1 ON io1.id = iop.operation_id
					GROUP BY iop.modifier_id, io1.establishment_id
                    UNION ALL SELECT
					-1 * SUM(convert_quantity('M', ioi.modifier_id, ioi.unit_id, null, ioi.qyt)) AS qty,
					ioi.modifier_id AS modifier_id,
                    io1.establishment_id
					FROM
					transaction_sell_lines ioi
					INNER JOIN transactions io1 ON io1.id = ioi.transaction_id
					WHERE io1.type = 'transfer'
					GROUP BY ioi.modifier_id, io1.establishment_id
                    -- UNION ALL SELECT
-- 					SUM(ioi.qyt) AS qty,
-- 					ioi.modifier_id AS modifier_id,
--                     iot.establishment_id
-- 					FROM
-- 					transaction_sell_lines ioi
-- 					INNER JOIN transactions io1 ON io1.id = ioi.transaction_id
-- 					WHERE io1.type = 'transfer'
-- 					GROUP BY ioi.modifier_id, iot.establishment_id
                    ) op ON op.modifier_id = pii.id
        group by pii.id, establishment_id");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("CREATE OR REPLACE VIEW product_inventories AS
    	SELECT 
        pp.id AS product_id,
        establishment_id,
        SUM(qty) qty
    	FROM
        product_products pp
        LEFT JOIN (SELECT 
					-1 * SUM(ioi.qyt) AS qty, 
                    ioi.product_id AS product_id,
                    io1.establishment_id
					FROM 
					transaction_sell_lines ioi INNER JOIN 
					transactions io1 ON io1.id = ioi.transaction_id
					WHERE io1.type = 'rma'
					GROUP BY ioi.product_id, io1.establishment_id
                    UNION ALL SELECT
					SUM(ioi.qyt) AS qty,
					ioi.product_id AS product_id,
                    io1.establishment_id
					FROM
					transaction_sell_lines ioi
					INNER JOIN transactions io1 ON io1.id = ioi.transaction_id
					WHERE io1.type = 'purchases-order' or io1.type ='PO0'
					GROUP BY ioi.product_id, io1.establishment_id
                    UNION ALL SELECT
					-1 * SUM(ioi.qyt) AS qty,
					ioi.product_id AS product_id,
                    io1.establishment_id
					FROM
					transaction_sell_lines ioi
					INNER JOIN transactions io1 ON io1.id = ioi.transaction_id
					WHERE io1.type = 'WASTE'
					GROUP BY ioi.product_id, io1.establishment_id
                    UNION ALL SELECT
					-1 * SUM(ioi.qyt) * SUM(iop.times) AS qty,
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
					-1 * SUM(ioi.qyt) AS qty,
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
        group by pp.id, establishment_id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

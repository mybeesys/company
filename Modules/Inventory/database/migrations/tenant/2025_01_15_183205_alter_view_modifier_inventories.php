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
        DB::statement("CREATE OR REPLACE VIEW modifier_inventories AS
    	SELECT 
        pii.id AS modifier_id,
        establishment_id,
        SUM(qty) qty
    	FROM
        product_modifiers pii
        LEFT JOIN (SELECT 
					-1 * SUM(ioi.qyt) AS qty, 
                    ioi.modifier_id AS modifier_id,
                    io1.establishment_id
					FROM 
					transaction_sell_lines ioi INNER JOIN 
					transactions io1 ON io1.id = ioi.transaction_id
					WHERE io1.type = 'rma'
					GROUP BY ioi.modifier_id, io1.establishment_id
                    UNION ALL SELECT
					SUM(ioi.qyt) AS qty,
					ioi.modifier_id AS modifier_id,
                    io1.establishment_id
					FROM
					transaction_sell_lines ioi
					INNER JOIN transactions io1 ON io1.id = ioi.transaction_id
					WHERE io1.type = 'purchases-order' or io1.type ='PO0'
					GROUP BY ioi.modifier_id, io1.establishment_id
                    UNION ALL SELECT
					-1 * SUM(ioi.qyt) AS qty,
					ioi.modifier_id AS modifier_id,
                    io1.establishment_id
					FROM
					transaction_sell_lines ioi
					INNER JOIN transactions io1 ON io1.id = ioi.transaction_id
					WHERE io1.type = 'WASTE'
					GROUP BY ioi.modifier_id, io1.establishment_id
                    UNION ALL SELECT
					-1 * SUM(ioi.qyt) * SUM(iop.times) AS qty,
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
					-1 * SUM(ioi.qyt) AS qty,
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
        //
    }
};

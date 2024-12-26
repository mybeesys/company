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
					-1 * SUM(ioi.qty) AS qty, 
                    ioi.product_id AS product_id,
                    io1.establishment_id
					FROM 
					inventory_Operation_items ioi INNER JOIN 
					inventory_Operations io1 ON io1.id = ioi.operation_id
					WHERE io1.op_type = 2 /*rma*/
					GROUP BY ioi.product_id, io1.establishment_id
                    UNION ALL SELECT
					SUM(iopi.recievd_qty) AS qty,
					ioi.product_id AS product_id,
                    io1.establishment_id
					FROM
					inventory_Operation_items ioi
					INNER JOIN inventory_Op_purchaseOrder_items iopi ON iopi.operation_item_id = ioi.id
					INNER JOIN inventory_Operations io1 ON io1.id = ioi.operation_id
					WHERE io1.op_type = 0 /*po*/
					GROUP BY ioi.product_id, io1.establishment_id
                    UNION ALL SELECT
					-1 * SUM(ioi.qty) AS qty,
					ioi.product_id AS product_id,
                    io1.establishment_id
					FROM
					inventory_Operation_items ioi
					INNER JOIN inventory_Operations io1 ON io1.id = ioi.operation_id
					WHERE io1.op_type = 3 /*waste*/
					GROUP BY ioi.product_id, io1.establishment_id
                    UNION ALL SELECT
					-1 * SUM(ioi.qty) * SUM(iop.times) AS qty,
					ioi.product_id AS product_id,
                    io1.establishment_id
					FROM
					inventory_Operation_items ioi
					INNER JOIN inventory_Operations io1 ON io1.id = ioi.operation_id
					INNER JOIN inventory_Op_preps iop ON iop.operation_id = io1.id
					WHERE io1.op_type = 1 /*prep out*/
					GROUP BY ioi.product_id, io1.establishment_id
                    UNION ALL SELECT
					SUM(iop.times) AS qty,
					iop.product_id AS product_id,
                    io1.establishment_id
					FROM
					inventory_Op_preps iop /*prep in*/
                    INNER JOIN inventory_Operations io1 ON io1.id = iop.operation_id
					GROUP BY iop.product_id, io1.establishment_id
                    UNION ALL SELECT
					-1 * SUM(ioi.qty) AS qty,
					ioi.product_id AS product_id,
                    io1.establishment_id
					FROM
					inventory_Operation_items ioi
					INNER JOIN inventory_Operations io1 ON io1.id = ioi.operation_id
					INNER JOIN inventory_op_transfer iot ON iot.operation_id = io1.id
					WHERE io1.op_type = 4 /*transfer out*/
					GROUP BY ioi.product_id, io1.establishment_id
                    UNION ALL SELECT
					SUM(ioi.qty) AS qty,
					ioi.product_id AS product_id,
                    iot.establishment_id
					FROM
					inventory_Operation_items ioi
					INNER JOIN inventory_Operations io1 ON io1.id = ioi.operation_id
					INNER JOIN inventory_op_transfer iot ON iot.operation_id = io1.id
					WHERE io1.op_type = 4 /*transfer in*/
					GROUP BY ioi.product_id, iot.establishment_id
                    ) op ON op.product_id = pp.id
        group by pp.id, establishment_id");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('');
    }
};

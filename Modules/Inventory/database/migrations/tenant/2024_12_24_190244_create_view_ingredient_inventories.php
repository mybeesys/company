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
        DB::statement("CREATE OR REPLACE VIEW ingredient_inventories AS
    	SELECT 
        pii.id AS ingredient_id,
        establishment_id,
        SUM(qty) qty
    	FROM
        product_ingredients pii
        LEFT JOIN (SELECT 
					-1 * SUM(ioi.qty) AS qty, 
                    ioi.ingredient_id AS ingredient_id,
                    io1.establishment_id
					FROM 
					inventory_Operation_items ioi INNER JOIN 
					inventory_Operations io1 ON io1.id = ioi.operation_id
					WHERE io1.op_type = 2 /*rma*/
					GROUP BY ioi.ingredient_id, io1.establishment_id
                    UNION ALL SELECT
					SUM(iopi.recievd_qty) AS qty,
					ioi.ingredient_id AS ingredient_id,
                    io1.establishment_id
					FROM
					inventory_Operation_items ioi
					INNER JOIN inventory_Op_purchaseOrder_items iopi ON iopi.operation_item_id = ioi.id
					INNER JOIN inventory_Operations io1 ON io1.id = ioi.operation_id
					WHERE io1.op_type = 0 /*po*/
					GROUP BY ioi.ingredient_id, io1.establishment_id
                    UNION ALL SELECT
					-1 * SUM(ioi.qty) AS qty,
					ioi.ingredient_id AS ingredient_id,
                    io1.establishment_id
					FROM
					inventory_Operation_items ioi
					INNER JOIN inventory_Operations io1 ON io1.id = ioi.operation_id
					WHERE io1.op_type = 3 /*waste*/
					GROUP BY ioi.ingredient_id, io1.establishment_id
                    UNION ALL SELECT
					-1 * SUM(ioi.qty) * SUM(iop.times) AS qty,
					ioi.ingredient_id AS ingredient_id,
                    io1.establishment_id
					FROM
					inventory_Operation_items ioi
					INNER JOIN inventory_Operations io1 ON io1.id = ioi.operation_id
					INNER JOIN inventory_Op_preps iop ON iop.operation_id = io1.id
					WHERE io1.op_type = 1 /*prep out*/
					GROUP BY ioi.ingredient_id, io1.establishment_id
                    UNION ALL SELECT
					-1 * SUM(ioi.qty) AS qty,
					ioi.ingredient_id AS ingredient_id,
                    io1.establishment_id
					FROM
					inventory_Operation_items ioi
					INNER JOIN inventory_Operations io1 ON io1.id = ioi.operation_id
					INNER JOIN inventory_op_transfer iot ON iot.operation_id = io1.id
					WHERE io1.op_type = 4 /*transfer out*/
					GROUP BY ioi.ingredient_id, io1.establishment_id
                    UNION ALL SELECT
					SUM(ioi.qty) AS qty,
					ioi.ingredient_id AS ingredient_id,
                    iot.establishment_id
					FROM
					inventory_Operation_items ioi
					INNER JOIN inventory_Operations io1 ON io1.id = ioi.operation_id
					INNER JOIN inventory_op_transfer iot ON iot.operation_id = io1.id
					WHERE io1.op_type = 4 /*transfer in*/
					GROUP BY ioi.ingredient_id, iot.establishment_id
                    ) op ON op.ingredient_id = pii.id
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

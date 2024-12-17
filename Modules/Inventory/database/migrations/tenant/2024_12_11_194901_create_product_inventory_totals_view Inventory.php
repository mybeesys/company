<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::statement("CREATE 
VIEW product_inventory_totals AS
    SELECT 
        pp.id AS id,
        ((((COALESCE(po.qty, 0) - COALESCE(rma.qty, 0)) - COALESCE(waste.qty, 0)) - COALESCE(prep.qty, 0)) + COALESCE(prep1.qty, 0)) AS qty
    FROM
        (((((product_products pp
        LEFT JOIN (SELECT 
            SUM(ioi.qty) AS qty,
                ioi.product_id AS product_id
        FROM
            (inventory_operation_items ioi
        JOIN inventory_operations io1 ON ((io1.id = ioi.operation_id)))
        WHERE
            (io1.op_type = 2)
        GROUP BY ioi.product_id) rma ON ((rma.product_id = pp.id)))
        LEFT JOIN (SELECT 
            SUM(iopi.recievd_qty) AS qty,
                ioi.product_id AS product_id
        FROM
            ((inventory_operation_items ioi
        JOIN inventory_op_purchaseorder_items iopi ON ((iopi.operation_item_id = ioi.id)))
        JOIN inventory_operations io1 ON ((io1.id = ioi.operation_id)))
        WHERE
            (io1.op_type = 0)
        GROUP BY ioi.product_id) po ON ((po.product_id = pp.id)))
        LEFT JOIN (SELECT 
            SUM(ioi.qty) AS qty,
                ioi.product_id AS product_id
        FROM
            (inventory_operation_items ioi
        JOIN inventory_operations io1 ON ((io1.id = ioi.operation_id)))
        WHERE
            (io1.op_type = 3)
        GROUP BY ioi.product_id) waste ON ((waste.product_id = pp.id)))
        LEFT JOIN (SELECT 
            (SUM(ioi.qty) * SUM(iop.times)) AS qty,
                ioi.product_id AS product_id
        FROM
            ((inventory_operation_items ioi
        JOIN inventory_operations io1 ON ((io1.id = ioi.operation_id)))
        JOIN inventory_op_preps iop ON ((iop.operation_id = io1.id)))
        WHERE
            (io1.op_type = 1)
        GROUP BY ioi.product_id) prep ON ((prep.product_id = pp.id)))
        LEFT JOIN (SELECT 
            iop.product_id AS product_id,
                SUM(iop.times) AS qty
        FROM
            inventory_op_preps iop
        GROUP BY iop.product_id) prep1 ON ((prep1.product_id = pp.id)))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::statement("DROP VIEW product_inventory_totals");
    }
};

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
        DB::statement("CREATE OR REPLACE VIEW product_inventories AS
        SELECT 
        pp.id AS product_id,
        establishment_id,
        (select id from product_unit_transfer put where put.product_id = pp.id and not (put.unit2 is not null or put.deleted_at is not null)) unit_id, 
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
					WHERE io1.status ='approved'
					GROUP BY ioi.product_id, io1.establishment_id
                    UNION ALL SELECT
					SUM(convert_quantity('P', ioi.product_id, ioi.unit_id, null, ioi.qyt)) AS qty,
					ioi.product_id AS product_id,
                    io1.establishment_id
					FROM
					transactione_purchases_lines ioi
					INNER JOIN transactions io1 ON io1.id = ioi.transaction_id
					WHERE io1.status ='approved'
					GROUP BY ioi.product_id, io1.establishment_id
                    ) op ON op.product_id = pp.id
        group by pp.id, establishment_id");
        DB::statement("CREATE OR REPLACE VIEW modifier_inventories AS
        SELECT 
        pp.id AS modifier_id,
        establishment_id,
        (select id from product_unit_transfer put where put.modifier_id = pp.id and not (put.unit2 is not null or put.deleted_at is not null)) unit_id, 
        SUM(qty) qty
    	FROM
        product_modifiers pp
        LEFT JOIN (SELECT 
					-1 * SUM(convert_quantity('P', ioi.modifier_id, ioi.unit_id, null, ioi.qyt)) AS qty, 
                    ioi.modifier_id AS modifier_id,
                    io1.establishment_id
					FROM 
					transaction_sell_lines ioi INNER JOIN 
					transactions io1 ON io1.id = ioi.transaction_id
					WHERE io1.status ='approved'
					GROUP BY ioi.modifier_id, io1.establishment_id
                    UNION ALL SELECT
					SUM(convert_quantity('P', ioi.modifier_id, ioi.unit_id, null, ioi.qyt)) AS qty,
					ioi.modifier_id AS modifier_id,
                    io1.establishment_id
					FROM
					transactione_purchases_lines ioi
					INNER JOIN transactions io1 ON io1.id = ioi.transaction_id
					WHERE io1.status ='approved'
					GROUP BY ioi.modifier_id, io1.establishment_id
                    ) op ON op.modifier_id = pp.id
        group by pp.id, establishment_id");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

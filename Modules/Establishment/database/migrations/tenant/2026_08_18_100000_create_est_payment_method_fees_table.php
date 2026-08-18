<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('est_payment_method_fees')) {
            return;
        }

        Schema::create('est_payment_method_fees', function (Blueprint $table) {
            $table->id();

            // طريقة الدفع المرتبطة
            $table->unsignedBigInteger('payment_method_id');

            $table->string('name_ar')->default('');
            $table->string('name_en')->default('');

            // '0' = مبلغ ثابت  |  '1' = نسبة مئوية
            $table->string('fee_type', 2)->default('0');

            // القيمة (مبلغ أو نسبة)
            $table->decimal('amount', 15, 4)->default(0);

            // '0' = على كل منتج  |  '1' = على إجمالي الفاتورة
            $table->string('application_type', 2)->default('1');

            $table->boolean('is_active')->default(true);

            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->foreign('payment_method_id', 'est_pmf_method_fk')
                ->references('id')
                ->on('est_establishment_payment_accounts')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('est_payment_method_fees');
    }
};

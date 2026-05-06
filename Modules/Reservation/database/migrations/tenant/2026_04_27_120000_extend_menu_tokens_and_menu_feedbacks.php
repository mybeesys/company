<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_tokens', function (Blueprint $table) {
            if (! Schema::hasColumn('menu_tokens', 'est_ids')) {
                $table->json('est_ids')->nullable()->after('est_id');
            }
            if (! Schema::hasColumn('menu_tokens', 'custom_menu_id')) {
                $table->unsignedBigInteger('custom_menu_id')->nullable()->after('products');
            }
            if (! Schema::hasColumn('menu_tokens', 'map_lat')) {
                $table->decimal('map_lat', 10, 7)->nullable()->after('custom_menu_id');
            }
            if (! Schema::hasColumn('menu_tokens', 'map_lng')) {
                $table->decimal('map_lng', 10, 7)->nullable()->after('map_lat');
            }
            if (! Schema::hasColumn('menu_tokens', 'map_label')) {
                $table->string('map_label')->nullable()->after('map_lng');
            }
            if (! Schema::hasColumn('menu_tokens', 'allergy_document_path')) {
                $table->string('allergy_document_path')->nullable()->after('map_label');
            }
            if (! Schema::hasColumn('menu_tokens', 'section_flags')) {
                $table->json('section_flags')->nullable()->after('allergy_document_path');
            }
        });

        if (! Schema::hasTable('menu_feedbacks')) {
            Schema::create('menu_feedbacks', function (Blueprint $table) {
                $table->id();
                $table->string('token', 64)->index();
                $table->unsignedTinyInteger('stars');
                $table->text('comment')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_feedbacks');

        Schema::table('menu_tokens', function (Blueprint $table) {
            foreach (['section_flags', 'allergy_document_path', 'map_label', 'map_lng', 'map_lat', 'custom_menu_id', 'est_ids'] as $col) {
                if (Schema::hasColumn('menu_tokens', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateProductTypesTable extends Migration
{
    public function up()
    {
        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('status')->default(1);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            // `after('type')` only works where the legacy `type` column exists
            // (production); a fresh DB built from migrations never had it, so
            // fall back to appending the column (position is cosmetic).
            if (Schema::hasColumn('products', 'type')) {
                $table->unsignedBigInteger('type_id')->nullable()->after('type');
            } else {
                $table->unsignedBigInteger('type_id')->nullable();
            }
        });

        // Seed the one type that already exists in practice (every product
        // currently has the legacy `type` column set to 0 = "Noodle"), and
        // point all existing products at it so nothing goes uncategorised.
        $noodleId = DB::table('product_types')->insertGetId([
            'name' => 'Noodle',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('products')->update(['type_id' => $noodleId]);
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('type_id');
        });
        Schema::dropIfExists('product_types');
    }
}

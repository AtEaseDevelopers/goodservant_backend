<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobileErrorLogsTable extends Migration
{
    public function up()
    {
        Schema::create('mobile_error_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('app_version')->nullable();
            $table->string('screen')->nullable();
            $table->text('message')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->timestamps();

            $table->index('driver_id');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mobile_error_logs');
    }
}

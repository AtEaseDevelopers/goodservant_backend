<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentAttachmentsTable extends Migration
{
    public function up()
    {
        Schema::create('payment_attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->string('file_path');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_attachments');
    }
}

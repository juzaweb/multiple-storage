<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create(
            'multi_storage_storages',
            function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('type', 50);
                $table->json('configs')->nullable();
                $table->bigInteger('total_size')->default(0)->comment('KB');
                $table->bigInteger('used_size')->default(0)->comment('KB');
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->index('created_at');
                $table->index('updated_at');

                $table->integer('free_size')
                    ->storedAs('total_size - used_size');
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('multi_storage_storages');
    }
};

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
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('user_id')->nullable();

            $table->string('name');
            $table->string('slug')->unique();

            $table->text('description')->nullable();

            $table->string('image')->nullable();

            $table->boolean('is_active')->default(true);

            $table->uuid('parent_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
    |--------------------------------------------------------------------------
    | Foreign Keys
    |--------------------------------------------------------------------------
    */

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('parent_id')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();

            /*
    |--------------------------------------------------------------------------
    | Indexes
    |--------------------------------------------------------------------------
    */

            $table->index('parent_id');
            $table->index('is_active');
            $table->index('user_id');

            /*
    |--------------------------------------------------------------------------
    | Constraints
    |--------------------------------------------------------------------------
    */

            $table->unique([
                'name',
                'parent_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};

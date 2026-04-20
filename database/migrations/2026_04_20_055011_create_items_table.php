<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workspace_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('collection_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('slug')->nullable();

            $table->text('description')->nullable();

            $table->string('status')->default('active');
            $table->string('condition')->nullable();

            $table->decimal('purchase_price', 10)->nullable();
            $table->decimal('estimated_value', 10)->nullable();

            $table->date('acquired_at')->nullable();

            $table->string('location')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('workspace_id');
            $table->index('collection_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};

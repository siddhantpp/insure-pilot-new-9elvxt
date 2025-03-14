<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProducersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the producers table in the database.
     * This table stores information about insurance agents or brokers
     * who sell policies and are associated with documents.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('producers', function (Blueprint $table) {
            // Primary key
            $table->bigIncrements('id');
            
            // Foreign keys and fields
            $table->unsignedBigInteger('producer_code_id');
            $table->string('number');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('producer_type_id');
            $table->boolean('signature_required')->default(false);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Unique constraints
            $table->unique('number');
            
            // Foreign key constraints
            $table->foreign('producer_code_id')->references('id')->on('producer_codes');
            $table->foreign('status_id')->references('id')->on('statuses');
            $table->foreign('producer_type_id')->references('id')->on('producer_types');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            
            // Indexes for frequently queried fields
            $table->index('status_id');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the producers table from the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('producers');
    }
}
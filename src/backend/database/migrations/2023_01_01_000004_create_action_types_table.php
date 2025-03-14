<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActionTypesTable extends Migration
{
    /**
     * Run the migrations - creates the action_types table.
     *
     * This table stores categories of actions that can be performed on documents,
     * supporting the Document History & Audit Trail feature.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('action_types', function (Blueprint $table) {
            // Define primary key
            $table->bigIncrements('id');
            
            // Action type information
            $table->string('name', 50)->comment('Action type name (e.g., "view", "edit", "process", "trash")');
            $table->text('description')->nullable()->comment('Detailed description of this action type');
            
            // Relationship and tracking columns
            $table->unsignedBigInteger('status_id')->comment('Foreign key to statuses table');
            $table->unsignedBigInteger('created_by')->comment('User ID who created this record');
            $table->unsignedBigInteger('updated_by')->comment('User ID who last updated this record');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('status_id')->references('id')->on('statuses');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            
            // Indexes for performance
            $table->unique('name');
            $table->index('status_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations - drops the action_types table.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('action_types');
    }
}
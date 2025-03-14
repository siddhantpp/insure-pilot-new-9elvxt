<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to create the actions table for tracking document operations
 * in the Insure Pilot application. This table is a central component of the
 * Document History & Audit Trail feature (F-004).
 */
return new class extends Migration
{
    /**
     * Run the migrations - creates the actions table.
     *
     * This table stores records of all operations performed on documents,
     * enabling comprehensive tracking of document-related activities
     * with full user attribution.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('actions', function (Blueprint $table) {
            // Primary key
            $table->bigIncrements('id');
            
            // Reference to the associated record (e.g., document, policy)
            $table->bigInteger('record_id')->nullable();
            
            // Action type (e.g., view, edit, process, trash)
            $table->bigInteger('action_type_id')->unsigned();
            
            // Detailed description of the action performed
            $table->text('description');
            
            // Status of this action record
            $table->bigInteger('status_id')->unsigned();
            
            // User attribution - who created and updated this record
            $table->bigInteger('created_by')->unsigned();
            $table->bigInteger('updated_by')->unsigned();
            
            // Timestamps for creation and last update
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('action_type_id')
                  ->references('id')
                  ->on('action_types')
                  ->onDelete('restrict');
                  
            $table->foreign('status_id')
                  ->references('id')
                  ->on('statuses')
                  ->onDelete('restrict');
                  
            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');
                  
            $table->foreign('updated_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');
            
            // Add indexes for frequently queried columns to improve performance
            $table->index('action_type_id');
            $table->index('created_by');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations - drops the actions table.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('actions');
    }
};
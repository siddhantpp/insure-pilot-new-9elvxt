<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMapDocumentActionsTable extends Migration
{
    /**
     * Run the migrations - creates the map_document_actions table.
     *
     * This table serves as a pivot table linking documents to actions,
     * providing a comprehensive audit trail of all document-related activities.
     * Each record represents an action performed on a document with user attribution.
     *
     * @return void
     */
    public function up()
    {
        // Use Schema facade to create the map_document_actions table
        Schema::create('map_document_actions', function (Blueprint $table) {
            // Define id column as auto-incrementing bigInteger primary key
            $table->bigIncrements('id');
            
            // Define document_id column as bigInteger with foreign key to documents table
            $table->unsignedBigInteger('document_id');
            
            // Define action_id column as bigInteger with foreign key to actions table
            $table->unsignedBigInteger('action_id');
            
            // Define description column as text for additional context about the action
            $table->text('description')->nullable();
            
            // Define status_id column as bigInteger with foreign key to statuses table
            $table->unsignedBigInteger('status_id');
            
            // Define created_by column as bigInteger with foreign key to users table
            $table->unsignedBigInteger('created_by');
            
            // Define updated_by column as bigInteger with foreign key to users table
            $table->unsignedBigInteger('updated_by')->nullable();
            
            // Define created_at and updated_at timestamp columns for tracking record changes
            $table->timestamps();
            
            // Add foreign key constraint on document_id referencing id on documents table
            $table->foreign('document_id')->references('id')->on('documents');
            
            // Add foreign key constraint on action_id referencing id on actions table
            $table->foreign('action_id')->references('id')->on('actions');
            
            // Add foreign key constraint on status_id referencing id on statuses table
            $table->foreign('status_id')->references('id')->on('statuses');
            
            // Add foreign key constraint on created_by referencing id on users table
            $table->foreign('created_by')->references('id')->on('users');
            
            // Add foreign key constraint on updated_by referencing id on users table
            $table->foreign('updated_by')->references('id')->on('users');
            
            // Add composite index on document_id and action_id for efficient lookups
            $table->index(['document_id', 'action_id']);
            
            // Add index on document_id for filtering actions by document
            $table->index('document_id');
            
            // Add index on action_id for filtering documents by action
            $table->index('action_id');
            
            // Add index on created_at for chronological queries
            $table->index('created_at');
            
            // Add index on created_by for filtering by user
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations - drops the map_document_actions table.
     *
     * @return void
     */
    public function down()
    {
        // Use Schema facade to drop the map_document_actions table if it exists
        Schema::dropIfExists('map_document_actions');
    }
}
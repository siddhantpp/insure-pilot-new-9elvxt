<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration class that creates the map_user_documents pivot table for linking users to documents
 * in the Insure Pilot application.
 * 
 * This table enables the "Assigned To" field functionality in the Documents View interface,
 * allowing documents to be assigned to specific users. It tracks the relationship between
 * users and documents with additional metadata and audit information.
 */
class CreateMapUserDocumentsTable extends Migration
{
    /**
     * Run the migrations - creates the map_user_documents table.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('map_user_documents', function (Blueprint $table) {
            // Primary key
            $table->bigIncrements('id');
            
            // Relationship columns
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('document_id');
            
            // Additional metadata
            $table->text('description')->nullable();
            $table->unsignedBigInteger('status_id');
            
            // Audit columns
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();

            // Add unique constraint to prevent duplicate assignments
            $table->unique(['user_id', 'document_id']);

            // Add foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreign('status_id')->references('id')->on('statuses');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            // Add indexes for frequently queried columns
            $table->index('user_id');
            $table->index('document_id');
            $table->index('created_by');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations - drops the map_user_documents table.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('map_user_documents');
    }
}
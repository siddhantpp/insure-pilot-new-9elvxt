<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration class that creates the map_document_files pivot table for establishing many-to-many 
 * relationships between documents and files in the Insure Pilot application.
 * 
 * This table is essential for the Documents View feature, allowing documents to be associated
 * with one or more files and enabling the display of document content in the lightbox interface.
 */
class CreateMapDocumentFilesTable extends Migration
{
    /**
     * Run the migrations - creates the map_document_files table.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('map_document_files', function (Blueprint $table) {
            // Define id column as auto-incrementing bigInteger primary key
            $table->bigIncrements('id');
            
            // Define document_id column as bigInteger with foreign key to documents table
            $table->unsignedBigInteger('document_id');
            
            // Define file_id column as bigInteger with foreign key to files table
            $table->unsignedBigInteger('file_id');
            
            // Define description column as text for optional mapping description
            $table->text('description')->nullable();
            
            // Define status_id column as bigInteger with foreign key to statuses table
            $table->unsignedBigInteger('status_id');
            
            // Define created_by column as bigInteger with foreign key to users table
            $table->unsignedBigInteger('created_by');
            
            // Define updated_by column as bigInteger with foreign key to users table
            $table->unsignedBigInteger('updated_by');
            
            // Define created_at and updated_at timestamp columns for tracking record changes
            $table->timestamps();
            
            // Add foreign key constraint on document_id referencing id on documents table
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            
            // Add foreign key constraint on file_id referencing id on files table
            $table->foreign('file_id')->references('id')->on('files')->onDelete('cascade');
            
            // Add foreign key constraint on status_id referencing id on statuses table
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('restrict');
            
            // Add foreign key constraint on created_by referencing id on users table
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            
            // Add foreign key constraint on updated_by referencing id on users table
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('restrict');
            
            // Add unique constraint on document_id and file_id combination to prevent duplicate mappings
            $table->unique(['document_id', 'file_id']);
            
            // Add indexes for frequently queried columns (document_id, file_id, created_by)
            $table->index('document_id');
            $table->index('file_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations - drops the map_document_files table.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('map_document_files');
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateFilesTable Migration
 * 
 * This migration creates the files table for storing document file metadata 
 * in the Insure Pilot application. It is a critical component for the Document 
 * Viewing & Interface feature, enabling storage and retrieval of document files.
 * 
 * The table stores metadata (name, path, mime_type, size) with references to 
 * securely stored document files on NFS storage.
 */
class CreateFilesTable extends Migration
{
    /**
     * Run the migrations - creates the files table.
     * 
     * Creates a table for storing document file metadata including:
     * - File information (name, path, mime_type, size)
     * - Reference information (status, created_by, updated_by)
     * - Tracking information (created_at, updated_at, deleted_at)
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            // Define id column as auto-incrementing bigInteger primary key
            $table->bigIncrements('id');
            
            // Define name column as string for file name
            $table->string('name');
            
            // Define path column as string for file storage path
            $table->string('path');
            
            // Define mime_type column as string for file MIME type
            $table->string('mime_type');
            
            // Define size column as bigInteger for file size in bytes
            $table->bigInteger('size');
            
            // Define status_id column as bigInteger with foreign key to statuses table
            $table->unsignedBigInteger('status_id');
            
            // Define created_by column as bigInteger with foreign key to users table
            $table->unsignedBigInteger('created_by');
            
            // Define updated_by column as bigInteger with foreign key to users table
            $table->unsignedBigInteger('updated_by');
            
            // Define created_at and updated_at timestamp columns for tracking record changes
            $table->timestamps();
            
            // Define soft deletes column for trashed files
            $table->softDeletes();
            
            // Add foreign key constraint on status_id referencing id on statuses table
            $table->foreign('status_id')->references('id')->on('statuses');
            
            // Add foreign key constraint on created_by referencing id on users table
            $table->foreign('created_by')->references('id')->on('users');
            
            // Add foreign key constraint on updated_by referencing id on users table
            $table->foreign('updated_by')->references('id')->on('users');
            
            // Add indexes for frequently queried columns (mime_type, created_by, updated_by)
            $table->index('mime_type');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    /**
     * Reverse the migrations - drops the files table.
     * 
     * Removes the files table from the database if it exists.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
}
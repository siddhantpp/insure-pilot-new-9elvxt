<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration class that creates the map_user_group_documents pivot table
 * for linking user groups to documents in the Insure Pilot application.
 */
class CreateMapUserGroupDocumentsTable extends Migration
{
    /**
     * Run the migrations - creates the map_user_group_documents table.
     * 
     * This table links user groups to documents, allowing group-based
     * document assignments via the 'Assigned To' field in the Documents View.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('map_user_group_documents', function (Blueprint $table) {
            // Primary key
            $table->bigIncrements('id');
            
            // Foreign keys
            $table->unsignedBigInteger('user_group_id');
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            
            // Other columns
            $table->text('description')->nullable();
            $table->timestamps();

            // Add unique constraint to prevent duplicate assignments
            $table->unique(['user_group_id', 'document_id']);

            // Add foreign key constraints
            $table->foreign('user_group_id')->references('id')->on('user_groups')->onDelete('cascade');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreign('status_id')->references('id')->on('statuses');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            // Add indexes for frequently queried columns
            $table->index('user_group_id');
            $table->index('document_id');
            $table->index('created_by');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations - drops the map_user_group_documents table.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('map_user_group_documents');
    }
}
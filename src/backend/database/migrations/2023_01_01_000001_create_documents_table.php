<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint; // Laravel Framework ^10.0
use Illuminate\Support\Facades\Schema; // Laravel Framework ^10.0

class CreateDocumentsTable extends Migration
{
    /**
     * Run the migrations - creates the documents table
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Document metadata
            $table->string('name');
            $table->date('date_received');
            $table->text('description')->nullable();
            $table->boolean('signature_required')->default(false);
            
            // Foreign keys - relationships to other entities
            $table->foreignId('policy_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('loss_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('claimant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('producer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('status_id')->constrained();
            
            // Audit trail fields
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            
            // Timestamps for record creation and modification tracking
            $table->timestamps();
            
            // Soft deletes for trashed documents (allows recovery within 90 days)
            $table->softDeletes();
            
            // Additional compound indexes for frequently queried combinations
            $table->index(['status_id', 'created_by']);
            $table->index(['policy_id', 'status_id']);
            $table->index(['loss_id', 'status_id']);
            $table->index(['producer_id', 'status_id']);
        });
    }

    /**
     * Reverse the migrations - drops the documents table
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documents');
    }
}
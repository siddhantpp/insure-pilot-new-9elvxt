<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to create the map_policy_losses table.
 * 
 * This table establishes a many-to-many relationship between policies and losses,
 * which is essential for the Documents View feature's metadata management, particularly
 * for the dynamic dropdown controls where Loss Sequence options are filtered based
 * on the selected Policy Number.
 */
class CreateMapPolicyLossesTable extends Migration
{
    /**
     * Run the migrations to create the map_policy_losses table.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('map_policy_losses', function (Blueprint $table) {
            // Primary key
            $table->bigIncrements('id');
            
            // Foreign keys for the relationship
            $table->unsignedBigInteger('policy_id');
            $table->unsignedBigInteger('loss_id');
            
            // Additional fields
            $table->text('description')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            
            // Timestamps and soft delete
            $table->timestamps();
            $table->softDeletes();
            
            // Add unique constraint to prevent duplicate relationships
            $table->unique(['policy_id', 'loss_id']);
            
            // Add foreign key constraints
            $table->foreign('policy_id')
                  ->references('id')
                  ->on('policies')
                  ->onDelete('cascade');
                  
            $table->foreign('loss_id')
                  ->references('id')
                  ->on('losses')
                  ->onDelete('cascade');
                  
            $table->foreign('status_id')
                  ->references('id')
                  ->on('statuses');
                  
            $table->foreign('created_by')
                  ->references('id')
                  ->on('users');
                  
            $table->foreign('updated_by')
                  ->references('id')
                  ->on('users');
            
            // Add indexes for frequently queried fields
            $table->index('policy_id');
            $table->index('loss_id');
            $table->index('status_id');
        });
    }

    /**
     * Reverse the migrations by dropping the map_policy_losses table.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('map_policy_losses');
    }
}
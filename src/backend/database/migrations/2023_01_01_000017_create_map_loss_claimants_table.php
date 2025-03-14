<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for creating the map_loss_claimants table.
 *
 * This table establishes a many-to-many relationship between losses and claimants
 * in the Insure Pilot system. It's essential for the Documents View feature's metadata
 * management, particularly for the dynamic dropdown controls where Claimant options
 * are filtered based on the selected Loss Sequence.
 */
class CreateMapLossClaimantsTable extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the map_loss_claimants table with appropriate columns, indexes,
     * and foreign key constraints.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('map_loss_claimants', function (Blueprint $table) {
            // Primary key
            $table->bigIncrements('id');
            
            // Foreign keys
            $table->unsignedBigInteger('loss_id');
            $table->unsignedBigInteger('claimant_id');
            
            // Additional fields
            $table->text('description')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            
            // Timestamps and soft delete
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for efficient lookups
            $table->unique(['loss_id', 'claimant_id']);
            $table->index('loss_id');   // For filtering claimants by loss
            $table->index('claimant_id'); // For filtering losses by claimant
            $table->index('status_id');   // For filtering by status
            
            // Foreign key constraints with restrict on delete
            $table->foreign('loss_id')->references('id')->on('losses')->onDelete('restrict');
            $table->foreign('claimant_id')->references('id')->on('claimants')->onDelete('restrict');
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the map_loss_claimants table if it exists.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('map_loss_claimants');
    }
}
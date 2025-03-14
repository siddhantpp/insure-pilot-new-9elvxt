<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClaimantsTable extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the claimants table in the database. This table stores information about
     * individuals or entities making insurance claims and is referenced by documents
     * for metadata management.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('claimants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('name_id');
            $table->unsignedBigInteger('policy_id')->nullable();
            $table->unsignedBigInteger('loss_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('claimant_type_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for frequently queried fields
            $table->index('name_id');
            $table->index('policy_id');
            $table->index('loss_id');
            $table->index('status_id');
            $table->index('claimant_type_id');
            
            // Foreign key constraints
            $table->foreign('name_id')->references('id')->on('names');
            $table->foreign('policy_id')->references('id')->on('policies');
            $table->foreign('loss_id')->references('id')->on('losses');
            $table->foreign('status_id')->references('id')->on('statuses');
            $table->foreign('claimant_type_id')->references('id')->on('claimant_types');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the claimants table from the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('claimants');
    }
}
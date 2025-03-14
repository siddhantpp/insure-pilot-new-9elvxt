<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for creating the losses table.
 * 
 * This table stores information about insurance loss events and is referenced
 * by documents for metadata management in the Documents View feature. It supports
 * the dynamic dropdown controls where Loss Sequence options are filtered based on
 * the selected Policy Number.
 */
class CreateLossesTable extends Migration
{
    /**
     * Creates the losses table in the database.
     * 
     * This table is a critical component for the Documents View feature, providing
     * loss data for document metadata management.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('losses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->date('date');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('loss_type_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for frequently queried fields
            $table->index('date');
            $table->index('status_id');
            $table->index('loss_type_id');

            // Foreign key constraints
            $table->foreign('status_id')->references('id')->on('statuses');
            $table->foreign('loss_type_id')->references('id')->on('loss_types');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    /**
     * Drops the losses table from the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('losses');
    }
}
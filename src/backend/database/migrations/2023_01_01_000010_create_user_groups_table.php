<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserGroupsTable extends Migration
{
    /**
     * Run the migrations - creates the user_groups table.
     *
     * This migration creates the user_groups table which stores information about
     * user groups in the Insure Pilot application. User groups are collections of users
     * that can be assigned to documents for organizational and permission management purposes.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_groups', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Core fields
            $table->string('name');
            $table->text('description')->nullable();
            
            // Relationship fields
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            
            // Timestamps
            $table->timestamps();
            
            // Constraints
            $table->unique('name');
            
            // Foreign keys
            $table->foreign('status_id')->references('id')->on('statuses');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            
            // Indexes for frequently queried columns
            $table->index('status_id');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    /**
     * Reverse the migrations - drops the user_groups table.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_groups');
    }
}
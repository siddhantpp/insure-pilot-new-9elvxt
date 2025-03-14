<?php

use Illuminate\Database\Migrations\Migration; // Laravel 10.x
use Illuminate\Database\Schema\Blueprint; // Laravel 10.x
use Illuminate\Support\Facades\Schema; // Laravel 10.x

/**
 * Migration to create the policy_prefixes table.
 * 
 * This table stores standardized codes that appear before policy numbers
 * and help categorize different types of insurance policies in the Insure Pilot system.
 * Examples include 'PLCY', 'AUTO', 'HOME', etc.
 */
class CreatePolicyPrefixesTable extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the policy_prefixes table in the database with all necessary
     * fields, indexes and foreign key constraints.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('policy_prefixes', function (Blueprint $table) {
            // Primary key
            $table->bigIncrements('id');
            
            // Fields
            $table->string('name')->comment("Prefix code (e.g., 'PLCY', 'AUTO', 'HOME')");
            $table->text('description')->nullable()->comment('Additional details about the prefix');
            $table->unsignedBigInteger('status_id')->comment('Reference to status record');
            $table->unsignedBigInteger('created_by')->comment('User who created the record');
            $table->unsignedBigInteger('updated_by')->comment('User who last updated the record');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes and constraints
            $table->unique('name', 'policy_prefixes_name_unique');
            $table->foreign('status_id')->references('id')->on('statuses');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->index('status_id', 'policy_prefixes_status_id_index');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the policy_prefixes table from the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('policy_prefixes');
    }
}
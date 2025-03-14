<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoliciesTable extends Migration
{
    /**
     * Run the migrations to create the policies table.
     * This table stores insurance policy information that's used in the Documents View feature.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('policies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('policy_prefix_id');
            $table->string('number');
            $table->unsignedBigInteger('policy_type_id');
            $table->date('effective_date')->nullable();
            $table->date('inception_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->date('renewal_date')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('term_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();

            // Unique index for policy number with prefix to prevent duplicate policy numbers
            $table->unique(['policy_prefix_id', 'number']);

            // Foreign key constraints for data integrity
            $table->foreign('policy_prefix_id')->references('id')->on('policy_prefixes');
            $table->foreign('policy_type_id')->references('id')->on('policy_types');
            $table->foreign('status_id')->references('id')->on('statuses');
            $table->foreign('term_id')->references('id')->on('terms');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            // Indexes for frequently queried fields to optimize performance
            $table->index('effective_date');
            $table->index('expiration_date');
            $table->index('status_id');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     * Drops the policies table from the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('policies');
    }
}
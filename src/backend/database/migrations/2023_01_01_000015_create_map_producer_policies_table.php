<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMapProducerPoliciesTable extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the map_producer_policies table which establishes the many-to-many 
     * relationship between producers (agents/brokers) and policies. This table
     * is essential for the Documents View feature, particularly for associating
     * producers with policies and enabling policy filtering by producer number.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('map_producer_policies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('producer_id');
            $table->unsignedBigInteger('policy_id');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('producer_id')->references('id')->on('producers');
            $table->foreign('policy_id')->references('id')->on('policies');
            $table->foreign('status_id')->references('id')->on('statuses');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            // Prevent duplicate producer-policy relationships
            $table->unique(['producer_id', 'policy_id']);
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the map_producer_policies table if the migration needs to be rolled back.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('map_producer_policies');
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAzureTask extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'azure_tasks',
            function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('task_id');

                $table->string('url');
                $table->string('assignedTo');
                $table->string('creatorName');
                $table->string('itemType');
                $table->string('itemStatus');
                $table->string('title');
                $table->string('transitionColumnName');
                $table->longText('originalJson');

                $table->timestamps();
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('azure_tasks');
    }
}

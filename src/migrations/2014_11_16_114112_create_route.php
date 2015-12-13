<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoute extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'Routes',
            function (Blueprint $table) {
                $table->increments('id');
                $table->integer('routableId')->unsigned()->nullable();
                $table->string('routableType')->nullable();
                $table->boolean('isActive');
                $table->timestamp('createdAt');
                $table->timestamp('updatedAt');
            }
        );

        Schema::create(
            'RouteTranslations',
            function (Blueprint $table) {
                $table->increments('id');
                $table->string('langCode', 2);
                $table->integer('routeId')->unsigned();
                $table->string('url')->index();
                $table->boolean('isActive');
                $table->timestamp('createdAt');
                $table->timestamp('updatedAt');
                $table->foreign('routeId')->references('id')->on('Routes')->onDelete('CASCADE');
                $table->foreign('langCode')->references('code')->on('Langs')->onDelete('CASCADE');
                $table->unique(['langCode', 'routeId']); // Only one translation in specific language
                $table->unique(['langCode', 'url']); // Unique url in specific language
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
        Schema::dropIfExists('RouteTranslations');
        Schema::dropIfExists('Routes');
    }

}

<?php namespace Yfktn\SurveyKepuasan\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateYfktnSurveykepuasanOrang extends Migration
{
    public function up()
    {
        Schema::create('yfktn_surveykepuasan_orang', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('ip_address')->index();
            $table->string('nama')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('yfktn_surveykepuasan_orang');
    }
}
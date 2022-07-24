<?php namespace Yfktn\SurveyKepuasan\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateYfktnSurveykepuasanPertanyaan extends Migration
{
    public function up()
    {
        Schema::create('yfktn_surveykepuasan_pertanyaan', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->text('pertanyaan');
            $table->string('cara_menjawab')->index();
            $table->text('pilihan');
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('survey_id')->unsigned()->index();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('yfktn_surveykepuasan_pertanyaan');
    }
}
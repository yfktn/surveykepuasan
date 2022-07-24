<?php namespace Yfktn\SurveyKepuasan\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateYfktnSurveykepuasanJawaban extends Migration
{
    public function up()
    {
        Schema::create('yfktn_surveykepuasan_jawaban', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('survey_id')->unsigned()->index();
            $table->integer('pertanyaan_id')->unsigned()->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('orang_id')->unsigned()->index();
            $table->text('jawaban');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('yfktn_surveykepuasan_jawaban');
    }
}
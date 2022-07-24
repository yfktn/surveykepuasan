<?php namespace Yfktn\SurveyKepuasan\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateYfktnSurveykepuasanPertanyaan extends Migration
{
    public function up()
    {
        Schema::table('yfktn_surveykepuasan_pertanyaan', function($table)
        {
            $table->smallInteger('wajib_dijawab')->unsigned()->default(0);
        });
    }
    
    public function down()
    {
        Schema::table('yfktn_surveykepuasan_pertanyaan', function($table)
        {
            $table->dropColumn('wajib_dijawab');
        });
    }
}
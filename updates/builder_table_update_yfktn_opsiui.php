<?php namespace Yfktn\SurveyKepuasan\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateYfktnOpsiui extends Migration
{
    public function up()
    {
        Schema::table('yfktn_surveykepuasan_pertanyaan', function($table)
        {
            $table->text('opsi_ui')->nullable()->default('[]');
        });
    }
    
    public function down()
    {
        Schema::table('yfktn_surveykepuasan_pertanyaan', function($table)
        {
            $table->dropColumn('opsi_ui');
        });
    }
}
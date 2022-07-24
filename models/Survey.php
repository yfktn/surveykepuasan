<?php namespace Yfktn\SurveyKepuasan\Models;

use Model;
use October\Rain\Database\Traits\Sluggable;

/**
 * Model
 */
class Survey extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use Sluggable;

    protected $slugs = ['slug'=>'nama'];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'yfktn_surveykepuasan_';

    /**
     * @var array Validation rules
     */
    public $rules = [
        'nama' => 'required'
    ];

    public $hasMany = [
        'pertanyaan' => [
            'Yfktn\SurveyKepuasan\Models\PertanyaanSurvey',
            'key' => 'survey_id'
        ]
    ];
}

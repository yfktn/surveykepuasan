<?php namespace Yfktn\SurveyKepuasan\Models;

use Model;

/**
 * Model
 */
class OrangDiSurvey extends Model
{
    use \October\Rain\Database\Traits\Validation;
    

    /**
     * @var string The database table used by the model.
     */
    public $table = 'yfktn_surveykepuasan_orang';

    /**
     * @var array Validation rules
     */
    public $rules = [
        'ip_address' => 'required'
    ];

    public $hasMany = [
        'jawaban' => [
            'Yfktn\SurveyKepuasan\Models\JawabanSurvey',
            'key' => 'orang_id'
        ]
    ];
}

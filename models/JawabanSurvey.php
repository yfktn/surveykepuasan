<?php namespace Yfktn\SurveyKepuasan\Models;

use Model;

/**
 * Model
 */
class JawabanSurvey extends Model
{
    use \October\Rain\Database\Traits\Validation;
    

    /**
     * @var string The database table used by the model.
     */
    public $table = 'yfktn_surveykepuasan_jawaban';

    /**
     * @var array Validation rules
     */
    public $rules = [
        'jawaban' => 'required'
    ];

    public $belongsTo = [
        'survey' => [
            'Yfktn\SurveyKepuasan\Models\Survey',
            'key' => 'survey_id'
        ],
        'pertanyaan' => [
            'Yfktn\SurveyKepuasan\Models\PertanyaanSurvey',
            'key' => 'pertanyaan_id'
        ],
        'responder' => [
            'Yfktn\SurveyKepuasan\Models\OrangDiSurvey',
            'key' => 'orang_id'
        ],
    ];
}

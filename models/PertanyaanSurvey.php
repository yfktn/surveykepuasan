<?php namespace Yfktn\SurveyKepuasan\Models;

use Model;
use October\Rain\Database\Traits\Sortable;

/**
 * Model
 */
class PertanyaanSurvey extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use Sortable;

    protected $jsonable = ['pilihan'];
    /**
     * @var string The database table used by the model.
     */
    public $table = 'yfktn_surveykepuasan_pertanyaan';

    /**
     * @var array Validation rules
     */
    public $rules = [
        'pertanyaan' => 'required'
    ];

    public $hasOne = [
        'jawaban' => [
            'Yfktn\SurveyKepuasan\Models\JawabanSurvey',
            'key' => 'pertanyaan_id'
        ]
    ];

    public function caraMenjawabOptions()
    {
        return [
            'radio'=> 'User Memilih Satu Saja',
            'checkbox'=> 'User bisa memilih lebih dari satu',
            'text'=> 'User menjawab dengan menulis tulisan',
        ];
    }

    public function caraMenjawabLabel()
    {
        $d = $this->caraMenjawabOptions();
        return isset($d[$this->cara_menjawab])?$d[$this->cara_menjawab]: '?' ;
    }
}

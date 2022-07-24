<?php namespace Yfktn\SurveyKepuasan\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * Pertanyaan Survey Back-end Controller
 */
class PertanyaanSurvey extends Controller
{
    /**
     * @var array Behaviors that are implemented by this controller.
     */
    public $implement = [
        'Backend.Behaviors.ReorderController',
        // 'Backend.Behaviors.FormController',
        // 'Backend.Behaviors.ListController'
    ];

    /**
     * @var string Configuration file for the `FormController` behavior.
     */
    // public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    /**
     * @var string Configuration file for the `ListController` behavior.
     */
    // public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Yfktn.SurveyKepuasan', 'surveykepuasan', 'pertanyaansurvey');
    }

    /**
     * Karena kita hanya ingin melakukan order untuk bagian pertanyaan yang aktif saja, maka pastikan
     * yang tampil adalah untuk pilihan pertanyaan terpilih saja!
     * @param mixed $query 
     * @return void 
     */
    public function reorderExtendQuery($query)
    {
        // /backend/yfktn/surveykepuasan/pertanyaansurvey/reorder/{:id}
        $idSurveyId = request()->segment(6); /// 
        $query->where('survey_id', $idSurveyId);
    }
}

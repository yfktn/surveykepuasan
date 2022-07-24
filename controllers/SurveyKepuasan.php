<?php namespace Yfktn\SurveyKepuasan\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * Survey Kepuasan Back-end Controller
 */
class SurveyKepuasan extends Controller
{
    /**
     * @var array Behaviors that are implemented by this controller.
     */
    public $implement = [
        'Backend.Behaviors.RelationController',
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    /**
     * @var string Configuration file for the `FormController` behavior.
     */
    public $relationConfig = 'config_relation.yaml';
    public $formConfig = 'config_form.yaml';

    /**
     * @var string Configuration file for the `ListController` behavior.
     */
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Yfktn.SurveyKepuasan', 'surveykepuasan', 'surveykepuasan');
    }

    
    public function update($recordId, $context = null)
    {
        $this->vars['recordId'] = $recordId;
        $this->vars['urlReorder'] = 'yfktn/surveykepuasan/pertanyaansurvey/reorder/' . $recordId;
        
    
        // Call the FormController behavior update() method
        return $this->asExtension('FormController')->update($recordId, $context);
    }
}

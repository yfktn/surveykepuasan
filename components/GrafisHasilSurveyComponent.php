<?php namespace Yfktn\SurveyKepuasan\Components;

use Cms\Classes\ComponentBase;
use Illuminate\Support\Facades\DB;
use Yfktn\SurveyKepuasan\Classes\TraitRenderResult;
use Yfktn\SurveyKepuasan\Models\Survey;

class GrafisHasilSurveyComponent extends ComponentBase
{
    use TraitRenderResult;
    public $hasilSurveyTerpilih = [];

    public function componentDetails()
    {
        return [
            'name'        => 'Grafis Hasil Survey Component',
            'description' => 'Menampilkan grafis hasil survey, menggunakan chartjs dan doughnut.'
        ];
    }

    public function defineProperties()
    {
        return [
            'slug' => [
                'title' => 'Slug Hasil Survey Ditampilkan',
                'description' => 'Slug hasil survey yang ingin ditampilkan.',
                'default'     => '{{ :slug }}',
                'type'        => 'string',
            ],
        ];
    }

    public function onRun()
    {
        $this->addJs('assets/chart.min.js', [
            'build' => 'Yfktn.SurveyKepuasan',
            'defer' => true
        ]);
        $this->addJs('assets/chartjs-plugin-datalabels@2.0.0.min.js', [
            'build' => 'Yfktn.SurveyKepuasan',
            'defer' => true
        ]);

        $this->hasilSurveyTerpilih = $this->generateResult($this->property('slug'));

    }
}

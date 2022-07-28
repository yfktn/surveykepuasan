<?php namespace Yfktn\SurveyKepuasan\Components;

use Cms\Classes\ComponentBase;
use Illuminate\Support\Facades\DB;
use Yfktn\SurveyKepuasan\Models\Survey;

class GrafisHasilSurveyComponent extends ComponentBase
{
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

        $sqlSelector = <<<SQL
select survey.nama, pertanyaan.id as pertanyaannya_id, pertanyaan.pertanyaan, jawaban.jawaban, count(*) as jumlah
from yfktn_surveykepuasan_ survey
inner join yfktn_surveykepuasan_pertanyaan pertanyaan on survey.id = pertanyaan.survey_id 
    and pertanyaan.cara_menjawab in ('radio')
inner join yfktn_surveykepuasan_jawaban jawaban on survey.id = jawaban.survey_id 
    and pertanyaan.id = jawaban.pertanyaan_id
where survey.slug = ?
group by survey.nama, pertanyaannya_id, pertanyaan.pertanyaan, jawaban.jawaban

SQL;

        $hasilJawaban = DB::select($sqlSelector, [$this->property('slug')]);
        $untukGrafis = [];
        $currentPertanyaan = '';
        // mari kita generate struktur datanya!
        foreach($hasilJawaban as $hasil) {
            if($currentPertanyaan != $hasil->pertanyaannya_id) {
                $currentPertanyaan = $hasil->pertanyaannya_id;
                $untukGrafis[$currentPertanyaan]['labels'] = [];
                $untukGrafis[$currentPertanyaan]['data'] = [];
                $untukGrafis[$currentPertanyaan]['label'] = $hasil->pertanyaan;
            }
            $untukGrafis[$currentPertanyaan]['labels'][] = $hasil->jawaban . ' ' . $hasil->jumlah;
            $untukGrafis[$currentPertanyaan]['data'][] = $hasil->jumlah;
        }
        $this->hasilSurveyTerpilih = $untukGrafis;

    }
}

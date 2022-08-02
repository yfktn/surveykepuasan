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

        $sqlSelectorRadio = <<<SQL
select survey.nama, pertanyaan.id as pertanyaannya_id, pertanyaan.pertanyaan, jawaban.jawaban as jawabannya_user, count(*) as jumlah
from yfktn_surveykepuasan_ survey
inner join yfktn_surveykepuasan_pertanyaan pertanyaan on survey.id = pertanyaan.survey_id 
    and pertanyaan.cara_menjawab in ('radio')
inner join yfktn_surveykepuasan_jawaban jawaban on survey.id = jawaban.survey_id 
    and pertanyaan.id = jawaban.pertanyaan_id
where survey.slug = ?
group by survey.nama, pertanyaannya_id, pertanyaan.pertanyaan, jawabannya_user
SQL;
        $sqlSelectorCheckBox = <<<SQL
select survey.nama, pertanyaan.id as pertanyaannya_id, pertanyaan.pertanyaan, JT.pilihan as jawabannya_user, count(*) as jumlah
from yfktn_surveykepuasan_jawaban as jawaban, 
    yfktn_surveykepuasan_ as survey, 
    yfktn_surveykepuasan_pertanyaan as pertanyaan,
JSON_TABLE(
    jawaban.jawaban,
    '$[*]' columns(pilihan varchar(30) path '$')
) as JT 
where survey.id = jawaban.survey_id and survey.slug = ? and pertanyaan.survey_id = survey.id
    and pertanyaan.cara_menjawab = 'checkbox'
group by survey.nama, pertanyaannya_id, pertanyaan.pertanyaan, jawabannya_user
SQL;

        $hasilJawaban = collect(DB::select($sqlSelectorRadio, [$this->property('slug')]));
        $hasilJawaban = $hasilJawaban->merge(
            DB::select($sqlSelectorCheckBox, [$this->property('slug')])
        );
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
            $untukGrafis[$currentPertanyaan]['labels'][] = $hasil->jawabannya_user /*. ' ' . $hasil->jumlah*/;
            $untukGrafis[$currentPertanyaan]['data'][] = $hasil->jumlah;
        }
        $this->hasilSurveyTerpilih = $untukGrafis;

    }

    public function renderStrukturDataUntukTipeDataRadio()
    {
        
    }
}

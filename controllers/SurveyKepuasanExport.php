<?php namespace Yfktn\SurveyKepuasan\Controllers;

use ApplicationException;
use Backend\Classes\Controller;
use Backend\Facades\Backend;
use BackendMenu;
use Cache;
use DateTime;
use Flash;
use Log;
use Response;
use Yfktn\SurveyKepuasan\Models\JawabanSurvey;
use Yfktn\SurveyKepuasan\Models\Survey;
use Yfktn\Yfktnutil\Classes\TraitQueryPeriod;

class SurveyKepuasanExport extends Controller
{
    use TraitQueryPeriod;
    public $implement = [
        \Backend\Behaviors\FormController::class
    ];

    public $formConfig = 'config_form.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Yfktn.SurveyKepuasan', 'surveykepuasan', 'surveykepuasan');
    }

    public function index($recordId)
    {
        $this->pageTitle = "Mencetak Hasil Survey";
        return Backend::redirect("yfktn/surveykepuasan/surveykepuasanexport/create/" . $recordId);
    }

    public function create($recordId)
    {
        $this->pageTitle = "Rekap Hasil Survey";
        $this->vars['recordId'] = $recordId;
        return $this->asExtension('FormController')->create();
    }

    public function onCetakReport()
    {
        $dataIsian = post('Survey');
        $recordId = post('recordId');

        if(empty($recordId)) {
            throw new ApplicationException('Survey ID Tidak Ditemukan!');
        }

        // now ACTION
        $periodeDari = empty($dataIsian['periode_dari'])? null : DateTime::createFromFormat('Y-m-d H:i:s', $dataIsian['periode_dari'])->format('Y-m-d');
        $periodeSampai = empty($dataIsian['periode_sampai_dengan'])? null : DateTime::createFromFormat('Y-m-d H:i:s', $dataIsian['periode_sampai_dengan'])->format('Y-m-d');
        $terperiode = !empty($periodeDari) || !empty($periodeSampai);

        $hashkey = 'ske' . sha1($periodeDari.$periodeSampai.($terperiode?'1':'0')).$recordId;

        Cache::remember($hashkey, 10, function() use ($periodeDari, $periodeSampai, $terperiode, $recordId) {
            return [
                'periode_dari' => $periodeDari,
                'periode_sampai_dengan' => $periodeSampai,
                'terperiode' => $terperiode,
                'recordId' => $recordId
            ];
        });

        return Backend::redirect("yfktn/surveykepuasan/surveykepuasanexport?hashkey=$hashkey");
    }

    public function download()
    {
        $hashkey = get('hashkey');
        $data = Cache::get($hashkey);
        if($data === null) {
            Flash::error("Data tidak ditemukan dengan hashkey: $hashkey");
            return Backend::redirect("yfktn/surveykepuasan/surveykepuasan");
        }

        $hasilSurvey = JawabanSurvey::with(['pertanyaan', 'responder', 'survey'])
            ->where('survey_id', $data['recordId']);

        $this->applyPeriodFilter($hasilSurvey, 'created_at', $data['periode_dari'], $data['periode_sampai_dengan']);
        
        $filename = 'jawabansurvey-'. $data['recordId']
            .($data['terperiode']? '_'. $data['periode_dari'].'-'.$data['periode_sampai_dengan']: '')
            .'.html';

        $dataFilter = [];
        foreach($data as $key=>$value) {
            if($key == 'periode_dari' || $key == 'periode_sampai_dengan') {
                $dataFilter[$key] = empty($value) ? null: date('d-m-Y', strtotime($value));
            } else {
                $dataFilter[$key] = $value;
            }
        }
        
        return Response::stream(function() use($hasilSurvey, $dataFilter) {
            echo $this->makePartial('report_hasil_survey', [
                'hasilSurvey' => $hasilSurvey->orderBy('created_at', 'desc')->get(),
                'dataFilter' => $dataFilter
            ]);
        }, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => sprintf('%s; filename="%s"', 'attachment', $filename)
        ]);
    }

}
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
use Yfktn\SurveyKepuasan\Classes\TraitRenderResult;
use Yfktn\SurveyKepuasan\Models\JawabanSurvey;
use Yfktn\SurveyKepuasan\Models\Survey;
use Yfktn\Yfktnutil\Classes\TraitQueryPeriod;

class SurveyKepuasanExport extends Controller
{
    use TraitRenderResult;
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
        
        // Log::debug("Mencetak Hasil Survey: $periodeDari - $periodeSampai - $terperiode - $recordId - $hashkey");

        Cache::remember($hashkey, 10, function() use ($periodeDari, $periodeSampai, $terperiode, $recordId, $hashkey) {
            // Log::debug("Cache Set: $periodeDari - $periodeSampai - $terperiode - $recordId - $hashkey");
            return [
                'periode_dari' => $periodeDari,
                'periode_sampai_dengan' => $periodeSampai,
                'terperiode' => $terperiode,
                'recordId' => $recordId
            ];
        });

        return Backend::redirect("yfktn/surveykepuasan/surveykepuasanexport/download?hashkey=$hashkey");
    }

    public function download()
    {
        $hashkey = get('hashkey');
        $dataFilter = Cache::get($hashkey);
        if($dataFilter === null) {
            // Log::error("Data tidak ditemukan dengan hashkey: $hashkey");
            Flash::error("Data tidak ditemukan dengan hashkey: $hashkey");
            return Backend::redirect("yfktn/surveykepuasan/surveykepuasan");
        }

        $survey = Survey::findOrFail($dataFilter['recordId']);

        $hasilSurvey = $this->generateResult($survey->slug, $dataFilter);
        $hasilSurveyBerupaTeks = $this->getTextAnswer($survey->slug, $dataFilter);

        $filename = "hasil-survey-" . str_slug($survey->nama) . ".html";
        
        return Response::stream(function() use($hasilSurvey, $dataFilter, $survey, $hasilSurveyBerupaTeks) {
            echo $this->makePartial('report_hasil_survey', [
                'survey' => $survey,
                'hasilSurveyTerpilih' => $hasilSurvey,
                'hasilSurveyTeks' => $hasilSurveyBerupaTeks,
                'dataFilter' => $dataFilter
            ]);
        }, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => sprintf('%s; filename="%s"', 'attachment', $filename)
        ]);
    }

}
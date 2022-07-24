<?php namespace Yfktn\SurveyKepuasan\Components;
use Validator;
use ValidationException;
use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Exception;
use Db;
use Illuminate\Support\Facades\Log;
use October\Rain\Exception\ApplicationException;
use Yfktn\SurveyKepuasan\Models\JawabanSurvey;
use Yfktn\SurveyKepuasan\Models\OrangDiSurvey;
use Yfktn\SurveyKepuasan\Models\Survey;

class SurveyKepuasanComponent extends ComponentBase
{
    public $survey;

    public function componentDetails()
    {
        return [
            'name'        => 'SurveyKepuasan Component',
            'description' => 'Tampilkan pertanyaan survey.'
        ];
    }

    public function defineProperties()
    {
        return [
            'slug' => [
                'title' => 'Slug Survey Ditampilkan',
                'description' => 'Slug survey yang ingin ditampilkan.',
                'default'     => '{{ :slug }}',
                'type'        => 'string',
            ],
        ];
    }

    public function onRun()
    {
        $this->survey = Survey::where('slug', $this->property('slug'))->first();
    }

    protected function isThrottled($ip, $inMinute = 5, $maxCount = 2)
    {
        // in 5 minutes no more than 2
        $now = Carbon::now();
        $min5Minute = $now->copy()->subMinutes($inMinute);
        // dapatkan dari 5 menit sebelum sampai sekarang, apakah ada yang sama ip address nya sama?
        $count = OrangDiSurvey::where('ip_address', $ip)->whereBetween('created_at', [$min5Minute, $now])->count();
        return $count > $maxCount;
    }


    public function onSubmitAnswer()
    {
        // dapatkan user ip
        $ip = request()->ip();
        if($this->isThrottled($ip)) {
            throw new ApplicationException("Request Throttled! Terlalu banyak posting dari IP yang sama.");
        }
        // go checkout selected survey 
        $currentSurveySlug = $this->property('slug');
        $currentSurvey = Survey::with('daftarPertanyaan')->where('slug', $currentSurveySlug)->first();
        if($currentSurvey==null) {
            throw new ApplicationException("Survey tidak ditemukan!");
        }
        // get question list
        $questionsList = $currentSurvey->daftarPertanyaan;
        $rules = [];
        foreach($questionsList as $question) {
            $nameid = "s-{$currentSurvey->id}-p-{$question->id}"; // render our nameid
            if($question->wajib_dijawab) {
                $rules[$nameid] = "required";
            }
        }
        // ready for validation
        $userAnswer = post();
        $validation = Validator::make($userAnswer, $rules);
        if($validation->fails()) {
            throw new ValidationException($validation);
        }
        try {
            Db::beginTransaction();
            // who is this guy?
            $orang = new OrangDiSurvey;
            $orang->ip_address = $ip; // current version by IP address
            $orang->save(); // save 
            // go find out the answer 
            $answer = [];
            $answerCnt = 0;
            $theNow = Carbon::now()->format("Y-m-d H:i:s");
            // get the answer
            foreach($questionsList as $question) {
                $nameid = "s-{$currentSurvey->id}-p-{$question->id}";
                if(!isset($userAnswer[$nameid])) continue;
                $answerItem = [
                    'survey_id' => $currentSurvey->id,
                    'pertanyaan_id' => $question->id,
                    'orang_id' => $orang->id,
                    'created_at' => $theNow,
                    'updated_at' => $theNow,
                ];
                if($question->cara_menjawab == 'radio') {
                    $answerItem['jawaban'] = $userAnswer[$nameid];
                } else if($question->cara_menjawab == 'checkbox') {
                    $answerItem['jawaban'] = json_encode($userAnswer[$nameid]);
                } else if($question->cara_menjawab == 'text') {
                    $answerItem['jawaban'] = $userAnswer[$nameid];
                }
                $answerCnt = array_push($answer, $answerItem);
            }
            if($answerCnt > 0) {
                JawabanSurvey::insert($answer);
            }
            $currentSurvey->touch(); // touch and update timestamps!
            Db::commit();
        } catch(Exception $ex) {
            Log::error($ex->getMessage() . "\n" . $ex->getTraceAsString(), [
                "SurveyKepuasanComponent::onSubmitAnswer"
            ]);
            Db::rollBack();
            throw new ApplicationException("Unhandled Exception, sorry!");
        }
    }
}

<?php namespace Yfktn\SurveyKepuasan\Components;
use Validator;
use ValidationException;
use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Exception;
use Db;
use Flash;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;
use October\Rain\Exception\ApplicationException;
use Symfony\Component\HttpFoundation\Exception\ConflictingHeadersException;
use Yfktn\SurveyKepuasan\Models\JawabanSurvey;
use Yfktn\SurveyKepuasan\Models\OrangDiSurvey;
use Yfktn\SurveyKepuasan\Models\Survey;
/**
 * Menampilkan form untuk melakukan survey di frontend
 * @package Yfktn\SurveyKepuasan\Components
 */
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

    /**
     * Ini akan di jalankan setelah user mengklik submit!
     * @return void 
     * @throws BindingResolutionException 
     * @throws ConflictingHeadersException 
     * @throws ApplicationException 
     */
    public function onSubmitAnswer()
    {
        $enableCaptcha = config('yfktn.surveykepuasan::enableCaptcha', true);
        $debugMode = config('app.debug');
        
        if( $debugMode ) {
            Log::alert("In Debug Mode! Survey selalu disimpan!");
            Flash::error("In Debug Mode! Survey selalu disimpan!");
        } 
        // spam?
        $isReCaptchaValid = Validator::make(post(), [
            'g-recaptcha-response' => ['required', new \Yfktn\YfktnUtil\Classes\ReCaptchaValidator],
        ]);   

        if($enableCaptcha && $isReCaptchaValid->fails()) {
            // captcha enabled and the captcha is in the not valid state!
            if(!$debugMode) {
                throw new ApplicationException("ReCaptcha Tidak Valid!");
            }
            Flash::error("ReCaptcha Tidak Valid! But in the debug mode");
            Log::alert("ReCaptcha Tidak Valid! But in the debug mode");
        }

        // dapatkan user ip
        $ip = request()->ip();
        if($this->isThrottled($ip,
            config('yfktn.surveykepuasan::throotleTrigger.interval', 5),
            config('yfktn.surveykepuasan::throotleTrigger.maxPostCount', 2))
        ) {
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
                $this->makeSureAnsweredItemIsValid($question, $userAnswer[$nameid]);
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

    /**
     * Kadang kala ada yang menambahkan jawaban yang tidak sesuai dengan pilihan yang disediakan.
     * @param mixed $question 
     * @param mixed $answer 
     * @return void 
     * @throws ApplicationException 
     */
    protected function makeSureAnsweredItemIsValid($question, $answer)
    {
        if($question->cara_menjawab == 'radio') {
            $pilihanJawaban = array_flip(array_column($question->pilihan, 'pilihan_label'));
            if(!isset($pilihanJawaban[$answer])) {
                throw new ApplicationException("Jawaban: '{$answer}' terpilih tidak ada untuk pertanyaan '{$question->pertanyaan}'");
            }
        } else if($question->cara_menjawab == 'checkbox') {
            $pilihanJawaban = array_flip(array_column($question->pilihan, 'pilihan_label'));
            foreach($answer as $item) {
                if(!isset($pilihanJawaban[$item])) {
                    throw new ApplicationException("Jawaban: '{$item}' terpilih tidak ada untuk pertanyaan '{$question->pertanyaan}'");
                }
            }
        }
    }
}

<?php namespace Yfktn\SurveyKepuasan\Classes;

use Illuminate\Support\Facades\DB;

trait TraitRenderResult
{
    public function generateResult($slugSelectedSurvey)
    {
        $sqlSelectorRadio = <<<SQL
select survey.nama, pertanyaan.id as pertanyaannya_id, pertanyaan.pertanyaan, 
    jawaban.jawaban as jawabannya_user, count(*) as jumlah, pertanyaan.pilihan as pertanyaan_pilihan
from yfktn_surveykepuasan_ as survey
inner join yfktn_surveykepuasan_pertanyaan as pertanyaan on survey.id = pertanyaan.survey_id 
    and pertanyaan.cara_menjawab in ('radio')
inner join yfktn_surveykepuasan_jawaban as jawaban on survey.id = jawaban.survey_id 
    and pertanyaan.id = jawaban.pertanyaan_id
where survey.slug = ?
group by survey.nama, pertanyaannya_id, pertanyaan.pertanyaan, jawabannya_user, pertanyaan_pilihan
SQL;
        $sqlSelectorCheckBox = <<<SQL
select survey.nama, pertanyaan.id as pertanyaannya_id, pertanyaan.pertanyaan, 
    jawaban.jawaban as jawabannya_user, count(*) as jumlah, pertanyaan.pilihan as pertanyaan_pilihan
from yfktn_surveykepuasan_ as survey
inner join yfktn_surveykepuasan_pertanyaan as pertanyaan on survey.id = pertanyaan.survey_id 
    and pertanyaan.cara_menjawab in ('checkbox')
inner join yfktn_surveykepuasan_jawaban as jawaban on survey.id = jawaban.survey_id 
    and pertanyaan.id = jawaban.pertanyaan_id
where survey.slug = ?
group by survey.nama, pertanyaannya_id, pertanyaan.pertanyaan, jawabannya_user, pertanyaan_pilihan
SQL;

        $hasilJawaban = collect(DB::select($sqlSelectorRadio, [$slugSelectedSurvey]));
        $hasilJawaban = $hasilJawaban->merge(
            $this->processForCheckboxGroupingCount(DB::select($sqlSelectorCheckBox, [$slugSelectedSurvey]))
        );
        $untukGrafis = [];
        $indexWarna = [];
        $indexWarnaDefault = [
            "#FF6384",
            "#63FF84",
            "#84FF63",
            "#8463FF",
            "#6384FF"];
        $indexWarnaDefaultOffset = 0;
        $currentPertanyaan = '';
        // mari kita generate struktur datanya!
        foreach($hasilJawaban as $hasil) {
            if($currentPertanyaan != $hasil->pertanyaannya_id) {
                $currentPertanyaan = $hasil->pertanyaannya_id;
                // dapatkan untuk grafis?
                $indexWarna = [];
                $pilihan = json_decode($hasil->pertanyaan_pilihan);
                foreach($pilihan as $p) {
                    $indexWarna[$p->pilihan_label] = $p->pilihan_warna;
                }
                $untukGrafis[$currentPertanyaan]['labels'] = [];
                $untukGrafis[$currentPertanyaan]['data'] = [];
                $untukGrafis[$currentPertanyaan]['warna'] = [];
                $untukGrafis[$currentPertanyaan]['label'] = $hasil->pertanyaan;
            }
            $untukGrafis[$currentPertanyaan]['labels'][] = $hasil->jawabannya_user /*. ' ' . $hasil->jumlah*/;
            $untukGrafis[$currentPertanyaan]['data'][] = $hasil->jumlah;
            if(isset($indexWarna[$hasil->jawabannya_user])) {
                $untukGrafis[$currentPertanyaan]['warna'][] = $indexWarna[$hasil->jawabannya_user]; 
            } else {
                if($indexWarnaDefaultOffset + 1 > 4) {
                    $indexWarnaDefaultOffset = 0;
                }
                $untukGrafis[$currentPertanyaan]['warna'][] = $indexWarnaDefault[$indexWarnaDefaultOffset]; 
            }
        }
        
        return $untukGrafis;

    }

    /**
     * Hasil dari query untuk tipe pertanyaan checkbox masih berbentuk JSON pada field jawabannya_user, 
     * di mana membutuhkan proses lebih lanjut. Masalahnya adalah: fungsi JSON_TABLE ternyata tidak bisa
     * diandalkan sementara karena server di production tidak menggunakan versi mysql yang dibutuhkan.
     * Jadi di sini lakukan perhitungan ulang dari hasil query dengan cara merekontruksi ulang hasil query
     * asli sehingga kita bisa mendapatkan nilai yang telah dikategorikan berdasarkan jawaban dengan
     * tipe checkbox (bisa memilih lebih dari satu).
     * @return array 
     */
    protected function processForCheckboxGroupingCount(array $originalResult)
    {
        $groupingQuestion = [];
        $groupingCounter = [];
        foreach($originalResult as $theRow){
            $theIdKey = $theRow->pertanyaannya_id;
            // ----- GROUPING Question
            if(!isset($groupingQuestion[$theIdKey])) {
                $buffer = json_decode(json_encode($theRow), true);
                $groupingQuestion[$theIdKey] = array_filter($buffer, 
                    function($key) {
                        if($key == 'jawabannya_user' || $key == 'jumlah') {
                            return false;
                        }
                        return true;
                    }, ARRAY_FILTER_USE_KEY);
            }
            // ----- GROUPING Counter!
            // ambil dulu nilai pilihan jawabannya user! dan decode ke array untuk dibaca!
            $arrayValue = json_decode($theRow->jawabannya_user);
            // bug bila sudah dibuat sebagai radio button lalu dirubah sebagai checkbox!
            if(json_last_error() !== JSON_ERROR_NONE) {
                $arrayValue = [$theRow->jawabannya_user];
            }
            // baru hitung ke dalam groupingCounter nya
            foreach($arrayValue as $key => $value) {
                if(!isset($groupingCounter[$theIdKey])) {
                    $groupingCounter[$theIdKey] = []; // bikin array baru
                }
                if(isset($groupingCounter[$theIdKey][$value])) {
                    $groupingCounter[$theIdKey][$value] += $theRow->jumlah;
                } else {
                    $groupingCounter[$theIdKey][$value] = $theRow->jumlah;
                }
            }
        }
        // di sini semestinya sudah semua tergroupkan maka susun ulang lagi 
        $newData = [];
        $counter = -1;
        foreach($groupingCounter as $theIdKey => $checkboxGroupedValue) {
            foreach($checkboxGroupedValue as $value => $count) {
                $newData[++$counter] = $groupingQuestion[$theIdKey];
                $newData[$counter]['jawabannya_user'] = $value;
                $newData[$counter]['jumlah'] = $count;
                // to make it sync with the other results then we need to convert each items
                // from assoc array to object.
                $newData[$counter] = (Object) $newData[$counter];
            }
        }
        return $newData;
    }
}
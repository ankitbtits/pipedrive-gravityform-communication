<?php
function getPipeDriveAPIEndPoint($atr = false){
    $pipedriveAPI = [
        [
            'label' => 'Add a Person',
            'end_point'=> 'persons'
        ],
        [
            'label' => 'Add an organization',
            'end_point'=> 'organizations'
        ],
        [
            'label' => 'Add a deal',
            'end_point'=> 'deals'
        ],
        [
            'label' => 'Add an activities',
            'end_point'=> 'activities'
        ],
    ]; 
    if($atr){
        foreach($pipedriveAPI as $val){
            if($val['label'] == $atr){
                $pipedriveAPI = $val['end_point'];
            }
        }
    }
    
    return $pipedriveAPI;
}

function getSampleData() {
    return [
        'id' => 6,
        'status' => 'active',
        'form_id' => 4,
        'ip' => '::1',
        'source_url' => 'http://localhost/pipedrive/sample-page/',
        'currency' => 'USD',
        'post_id' => null,
        'date_created' => '2025-03-25 07:30:33',
        'date_updated' => '2025-03-25 07:30:33',
        'is_starred' => 0,
        'is_read' => 0,
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
        'payment_status' => null,
        'payment_date' => null,
        'payment_amount' => null,
        'payment_method' => null,
        'transaction_id' => null,
        'is_fulfilled' => null,
        'created_by' => 1,
        'transaction_type' => null,
        'source_id' => 2,
        '1.2' => null,
        '1.3' => 'first name',
        '1.4' => null,
        '1.6' => 'last name',
        '1.8' => null,
        '10' => 'AUDITORIUM (283 PAX + UN MASSIMO DI 11 RELATORI SUL PALCO)',
        '5' => '12121',
        '6' => '2025-03-25',
        '11' => '2025-03-20',
        '7.1' => 'Si',
        '7.2' => null,
        '8' => 'test description',
        '3.1' => 'Crea account',
        '9.1' => 'Il /La sottoscritto/a nel trasmettere i propri dati a PromoFirenze Azienda Speciale della Camera di Commercio di Firenze, dichiara ai sensi dell’art. 13 del Regolamento UE 2016/679 del 27 aprile 2016 e del Codice Privacy D.lgs n. 196/2003 come modificato dal D.lgs n. 101/2018, di aver preso visione sul sito www.promofirenze.it nell’apposita sezione, dell’intera informativa al consenso del trattamento dei dati.',
        '12.1' => 'Consento',
        '13.1' => 'Consento',
        '13.2' => null,
    ];
}

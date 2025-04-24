<?php

function showDealsField(){
    return $dataField = [
        'title'=> [
            'fieldKey' => 'title',
            'label' => 'Deal Name'
        ],
        'label' => [
            'fieldKey' => 'label',
            'label' => 'Deal Type'
        ],
        'stage_id'=>[
            'fieldKey' => 'stage_id',
            'label' => 'Deal Stage'
        ]
    ];
}


function getPipeDriveAPIEndPoint($atr = false){
    $pipedriveAPI = [
        [
            'label' => __('Add a Person', 'pgfc'),
            'end_point' => 'persons',
            'singular_end_point' => 'person',
        ],
        [
            'label' => __('Add an organization', 'pgfc'),
            'end_point' => 'organizations',
            'singular_end_point' => 'organization',
        ],
        [
            'label' => __('Add a deal', 'pgfc'),
            'end_point' => 'deals',
            'singular_end_point' => 'deal',
        ],
        [
            'label' => __('Add an activities', 'pgfc'),
            'end_point' => 'activities',
            'singular_end_point' => 'activity',
        ],
    ];
    
    if($atr){
        foreach($pipedriveAPI as $val){
            if($val['singular_end_point'] == $atr || $val['label'] == $atr){
                $pipedriveAPI = $val['end_point'];
            }
        }
    }
    
    return $pipedriveAPI;
}
// function alloedProfileData($attr = false){
//     $res = [
//         'persons'=>[
//             [
//                 'key' => 'name',
//                 'action' => 'edit',
//             ],
//             [
//                 'key' => 'email',
//                 'action' => 'edit',
//             ],
//             [
//                 'key' => 'phone',
//                 'action' => 'edit',
//             ],
           
//             [
//                 'key' => 'marketing_status',
//                 'action' => 'edit',
//             ],
//             [
//                 'key' => 'ec9fda99f1140b87d4c68162f19e726a61c12033',
//                 'action' => 'edit',
//             ],
//             [
//                 'key' => '1857792581662799944dcb3f2eadc7b78e477120',
//                 'action' => 'edit',
//             ],
//             [
//                 'key' => 'd71b88d861c0644607d4a070a78daa90951fd7f4',
//                 'action' => 'edit',
//             ]
//         ],
//         'organizations'=>[
//             [
//                 'key' => 'name',
//                 'action' => 'edit',
//             ]
//         ],
//         'deals'=>[
//             [
//                 'key' => 'title',
//                 'action' => 'edit',
//             ]
//         ],
//         'activities'=>[
//             [
//                 'key' => 'subject',
//                 'action' => 'edit',
//             ]
//         ]
//     ];
//     if($attr){
//         $res = $res[$attr];
//     }
//     return $res;
// }
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
        '9.1'=>'',
        //'9.1' => 'Il /La sottoscritto/a nel trasmettere i propri dati a PromoFirenze Azienda Speciale della Camera di Commercio di Firenze, dichiara ai sensi dell’art. 13 del Regolamento UE 2016/679 del 27 aprile 2016 e del Codice Privacy D.lgs n. 196/2003 come modificato dal D.lgs n. 101/2018, di aver preso visione sul sito www.promofirenze.it nell’apposita sezione, dell’intera informativa al consenso del trattamento dei dati.',
        '12.1' => 'Consento',
        '13.1' => 'Consento',
        '13.2' => null,
    ];
}
function getSampleData2() {
    return [
        'id' => 16,
        'status' => 'active',
        'form_id' => 3,
        'ip' => '::1',
        'source_url' => 'http://localhost/pipedrive/sample-page/',
        'currency' => 'USD',
        'post_id' => null,
        'date_created' => '2025-03-27 05:15:33',
        'date_updated' => '2025-03-27 05:15:33',
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
        '1.3' => 'deve 003',
        '1.4' => null,
        '1.6' => 'last',
        '1.8' => null,
        '3' => 'te@gmail.com',
        '5' => 'activity one by dev',
        '6' => 'activity 2 label',
        '7' => 'dealname for test',
        '8' => 'orgname',
    ];
}

function getSampleData_3() {
    return array(
        'id' => 30,
        'status' => 'active',
        'form_id' => 3,
        'ip' => '38.137.49.58',
        'source_url' => 'https://promofirenzdev.wpenginepowered.com/nuove-imprese/',
        'currency' => 'EUR',
        'post_id' => '',
        'date_created' => '2025-04-01 14:14:33',
        'date_updated' => '2025-04-01 14:14:33',
        'is_starred' => 0,
        'is_read' => 0,
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
        'payment_status' => '',
        'payment_date' => '',
        'payment_amount' => '',
        'payment_method' => '',
        'transaction_id' => '',
        'is_fulfilled' => '',
        'created_by' => 12,
        'transaction_type' => '',
        'source_id' => 22699,
        1 => '2024-04-05',
        '3.2' => '',
        '3.3' => 'name',
        '3.4' => '',
        '3.6' => 'last name',
        '3.8' => '',
        4 => '001122',
        5 => 'test@gmail.com',
        6 => 'Codice Fiscale',
        7 => '2023-03-02',
        8 => 'Aspirante Imprenditore',
        9 => 'Turismo',
        10 => 'Specificare attività, settore, dove si intende localiz',
        '11.1' => '',
        '14.1' => 'Il/La sottoscritto/a nel trasmettere i propri dati a PromoFirenze Azienda Speciale della Camera di Commercio di Firenze, dichiara ai sensi dell’art. 13 del Regolamento UE 2016/679 del 27 aprile 2016 e del Codice Privacy D.lgs n. 196/2003 come modificato dal D.lgs n. 101/2018, di aver preso visione sul sito https://www.promofirenze.it/informativa-privacy/ nell’apposita sezione, dell’intera informativa al consenso del trattamento dei dati.',
        '12.1' => 'Il /La sottoscritto/a dichiara altresì che, riguardo al trattamento dei dati per le finalità promozionali e commerciali proprie del titolare',
        '13.1' => '',
        15 => 'Dev deal',
    );
}
function getSampleData_4() {
    return array(
        'persons' => array(
            'name' => 'name - last name',
            'email' => 'test@gmail.com',
            'd71b88d861c0644607d4a070a78daa90951fd7f4' => 'Codice Fiscale',
            '627e61b2befdbf7020b0cbbb362618dd7f1e6d91' => 'Aspirante Imprenditore',
            '07aaf6652ad9c3267cf10b014b3b8ac139e69054' => 'Il/La sottoscritto/a nel trasmettere i propri dati a PromoFirenze Azienda Speciale della Camera di Commercio di Firenze, dichiara ai sensi dell’art. 13 del Regolamento UE 2016/679 del 27 aprile 2016 e del Codice Privacy D.lgs n. 196/2003 come modificato dal D.lgs n. 101/2018, di aver preso visione sul sito https://www.promofirenze.it/informativa-privacy/ nell’apposita sezione, dell’intera informativa al consenso del trattamento dei dati.',
            'marketing_status' => 'Il /La sottoscritto/a dichiara altresì che, riguardo al trattamento dei dati per le finalità promozionali e commerciali proprie del titolare',
            'ec9fda99f1140b87d4c68162f19e726a61c12033' => '',
        ),
        'deals' => array(
            '1447c372161752b96ec178bdc807e041ea581bb5' => 'Specificare attività, settore, dove si intende localiz',
            'title' => 'name',
        ),
    );
}

function skipPopulateFieldTypes(){
    return ['date', 'radio', 'multi_choice', 'checkbox'];
}

function skipPopulateFieldTypesNew(){
    return ['date', 'multi_choice'];
}

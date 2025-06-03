<?php

function getPipeDriveAPIEndPoint($atr = false){
    $pipedriveAPI = [
        [
            'label' => __('Add a Person', PGFC_TEXT_DOMAIN),
            'end_point' => 'persons',
            'singular_end_point' => 'person',
        ],
        [
            'label' => __('Add an organization', PGFC_TEXT_DOMAIN),
            'end_point' => 'organizations',
            'singular_end_point' => 'organization',
        ],
        [
            'label' => __('Add a deal', PGFC_TEXT_DOMAIN),
            'end_point' => 'deals',
            'singular_end_point' => 'deal',
        ],
        [
            'label' => __('Add an activities', PGFC_TEXT_DOMAIN),
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
function getPipeDriveAPIArray($atr){
    $arr = [];
    foreach(getPipeDriveAPIEndPoint() as $key => $val){
        if($val['singular_end_point'] == $atr || $val['label'] == $atr || $val['end_point'] == $atr){
            $arr = $val;
        }
    }
    return $arr;
}
function allowSubFieldsType($type){
    $arr = ['select', 'radio', 'checkbox', 'post_category', 'multiselect'];
    return !in_array($type, $arr);
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

function getSampleData02(){
    return $data = [
        'id' => 295,
        'status' => 'active',
        'form_id' => 4,
        'ip' => '49.43.99.13',
        'source_url' => 'https://promofirenzdev.wpenginepowered.com/finanza-3/',
        'currency' => 'EUR',
        'post_id' => null,
        'date_created' => '2025-04-30 07:05:42',
        'date_updated' => '2025-04-30 07:05:42',
        'is_starred' => 0,
        'is_read' => 0,
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
        'payment_status' => null,
        'payment_date' => null,
        'payment_amount' => null,
        'payment_method' => null,
        'transaction_id' => null,
        'is_fulfilled' => null,
        'created_by' => null,
        'transaction_type' => null,
        'source_id' => 22701,
        '1.2' => null,
        '1.3' => 'dev421',
        '1.4' => null,
        '1.6' => 'las421',
        '1.8' => null,
        '3' => 'dev421@gmail.com',
        '4' => 'Organizzazione / Azienda 421',
        '18' => 'Seconda scelta',
        '6' => 'Ruolo 421',
        '7' => '00222421',
        '8.1' => 'Indirizzo 421',
        '8.2' => null,
        '8.3' => 'citta421',
        '8.4' => 'state 421',
        '8.5' => '1245421',
        '8.6' => 'Antigua e Barbuda',
        '9.1' => null,
        '12.1' => "Accetto l' informativa Privacy*",
        '10.1' => 'subscribed',
        '17' => 'Non consento',
        '13' => '[FINANZA] Richiesta info sui bandi',
        '14' => 'Finanza agevolata',
        '16' => '21',
    ];
    
}

function skipPopulateFieldTypes(){
    return ['date', 'radio', 'multi_choice', 'checkbox'];
}

function skipPopulateFieldTypesNew(){
    return ['date', 'multi_choice'];
}

function staticText() {
    return array(
        'loadingText'    => __('Checking', PGFC_TEXT_DOMAIN),
        'searchingText'  => __('Searching', PGFC_TEXT_DOMAIN),
        'orgNotFound'    => __('A new organization with this name will be created.', PGFC_TEXT_DOMAIN),
    );
}
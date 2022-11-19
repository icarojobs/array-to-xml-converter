<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    $array = [
        'name' => 'Tio Jobs',
        'city' => 'São Paulo',
    ];

//    $xml = \App\Actions\Array2XMLAction::createXML('teste', $array);
//    dd($xml->saveXML());

    $arrayConverter = new \App\Actions\Array2XMLAction(nodeName: 'teste', array: $array);
    $arrayConverter->convertXML();

    dd($arrayConverter->saveXML());
});

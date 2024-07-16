<?php

namespace App\Services\General;

use App\Http\Controllers\APIBitrixController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitrixRPAService
{
    protected $hook;

    public function __construct($hook)
    {
        $this->hook = $hook;
    }




    public function getRPAList(
        $data
        // typeId - идентификатор процесса
        // order - список для сортировки, где ключ - поле, а значение - ASC или DESC
        // filter - список для фильтрации. Ключи для фильтрации по пользовательским полям должны быть в UPPER_CASE, остальные - в camelCase. Примеры фильтров ниже
    ) {
        $method = '/rpa.item.list';

        $response = Http::get($this->hook . $method, $data);
        $result =  APIBitrixController::getBitrixRespone($response, 'getRPAList');
        return  $result;
    }

    public function getRPAItem(
        $typeId,
        $rpaItemId
        // typeId - идентификатор процесса
        // order - список для сортировки, где ключ - поле, а значение - ASC или DESC
        // filter - список для фильтрации. Ключи для фильтрации по пользовательским полям должны быть в UPPER_CASE, остальные - в camelCase. Примеры фильтров ниже
    ) {
        $method = '/rpa.item.list';
        $data = [
            'typeId' => $typeId,
            'id' => $rpaItemId,

        ];
        $response = Http::get($this->hook . $method, $data);
        $result =  APIBitrixController::getBitrixRespone($response, 'getRPAList');
        return  $result;
    }

    public function setRPAItem(
        $data
        // typeId - идентификатор процесса
        // fields - значения пользовательских полей элемента. Все остальные поля будут проигнорированы. Не обязательный параметр
    ) {
        $method = '/rpa.item.add';

        $response = Http::get($this->hook . $method, $data);
        
        Log::channel('telegram')->info('plan deals ids', [
            'response' => $response

        ]);
        $result =  APIBitrixController::getBitrixRespone($response, 'setRPAItem');
        return  $result;
    }

    public function updateRPAItem(
        $typeId,
        $rpaItemId
        // typeId - идентификатор процесса
        // fields - значения пользовательских полей элемента. Все остальные поля будут проигнорированы. Не обязательный параметр
    ) {
        // fields[stageId] - идентификатор стадии
        // fields[UF_RPA_...] - значения пользовательских полей

        $method = '/rpa.item.list';
        $data = [
            'typeId' => $typeId,
            'id' => $rpaItemId,

        ];
        $response = Http::get($this->hook . $method, $data);
        $result =  APIBitrixController::getBitrixRespone($response, 'getRPAList');
        return  $result;
    }
}

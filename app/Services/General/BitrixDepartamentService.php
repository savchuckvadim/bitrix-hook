<?php

namespace App\Services\General;



class BitrixDepartamentService
{

    //smart
    static function getDepartamentTypeByUserId(
        // $hook,
        // $userId, //lidId ? from lead


    ) {
        $currentDepartamentGroup = 'sales'; //'sales', tmc, service, edu
        try {


            return $currentDepartamentGroup;
        } catch (\Throwable $th) {
            return $currentDepartamentGroup;
        }
    }
}

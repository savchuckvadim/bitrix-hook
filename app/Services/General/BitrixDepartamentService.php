<?php

namespace App\Services\General;

use App\Http\Controllers\APIBitrixController;
use Illuminate\Support\Facades\Http;


class BitrixDepartamentService
{
    protected $hook;

    public function __construct($hook)
    {
        $this->hook = $hook;
    }


    static function getDepartamentTypeByUserId(
        // $hook,
        // $userId, //lidId ? from lead


    )
    {
        $currentDepartamentGroup = 'sales'; //'sales', tmc, service, edu
        try {


            return $currentDepartamentGroup;
        } catch (\Throwable $th) {
            return $currentDepartamentGroup;
        }
    }


    static function getDepartamentIdByPortal(
        $portal


    ) {
        $result = false; //'sales', tmc, service, edu
        try {
            if (!empty($portal)) {
                if (!empty($portal['departament'])) {
                    $result = $portal['departament'];
                }
            }

            return $result;
        } catch (\Throwable $th) {
            return $result;
        }
    }


    public function getDepartments($data)
    {
        $method = '/department.get';
    
        $response = Http::get($this->hook . $method, $data);
        $result =  APIBitrixController::getBitrixRespone($response, 'getDepartments');
        return  $result;
    }


    public function getUsersByDepartment($departmentId)
    {
        $method = '/user.get';
        $params = [
            'FILTER' => ['UF_DEPARTMENT' => $departmentId]
        ];
        $response = Http::get($this->hook . $method, $params);
        $result =  APIBitrixController::getBitrixRespone($response, 'getDepartments');

        return  $result;
    }

    public function getAllUsersFromSalesIncludingSubdeps($salesDeptId)
    {
        $departments = $this->getDepartments([]);  // Список всех подразделений
        $deptIdsToQuery = $this->getSubDepartmentsIds($departments, $salesDeptId);
        $deptIdsToQuery[] = $salesDeptId; // Добавляем корневой отдел продаж

        $allUsers = [];
        $tmcUsers = [];
        foreach ($deptIdsToQuery as $deptId) {
            $users = $this->getUsersByDepartment($deptId);
            foreach ($users as $user) {
                $allUsers[] = $user;
                if ($user['UF_POSITION'] === 'ТМЦ') { // Допустим, что 'ТМЦ' это искомая должность
                    $tmcUsers[] = $user;
                }
            }
        }
        return [
            'allUsers' => $allUsers,
            'tmcUsers' => $tmcUsers
        ];
    }

    // Рекурсивная функция для получения всех вложенных ID подразделений
    private function getSubDepartmentsIds($departments, $parentId)
    {
        $subDeptIds = [];
        foreach ($departments as $dept) {
            if ($dept['PARENT'] === $parentId) {
                $subDeptIds[] = $dept['ID'];
                $subDeptIds = array_merge($subDeptIds, $this->getSubDepartmentsIds($departments, $dept['ID']));
            }
        }
        return $subDeptIds;
    }
}

// class BitrixDepartamentService
// {

//     //smart
//     static function getDepartamentTypeByUserId(
//         // $hook,
//         // $userId, //lidId ? from lead


//     ) {
//         $currentDepartamentGroup = 'sales'; //'sales', tmc, service, edu
//         try {


//             return $currentDepartamentGroup;
//         } catch (\Throwable $th) {
//             return $currentDepartamentGroup;
//         }
//     }

//     static function getDepartment(
//         $hook,
//         $currentDepartmentId,

//     ) {
//         // lidIds UF_CRM_7_1697129081
//         $currentDepartment = null;

//         try {
//             $method = '/user.get.json';
//             $url = $hook . $method;
//             // $portalDealCategories =  $portalDeal['categories'];
//             $data = [

//             ];
//             $response = Http::get($url, $data);
//             $currentDepartment = APIBitrixController::getBitrixRespone($response, 'getDepartment');

//             // Log::channel('telegram')->info('COLD DEAL get currentDeal', [
//             //     'currentDeal' => $currentDeal
//             // ]);
//             // Log::info('DEAL TEST', [

//             //     'BitrixDealService::getDealId' => $currentDeal,

//             // ]);
//             return $currentDepartment;
//         } catch (\Throwable $th) {
//             return $currentDepartment;
//         }
//     }

// }

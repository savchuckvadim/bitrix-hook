<?php

namespace App\Http\Controllers\MigrateCRM;

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Front\EventCalling\FullEventInitController;
use App\Http\Controllers\InstallHelpers\GoogleInstallController;
use App\Http\Controllers\PortalController;
use App\Services\BitrixGeneralService;
use App\Services\General\BitrixDepartamentService;
use Illuminate\Support\Facades\Log;

class MigrateCRMController extends Controller
{

    protected $token;
    protected $domain;
    protected $hook;
    protected $portal;
    public function __construct(
        $token, $domain

    ) {

        $this->token = $token;
        $this->domain = $domain;
        
        $portal = PortalController::getPortal($domain);
        $this->portal = $portal['data'];
        $this->hook = PortalController::getHook($domain);
    }

    public function crm()
    {
        $result = null;
        $clients = [];
        try {
            
            $googleData = GoogleInstallController::getData($this->token);

            if (!empty($googleData)) {
                if (!empty($googleData['infoblocks'])) {
                    $clients = $googleData['clients'];
                }
            }

            if (!empty($clients)) {


                foreach ($clients as $index => $client) {
                    if ($index <= 3) {

                        $fullDepartment = $this->getFullDepartment();
                        $userId = 201;
                        if(!empty($fullDepartment)){
                            if(!empty($fullDepartment['allUsers'])){
                            foreach ($fullDepartment['allUsers'] as $user) {
                                if (strpos($client['assigned'], $user['LAST_NAME']) !== false) {
                                    $userId = $user['ID'];
                                }
                            }
                            } 
                        }
                        $newClientData = [
                            'TITLE' => $client['name'],
                            'UF_CRM_OP_WORK_STATUS' => $client['name'],
                            'UF_CRM_OP_PROSPECTS_TYPE' => $client['name'],
                            'UF_CRM_OP_CLIENT_STATUS' => $client['name'], //ЧОК ОК
                            'UF_CRM_OP_SMART_LID' => $client['name'], // сюда записывать id из старой crm
                            'UF_CRM_OP_CONCURENTS' => $client['name'],  // конкуренты
                            'UF_CRM_OP_CATEGORY' => $client['name'],  // ККК ..
                            'UF_CRM_OP_WORK_STATUS' => $client['name'],
                            'ASSIGNED_BY_ID' =>  $userId,
                            'ADDRESS' => $client['name'],
                        ];

                        $newCompany = BitrixGeneralService::setEntity(
                            $this->hook,
                            'company',
                            $newClientData
                        );

                        Log::channel()->info('TEST CRM MIGRATE', [
                            'newCompany' => $newCompany
                        ]);
                    }
                }
            }


            return APIOnlineController::getError(
                'infoblocks not found',
                ['clients' => $clients, 'googleData' => $googleData]
            );
        } catch (\Throwable $th) {
            return APIOnlineController::getError(
                $th->getMessage(),
                null
            );
        }
    }

    public function getWorkstatusFieldItemValue(
        $portalField, //with items
        $workStatus,
        $planEventType // only PLAN ! event type
    ) {
        $resultItemBtxId = null;
        //         inJob
        // setAside
        // success
        // fail
        // op_work_status

        // В работе	work
        // Отложена	long

        // В решении	in_progress
        // В оплате	money_await
        // Продажа	    op_status_success
        // Отказ	    op_status_fail
        $resultCode = 'work';
        switch ($workStatus) {
            case 'inJob':
                $resultCode = 'work';

                if ($planEventType == 'hot') {
                    $resultCode = 'in_progress';
                } else  if ($planEventType == 'moneyAwait') {
                    $resultCode = 'money_await';
                }


                break;
            case 'setAside': //in_long
                $resultCode = 'long';
                break;
            case 'fail':
                $resultCode = 'op_status_fail';
                break;
            case 'success':
                $resultCode = 'op_status_success';
                break;
            default:
                break;
        }


        if (!empty($portalField)) {
            if (!empty($portalField['items'])) {
                $pitems = $portalField['items'];
                foreach ($pitems as $pitem) {
                    if (!empty($pitem['code'])) {
                        if ($pitem['code'] == $resultCode) {
                            $resultItemBtxId = $pitem['bitrixId'];
                        }
                    }
                }
            }
        }
        // Log::channel('telegram')->info('HOOK TEST getWorkstatusFieldItemValue', [
        //     'resultCode' => $resultCode,
        //     'planEventType' => $planEventType,
        //     'workStatus' => $workStatus,
        //     'resultItemBtxId' => $resultItemBtxId,
        // ]);
        return $resultItemBtxId;
    }


    public function getProspectsFieldItemValue(
        $portalField, //with items
        $workStatus,
        $failType
    ) {
        $resultItemBtxId = null;
        //         inJob
        // setAside
        // success
        // fail


        // {id: 0, code: "op_prospects_good", name: "Перспективная", isActive: false} 
        // {id: 1, code: "op_prospects_good", name: "Нет перспектив", isActive: false} 
        // {id: 2, code: "garant", name: "Гарант/Запрет", isActive: true} 
        // {id: 3, code: "go", name: "Покупает ГО", isActive: true} 
        // {id: 4, code: "territory", name: "Чужая территория", isActive: true} 
        // {id: 5, code: "accountant", name: "Бухприх", isActive: true} 
        // {id: 6, code: "autsorc", name: "Аутсорсинг", isActive: true} 
        // {id: 7, code: "depend", name: "Несамостоятельная организация", isActive: true} 
        // {id: 8, code: "op_prospects_nophone", name: "Недозвон", isActive: true}
        // {id: 9, code: "op_prospects_company", name: "Компания не существует", isActive: true}
        // {id: 10, code: "failure", name: "Отказ", isActive: true}


        // Перспективность	op_prospects_type	Перспективная	op_prospects_good	calling
        // Нет перспектив	op_prospects_nopersp	calling
        // Гарант/Запрет	op_prospects_garant	calling
        // Покупает ГО	op_prospects_go	calling
        // Чужая территория	op_prospects_territory	calling
        // Бухприх	op_prospects_acountant	calling
        // Аутсорсинг	op_prospects_autsorc	calling
        // Несамостоятельная организация	op_prospects_depend	calling
        // недозвон	op_prospects_nophone	calling
        // компания не существует	op_prospects_company	calling
        // не хотят общаться	op_prospects_off	calling
        // Отказ	op_prospects_fail	calling


        $resultCode = 'op_prospects_good';
        if ($workStatus !== 'inJob' && $workStatus !== 'success' && $workStatus !== 'setAside') {

            if (!empty($failType) && !empty($failType['code'])) {
                $failCode = $failType['code'];

                switch ($failCode) {
                    case 'op_prospects_nopersp': //Нет перспектив
                        $resultCode = 'op_prospects_nopersp';

                        break;
                    case 'garant': //Гарант/Запрет
                        $resultCode = 'op_prospects_garant';
                        break;
                    case 'go':
                        $resultCode = 'op_prospects_go';
                        break;
                    case 'territory':
                        $resultCode = 'op_prospects_territory';
                        break;
                    case 'accountant':
                        $resultCode = 'op_prospects_acountant';
                        break;
                    case 'autsorc':
                        $resultCode = 'op_prospects_autsorc';
                        break;
                    case 'depend':
                        $resultCode = 'op_prospects_depend';
                        break;

                        //todo
                    case 'op_prospects_nophone':  //недозвон
                    case 'op_prospects_company': //компания не существует
                    case 'op_prospects_off': //не хотят общаться
                        $resultCode = $failCode;
                        break;

                    case 'failure':
                        $resultCode = 'op_prospects_fail';

                        break;


                    default:

                        $resultCode = 'op_prospects_good';
                        break;
                }
            }
        }


        if (!empty($portalField)) {
            if (!empty($portalField['items'])) {
                $pitems = $portalField['items'];
                foreach ($pitems as $pitem) {
                    if (!empty($pitem['code'])) {
                        if ($pitem['code'] == $resultCode) {
                            $resultItemBtxId = $pitem['bitrixId'];
                            break;
                        }
                    }
                }
            }
        }

        return $resultItemBtxId;
    }

    protected function getFullDepartment()
    {
        date_default_timezone_set('Europe/Moscow'); // Установка временной зоны
        $currentMonthDay = date('md');
        $result = [];
        $departmentResult = null;
        $generalDepartment = null;

        $childrenDepartments = null;
        $resultGeneralDepartment = [];

        $resultChildrenDepartments = [];
        try {
            //code...

            // записывает в session подготовленную data department по domain

           


            $sessionKey = 'department_' . $this->domain . '_' . $currentMonthDay;
            $sessionData = FullEventInitController::getSessionItem($sessionKey);

            if (!empty($sessionData)) {

                if (!empty($sessionData['department'])) {
                    $result =  $sessionData;
                    $departmentResult = $sessionData['department'];
                    $result['fromSession'] = true;
                }
            }

            if (empty($departmentResult)) {                               // если в сессии нет department
                $departamentService = new BitrixDepartamentService($this->hook);
                $department =  $departamentService->getDepartamentIdByPortal($this->portal);

                $allUsers = [];
                if (!empty($department)) {

                    if (!empty($department['bitrixId'])) {
                        $departmentId =  $department['bitrixId'];


                        if ($departmentId) {
                            $generalDepartment = $departamentService->getDepartments([
                                'ID' =>  $departmentId
                            ]);
                            $childrenDepartments = $departamentService->getDepartments([
                                'PARENT' =>  $departmentId
                            ]);


                            if (!empty($generalDepartment)) {
                                foreach ($generalDepartment as $gDep) {
                                    if (!empty($gDep)) {
                                        if (!empty($gDep['ID'])) {
                                            // array_push($departamentIds, $gDep['ID']);
                                            $departmentUsers = $departamentService->getUsersByDepartment($gDep['ID']);

                                            $resultDep = $gDep;
                                            $resultDep['USERS'] = $departmentUsers;
                                            $allUsers = array_merge($allUsers, $departmentUsers);
                                            array_push($resultGeneralDepartment, $resultDep);
                                        }
                                    }
                                }
                            }

                            if (!empty($childrenDepartments)) {
                                foreach ($childrenDepartments as $chDep) {
                                    if (!empty($chDep)) {
                                        if (!empty($chDep['ID'])) {
                                            // array_push($departamentIds, $chDep['ID']);
                                            $departmentUsers  = $departamentService->getUsersByDepartment($chDep['ID']);
                                            $resultDep = $gDep;
                                            $resultDep['USERS'] = $departmentUsers;

                                            $allUsers = array_merge($allUsers, $departmentUsers);
                                            array_push($resultChildrenDepartments, $resultDep);
                                        }
                                    }
                                }
                            }
                        }
                        $departmentResult = [
                            'generalDepartment' => $resultGeneralDepartment,
                            'childrenDepartments' => $resultChildrenDepartments,
                            'allUsers' => $allUsers,
                        ];
                        $result =  ['department' => $departmentResult];
                        FullEventInitController::setSessionItem(
                            $sessionKey,
                            $result
                        );
                    }
                }
            }


            return $result;
        } catch (\Throwable $th) {
            return null;
        }
    }
}

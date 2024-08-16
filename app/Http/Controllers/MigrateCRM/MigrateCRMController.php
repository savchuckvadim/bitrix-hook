<?php

namespace App\Http\Controllers\MigrateCRM;

use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Front\EventCalling\FullEventInitController;
use App\Http\Controllers\InstallHelpers\GoogleInstallController;
use App\Http\Controllers\PortalController;
use App\Jobs\BtxCreateListItemJob;
use App\Services\BitrixGeneralService;
use App\Services\General\BitrixDepartamentService;
use App\Services\HookFlow\BitrixListDocumentFlowService;
use App\Services\HookFlow\BitrixListFlowService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MigrateCRMController extends Controller
{

    protected $token;
    protected $domain;
    protected $hook;
    protected $portal;
    protected $portalBxLists;
    protected $portalBxCompany;

    public function __construct(
        $token,
        $domain

    ) {

        $this->token = $token;
        $this->domain = $domain;

        $portal = PortalController::getPortal($domain);
        $this->portal = $portal['data'];
        $this->hook = PortalController::getHook($domain);
        $this->portalBxLists = $this->portal['bitrixLists'];
        $this->portalBxCompany  = $this->portal['company'];
    }

    public function crm()
    {
        $result = null;
        $clients = [];
        $results = [];
        $googleData = null;
        try {

            $googleData = GoogleInstallController::getData($this->token);

            if (!empty($googleData)) {
                if (!empty($googleData['clients'])) {
                    $clients = $googleData['clients'];
                }
            }

            if (!empty($clients)) {


                foreach ($clients as $index => $client) {
                    // if ($index <= 15) {

                    $fullDepartment = $this->getFullDepartment();
                    $userId = 13; //201 - man savchuk in rostov
                    if (!empty($fullDepartment)) {
                        if (!empty($fullDepartment['allUsers'])) {
                            foreach ($fullDepartment['allUsers'] as $user) {
                                if (strpos($client['assigned'], $user['LAST_NAME']) !== false) {
                                    $userId = $user['ID'];
                                }
                            }
                        }
                    }
                    $perspekt = $this->getCompanyPerspect($client['perspect']);
                    $concurent = $this->getCompanyConcurent($client['concurent']);
                    $statusk = $this->getCompanyStatus($client['statusk']);
                    $category = $this->getCompanyCategory($client['category']);
                    $prognoz = $this->getCompanyPrognoz($client['prognoz']);

                    $contacts = $this->getContactsField($client['contacts']);
                    // $history = $this->getHistoryField($client['events']);


                    $workStatus = $this->getCompanyWorkStatust($client['perspect']);
                    $workResult = $this->getCompanyItemFromName($client['perspect'], 'op_work_result');
                    $source = $this->getCompanyItemFromName($client['source'], 'op_source_select');


                    $newClientData = [
                        'TITLE' => $client['name'],
                        // 'UF_CRM_OP_WORK_STATUS' => $client['name'],
                        'UF_CRM_OP_PROSPECTS_TYPE' => $perspekt['UF_CRM_OP_PROSPECTS_TYPE'],
                        'UF_CRM_OP_CLIENT_STATUS' => $statusk['UF_CRM_OP_CLIENT_STATUS'], //ЧОК ОК
                        'UF_CRM_OP_SMART_LID' => $client['id'], // сюда записывать id из старой crm
                        'UF_CRM_OP_CONCURENTS' => $concurent['UF_CRM_OP_CONCURENTS'], // конкуренты

                        'UF_CRM_OP_CATEGORY' => $category['UF_CRM_OP_CATEGORY'],  // ККК ..
                        'UF_CRM_OP_CURRENT_STATUS' => $client['perspect'],
                        'UF_CRM_OP_WORK_STATUS' => $workStatus['UF_CRM_OP_WORK_STATUS'],

                        'UF_CRM_OP_PROSPECTS' => $prognoz['UF_CRM_OP_PROSPECTS'],
                        'UF_CRM_OP_CONTACTS' => $contacts['UF_CRM_OP_CONTACTS'],
                        'UF_CRM_OP_HISTORY' =>  $client['commaent'],
                        'COMMENT' =>  $client['commaent'],
                        // 'UF_CRM_OP_MHISTORY' =>  $history['UF_CRM_OP_MHISTORY'],

                        //new
                        'UF_CRM_OP_WORK_RESULT' =>  $workResult['UF_CRM_OP_WORK_RESULT'],
                        'UF_CRM_OP_WORK_RESULT_STRING' =>  $client['perspect'],
                        'UF_CRM_OP_SOURCE_SELECT' =>  $source['UF_CRM_OP_SOURCE_SELECT'],
                        'UF_CRM_CLIENT_SOURCE' =>  $client['source'],
                        'ASSIGNED_BY_ID' =>  $userId,
                        'ADDRESS' => $client['adress'],
                    ];
                    $rand = mt_rand(300000, 1900000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                    usleep($rand);
                    $newCompanyId = BitrixGeneralService::setEntity(
                        $this->hook,
                        'company',
                        $newClientData
                    );

                    $rand = mt_rand(300000, 1900000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                    usleep($rand);
                    if (!empty($newCompanyId) && !empty($client['events'])) {
                        // $newCompany = BitrixGeneralService::getEntity(
                        //     $this->hook,
                        //     'company',
                        //     $newCompanyId
                        // );

                        foreach ($client['events'] as $garusEvent) {
                            // $updatedHistoryField = $this->getHistoryField($garusEvent, $newCompany['UF_CRM_OP_MHISTORY']);
                            // sleep(3);
                            // $updtdCompanyWithHistory = BitrixGeneralService::updateEntity(
                            //     $this->hook,
                            //     'company',
                            //     $newCompanyId,
                            //     $updatedHistoryField
                            // );
                            $rand = mt_rand(300000, 900000); // случайное число от 300000 до 900000 микросекунд (0.3 - 0.9 секунды)
                            usleep($rand);
                            $this->getListFlow($garusEvent, $newCompanyId, $userId);
                        }
                    }

                    // }
                }
            }


            return APIOnlineController::getError(
                'infoblocks not found',
                ['clients' => $results]
            );
        } catch (\Throwable $th) {
            $errorMessages =  [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ];
            Log::error('ERROR COLD APIBitrixController: Exception caught',  $errorMessages);
            Log::info('error COLD APIBitrixController', ['error' => $th->getMessage()]);

            return APIOnlineController::getError(
                $th->getMessage(),
                [
                    // 'portal' => $this->portal,
                    // 'hook' => $this->hook,
                    // 'portalBxLists' => $this->portalBxLists,


                    'portalBxCompany' => $this->portalBxCompany,
                    'googleData' => $googleData,
                ]
            );
        }
    }

    protected function  getContactsField($contacts) //contacts
    {
        // op_contacts

        $pFields =  $this->portalBxCompany['bitrixfields'];
        $result = null;
        $pFieldBxId = null;
        $resultValue = [];
        foreach ($pFields as $pField) {
            if ($pField['code'] === 'op_contacts') {

                $pFieldBxId = 'UF_CRM_' . $pField['bitrixId'];
                $result = [$pFieldBxId => $resultValue];
            }
        }

        foreach ($contacts as $contact) {
            $resultContactstring = $contact['name'] . ' ' . $contact['position'] . ' ' . $contact['telefon'];
            if (!empty($contact['dobTel']) && $contact['dobTel'] !== '-' && $contact['dobTel'] !== 'NULL'  && $contact['dobTel'] !== "\"NULL\"") {
                $resultContactstring = $resultContactstring . ' доб: ' . $contact['dobTel'];
            }
            if (!empty($contact['email']) && $contact['email'] !== '-' && $contact['email'] !== 'NULL'  && $contact['email'] !== "\"NULL\"") {
                $resultContactstring = $resultContactstring . "\n " . 'email: ' . $contact['email'];
            }
            if (!empty($contact['comment'])  && $contact['comment'] !== '-' && $contact['comment'] !== 'NULL'  && $contact['comment'] !== "\"NULL\"") {
                $resultContactstring = $resultContactstring . " \n" . '' . $contact['comment'];
            }
            if (!empty($contact['isLpr']) && $contact['isLpr'] !== '-' && $contact['isLpr'] !== 'NULL'  && $contact['isLpr'] !== "\"NULL\"") {
                $resultContactstring = $resultContactstring . " \n" . 'ЛПР';
            }
            if (!empty($resultContactstring)) {
                array_push($resultValue, $resultContactstring);
            }
        }
        $result = [$pFieldBxId => $resultValue];

        return $result;
    }
    protected function  getHistoryField($event, $currentFieldValue) //events
    {
        //    ОП История (Комментарии)	general	multiple		op_mhistory

        $pFields =  $this->portalBxCompany['bitrixfields'];
        $result = null;
        $pFieldBxId = null;
        $resultValue = $currentFieldValue;
        foreach ($pFields as $pField) {
            if ($pField['code'] === 'op_mhistory') {
                $pFieldBxId = 'UF_CRM_' . $pField['bitrixId'];
                $result = [$pFieldBxId => null];
            }
        }

        // foreach ($events as $event) {

        $date = $this->getDateTimeValue($event['date'], $event['time']);
        $eventValue = $date . ' ' . $event['eventType'];
        if ($event['comment'] !== "" &&  $event['comment'] !== null && $event['comment'] !== "NULL"  && $event['comment'] !== "-") {

            $eventValue = $eventValue . "\n " . $event['comment'];
        }

        if ($event['planComment'] !== "" &&  $event['planComment'] !== null && $event['planComment'] !== "NULL"  && $event['planComment'] !== "-") {

            $eventValue = $eventValue . "\n " . "План: " . $event['planComment'];
        }
        if ($event['contact'] !== "" &&  $event['contact'] !== null && $event['contact'] !== "NULL"  && $event['contact'] !== "-") {

            $eventValue = $eventValue . "\n " . "Контакт: " . $event['contact'];
        }

        array_push($resultValue, $eventValue);
        // }

        $result = [$pFieldBxId => $resultValue];
        return  $result;
    }
    protected function getCompanyWorkStatust($garusResultat)
    {

        $pFields =  $this->portalBxCompany['bitrixfields'];
        $result = null;
        foreach ($pFields as $pField) {
            if ($pField['code'] === 'op_work_status') {
                $result = ['UF_CRM_' . $pField['bitrixId'] => null];

                if (!empty($pField['items'])) {
                    foreach ($pField['items'] as $pItem) {
                        if ($garusResultat == $pItem['name']) {
                        }
                        switch ($garusResultat) {
                            case 'Отказ':
                            case 'Гарант/ Запрет':
                            case 'Конкурент':
                            case 'Неверный контакт':
                            case 'Нет финансирования':
                            case 'Нет_ перспектив':
                            case 'Пересечение':
                            case 'Покупает ГО':
                            case 'Потерявшиеся/Закрывшиеся':
                            case 'ХВА':
                            case 'чужая территория':
                                if ($pItem['code'] === 'op_status_fail') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'Клиенты':
                            case 'Пользователи':
                            case 'Должники':

                                if ($pItem['code'] === 'op_status_success') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'Чердак':
                                if ($pItem['code'] === 'long') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;


                            default:
                                if ($pItem['code'] === 'work') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }


                                break;
                        }
                    }
                }
            }
        }

        return  $result;
    }
    protected function getCompanyItemFromName($garusResultat, $fieldCode) //resulotat and source
    {

        $pFields =  $this->portalBxCompany['bitrixfields'];
        $result = null;
        foreach ($pFields as $pField) {

            if ($pField['code'] === $fieldCode) {
                $result = ['UF_CRM_' . $pField['bitrixId'] => null];

                if (!empty($pField['items'])) {
                    foreach ($pField['items'] as $pItem) {
                        $normalizedGarusResultat = $this->normalizeString($garusResultat);
                        $normalizedPItemName = $this->normalizeString($pItem['name']);

                        if ($normalizedGarusResultat == $normalizedPItemName) {

                            $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                        }
                    }
                }
            }
        }

        return  $result;
    }

    protected function normalizeString($string)
    {
        // Приведение к нижнему регистру
        $string = mb_strtolower($string);


        // Удаление пробелов и спецсимволов, оставляя буквы и цифры (как кириллицу, так и латиницу)
        $string = preg_replace('/[^\p{L}\p{N}]/u', '', $string);

        return $string;
    }
    protected function getCompanyConcurent($garusConcurent)
    {

        $pFields =  $this->portalBxCompany['bitrixfields'];
        $result = null;
        foreach ($pFields as $pField) {
            if ($pField['code'] === 'op_concurents') {

                $pFieldBxId = 'UF_CRM_' . $pField['bitrixId'];
                $result = [$pFieldBxId => null];
                // k
                // action
                // kodex
                // bitrix
                // kontur
                // internet
                // magazine
                if (!empty($pField['items'])) {
                    foreach ($pField['items'] as $pItem) {
                        switch ($garusConcurent) {
                            case 'К+':
                                if ($pItem['code'] === 'k') {
                                    $result = [$pFieldBxId => $pItem['bitrixId']];
                                }
                                break;
                            case 'Актион':
                                if ($pItem['code'] === 'action') {
                                    $result = [$pFieldBxId => $pItem['bitrixId']];
                                }
                                break;
                            case 'Кодекс':
                                if ($pItem['code'] === 'kodex') {
                                    $result = [$pFieldBxId => $pItem['bitrixId']];
                                }
                                break;

                            case '1С':
                                if ($pItem['code'] === 'bitrix') {
                                    $result = [$pFieldBxId => $pItem['bitrixId']];
                                }
                                break;

                            case 'Контур':
                                if ($pItem['code'] === 'kontur') {
                                    $result = [$pFieldBxId => $pItem['bitrixId']];
                                }
                                break;
                            case 'Интернет':
                                if ($pItem['code'] === 'internet') {
                                    $result = [$pFieldBxId => $pItem['bitrixId']];
                                }
                                break;

                            case 'Журналы':
                                if ($pItem['code'] === 'magazine') {
                                    $result = [$pFieldBxId => $pItem['bitrixId']];
                                }
                                break;

                            default:
                                $result = [$pFieldBxId => null];

                                break;
                        }
                    }
                }
            }
        }

        return  $result;
    }
    protected function getCompanyCategory($garusCategory)
    {

        $pFields =  $this->portalBxCompany['bitrixfields'];
        $result =  null;
        foreach ($pFields as $pField) {
            if ($pField['code'] === 'op_category') {
                $pFieldBxId = 'UF_CRM_' . $pField['bitrixId'];
                $result = [$pFieldBxId => null];
                // kkk
                // kk
                // vip
                // k
                // c
                if (!empty($pField['items'])) {
                    foreach ($pField['items'] as $pItem) {
                        switch ($garusCategory) {
                            case 'ККК':
                                if ($pItem['code'] === 'kkk') {
                                    $result = [$pFieldBxId  => $pItem['bitrixId']];
                                }
                                break;
                            case 'КК':
                                if ($pItem['code'] === 'kk') {
                                    $result = [$pFieldBxId => $pItem['bitrixId']];
                                }
                                break;
                            case 'VIP':
                                if ($pItem['code'] === 'vip') {
                                    $result = [$pFieldBxId => $pItem['bitrixId']];
                                }
                                break;

                            case 'К':
                                if ($pItem['code'] === 'k') {
                                    $result = [$pFieldBxId  => $pItem['bitrixId']];
                                }
                                break;

                            case 'С':
                                if ($pItem['code'] === 'c') {
                                    $result = [$pFieldBxId  => $pItem['bitrixId']];
                                }
                                break;
                            case 'М':
                                if ($pItem['code'] === 'm') {
                                    $result = [$pFieldBxId  => $pItem['bitrixId']];
                                }
                                break;


                            default:
                                $result = [$pFieldBxId  => null];

                                break;
                        }
                    }
                }
            }
        }

        return $result;
    }
    protected function getCompanyPrognoz($garusPrognoz)
    {

        $pFields =  $this->portalBxCompany['bitrixfields'];
        $result =  null;
        foreach ($pFields as $pField) {
            if ($pField['code'] === 'op_prospects') {
                // op_prospects	Красный	red
                // op_prospects	Желтый	yellow
                // op_prospects	Зеленый	green


                if (!empty($pField['items'])) {
                    foreach ($pField['items'] as $pItem) {
                        switch ($garusPrognoz) {
                            case 'красный':
                                if ($pItem['code'] === 'red') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'желтый':
                                if ($pItem['code'] === 'yellow') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'зеленый':
                            case 'зёленый':
                                if ($pItem['code'] === 'green') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;

                            default:
                                $result = ['UF_CRM_' . $pField['bitrixId'] => null];

                                break;
                        }
                    }
                }
            }
        }

        return $result;
    }
    protected function getCompanyStatus($garusConcurent)
    {

        $pFields =  $this->portalBxCompany['bitrixfields'];
        $result = null;
        foreach ($pFields as $pField) {
            if ($pField['code'] === 'op_client_status') {
                // free
                // chok
                // nok
                // ok
                // stranger_kup
                // stranger_kupkk
                // stranger_kgurp
                // own_kup
                // own_kupkk
                // own_kgurp
                if (!empty($pField['items'])) {
                    foreach ($pField['items'] as $pItem) {
                        switch ($garusConcurent) {
                            case 'ЧОК':
                                if ($pItem['code'] === 'chok') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'НОК':
                                if ($pItem['code'] === 'nok') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'ОК':
                                if ($pItem['code'] === 'ok') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;

                            case 'Чужой КУП':
                                if ($pItem['code'] === 'stranger_kup') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'Чужой КУП КК':
                                if ($pItem['code'] === 'stranger_kupkk') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'Чужой КГУ РП':
                                if ($pItem['code'] === 'stranger_kgurp') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }

                                break;
                            case 'Свой КУП':
                                if ($pItem['code'] === 'own_kup') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }

                                break;
                            case 'Свой КУП КК':
                                if ($pItem['code'] === 'own_kupkk') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }

                                break;
                            case 'Свой КГУ РП':
                                if ($pItem['code'] === 'own_kgurp') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }

                                break;
                            default:
                                if ($pItem['code'] === 'free') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                        }
                    }
                }
            }
        }

        return  $result;
    }
    protected function getCompanyPerspect($garusFailReasone)
    {

        $pFields =  $this->portalBxCompany['bitrixfields'];

        $result = null;
        foreach ($pFields as $pField) {
            if ($pField['code'] === 'op_prospects_type') {
                // op_prospects_good
                // op_prospects_nopersp
                // op_prospects_garant
                // op_prospects_go
                // op_prospects_territory
                // op_prospects_acountant
                // op_prospects_autsorc
                // op_prospects_depend
                // op_prospects_nophone
                // op_prospects_company
                // op_prospects_fail
                if (!empty($pField['items'])) {
                    foreach ($pField['items'] as $pItem) {
                        switch ($garusFailReasone) {
                            case 'Гарант/Запрет':
                                if ($pItem['code'] === 'op_prospects_garant') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'Нет перспектив':
                                if ($pItem['code'] === 'op_prospects_nopersp') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'Покупает ГО':
                                if ($pItem['code'] === 'op_prospects_go') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;

                            case 'Чужая территория':
                                if ($pItem['code'] === 'op_prospects_territory') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                            case 'Отказ':
                                if ($pItem['code'] === 'op_prospects_fail') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;

                            default:
                                if ($pItem['code'] === 'op_prospects_good') {
                                    $result = ['UF_CRM_' . $pField['bitrixId'] => $pItem['bitrixId']];
                                }
                                break;
                        }
                    }
                }
            }
        }
        return   $result;
    }


    /**
     * LIST
     */
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



    protected function getDateTimeValue($dateValue, $timeValue)
    {

        $date = Carbon::parse($dateValue);
        $time = Carbon::parse($timeValue);
        // Объединяем дату и время
        $datetime = Carbon::create(
            $date->year,
            $date->month,
            $date->day,
            $time->hour,
            $time->minute,
            $time->second
        );
        $formattedDatetime = $datetime->format('d.m.Y H:i:s');

        return $formattedDatetime;
    }


    protected function  getListFlowData($event, $companyId) //events
    {
        $flowdata = null;
        $hook = $this->hook;
        $bitrixLists = $this->portalBxLists;
        // $eventType, // xo warm presentation, offer invoice
        // $eventTypeName, //звонок по решению по оплате
        // $eventAction,  // plan done //если будет репорт и при этом не было переноса придет done или nodone - типа состоялся или нет
        // // $eventName,
        // $deadline,
        // $created,
        // $responsible,
        // $suresponsible,
        // $companyId,
        // $comment,
        // $workStatus, //inJob
        // $resultStatus,  // result noresult   .. without expired new !
        // $noresultReason,
        // $failReason,
        // $failType,
        // $dealIds,
        // $currentBaseDealId

        /**
         * FOR DOCUMENT FLOW
         */

        //  $eventType, // ev_invoice,  ev_offer_pres ....

        //  // Коммерческое Предлжение	event_type	ev_offer	EV_OFFER
        //  // Счет	event_type	ev_invoice	EV_INVOICE
        //  // Коммерческое Предлжение после презентации	event_type	ev_offer_pres	EV_OFFER_PRES
        //  // Счет после презентации	event_type	ev_invoice_pres	EV_INVOICE_PRES
        //  // Договор	event_type	ev_contract	EV_CONTRACT
        //  // Поставка	event_type	ev_supply	EV_SUPPLY
        //  $eventTypeName, //Коммерческое Предлжение   Счет после презентации Поставка


        //  $eventAction,  // 
        //  // Отправлен	event_action	act_send	ACT_SEND
        //  // Подписан	event_action	act_sign	ACT_SIGN
        //  // Оплачен	event_action	act_pay	ACT_PAY
        //  // $nowDate,
        //  $created,
        //  $responsible,
        //  $suresponsible,
        //  $companyId,
        //  $comment,
        //  $dealIds,
        //  $currentBaseDealId = null

        return $flowdata;
    }

    protected function getListFlow($event, $companyId, $responsibleId)
    {

        $resultEventType = 'warm';
        $resultAction = 'done';
        $isDocumentFlow = false;
        $resultStatus = 'result';
        $workStatus = ['code' => 'inJob']; //setAside fail
        $noresultReason = '';
        $failReason = '';
        $nowDate = '';

        $date = $this->getDateTimeValue($event['date'], $event['time']);
        $comment = $event['comment'];


        if ($event['planComment'] !== "" &&  $event['planComment'] !== null && $event['planComment'] !== "NULL"  && $event['planComment'] !== "-") {

            $comment = $comment . "\n " . "План: " . $event['planComment'];
        }
        if ($event['contact'] !== "" &&  $event['contact'] !== null && $event['contact'] !== "NULL"  && $event['contact'] !== "-") {

            $comment = $comment . "\n " . "Контакт: " . $event['contact'];
        }

        $normalizedGarusResultat = $this->normalizeString($event['eventType']);
        $actions = [
            $this->normalizeString('Звонок') => 'call',
            $this->normalizeString('Пред.договоренность') => 'plan',
            $this->normalizeString('Заявка на презу') => 'presentation',
            $this->normalizeString('Дист. заявка') => 'presentation',
            $this->normalizeString('Тлф.Заявка') => 'presentation',

        ];
        $normalizedGarusResultat = $this->normalizeString($event['eventType']);

        $switchCases = [
            'plan' => [
                'cases' => [
                    $this->normalizeString('Пред.договоренность'),
                    $this->normalizeString('Заявка на презу'),
                    $this->normalizeString('Дист. заявка'),
                    $this->normalizeString('Тлф.Заявка')
                ],
                'action' => 'plan',
                'eventType' => 'presentation'
            ],
            'done_presentation' => [
                'cases' => [
                    $this->normalizeString('Презентация')
                ],
                'action' => 'done',
                'eventType' => 'presentation'
            ],
            'expired' => [
                'cases' => [
                    $this->normalizeString('Перенос'),
                    $this->normalizeString('Повтор')
                ],
                'status' => 'expired'
            ],
            'act_send_offer' => [
                'cases' => [
                    $this->normalizeString('Отправлено КП')
                ],
                'action' => 'act_send',
                'eventType' => 'ev_offer_pres',
                'isDocumentFlow' => true
            ],
            'act_send_contract' => [
                'cases' => [
                    $this->normalizeString('Отправлен договор')
                ],
                'action' => 'act_send',
                'eventType' => 'ev_contract',
                'isDocumentFlow' => true
            ],
            'act_send_invoice' => [
                'cases' => [
                    $this->normalizeString('Выставлен счет')
                ],
                'action' => 'act_send',
                'eventType' => 'ev_invoice_pres',
                'isDocumentFlow' => true
            ],
            'act_sign_contract' => [
                'cases' => [
                    $this->normalizeString('Договор подписан')
                ],
                'action' => 'act_sign',
                'eventType' => 'ev_contract',
                'isDocumentFlow' => true
            ],
            'act_pay_invoice' => [
                'cases' => [
                    $this->normalizeString('Счет оплачен')
                ],
                'action' => 'act_pay',
                'eventType' => 'ev_invoice_pres',
                'isDocumentFlow' => true
            ],
            'done_supply' => [
                'cases' => [
                    $this->normalizeString('Поставка')
                ],
                'action' => 'done',
                'eventType' => 'ev_supply',
                'isDocumentFlow' => true
            ],
            'cancel' => [
                'cases' => [
                    $this->normalizeString('Отмена')
                ],
                'status' => 'nodone',
                'resultStatus' => 'noresult'
            ]
        ];

        foreach ($switchCases as $case) {
            if (in_array($normalizedGarusResultat, $case['cases'])) {
                if (isset($case['action'])) {
                    $resultAction = $case['action'];
                }
                if (isset($case['eventType'])) {
                    $resultEventType = $case['eventType'];
                }
                if (isset($case['status'])) {
                    $resultStatus = $case['status'];
                }
                if (isset($case['isDocumentFlow'])) {
                    $isDocumentFlow = $case['isDocumentFlow'];
                }
                break;
            }
        }


        if ($isDocumentFlow) {
            // BitrixListDocumentFlowService::getListsFlow(  //report - отчет по текущему событию
            //     $this->hook,
            //     $this->portalBxLists,
            //     $resultEventType,
            //     $event['eventType'],
            //     'act_send',  // сделано, отправлено
            //     $responsibleId,
            //     $responsibleId,
            //     $responsibleId,
            //     $companyId,
            //     $comment,
            //     null, // $currentBxDealIds,
            //     null, //  $this->currentBaseDeal['ID']
            //     $date,
            //     $event['eventType']


            // );
        } else {
            BitrixListFlowService::getListsFlow(  //report - отчет по текущему событию
                $this->hook,
                $this->portalBxLists,
                $resultEventType,
                $event['eventType'],
                $resultAction,
                // $this->stringType,
                '', //$this->planDeadline,
                $responsibleId,
                $responsibleId,
                $responsibleId,
                $companyId,
                $comment,
                $workStatus,
                $resultStatus, // result noresult expired,
                $noresultReason,
                $failReason,
                '', // $failType,
                '', // $currentDealIds,
                '', // $currentBaseDealId
                $date,
                $event['eventType'] //$hotName

            );
        }
    }
}

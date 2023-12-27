<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class APIBitrixController extends Controller
{
    public static function createTask(
        $domain,
        $companyId,
        $createdId,
        $responsibleId,
        $deadline,
        $name,
        $crm
    ) {
        $portal = PortalController::getPortal($domain);
        Log::info('portal', ['portal' => $portal]);
        try {
            $portal = $portal['data'];
            Log::info('portalData', ['portal' => $portal]);
            $webhookRestKey = $portal['C_REST_WEB_HOOK_URL'];
            $hook = 'https://' . $domain  . '/' . $webhookRestKey;


            //company and contacts
            $methodContacts = '/crm.contact.list.json';
            $methodCompany = '/crm.company.get.json';
            $url = $hook . $methodContacts;
            $contactsData =  [
                'FILTER' => [
                    'COMPANY_ID' => $companyId,

                ],
                'select' => ["ID", "NAME", "LAST_NAME", "SECOND_NAME", "TYPE_ID", "SOURCE_ID", "PHONE", "EMAIL", "COMMENTS"],
            ];
            $getCompanyData = [
                'ID'  => $companyId,
                'select' => ["TITLE"],
            ];

            $contacts = Http::get($url,  $contactsData);
            $url = $hook . $methodCompany;
            $company = Http::get($url,  $getCompanyData);

            $contactsString = '';

            foreach ($contacts['result'] as  $contact) {
                $contactPhones = '';
                foreach ($contact["PHONE"] as $phone) {
                    $contactPhones = $contactPhones .  $phone["VALUE"] . "   ";
                }
                $contactsString = $contactsString . "<p>" . $contact["NAME"] . " " . $contact["SECOND_NAME"] . " " . $contact["SECOND_NAME"] . "  "  .  $contactPhones . "</p>";
            }

            $companyTitleString = $company['result']['TITLE'];
            $description =  '<p>' . $companyTitleString . '</p>' . '<p> Контакты компании: </p>' . $contactsString;


            //task
            $methodTask = '/tasks.task.add.json';
            $url = $hook . $methodTask;

            $nowDate = now();
            $novosibirskTime = Carbon::createFromFormat('d.m.Y H:i:s', $deadline, 'Asia/Novosibirsk');
            $moscowTime = $novosibirskTime->setTimezone('Europe/Moscow');
            $moscowTime = $moscowTime->format('Y-m-d H:i:s');
            Log::info('novosibirskTime', ['novosibirskTime' => $novosibirskTime]);
            Log::info('moscowTime', ['moscowTime' => $moscowTime]);


            $taskData =  [
                'fields' => [
                    'TITLE' => 'Холодный обзвон  ' . $name . '  ' . $deadline,
                    'RESPONSIBLE_ID' => $responsibleId,
                    'GROUP_ID' => env('BITRIX_CALLING_GROUP_ID'),
                    'CHANGED_BY' => $createdId, //- постановщик;
                    'CREATED_BY' => $createdId, //- постановщик;
                    'CREATED_DATE' => $nowDate, // - дата создания;
                    'DEADLINE' => $moscowTime, //- крайний срок;
                    'UF_CRM_TASK' => ['T9c_' . $crm],
                    'ALLOW_CHANGE_DEADLINE' => 'N',
                    'DESCRIPTION' => $description
                ]
            ];


            $responseData = Http::get($url, $taskData);

            Log::info('SUCCESS RESPONSE TASK', ['createdTask' => $responseData]);
            return APIOnlineController::getResponse(0, 'success', ['createdTask' => $responseData]);
        } catch (\Throwable $th) {
            Log::error('ERROR: Exception caught', [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ]);
            return APIOnlineController::getResponse(1, $th->getMessage(), null);
        }
    }
    public static function createOrUpdateSmart(
        $domain,
        $companyId,
        $createdId,
        $responsibleId,
        $deadline,
        $name,
        $crm
    ) {

        try {
            $portal = PortalController::getPortal($domain,);
            $webhookRestKey = $portal[' C_REST_WEB_HOOK_URL'];
            $hook = 'https://' . $domain  . '/' . $webhookRestKey;


            //company and contacts
            $methodContacts = '/crm.contact.list.json';
            $methodCompany = '/crm.company.get.json';
            $url = $hook . $methodContacts;
            $contactsData =  [
                'FILTER' => [
                    'COMPANY_ID' => $companyId,

                ],
                'select' => ["ID", "NAME", "LAST_NAME", "SECOND_NAME", "TYPE_ID", "SOURCE_ID", "PHONE", "EMAIL", "COMMENTS"],
            ];
            $getCompanyData = [
                'ID'  => $companyId,
                'select' => ["TITLE"],
            ];

            $contacts = Http::get($url,  $contactsData);
            $url = $hook . $methodCompany;
            $company = Http::get($url,  $getCompanyData);

            $contactsString = '';

            foreach ($contacts['result'] as  $contact) {
                $contactPhones = '';
                foreach ($contact["PHONE"] as $phone) {
                    $contactPhones = $contactPhones .  $phone["VALUE"] . "   ";
                }
                $contactsString = $contactsString . "<p>" . $contact["NAME"] . " " . $contact["SECOND_NAME"] . " " . $contact["SECOND_NAME"] . "  "  .  $contactPhones . "</p>";
            }

            $companyTitleString = $company['result']['TITLE'];
            $description =  '<p>' . $companyTitleString . '</p>' . '<p> Контакты компании: </p>' . $contactsString;


            //task
            $methodTask = '/tasks.task.add.json';
            $url = $hook . $methodTask;

            $nowDate = now();
            $novosibirskTime = Carbon::createFromFormat('d.m.Y H:i:s', $deadline, 'Asia/Novosibirsk');
            $moscowTime = $novosibirskTime->setTimezone('Europe/Moscow');
            $moscowTime = $moscowTime->format('Y-m-d H:i:s');
            Log::info('novosibirskTime', ['novosibirskTime' => $novosibirskTime]);
            Log::info('moscowTime', ['moscowTime' => $moscowTime]);


            $taskData =  [
                'fields' => [
                    'TITLE' => 'Холодный обзвон  ' . $name . '  ' . $deadline,
                    'RESPONSIBLE_ID' => $responsibleId,
                    'GROUP_ID' => env('BITRIX_CALLING_GROUP_ID'),
                    'CHANGED_BY' => $createdId, //- постановщик;
                    'CREATED_BY' => $createdId, //- постановщик;
                    'CREATED_DATE' => $nowDate, // - дата создания;
                    'DEADLINE' => $moscowTime, //- крайний срок;
                    'UF_CRM_TASK' => ['T9c_' . $crm],
                    'ALLOW_CHANGE_DEADLINE' => 'N',
                    'DESCRIPTION' => $description
                ]
            ];


            $responseData = Http::get($url, $taskData);

            Log::info('SUCCESS RESPONSE TASK', ['createdTask' => $responseData]);
            return APIOnlineController::getResponse(0, 'success', ['createdTask' => $responseData]);
        } catch (\Throwable $th) {
            Log::error('ERROR: Exception caught', [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ]);
            return APIOnlineController::getResponse(1, $th->getMessage(), null);
        }
    }
    public static function getSmartStages(
        $domain
    ) {
        $portal = PortalController::getPortal($domain);
        Log::info('portal', ['portal' => $portal]);
        try {

            //CATEGORIES
            $webhookRestKey = $portal['data']['C_REST_WEB_HOOK_URL'];
            $hook = 'https://' . $domain  . '/' . $webhookRestKey;

            $methodSmart = '/crm.category.list.json';
            $url = $hook . $methodSmart;
            $entityId = env('APRIL_BITRIX_SMART_MAIN_ID');
            $hookCategoriesData = ['entityTypeId' => $entityId];

            // Возвращение ответа клиенту в формате JSON

            $smartCategoriesResponse = Http::get($url, $hookCategoriesData);
            $bitrixResponse = $smartCategoriesResponse->json();
            Log::info('SUCCESS RESPONSE SMART CATEGORIES', ['categories' => $bitrixResponse]);
            $categories = $smartCategoriesResponse['result']['categories'];

            //STAGES

            foreach ($categories as $category) {
                Log::info('category', ['category' => $category]);
                $hook = 'https://' . $domain  . '/' . $webhookRestKey;
                $stageMethod = '/crm.status.list.json';
                $url = $hook . $stageMethod;
                $hookStagesData = [
                    'entityTypeId' => $entityId,
                    'entityId' => 'STATUS',
                    'categoryId' => $category['id'],
                    'filter' => ['ENTITY_ID' => 'DYNAMIC_' . $entityId . '_STAGE_' . $category['id']]

                ];


                Log::info('hookStagesData', ['hookStagesData' => $hookStagesData]);
                $stagesResponse = Http::get($url, $hookStagesData);
                $stages = $stagesResponse['result'];
                Log::info('stages', ['stages' => $stages]);
                foreach ($stages as $stage) {
                    $resultstageData = [
                        // 'category' => [
                        //     'id' => $category['id'],
                        //     'name' => $category['name'],
                        // ],
                        'id' => $stage['ID'],
                        'entityId' => $stage['ENTITY_ID'],
                        'statusId' => $stage['STATUS_ID'],
                        'title' => $stage['NAME'],
                        'nameInit' => $stage['NAME_INIT'],

                    ];
                    Log::info('STAGE', [$stage['NAME'] => $stage]);
                }
            }


            return APIOnlineController::getResponse(0, 'success', ['Smart-Categories' => $bitrixResponse]);
        } catch (\Throwable $th) {
            Log::error('ERROR: Exception caught', [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ]);
            return APIOnlineController::getResponse(1, $th->getMessage(), null);
        }
    }
    public static function installSmart(
        $domain
    ) {
        //1) создает смарт процесс и сам задает  "entityTypeId" => 134,

        //3) записывает стадии и направления ввиде одного объекта json связь portal-smart


        $portal = PortalController::getPortal($domain);
        Log::info('portal', ['portal' => $portal]);
        try {

            //CATEGORIES
            $webhookRestKey = $portal['data']['C_REST_WEB_HOOK_URL'];
            $hook = 'https://' . $domain  . '/' . $webhookRestKey;

            $methodSmartInstall = '/crm.type.add.json';
            $url = $hook . $methodSmartInstall;
            // $entityId = env('APRIL_BITRIX_SMART_MAIN_ID');
            $hookSmartInstallData = [
                'fields' => [
                    'id' => 134,
                    "title" => "TEST Смарт-процесс",
                    "entityTypeId" => 134,
                    'code' => 'april-garant',
                    "isCategoriesEnabled" => "Y",
                    "isStagesEnabled" => "Y",
                    "isClientEnabled" => "Y",
                    "isUseInUserfieldEnabled" => "Y",
                    "isLinkWithProductsEnabled" => "Y",
                    "isAutomationEnabled" => "Y",
                    "isBizProcEnabled" => "Y",
                ]
            ];

            // Возвращение ответа клиенту в формате JSON

            $smartInstallResponse = Http::get($url, $hookSmartInstallData);
            //2) использует "entityTypeId" чтобы создать направления и стадии
            $methodCategoryInstall = '/crm.type.add.json';
            $url = $hook . $methodCategoryInstall;
            $hookCategoriesData1  =
                [
                    "entityTypeId" => 134,
                    'NAME' => 'Холодный обзвон',
                    'fields' => [
                       
                        "isDefault" => "N"
                    ]
                ];
            $hookCategoriesData2  =
                [
                    "entityTypeId" => 134,
                    'name' => 'Продажи',
                    'fields' => [
                     
                        "isDefault" => "Y"
                    ]
                ];
            $smartCategoriesResponse1 = Http::get($url, $hookCategoriesData1);
            $smartCategoriesResponse2 = Http::get($url, $hookCategoriesData2);


            $bitrixResponse = $smartInstallResponse->json();
            $bitrixResponseCategory1 = $smartCategoriesResponse1->json();
            $bitrixResponseCategory2 = $smartCategoriesResponse2->json();
            Log::info('SUCCESS SMART INSTALL', ['smart' => $bitrixResponse]);
            Log::info('SUCCESS CATEGORY INSTALL', ['categories1' => $bitrixResponseCategory1]);
            Log::info('SUCCESS CATEGORY INSTALL', ['categories2' => $bitrixResponseCategory2]);
            //STAGES




            return APIOnlineController::getResponse(0, 'success', ['Smart-Categories' => $bitrixResponse]);
        } catch (\Throwable $th) {
            Log::error('ERROR: Exception caught', [
                'message'   => $th->getMessage(),
                'file'      => $th->getFile(),
                'line'      => $th->getLine(),
                'trace'     => $th->getTraceAsString(),
            ]);
            return APIOnlineController::getResponse(1, $th->getMessage(), null);
        }
    }
}

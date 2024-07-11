<?php

namespace App\Http\Controllers\Front\Calling;

use App\Http\Controllers\APIController;
use App\Http\Controllers\APIOnlineController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PortalController;
use App\Jobs\EventJob;
use App\Services\BitrixGeneralService;
use App\Services\FullEventReport\EventReportService;
use App\Services\General\BitrixDealService;
use App\Services\General\BitrixDepartamentService;
use App\Services\General\BitrixListService;
use App\Services\HookFlow\BitrixEntityFlowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportCompanyController extends Controller
{
    public static function getCompanyForm(Request $request)
    {
        try {
            //             Статус Клиента	op_client_status	Cвободен	free
            // 	op_client_status	ЧОК	chok
            // 	op_client_status	НОК	nok
            // 	op_client_status	ОК	ok
            // 	op_client_status	Чужой КУП	stranger_kup
            // 	op_client_status	Чужой КУП КК	stranger_kupkk
            // 	op_client_status	Чужой КГУ РП	stranger_kgurp
            // 	op_client_status	Свой КУП	own_kup
            // 	op_client_status	Свой КУП КК	own_kupkk
            // 	op_client_status	Свой КГУ РП	own_kgurp
            // Настроение	op_prospects	Красный	red
            // 	op_prospects	Желтый	yellow
            // 	op_prospects	Зеленый	green
            // Тип	op_client_type	Бюджетники	state
            // 	op_client_type	Коммерческие	commerc
            // 	op_client_type	ИП	ip
            // 	op_client_type	Физ лицо	fiz
            // 	op_client_type	Адвокаты	layer
            // Конкуренты	op_concurents	Консультант+	k
            // 	op_concurents	Актион	action
            // 	op_concurents	Кодекс	kodex
            // 	op_concurents	1С	bitrix
            // 	op_concurents	Контур	kontur
            // 	op_concurents	Интернет	internet
            // 	op_concurents	Журналы	magazine
            // Категории	op_category	ККК	kkk
            // 	op_category	КК	kk
            // 	op_category	VIP	vip
            // 	op_category	К	k
            // 	op_category	С	c
            // 	op_category	М	m
            $data = [
                'currentTask' => null,
                'report' => null,
                'plan' => null,
                'placement' => null,
                'presentation' => null,
                'domain' => null,

            ];
            $data = $request->all();
            $isFullData = false;

            // if(!empty($data['domain'] && !empty($data['companyId'])){


            // }
            if ($isFullData) {
                // $service = new EventReportService($data);
                // $result = $service->getEventFlow();
                // return $result;
                dispatch(
                    new EventJob($data)
                )->onQueue('high-priority');

                return APIOnlineController::getSuccess(
                    [
                        'result' => 'success',
                        'message' => 'job !'

                    ]

                );
            } else {

                return APIOnlineController::getError(
                    'is not full data',
                    [
                        'rq' => $request->all()

                    ]

                );
            }
        } catch (\Throwable $th) {
            return APIOnlineController::getError(
                $th->getMessage(),
                [
                    'task' => [
                        'message' => 'success'
                    ],
                    'rq' => $request->all()

                ]

            );
        }
    }
}

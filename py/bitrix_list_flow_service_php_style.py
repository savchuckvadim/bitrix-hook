


from datetime import datetime
import hashlib
import random
import logging

class BitrixListFlowService:

    @staticmethod
    def getBatchListFlow(
        hook,
        bitrixLists,
        eventType,  # xo warm presentation, offer invoice
        eventTypeName,  # звонок по решению по оплате
        eventAction,  # plan done
        # eventName
        deadline,
        created,
        responsible,
        suresponsible,
        companyId,
        comment,
        workStatus,  # inJob
        resultStatus,  # result noresult .. without expired new!
        noresultReason,
        failReason,
        failType,
        dealIds,
        currentBaseDealId,
        nowDate=None,
        hotName=None,
        contactId=None,
        resultBatchCommands=None
    ):
        try:
            if resultBatchCommands is None:
                resultBatchCommands = {}

            if not nowDate:
                nowDate = datetime.now().strftime('%d.%m.%Y %H:%M:%S')

            eventActionName = 'Запланирован'
            evTypeName = 'Звонок'
            nextCommunication = deadline
            isUniqPresPlan = False
            isUniqPresReport = False

            crmValue = {'n0': f'CO_{companyId}'}
            dealIndex = 1
            if contactId:
                dealIndex = 2
                crmValue['n1'] = f'C_{contactId}'

            if dealIds:
                for key, dealId in enumerate(dealIds):
                    crmValue[f'n{key + dealIndex}'] = f'D_{dealId}'

            if eventType in ['xo', 'cold']:
                evTypeName = 'Холодный звонок'
            elif eventType in ['warm', 'call', 'supply']:
                evTypeName = 'Звонок'
            elif eventType == 'presentation':
                evTypeName = 'Презентация'
                eventActionName = 'Запланирована'
            elif eventType in ['hot', 'inProgress', 'in_progress']:
                evTypeName = 'Звонок по решению'
            elif eventType in ['money', 'moneyAwait', 'money_await']:
                evTypeName = 'Звонок по оплате'

            if eventAction == 'expired':
                eventAction = 'pound'
                eventActionName = 'Перенос'
            elif eventAction == 'done':
                eventActionName = 'Состоялся'
                if eventType == 'presentation':
                    eventActionName = 'Состоялась'
                    isUniqPresReport = True
            elif eventAction == 'plan':
                if eventType == 'presentation':
                    isUniqPresPlan = True
            elif eventAction == 'nodone':
                nextCommunication = None
                eventActionName = 'Не Состоялся'
                eventAction = 'act_noresult_fail'
                if eventType == 'presentation':
                    eventActionName = 'Не Состоялась'
                if workStatus['code'] == 'fail':
                    eventActionName += ': Отказ'

            if eventAction != 'plan':
                if workStatus['code'] not in ['inJob', 'setAside']:
                    nextCommunication = None

            if not hotName:
                hotName = f'{evTypeName} {eventActionName}'

            if eventType == 'success':
                hotName = 'Продажа'
            elif eventType == 'fail':
                hotName = 'Отказ'

            xoFields = [
                {'code': 'event_date', 'name': 'Дата', 'value': nowDate},
                {'code': 'event_title', 'name': 'Название', 'value': hotName},
                {'code': 'plan_date', 'name': 'Дата Следующей коммуникации', 'value': nextCommunication},
                {'code': 'author', 'name': 'Автор', 'value': created},
                {'code': 'responsible', 'name': 'Ответственный', 'value': responsible},
                {'code': 'su', 'name': 'Соисполнитель', 'value': suresponsible},
                {'code': 'crm', 'name': 'crm', 'value': crmValue},
                {'code': 'crm_company', 'name': 'crm_company', 'value': {'n0': f'CO_{companyId}'}},
                {'code': 'manager_comment', 'name': 'Комментарий', 'value': comment},
                {
                    'code': 'event_type',
                    'name': 'Тип События',
                    'list': {
                        'code': BitrixListFlowService.getEventType(eventType),
                        'name': eventTypeName
                    },
                },
                {
                    'code': 'event_action',
                    'name': 'Событие Действие',
                    'list': {'code': eventAction}
                },
                {
                    'code': 'op_work_status',
                    'name': 'Статус Работы',
                    'list': {
                        'code': BitrixListFlowService.getCurrentWorkStatusCode(workStatus, eventType)
                    },
                },
                {
                    'code': 'op_result_status',
                    'name': 'Результативность',
                    'list': {
                        'code': BitrixListFlowService.getResultStatus(resultStatus)
                    },
                },
            ]

            if contactId:
                contact = {
                    'code': 'crm_contact',
                    'name': 'crm_contact',
                    'value': {'n0': f'C_{contactId}'}
                }
                xoFields.append(contact)

            # Тип нерезультативности
            if resultStatus not in ['result', 'new']:
                if noresultReason and noresultReason.get('code'):
                    xoFields.append({
                        'code': 'op_noresult_reason',
                        'name': 'Тип Нерезультативности',
                        'list': {'code': noresultReason['code']}
                    })
            else:
                xoFields.append({
                    'code': 'op_noresult_reason',
                    'name': 'Тип Нерезультативности',
                    'list': {'code': None}
                })

            # Перспективность и причина отказа
            if workStatus and workStatus.get('code'):
                if workStatus['code'] == 'fail' and failType and failType.get('code'):
                    xoFields.append({
                        'code': 'op_prospects_type',
                        'name': 'Перспективность',
                        'list': {
                            'code': BitrixListFlowService.getPerspectStatus(failType['code'])
                        }
                    })
                    if failType['code'] == 'failure' and failReason and failReason.get('code'):
                        xoFields.append({
                            'code': 'op_fail_reason',
                            'name': 'ОП Причина Отказа',
                            'list': {
                                'code': BitrixListFlowService.getFailType(failReason['code'])
                            }
                        })
                else:
                    xoFields.append({
                        'code': 'op_prospects_type',
                        'name': 'Перспективность',
                        'list': {'code': 'op_prospects_good'}
                    })

            fieldsData = {'NAME': hotName}

            for bitrixList in bitrixLists:
                if bitrixList['type'] in ['kpi', 'history'] and bitrixList['group'] == 'sales':
                    for xoValue in xoFields:
                        fieldCode = f"{bitrixList['group']}_{bitrixList['type']}_{xoValue['code']}"
                        btxId = BitrixListFlowService.getBtxListCurrentData(bitrixList, fieldCode, None)

                        if xoValue.get('value'):
                            fieldsData[btxId] = xoValue['value']

                        if xoValue.get('list'):
                            btxItemId = BitrixListFlowService.getBtxListCurrentData(
                                bitrixList, fieldCode, xoValue['list']['code']
                            )
                            fieldsData[btxId] = btxItemId

                    uniqueHash = hashlib.md5(str(random.random()).encode()).hexdigest()
                    fullCode = f"{bitrixList['type']}_{companyId}_{uniqueHash}"

                    command = BitrixListService.getBatchCommandSetItem(
                        hook,
                        bitrixList['bitrixId'],
                        fieldsData,
                        fullCode
                    )
                    resultBatchCommands[f'set_list_item_{fullCode}'] = command

            # Для уникальных презентаций
            if resultStatus in ['result', 'new'] and (isUniqPresPlan or isUniqPresReport):
                xoFields[9]['list']['code'] = 'presentation_uniq'

                if isUniqPresPlan:
                    code = f"{companyId}_{currentBaseDealId}_plan"
                if isUniqPresReport:
                    code = f"{companyId}_{currentBaseDealId}_done"
                    if responsible != suresponsible:
                        code = f"{companyId}_{currentBaseDealId}_done_{responsible}"

                for bitrixList in bitrixLists:
                    if bitrixList['type'] == 'kpi' and bitrixList['group'] == 'sales':
                        for xoValue in xoFields:
                            fieldCode = f"{bitrixList['group']}_{bitrixList['type']}_{xoValue['code']}"
                            btxId = BitrixListFlowService.getBtxListCurrentData(bitrixList, fieldCode, None)

                            if xoValue.get('value'):
                                fieldsData[btxId] = xoValue['value']
                            if xoValue.get('list'):
                                btxItemId = BitrixListFlowService.getBtxListCurrentData(
                                    bitrixList, fieldCode, xoValue['list']['code']
                                )
                                fieldsData[btxId] = btxItemId

                        command = BitrixListService.getBatchCommandSetItem(
                            hook,
                            bitrixList['bitrixId'],
                            fieldsData,
                            code
                        )
                        resultBatchCommands[f'set_list_item_{code}'] = command

            return resultBatchCommands

        except Exception as e:
            error_messages = {
                'message': str(e),
                'trace': str(e.__traceback__)
            }
            logging.error('ERROR COLD: getListsFlow', exc_info=True)
            logging.getLogger('telegram').error('APRIL_HOOK getListsFlow', exc_info=True)


    @staticmethod
    def getBtxListCurrentData(bitrixList, code, listCode):
        result = {
            'fieldBtxId': False,
            'fieldItemBtxId': False,
        }

        if bitrixList:
            if 'bitrixfields' in bitrixList and bitrixList['bitrixfields']:
                btxFields = bitrixList['bitrixfields']
                for btxField in btxFields:
                    if btxField.get('code') == code:
                        result['fieldBtxId'] = btxField.get('bitrixCamelId')

                    if 'items' in btxField and btxField['items']:
                        btxFieldItems = btxField['items']
                        for btxFieldItem in btxFieldItems:
                            if listCode:
                                if listCode in ['op_status_in_work', 'in_work']:
                                    pass  # пустой блок как в оригинале
                                if btxFieldItem.get('code') == listCode:
                                    result['fieldItemBtxId'] = btxFieldItem.get('bitrixId')

        if not listCode:
            return result['fieldBtxId']
        else:
            return result['fieldItemBtxId']
        

    @staticmethod
    def getEventType(eventType):  # xo warm presentation, offer invoice
        # Холодный звонок	event_type	xo
        # Звонок	event_type	call
        # Презентация	event_type	presentation
        # Презентация (уникальная)	event_type	presentation_uniq
        # Информация	event_type	info
        # Приглашение на семинар	event_type	seminar
        # Звонок по решению	event_type	call_in_progress
        # Звонок по оплате	event_type	call_in_money
        # Входящий звонок	event_type	come_call
        # Заявка с сайта	event_type	site
        # Коммерческое Предложение	event_type	ev_offer
        # Счет	event_type	ev_invoice
        # Коммерческое Предложение после презентации	event_type	ev_offer_pres
        # Счет после презентации	event_type	ev_invoice_pres
        # Договор	event_type	ev_contract
        # Поставка	event_type	ev_supply
        # Продажа	event_type	ev_success
        # Отказ	event_type	ev_fail

        result = 'xo'
        if eventType in ['call', 'warm', 'supply']:
            result = 'call'
        elif eventType == 'presentation':
            result = 'presentation'
        elif eventType in ['hot', 'inProgress', 'in_progress']:
            result = 'call_in_progress'
        elif eventType in ['moneyAwait', 'money_await', 'money']:
            result = 'call_in_money'
        elif eventType == 'fail':
            result = 'ev_fail'
        elif eventType == 'success':
            result = 'ev_success'

        return result
    
    @staticmethod
    def getCurrentWorkStatusCode(workStatus, currentEventType):
        # 0: {id: 1, code: "warm", name: "Звонок"}
        # 1: {id: 2, code: "presentation", name: "Презентация"}
        # 2: {id: 3, code: "hot", name: "Решение"}
        # 3: {id: 4, code: "moneyAwait", name: "Оплата"}

        resultCode = 'in_work'
        # В работе	op_work_status	op_status_in_work
        # На долгий период	op_work_status	op_status_in_long
        # Продажа	op_work_status	op_status_success
        # В решении	op_work_status	op_status_in_progress
        # В оплате	op_work_status	op_status_money_await
        # Отказ	op_work_status	op_status_fail

        # 0: {id: 0, code: "inJob", name: "В работе"} in_long
        # 1: {id: 1, code: "setAside", name: "Отложено"}
        # 2: {id: 2, code: "success", name: "Продажа"}
        # 3: {id: 3, code: "fail", name: "Отказ"}
        if workStatus and workStatus.get('code'):
            code = workStatus['code']
            if code == 'inJob':
                resultCode = 'op_status_in_work'
                if currentEventType == 'hot':
                    resultCode = 'op_status_in_progress'
                elif currentEventType == 'moneyAwait':
                    resultCode = 'op_status_money_await'
            elif code == 'setAside':
                resultCode = 'op_status_in_long'
            elif code == 'fail':
                resultCode = 'op_status_fail'
            elif code == 'success':
                resultCode = 'op_status_success'

        return resultCode

    @staticmethod
    def getResultStatus(resultStatus):
        result = 'op_call_result_yes'
        if resultStatus != 'result' and resultStatus != 'new':
            result = 'op_call_result_no'
        return result

    @staticmethod
    def getPerspectStatus(failTypeCode):
        result = 'op_prospects_good'
        if failTypeCode in [
            'op_prospects_good',
            'op_prospects_nopersp',
            'op_prospects_nophone',
            'op_prospects_company'
        ]:
            result = failTypeCode
        elif failTypeCode in [
            'garant',
            'go',
            'territory',
            'autsorc',
            'depend'
        ]:
            result = 'op_prospects_' + failTypeCode
        elif failTypeCode == 'accountant':
            result = 'op_prospects_acountant'
        elif failTypeCode == 'failure':
            result = 'op_prospects_fail'
        return result

  

    @staticmethod
    def getFailType(failReason):
        # не было времени	op_fail_reason	fail_notime
        # конкуренты - привыкли	op_fail_reason	c_habit
        # конкуренты - оплачено	op_fail_reason	c_prepay
        # конкуренты - цена	op_fail_reason	c_price
        # слишком дорого	op_fail_reason	to_expensive
        # слишком дешево	op_fail_reason	to_cheap
        # нет денег	op_fail_reason	nomoney
        # не видят надобности	op_fail_reason	noneed
        # лпр против	op_fail_reason	lpr
        # ключевой сотрудник против	op_fail_reason	employee
        return failReason

    @staticmethod
    def getFailReasone(resultStatus):
        # не было времени	op_fail_reason	fail_notime
        # конкуренты - привыкли	op_fail_reason	c_habit
        # конкуренты - оплачено	op_fail_reason	c_prepay
        # конкуренты - цена	op_fail_reason	c_price
        # слишком дорого	op_fail_reason	to_expensive
        # слишком дешево	op_fail_reason	to_cheap
        # нет денег	op_fail_reason	nomoney
        # не видят надобности	op_fail_reason	noneed
        # лпр против	op_fail_reason	lpr
        # ключевой сотрудник против	op_fail_reason	employee
        result = 'yes'
        if resultStatus != 'result':
            result = 'no'
        return result



# batchCommands = BitrixListFlowService.getBatchListFlow(  # report - отчет по текущему событию
#     self.hook,
#     self.bitrixLists,
#     'xo',
#     'Холодный обзвон',
#     'plan',
#     # self.stringType,
#     planDeadline,
#     self.createdId,
#     self.responsibleId,
#     self.responsibleId,
#     self.entityId,
#     'Холодный обзвон ' + self.name,
#     workStatus,
#     'result',  # result noresult expired,
#     None,
#     None,
#     None,
#     planDeals,
#     None,  # current base deal id for uniq pres count
#     None,  # nowDate,  # date,
#     None,  # hotName
#     None,  # contactId,
#     batchCommands
# )
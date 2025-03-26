
from datetime import datetime
import hashlib
import random
import logging
from typing import Optional, List, Dict, Any


class BitrixListFlowService:
    def __init__(self, hook: str):
        self.hook = hook

    def get_batch_list_flow(
        self,
        bitrix_lists,
        event_type,
        event_type_name,
        event_action,
        deadline,
        created,
        responsible,
        suresponsible,
        company_id,
        comment,
        work_status,
        result_status,
        noresult_reason,
        fail_reason,
        fail_type,
        deal_ids,
        current_base_deal_id,
        now_date=None,
        hot_name=None,
        contact_id=None,
        result_batch_commands=None
    ):
        try:
            if result_batch_commands is None:
                result_batch_commands = {}

            if not now_date:
                now_date = datetime.now().strftime('%d.%m.%Y %H:%M:%S')

            event_action_name = 'Запланирован'
            ev_type_name = 'Звонок'
            next_communication = deadline
            is_uniq_pres_plan = False
            is_uniq_pres_report = False

            crm_value = {'n0': f'CO_{company_id}'}
            deal_index = 1
            if contact_id:
                deal_index = 2
                crm_value['n1'] = f'C_{contact_id}'

            if deal_ids:
                for key, deal_id in enumerate(deal_ids):
                    crm_value[f'n{key + deal_index}'] = f'D_{deal_id}'

            if event_type in ['xo', 'cold']:
                ev_type_name = 'Холодный звонок'
            elif event_type in ['warm', 'call', 'supply']:
                ev_type_name = 'Звонок'
            elif event_type == 'presentation':
                ev_type_name = 'Презентация'
                event_action_name = 'Запланирована'
            elif event_type in ['hot', 'inProgress', 'in_progress']:
                ev_type_name = 'Звонок по решению'
            elif event_type in ['money', 'moneyAwait', 'money_await']:
                ev_type_name = 'Звонок по оплате'

            if event_action == 'expired':
                event_action = 'pound'
                event_action_name = 'Перенос'
            elif event_action == 'done':
                event_action_name = 'Состоялся'
                if event_type == 'presentation':
                    event_action_name = 'Состоялась'
                    is_uniq_pres_report = True
            elif event_action == 'plan':
                if event_type == 'presentation':
                    is_uniq_pres_plan = True
            elif event_action == 'nodone':
                next_communication = None
                event_action_name = 'Не Состоялся'
                event_action = 'act_noresult_fail'
                if event_type == 'presentation':
                    event_action_name = 'Не Состоялась'
                if work_status['code'] == 'fail':
                    event_action_name += ': Отказ'

            if event_action != 'plan' and work_status['code'] not in ['inJob', 'setAside']:
                next_communication = None

            if not hot_name:
                hot_name = f'{ev_type_name} {event_action_name}'

            if event_type == 'success':
                hot_name = 'Продажа'
            elif event_type == 'fail':
                hot_name = 'Отказ'

            xo_fields = [
                {'code': 'event_date', 'name': 'Дата', 'value': now_date},
                {'code': 'event_title', 'name': 'Название', 'value': hot_name},
                {'code': 'plan_date', 'name': 'Дата Следующей коммуникации', 'value': next_communication},
                {'code': 'author', 'name': 'Автор', 'value': created},
                {'code': 'responsible', 'name': 'Ответственный', 'value': responsible},
                {'code': 'su', 'name': 'Соисполнитель', 'value': suresponsible},
                {'code': 'crm', 'name': 'crm', 'value': crm_value},
                {'code': 'crm_company', 'name': 'crm_company', 'value': {'n0': f'CO_{company_id}'}},
                {'code': 'manager_comment', 'name': 'Комментарий', 'value': comment},
                {
                    'code': 'event_type',
                    'name': 'Тип События',
                    'list': {
                        'code': self._get_event_type(event_type),
                        'name': event_type_name
                    },
                },
                {
                    'code': 'event_action',
                    'name': 'Событие Действие',
                    'list': {'code': event_action}
                },
                {
                    'code': 'op_work_status',
                    'name': 'Статус Работы',
                    'list': {'code': self._get_current_work_status_code(work_status, event_type)},
                },
                {
                    'code': 'op_result_status',
                    'name': 'Результативность',
                    'list': {'code': self._get_result_status(result_status)},
                },
            ]

            if contact_id:
                xo_fields.append({
                    'code': 'crm_contact',
                    'name': 'crm_contact',
                    'value': {'n0': f'C_{contact_id}'}
                })

            if result_status not in ['result', 'new']:
                if noresult_reason and noresult_reason.get('code'):
                    xo_fields.append({
                        'code': 'op_noresult_reason',
                        'name': 'Тип Нерезультативности',
                        'list': {'code': noresult_reason['code']}
                    })
            else:
                xo_fields.append({
                    'code': 'op_noresult_reason',
                    'name': 'Тип Нерезультативности',
                    'list': {'code': None}
                })

            if work_status and work_status.get('code'):
                if work_status['code'] == 'fail' and fail_type and fail_type.get('code'):
                    xo_fields.append({
                        'code': 'op_prospects_type',
                        'name': 'Перспективность',
                        'list': {
                            'code': self._get_perspect_status(fail_type['code'])
                        }
                    })
                    if fail_type['code'] == 'failure' and fail_reason and fail_reason.get('code'):
                        xo_fields.append({
                            'code': 'op_fail_reason',
                            'name': 'ОП Причина Отказа',
                            'list': {
                                'code': self._get_fail_type(fail_reason['code'])
                            }
                        })
                else:
                    xo_fields.append({
                        'code': 'op_prospects_type',
                        'name': 'Перспективность',
                        'list': {'code': 'op_prospects_good'}
                    })

            fields_data = {'NAME': hot_name}

            for bitrix_list in bitrix_lists:
                if bitrix_list['type'] in ['kpi', 'history'] and bitrix_list['group'] == 'sales':
                    for xo_value in xo_fields:
                        field_code = f"{bitrix_list['group']}_{bitrix_list['type']}_{xo_value['code']}"
                        btx_id = self._get_btx_list_current_data(bitrix_list, field_code, None)

                        if xo_value.get('value'):
                            fields_data[btx_id] = xo_value['value']

                        if xo_value.get('list'):
                            btx_item_id = self._get_btx_list_current_data(
                                bitrix_list, field_code, xo_value['list']['code']
                            )
                            fields_data[btx_id] = btx_item_id

                    unique_hash = hashlib.md5(str(random.random()).encode()).hexdigest()
                    full_code = f"{bitrix_list['type']}_{company_id}_{unique_hash}"

# Получение batch комманды
                    # command = BitrixListService.get_batch_command_set_item(
                    #     self.hook,
                    #     bitrix_list['bitrixId'],
                    #     fields_data,
                    #     full_code
                    # )
                    # result_batch_commands[f'set_list_item_{full_code}'] = command

            if result_status in ['result', 'new'] and (is_uniq_pres_plan or is_uniq_pres_report):
                xo_fields[9]['list']['code'] = 'presentation_uniq'

                if is_uniq_pres_plan:
                    code = f"{company_id}_{current_base_deal_id}_plan"
                if is_uniq_pres_report:
                    code = f"{company_id}_{current_base_deal_id}_done"
                    if responsible != suresponsible:
                        code = f"{company_id}_{current_base_deal_id}_done_{responsible}"

                for bitrix_list in bitrix_lists:
                    if bitrix_list['type'] == 'kpi' and bitrix_list['group'] == 'sales':
                        for xo_value in xo_fields:
                            field_code = f"{bitrix_list['group']}_{bitrix_list['type']}_{xo_value['code']}"
                            btx_id = self._get_btx_list_current_data(bitrix_list, field_code, None)

                            if xo_value.get('value'):
                                fields_data[btx_id] = xo_value['value']
                            if xo_value.get('list'):
                                btx_item_id = self._get_btx_list_current_data(
                                    bitrix_list, field_code, xo_value['list']['code']
                                )
                                fields_data[btx_id] = btx_item_id
# Получение batch комманды
                        # command = BitrixListService.get_batch_command_set_item(
                        #     self.hook,
                        #     bitrix_list['bitrixId'],
                        #     fields_data,
                        #     code
                        # )
                        # result_batch_commands[f'set_list_item_{code}'] = command

            return result_batch_commands

        except Exception as e:
            logging.error('ERROR COLD: get_lists_flow', exc_info=True)
            logging.getLogger('telegram').error('APRIL_HOOK get_lists_flow', exc_info=True)



    def _get_btx_list_current_data(bitrix_list: dict, code: str, list_code: Optional[str]) -> Optional[str]:
        result = {
            'field_btx_id': False,
            'field_item_btx_id': False,
        }

        if bitrix_list and bitrix_list.get('bitrixfields'):
            for btx_field in bitrix_list['bitrixfields']:
                if btx_field.get('code') == code:
                    result['field_btx_id'] = btx_field.get('bitrixCamelId')

                if btx_field.get('items'):
                    for btx_field_item in btx_field['items']:
                        if list_code:
                            if list_code in ['op_status_in_work', 'in_work']:
                                pass
                            if btx_field_item.get('code') == list_code:
                                result['field_item_btx_id'] = btx_field_item.get('bitrixId')

        return result['field_btx_id'] if not list_code else result['field_item_btx_id']


    def _get_event_type(event_type: str) -> str:
        result = 'xo'
        if event_type in ['call', 'warm', 'supply']:
            result = 'call'
        elif event_type == 'presentation':
            result = 'presentation'
        elif event_type in ['hot', 'inProgress', 'in_progress']:
            result = 'call_in_progress'
        elif event_type in ['moneyAwait', 'money_await', 'money']:
            result = 'call_in_money'
        elif event_type == 'fail':
            result = 'ev_fail'
        elif event_type == 'success':
            result = 'ev_success'
        return result


    def _get_current_work_status_code(work_status: dict, current_event_type: str) -> str:
        result_code = 'in_work'
        if work_status and work_status.get('code'):
            code = work_status['code']
            if code == 'inJob':
                result_code = 'op_status_in_work'
                if current_event_type == 'hot':
                    result_code = 'op_status_in_progress'
                elif current_event_type == 'moneyAwait':
                    result_code = 'op_status_money_await'
            elif code == 'setAside':
                result_code = 'op_status_in_long'
            elif code == 'fail':
                result_code = 'op_status_fail'
            elif code == 'success':
                result_code = 'op_status_success'
        return result_code

    
    def _get_result_status(result_status: str) -> str:
        return 'op_call_result_no' if result_status not in ['result', 'new'] else 'op_call_result_yes'

  
    def _get_perspect_status(fail_type_code: str) -> str:
        if fail_type_code in [
            'op_prospects_good', 'op_prospects_nopersp',
            'op_prospects_nophone', 'op_prospects_company']:
            return fail_type_code
        elif fail_type_code in ['garant', 'go', 'territory', 'autsorc', 'depend']:
            return f'op_prospects_{fail_type_code}'
        elif fail_type_code == 'accountant':
            return 'op_prospects_acountant'
        elif fail_type_code == 'failure':
            return 'op_prospects_fail'
        return 'op_prospects_good'


    def _get_fail_type(fail_reason: str) -> str:
        return fail_reason


    def _get_fail_reason(result_status: str) -> str:
        return 'no' if result_status != 'result' else 'yes'

  
    def _get_no_result_reason(result_status: str, no_result_reason: dict) -> str:
        return 'no' if result_status != 'result' else 'yes'

# service = BitrixListFlowService('hook')
# batchCommands = service.get_batch_list_flow(  # report - отчет по текущему событию
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
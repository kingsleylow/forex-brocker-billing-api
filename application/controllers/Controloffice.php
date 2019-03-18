<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class Controloffice
 *
 * @property CI_URI $uri
 * @property FT_Form_validation $form_validation
 * @property PammAPI $pammapi
 * @property jqGridSSP $jqgridssp
 * @property WebActions $webactions
 */
class Controloffice extends FT_Basic_Auth_Controller {

    // Статусы инвесторских аккаунтов
    public $gaPammInvestorAccountStatuses = array(
        'NOT_ACTIVE' => 0,
        'ACTIVATED'  => 1,
        'CLOSED' 	 => 2,
        'DELETED' 	 => 3,
    );

    // Список типов инвестора
    public $gaPammInvestorTypes = array(
        'INVESTOR'	=> 1,
        'MANAGER'	=> 2,
    );

    // Статусы ПАММ-счетов
    public $gaPammAccountStatuses = array(
        'CLOSED'	=> 0,	// Закрыт
        'OPENED'	=> 1,	// Открыт
        'CLOSED_WPO'=> 2,	// Закрыт, средства распределены
    );

    // Типы ПАММ-платежей
    public $gaPammPaymentsTypes = array(
        'INITIAL'		=> 1,	// Начальный платеж (платеж, после которого ивенсторский счет стал активным)
        'ADDON'			=> 2,	// Дополнительный платеж (когда пользователь дополнительно вносит деньги на свой инвесторский счет)
        'REINVEST'		=> 3,	// Системный платеж (деньги переносятся на следующий период и учавствуют в пересчете долях)
        'PENALTY'		=> 4,	// Штрафная выплата
        'MANAGER_BONUS'	=> 5,	// Вознаграждение управляющего
        'AGENT_BONUS'	=> 6,	// Вознаграждение агента с инвесторского счета управляющего
        'PROFIT'		=> 7,	// Платеж по выплате прибыли
    );

    // Список статусов для оферт
    public $gaPammOffersStatuses = array(
        'CLOSED'	=> 0,
        'OPENED'	=> 1,
        'PENDING'	=> 2,
        'CREATED'	=> 3,
    );

    // Список комманд на вывод с Памм-счета
    // pamm_money_orders.operation
    public $gaPammWithdrawalOperations = array(
        'DEPOSIT'				=> 0,	// Пополнить счет
        'ALL_AND_CLOSE'			=> 1,	// Снять все деньги и закрыть счет
        'DEFINED_SUM'			=> 2,	// Снять фиксированную сумму
        //'UP_TO_MIN'			=> 3,	// Снять все до минимального остатка			// Устарело. Не используется.
        //'DEPOSIT_COMMISSION'	=> 4,	// Пополнение счета управляющего за счет досрочного снятия
        'PRETERM'				=> 5,	// Досрочно снять все (при этом взымается штраф за досрочное снятие)
        'PROFIT'				=> 6,	// Снять только прибыль
        //'CONVERT_TO_PAMM2_0'	=> 7,	// Перевести в ПАММ 2.0
        //'TRANSFER_TO_PAMM2_0'	=> 8,	// Перевести фиксированную сумму в ПАММ 2.0
        'CLOSE_PAMM'			=> 9,	// То же самое что и ALL_AND_CLOSE, но используется только при закрытии ПАММ
    );

    // Список статусов распоряжений по Памм-счетам
    // pamm_money_orders.status
    public $gaPammPaymentOrdersStatuses = array(
        'PENDING'					=> 0,
        'SUCCESS'					=> 1,
        'FAILED'					=> 2,
        'CANCELED_BY_MANAGER'		=> 3,
        'CANCELED_BY_INVESTOR'		=> 4,
        'CANCELED_BY_WITHDRAW_ORDER'=> 5,
        'CANCELED_BY_CLOSING_PAMM'	=> 6,
        'CANCELED_BY_CONSULTANT'	=> 7,
        'CANCELED_BY_SYSTEM'	    => 8,
        'CANCELED_BY_DEALER'	    => 9,
    );

    // Список уровней отчетности
    public $gaReportLevels = array(
        'ONLINE'		  => 1,
        'OFFLINE'		  => 2,
    );

    // Список уровней отчетности
    public $aYesNo = array(
        0   => 'Нет',
        1   => 'Да',
    );

    public $gaResponsibilityLevels = array(
        '100'	=> 100,
        '80'	=> 80,
        '70'	=> 70,
        '60'	=> 60,
        '50'	=> 50,
        '40'	=> 40,
        '30'	=> 30,
        '20'	=> 20,
    );

    public $gaAgentsComissionType = array(
        'WELCOME'   => 1,
        'PROFIT'    => 2,
    );

    public $gaAgentsPaymentStatuses = array(
        'FAILED'    => 0,
        'SUCCESS'   => 1,
        'PENDING'   => 2,
        'POSTPONED' => 3,
    );

    private $sTitle = "Control Office";

    public function __construct()
    {
        $this->sBasicRealm = "Control Office Authentication";
        parent::__construct();

        $this->load->helper('url');
        $this->load->library('form_validation');
        $this->load->library('PammAPI');
        $this->load->library('JqGridSSP');
        $this->load->library('WebActions');
        $this->pammapi->setPartnerId($this->getPartnerId());
        $this->pammapi->setPartnerMTLogin($this->getPartnerMTLogin());
        $this->pammapi->setPartnerPermissions($this->getPartnerPermissions());
    }

    public function index()
	{
        $aOutput['title'] = "Обзор - ".$this->sTitle;
        $aOutput['segment'] = $this->uri->segment(2, '');

        $iPartnerId = $this->getPartnerId();

        $sJSON = "[]";
        $rQuery = $this->db->query("SELECT action_name AS label, COUNT(*) AS value FROM pammapi_access_log WHERE partner_id = {$iPartnerId} AND module LIKE 'pamm' AND action_time > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) GROUP BY action_name ORDER BY value DESC");
        if ($rQuery->num_rows() > 0)
        {
            $aActions = $rQuery->result_array();
            $sJSON = json_encode($aActions);
        }
        $aOutput['chart_data_pamm'] = $sJSON;

        $sJSON = "[]";
        $rQuery = $this->db->query("SELECT action_name AS label, COUNT(*) AS value FROM pammapi_access_log WHERE partner_id = {$iPartnerId} AND module LIKE 'webactions' AND action_time > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) GROUP BY action_name ORDER BY value DESC");
        if ($rQuery->num_rows() > 0)
        {
            $aActions = $rQuery->result_array();
            $sJSON = json_encode($aActions);
        }
        $aOutput['chart_data_wa'] = $sJSON;

        $sJSON = "[]";
        $rQuery = $this->db->query("SELECT action_name AS label, COUNT(*) AS value FROM pammapi_access_log WHERE partner_id = {$iPartnerId} AND module LIKE 'tps' AND action_time > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) GROUP BY action_name ORDER BY value DESC");
        if ($rQuery->num_rows() > 0)
        {
            $aActions = $rQuery->result_array();
            $sJSON = json_encode($aActions);
        }
        $aOutput['chart_data_tps'] = $sJSON;

        $this->load->view('controloffice/header', $aOutput);
        $this->load->view('controloffice/index', $aOutput);
        $this->load->view('controloffice/footer', $aOutput);
    }

    public function settings()
    {
        $aOutput['title'] = "Настройки - ".$this->sTitle;
        $aOutput['segment'] = $this->uri->segment(2, '');

        $aOutput['id'] = $this->getPartnerId();
        $aOutput['name'] = $this->getPartnerName();
        $aOutput['trans_mt_login'] = $this->getPartnerMTLogin();
        $aOutput['agent_mt_login'] = $this->getPartnerAgentMTLogin();
        $aOutput['mt_range'] = "{$this->iMTRangeStart}-{$this->iMTRangeEnd}";
        $aOutput['ips'] = $this->getPartnerIPs();
        $aOutput['status_url'] = $this->getPartnerStatusURL();

        $this->load->view('controloffice/header', $aOutput);
        $this->load->view('controloffice/settings', $aOutput);
        $this->load->view('controloffice/footer', $aOutput);
    }

    /* ПАММ-счета */

    public function get_pamm_list()
    {
        // Main query to actually get the data
        $this->db
            ->select('po.id')
            ->select('po.partner_id')
            ->select('po.investor_id')
            ->select('po.pamm_mt_login')
            ->select('po.create_date')
            ->select('po.open_date')
            ->select('po.close_date')
            ->select('po.bonus')
            ->select('po.commission')
            ->select('po.responsibility')
            ->select('IFNULL(pr.initial_capital, 0) AS initial_capital', FALSE)
            ->select('po.trade_period')
            ->select('po.conditionally_periodic')
            ->select('po.adjusting_rollover_date')
            ->select('po.last_rollover_date')
            ->select('po.next_rollover_date')
            ->select('po.min_balance')
            ->select('po.allow_deposit')
            ->select('po.allow_withdraw')
            ->select('po.mc_stopout')
            ->select('po.status')
            ->select('po.agent_bonus')
            ->select('po.agent_bonus_profit')
            ->select('po.agent_pay_delay')
            ->select('po.report_level')
            ->select('po.cancel_manager_pmo')
            ->select('po.contact_method AS contact_method')
            ->select('po.ts_desc AS ts_desc')
            ->select('po.admin_note AS admin_note')
            ->select('IFNULL(pr.nickname, "") AS nickname', FALSE)
            ->select('IFNULL(pr.current_capital, 0) AS current_capital', FALSE)
            ->select('IFNULL(pr.investments, 0) AS investments', FALSE)
            ->select('IFNULL(pr.insured_sum, 0) AS insured_sum', FALSE)
            ->select('IFNULL(pr.offer_exists, 0) AS offer_exists', FALSE)
            ->select('IFNULL(pr.profit, 0) AS profit', FALSE)
            ->select('IFNULL(pr.profitness, 0) AS profitness', FALSE)
            ->select('IFNULL(pr.profitness_last_month, 0) AS profitness_last_month', FALSE)
            ->select('IFNULL(pr.rdrawdown, 0) AS rdrawdown', FALSE)
            ->select('IFNULL(pr.mdrawdown, 0) AS mdrawdown', FALSE)
            ->select('IFNULL(pr.opened_tickets, 0) AS opened_tickets', FALSE)
            ->select('IFNULL(pr.permitted_investments, 0) AS permitted_investments', FALSE)
            ->select('IFNULL(pr.last_update, 0) AS last_update', FALSE)
            ->from('pamm_offers AS po')
            ->join('pamm_rating AS pr', 'pr.mt_login = po.pamm_mt_login', 'left');
        $sQuery = $this->db->get_compiled_select();

        $this->jqgridssp->setName("list");
        $this->jqgridssp->setQuery($sQuery);
        $this->jqgridssp->setProperty('sortname', 'pamm_mt_login');
        $this->jqgridssp->setProperty('height', "100%");
        $this->jqgridssp->setProperty('autowidth', true);
        $this->jqgridssp->setProperty('shrinkToFit', false);
        $this->jqgridssp->addColumn('po.id', 'Номер оферты', 'integer');
        $this->jqgridssp->addColumn('po.partner_id', 'Партнер', 'integer', 90);
        $this->jqgridssp->addColumn('po.investor_id', 'Номер инв счета', 'integer', 125);
        $this->jqgridssp->addColumn('po.pamm_mt_login', 'ПАММ-счет', 'integer', 100, null, "function(cellvalue, options, rowObject){return '<a href=\"/controloffice/pamm_details/'+cellvalue+'/\" target=\"_blank\" style=\"text-decoration: underline\">'+cellvalue+'</a>';}");
        $this->jqgridssp->addColumn('po.create_date', 'Дата создания', 'datetime', 130);
        $this->jqgridssp->addColumn('po.open_date', 'Дата открытия', 'datetime', 130);
        $this->jqgridssp->addColumn('po.close_date', 'Дата закрытия', 'datetime', 130);
        $this->jqgridssp->addColumn('po.bonus', 'Вознаграждение', 'integer', 125);
        $this->jqgridssp->addColumn('po.commission', 'Штраф', 'integer', 100);
        $this->jqgridssp->addColumn('po.responsibility', 'Ответственность', 'integer', 125);
        $this->jqgridssp->addColumn('pr.initial_capital', 'Начальный КУ', 'number');
        $this->jqgridssp->addColumn('po.trade_period', 'ТП', 'integer');
        $this->jqgridssp->addColumn('po.conditionally_periodic', 'УП-ПАММ', 'select', 120, $this->aYesNo);
        $this->jqgridssp->addColumn('po.adjusting_rollover_date', 'Дата уст РО', 'datetime', 130);
        $this->jqgridssp->addColumn('po.last_rollover_date', 'Дата последнего РО', 'datetime', 130);
        $this->jqgridssp->addColumn('po.next_rollover_date', 'Дата следующего РО', 'datetime', 130);
        $this->jqgridssp->addColumn('po.min_balance', 'Мин баланс', 'number');
        $this->jqgridssp->addColumn('po.allow_deposit', 'Вкл депозит', 'select', 120, $this->aYesNo);
        $this->jqgridssp->addColumn('po.allow_withdraw', 'Вкл снятие', 'select', 120, $this->aYesNo);
        $this->jqgridssp->addColumn('po.mc_stopout', 'Стопаут КУ', 'select', 120, $this->aYesNo);
        $this->jqgridssp->addColumn('po.status', 'Статус', 'select', 120, array_flip($this->gaPammOffersStatuses));
        $this->jqgridssp->addColumn('po.agent_bonus', 'Агент возрн', 'integer');
        $this->jqgridssp->addColumn('po.agent_bonus_profit', 'АВ с прибыли', 'integer');
        $this->jqgridssp->addColumn('po.agent_pay_delay', 'Стопаут КУ', 'select', 120, $this->aYesNo);
        $this->jqgridssp->addColumn('po.report_level', 'Уровень отчетности', 'select', 120, array_flip($this->gaReportLevels));
        $this->jqgridssp->addColumn('po.cancel_manager_pmo', 'Отменить расп У', 'select', 120, $this->aYesNo);
        $this->jqgridssp->addColumn('po.contact_method', 'Связь с У', 'text');
        $this->jqgridssp->addColumn('po.ts_desc', 'Торговая стратегия', 'text');
        $this->jqgridssp->addColumn('po.admin_note', 'Примечание', 'text');
        $this->jqgridssp->addColumn('pr.nickname', 'Никнейм', 'text');
        $this->jqgridssp->addColumn('pr.current_capital', 'Текущий КУ', 'number');
        $this->jqgridssp->addColumn('pr.investments', 'Инвестиции', 'number');
        $this->jqgridssp->addColumn('pr.insured_sum', 'Застраховано', 'number');
        $this->jqgridssp->addColumn('pr.offer_exists', 'Оферта', 'select', 120, $this->aYesNo);
        $this->jqgridssp->addColumn('pr.profit', 'Прибыль', 'number');
        $this->jqgridssp->addColumn('pr.profitness', 'Прибыльность', 'number');
        $this->jqgridssp->addColumn('pr.profitness_last_month', 'Прибыльность за посл месяц', 'number');
        $this->jqgridssp->addColumn('pr.rdrawdown', 'Отн просадка', 'number');
        $this->jqgridssp->addColumn('pr.mdrawdown', 'Макс просадка', 'number');
        $this->jqgridssp->addColumn('pr.opened_tickets', 'Открыто сделок', 'integer');
        $this->jqgridssp->addColumn('pr.permitted_investments', 'Доступно инв', 'number');
        $this->jqgridssp->addColumn('pr.last_update', 'Дата посл обновления', 'datetime');

        $aOutput['jqgrid'] = $this->jqgridssp->getOutput();

        $aOutput['h1_page_header'] = "Список ПАММ-счетов";
        $aOutput['title'] = "{$aOutput['h1_page_header']} - {$this->sTitle}";
        $aOutput['segment'] = $this->uri->segment(2, '');

        $this->load->view('controloffice/header', $aOutput);
        $this->load->view('controloffice/get_pamm_list', $aOutput);
        $this->load->view('controloffice/footer');

        return true;
    }

    public function pamm_details($iPammMTLogin)
    {
        $iPammMTLogin   = (int)$iPammMTLogin;

        $aPammDetails   = $this->pammapi->getPammDetails($iPammMTLogin);
        $aProfitStat1   = $this->pammapi->getProfitStatement($iPammMTLogin, 1, 0, time());
        $aProfitStat2   = $this->pammapi->getProfitStatement($iPammMTLogin, 2, 0, time());
        $aDrawdowns     = $this->pammapi->getPammMaximumDrawdowns($iPammMTLogin, 0);

        $aOutput = array(
            'title'     => "Детали ПАММ-счета - ".$this->sTitle,
            'segment'   => $this->uri->segment(2, ''),
            'pamm_mt_login' => $iPammMTLogin,
            'pamm_details'  => $aPammDetails,
            'pamm_stat_1'   => $aProfitStat1,
            'pamm_stat_2'   => $aProfitStat2,
            'pamm_drawdowns'=> $aDrawdowns,
        );

        $this->load->view('controloffice/header', $aOutput);
        $this->load->view('controloffice/pamm_details', $aOutput);
        $this->load->view('controloffice/footer', $aOutput);
    }

    public function get_investors_list()
    {
        $fPammMinCapital = PAMM_MIN_CAPITAL;

        $this->db
            ->select('ai.id')
            ->select('ai.type')
            ->select('ai.partner_id')
            ->select('ai.user_id')
            ->select('ai.offer_id')
            ->select('ai.pamm_mt_login')
            ->select('ai.inv_mt_login')
            ->select('ai.created_at')
            ->select('ai.activated_at')
            ->select('ai.closed_at')
            ->select('ai.status')
            ->select('FLOOR(ROUND(ai.current_sum*100, 2))/100 AS current_sum', FALSE)
            ->select('ai.insured_sum')
            ->select('ai.for_index')
            ->select('ai.for_ic')
            ->select('ai.auto_withdrawal_profit')
            ->select('ai.show_mode')
            ->select('po.min_balance')
            ->select('po.commission')
            ->select('IF(ai.activated_at > UNIX_TIMESTAMP(DATE_SUB(DATE_FORMAT(DATE_ADD(po.last_rollover_date, INTERVAL 7-WEEKDAY(po.`last_rollover_date`) DAY), "%Y-%m-%d 00:00:00"), INTERVAL 1 HOUR)), ai.activated_at, UNIX_TIMESTAMP(DATE_SUB(DATE_FORMAT(DATE_ADD(po.`last_rollover_date`, INTERVAL 7-WEEKDAY(po.last_rollover_date) DAY), "%Y-%m-%d 00:00:00"), INTERVAL 1 HOUR))) AS trade_session_start_unixtime', FALSE)
            ->select('ai.total_deposits AS investors_deposit', FALSE)
            ->select('ABS(ai.total_withdrawal) AS investors_withdraw', FALSE)
            ->select('ai.trade_session_deposits AS trade_session_payments', FALSE)
            ->select("IF(ai.status = {$this->gaPammInvestorAccountStatuses['ACTIVATED']}, IFNULL(pis.period_profit, 0), 0) AS period_profit", FALSE)
            ->select("IF(ai.status = {$this->gaPammInvestorAccountStatuses['ACTIVATED']}, IF(IF(ai.type = {$this->gaPammInvestorTypes['MANAGER']}, FLOOR(IFNULL(pmcc.capital, 0)*100)/100, IFNULL(pis.availsum, 0))<0, 0, IF(ai.type = {$this->gaPammInvestorTypes['MANAGER']}, FLOOR(IFNULL(pmcc.capital, 0)*100)/100, IFNULL(pis.availsum, 0))), 0) AS availsum", FALSE)
            ->select("ROUND(((ABS(ai.total_withdrawal)+IFNULL(pis.availsum, 0))/ai.total_deposits-1)*100, 2) AS profit_percent", FALSE)
            ->select("IF(ai.status = {$this->gaPammInvestorAccountStatuses['ACTIVATED']}, IFNULL(pis.pretermsum, 0), 0) AS pretermsum", FALSE)
            ->select('FLOOR(IFNULL(pmcc.capital, 0)*100)/100 AS current_capital', FALSE)
            ->select("IF(ai.status = {$this->gaPammInvestorAccountStatuses['ACTIVATED']}, ROUND(IF(ai.type = {$this->gaPammInvestorTypes['MANAGER']}, TRUNCATE(IFNULL(pmcc.capital, 0), 2), IFNULL(pis.availsum, 0))-IF(ai.trade_session_deposits > IF(ai.type={$this->gaPammInvestorTypes['MANAGER']} AND po.min_balance<{$fPammMinCapital}, {$fPammMinCapital}, po.min_balance), ai.trade_session_deposits, IF(ai.type={$this->gaPammInvestorTypes['MANAGER']} AND po.min_balance<{$fPammMinCapital}, {$fPammMinCapital}, po.min_balance)), 2), 0) AS max_defined_sum", FALSE)
            ->select("IF(IFNULL(pis.last_ticket_ct, '1970-01-01') < IFNULL(pmcc.update_time, '1970-01-01'), IFNULL(pmcc.update_time, '1970-01-01'), 0) AS actual_datetime", FALSE)
            ->from('pamm_investors AS ai')
            ->join('pamm_offers AS po', 'po.pamm_mt_login = ai.pamm_mt_login')
            ->join('pamm_manager_capital_current AS pmcc', 'pmcc.pamm_mt_login = ai.pamm_mt_login', 'left')
            ->join('pamm_investors_stat AS pis', 'pis.investor_id = ai.id', 'left');
        $sQuery = $this->db->get_compiled_select();

        $this->jqgridssp->setName("list");
        $this->jqgridssp->setQuery($sQuery);
        $this->jqgridssp->setCountMethod("COUNT(*)");
        $this->jqgridssp->setProperty('sortname', 'NULL');
        $this->jqgridssp->setProperty('height', "100%");
        $this->jqgridssp->setProperty('autowidth', true);
        $this->jqgridssp->setProperty('shrinkToFit', false);
        $this->jqgridssp->addColumn('ai.id', 'Инвесторский счет', 'integer', 140, null, "function(cellvalue, options, rowObject){return '<a href=\"/controloffice/investor_details/'+cellvalue+'/\" target=\"_blank\" style=\"text-decoration: underline\">'+cellvalue+'</a>';}");
        $this->jqgridssp->addColumn('ai.type', 'Тип счета', 'select', 110, array_flip($this->gaPammInvestorTypes));
        $this->jqgridssp->addColumn('ai.partner_id', 'Партнер', 'integer', 80);
        $this->jqgridssp->addColumn('ai.user_id', 'ID', 'integer', 90);
        $this->jqgridssp->addColumn('ai.offer_id', 'Номер оферты', 'integer', 120);
        $this->jqgridssp->addColumn('ai.pamm_mt_login', 'ПАММ-счет', 'integer', 100, null, "function(cellvalue, options, rowObject){return '<a href=\"/controloffice/pamm_details/'+cellvalue+'/\" target=\"_blank\" style=\"text-decoration: underline\">'+cellvalue+'</a>';}");
        $this->jqgridssp->addColumn('ai.inv_mt_login', 'МТ-счет', 'integer', 100);
        $this->jqgridssp->addColumn('ai.created_at', 'Дата создания', 'datetime', 130);
        $this->jqgridssp->addColumn('ai.activated_at', 'Дата активации', 'datetime', 130);
        $this->jqgridssp->addColumn('ai.closed_at', 'Дата закрытия', 'datetime', 130);
        $this->jqgridssp->addColumn('ai.status', 'Статус', 'select', 120, array_flip($this->gaPammInvestorAccountStatuses));
        $this->jqgridssp->addColumn('current_sum', 'Сумма в управлении', 'number', 140);
        $this->jqgridssp->addColumn('ai.insured_sum', 'Застрахованная сумма', 'number', 150);
        $this->jqgridssp->addColumn('ai.for_index', 'Индекс', 'integer', 80);
        $this->jqgridssp->addColumn('ai.for_ic', 'ИК', 'integer', 80);
        $this->jqgridssp->addColumn('ai.auto_withdrawal_profit', 'Автоснятие', 'select', 100, $this->aYesNo);
        $this->jqgridssp->addColumn('ai.show_mode', 'Отображение', 'integer');
        $this->jqgridssp->addColumn('po.min_balance', 'Мин баланс', 'number');
        $this->jqgridssp->addColumn('po.commission', 'Штраф', 'integer', 100);
        $this->jqgridssp->addColumn('trade_session_start_unixtime', 'Начало торг сессии', 'integer', 125);
        $this->jqgridssp->addColumn('investors_deposit', 'Всего вход', 'number');
        $this->jqgridssp->addColumn('investors_withdraw', 'Всего выход', 'number');
        $this->jqgridssp->addColumn('trade_session_payments', 'Платежи за ТП', 'number');
        $this->jqgridssp->addColumn('period_profit', 'Прибыль за ТП', 'number');
        $this->jqgridssp->addColumn('availsum', 'Сумма к снятию', 'number');
        $this->jqgridssp->addColumn('profit_percent', 'Доходность', 'number');
        $this->jqgridssp->addColumn('pretermsum', 'Сумма к досрочному выводу', 'number');
        $this->jqgridssp->addColumn('current_capital', 'Текущий КУ', 'number');
        $this->jqgridssp->addColumn('max_defined_sum', 'Макс DEFINED_SUM', 'number');
        $this->jqgridssp->addColumn('actual_datetime', 'Дата обновления', 'datetime');

        $iPartnerId = $this->getPartnerId();
        $sWhere = "ai.status != {$this->gaPammInvestorAccountStatuses['DELETED']}";
        if (!empty($iPartnerId))
        {
            $sWhere .= " AND ai.partner_id = {$iPartnerId}";
        }
        $this->jqgridssp->addWhere($sWhere);

        $aOutput['jqgrid'] = $this->jqgridssp->getOutput();

        $aOutput['h1_page_header'] = "Список инвесторских счетов";
        $aOutput['title'] = "{$aOutput['h1_page_header']} - {$this->sTitle}";
        $aOutput['segment'] = $this->uri->segment(2, '');

        $this->load->view('controloffice/header', $aOutput);
        $this->load->view('controloffice/get_investors_list', $aOutput);
        $this->load->view('controloffice/footer');

        return true;
    }

    public function get_money_orders_list()
    {
        $iPartnerId = $this->getPartnerId();

        $this->db
            ->select('pmo.id')
            ->select('pmo.investor_id')
            ->select('pmo.sum')
            ->select('FROM_UNIXTIME(pmo.created_at) AS created_at', FALSE)
            ->select('FROM_UNIXTIME(pmo.confirmed_at) AS confirmed_at', FALSE)
            ->select('pmo.status')
            ->select('pmo.operation')
            ->select('pmo.error_code')
            ->from('pamm_money_orders AS pmo');
        if (!empty($iPartnerId))
        {
            $this->db->join('pamm_investors AS ai', "ai.id = pmo.investor_id AND ai.partner_id = {$iPartnerId}");
        }
        $sQuery = $this->db->get_compiled_select();

        $this->jqgridssp->setName("list");
        $this->jqgridssp->setQuery($sQuery);
        $this->jqgridssp->setCountMethod("COUNT(*)");
        $this->jqgridssp->setProperty('sortname', 'id');
        $this->jqgridssp->setProperty('height', "100%");
        //$this->jqgridssp->setProperty('autowidth', true);
        $this->jqgridssp->setProperty('shrinkToFit', false);
        $this->jqgridssp->addColumn('pmo.id', 'Номер распоряжения', 'integer', 150);
        $this->jqgridssp->addColumn('pmo.investor_id', 'Инвесторский счет', 'integer', 140, null, "function(cellvalue, options, rowObject){return '<a href=\"/controloffice/investor_details/'+cellvalue+'/\" target=\"_blank\" style=\"text-decoration: underline\">'+cellvalue+'</a>';}");
        $this->jqgridssp->addColumn('pmo.operation', 'Операция', 'select', 110, array_flip($this->gaPammWithdrawalOperations));
        $this->jqgridssp->addColumn('pmo.sum', 'Сумма', 'number', 100);
        $this->jqgridssp->addColumn('pmo.created_at', 'Дата создания', 'datetime', 140);
        $this->jqgridssp->addColumn('pmo.confirmed_at', 'Дата подтверждения', 'datetime', 150);
        $this->jqgridssp->addColumn('pmo.status', 'Статус', 'select', 190, array_flip($this->gaPammPaymentOrdersStatuses));
        $this->jqgridssp->addColumn('pmo.error_code', 'Код ошибки', 'integer', 100);

        $aOutput['jqgrid'] = $this->jqgridssp->getOutput();

        $aOutput['h1_page_header'] = "Список распоряжений";
        $aOutput['title'] = "{$aOutput['h1_page_header']} - {$this->sTitle}";
        $aOutput['segment'] = $this->uri->segment(2, '');

        $this->load->view('controloffice/header', $aOutput);
        $this->load->view('controloffice/get_money_orders_list', $aOutput);
        $this->load->view('controloffice/footer');

        return true;
    }

    public function get_agent_payments_list()
    {
        $iPartnerId = $this->getPartnerId();

        $this->db
            ->select('pap.id')
            ->select('pap.partner_id')
            ->select('pap.investor_id')
            ->select('pap.pmo_id')
            ->select('pap.amount_full')
            ->select('pap.amount_basis')
            ->select('pap.bonus_comission')
            ->select('pap.bonus_type')
            ->select('pap.bonus_received')
            ->select('pap.status')
            ->select('pap.date_created')
            ->select('pap.date_updated')
            ->from('pammapi_agents_payments AS pap');
        if (!empty($iPartnerId))
        {
            $this->db->join('pamm_investors AS ai', "ai.id = pap.investor_id AND ai.partner_id = {$iPartnerId}");
        }
        $sQuery = $this->db->get_compiled_select();

        $this->jqgridssp->setName("list");
        $this->jqgridssp->setQuery($sQuery);
        $this->jqgridssp->setCountMethod("COUNT(*)");
        $this->jqgridssp->setProperty('sortname', 'id');
        $this->jqgridssp->setProperty('height', "100%");
        $this->jqgridssp->setProperty('autowidth', true);
        $this->jqgridssp->setProperty('shrinkToFit', false);
        $this->jqgridssp->addColumn('pap.id', 'Номер выплаты', 'integer', 130);
        $this->jqgridssp->addColumn('pap.partner_id', 'Партнер', 'integer', 90);
        $this->jqgridssp->addColumn('pap.investor_id', 'Инвесторский счет', 'integer', 140, null, "function(cellvalue, options, rowObject){return '<a href=\"/controloffice/investor_details/'+cellvalue+'/\" target=\"_blank\" style=\"text-decoration: underline\">'+cellvalue+'</a>';}");
        $this->jqgridssp->addColumn('pap.pmo_id', 'Номер распоряжения', 'integer', 150);
        $this->jqgridssp->addColumn('pap.amount_full', 'Сумма основа', 'number');
        $this->jqgridssp->addColumn('pap.amount_basis', 'Сумма базис', 'number');
        $this->jqgridssp->addColumn('pap.bonus_comission', 'Вознаграждение', 'number');
        $this->jqgridssp->addColumn('pap.bonus_type', 'Тип', 'select', 130, array_flip($this->gaAgentsComissionType));
        $this->jqgridssp->addColumn('pap.bonus_received', 'Сумма получено', 'number');
        $this->jqgridssp->addColumn('pap.status', 'Статус', 'select', 130, array_flip($this->gaAgentsPaymentStatuses));
        $this->jqgridssp->addColumn('pap.date_created', 'Дата создания', 'datetime', 140);
        $this->jqgridssp->addColumn('pap.date_updated', 'Дата обновления', 'datetime', 140);

        $aOutput['jqgrid'] = $this->jqgridssp->getOutput();

        $aOutput['h1_page_header'] = "Список агентских выплат";
        $aOutput['title'] = "{$aOutput['h1_page_header']} - {$this->sTitle}";
        $aOutput['segment'] = $this->uri->segment(2, '');

        $this->load->view('controloffice/header', $aOutput);
        $this->load->view('controloffice/get_agent_payments_list', $aOutput);
        $this->load->view('controloffice/footer');

        return true;
    }

    public function get_agents_status_list()
    {
        $iPartnerId = $this->getPartnerId();

        $this->db
            ->select('pia.partner_id')
            ->select('pia.user_id')
            ->select('pia.enabled')
            ->from('pamm_investors_agents AS pia');
        if (!empty($iPartnerId))
        {
            $this->jqgridssp->addWhere("pia.partner_id = {$iPartnerId}");
        }
        $sQuery = $this->db->get_compiled_select();

        $this->jqgridssp->setName("list");
        $this->jqgridssp->setQuery($sQuery);
        $this->jqgridssp->setCountMethod("COUNT(*)");
        $this->jqgridssp->setProperty('sortname', 'NULL');
        $this->jqgridssp->setProperty('height', "100%");
        //$this->jqgridssp->setProperty('autowidth', true);
        $this->jqgridssp->setProperty('shrinkToFit', false);
        $this->jqgridssp->addColumn('pia.partner_id', 'Партнер', 'integer', 180);
        $this->jqgridssp->addColumn('pia.user_id', 'Пользователь', 'integer', 200);
        $this->jqgridssp->addColumn('pia.enabled', 'Включен', 'select', 180, $this->aYesNo);

        $aOutput['jqgrid'] = $this->jqgridssp->getOutput();

        $aOutput['h1_page_header'] = "Список агентских разрешений";
        $aOutput['title'] = "{$aOutput['h1_page_header']} - {$this->sTitle}";
        $aOutput['segment'] = $this->uri->segment(2, '');

        $this->load->view('controloffice/header', $aOutput);
        $this->load->view('controloffice/get_agents_status_list', $aOutput);
        $this->load->view('controloffice/footer');

        return true;
    }

    public function get_notifications_list()
    {
        $iPartnerId = $this->getPartnerId();

        $this->db
            ->select('pn.id')
            ->select('pn.partner_id')
            ->select('pn.date_created')
            ->select('pn.type')
            ->select('pn.data')
            ->select('pn.status')
            ->from('pammapi_notifications AS pn');
        if (!empty($iPartnerId))
        {
            $this->jqgridssp->addWhere("pn.partner_id = {$iPartnerId}");
        }
        $sQuery = $this->db->get_compiled_select();

        $this->jqgridssp->setName("list");
        $this->jqgridssp->setQuery($sQuery);
        $this->jqgridssp->setCountMethod("COUNT(*)");
        $this->jqgridssp->setProperty('sortname', 'NULL');
        $this->jqgridssp->setProperty('height', "100%");
        //$this->jqgridssp->setProperty('autowidth', true);
        $this->jqgridssp->setProperty('shrinkToFit', false);
        $this->jqgridssp->addColumn('pn.id', '№', 'integer', 100, null, "function(cellvalue, options, rowObject){return '<a href=\"/controloffice/notification/'+cellvalue+'/\" target=\"_blank\" style=\"text-decoration: underline\">'+cellvalue+'</a>';}");
        $this->jqgridssp->addColumn('pn.partner_id', 'Партнер', 'integer', 120);
        $this->jqgridssp->addColumn('pn.date_created', 'Дата создания', 'datetime', 140);
        $this->jqgridssp->addColumn('pn.type', 'Тип', 'select', 140, $this->aYesNo);
        $this->jqgridssp->addColumn('pn.data', 'Содержимое', 'string', 420);
        $this->jqgridssp->addColumn('pn.status', 'Статус', 'select', 140, $this->aYesNo);

        $aOutput['jqgrid'] = $this->jqgridssp->getOutput();

        $aOutput['h1_page_header'] = "Список уведомлений";
        $aOutput['title'] = "{$aOutput['h1_page_header']} - {$this->sTitle}";
        $aOutput['segment'] = $this->uri->segment(2, '');

        $this->load->view('controloffice/header', $aOutput);
        $this->load->view('controloffice/get_notifications_list', $aOutput);
        $this->load->view('controloffice/footer');

        return true;
    }

}

/* End of file pamm.php */
/* Location: ./application/controllers/pamm.php */
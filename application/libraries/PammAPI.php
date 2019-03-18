<?php

// Partner ID нашего сайта. т.к. мы не можем сами себе быть партнерами - то у нас 0
define('PAMM_WL_PARTNER_ID', 0);
// Минимальное кол-во средств для открытия ПАММ-счета
define('PAMM_MIN_CAPITAL', 500);
// Группа счетов, в которых создаются ПАММ-счет
define('PAMM_GROUP_NAME', 'INSIDEPAMM');
define('PAMM20_GROUP_NAME', 'PAMM20');
// Время ролловера
define('PAMM_ROLLOVER_TIME', '10:00:00');
// Типы агентских выплат
define('PAMMAGENTS_COMISSIONTYPE_WELCOME', 1);						// вступительный бонус
define('PAMMAGENTS_COMISSIONTYPE_PROFIT', 2);						// бонус с прибыли

/**
 * @property CI_DB_query_builder $db              This is the platform-independent base Active Record implementation class.
 * @property CI_DB_forge $dbforge                 Database Utility Class
 * @property CI_Benchmark $benchmark              This class enables you to mark points and calculate the time difference between them. Memory consumption can also be displayed.
 * @property CI_Calendar $calendar                This class enables the creation of calendars
 * @property CI_Cart $cart                        Shopping Cart Class
 * @property CI_Config $config                    This class contains functions that enable config files to be managed
 * @property CI_Controller $controller            This class object is the super class that every library in. CodeIgniter will be assigned to.
 * @property CI_Email $email                      Permits email to be sent using Mail, Sendmail, or SMTP.
 * @property CI_Encrypt $encrypt                  Provides two-way keyed encoding using XOR Hashing and Mcrypt
 * @property CI_Exceptions $exceptions            Exceptions Class
 * @property CI_Ftp $ftp                          FTP Class
 * @property CI_Hooks $hooks                      Provides a mechanism to extend the base system without hacking.
 * @property CI_Image_lib $image_lib              Image Manipulation class
 * @property CI_Input $input                      Pre-processes global input data for security
 * @property CI_Lang $lang                        Language Class
 * @property CI_Loader $load                      Loads views and files
 * @property CI_Log $log                          Logging Class
 * @property CI_Model $model                      CodeIgniter Model Class
 * @property CI_Output $output                    Responsible for sending final output to browser
 * @property CI_Pagination $pagination            Pagination Class
 * @property CI_Parser $parser                    Parses pseudo-variables contained in the specified template view, replacing them with the data in the second param
 * @property CI_Profiler $profiler                This class enables you to display benchmark, query, and other data in order to help with debugging and optimization.
 * @property CI_Router $router                    Parses URIs and determines routing
 * @property CI_Session $session                  Session Class
 * @property CI_Table $table                      HTML table generation Lets you create tables manually or from database result objects, or arrays.
 * @property CI_Trackback $trackback              Trackback Sending/Receiving Class
 * @property CI_Typography $typography            Typography Class
 * @property CI_Unit_test $unit_test              Simple testing class
 * @property CI_Upload $upload                    File Uploading Class
 * @property CI_URI $uri                          Parses URIs and determines routing
 * @property CI_User_agent $user_agent            Identifies the platform, browser, robot, or mobile devise of the browsing agent
 * @property CI_Xmlrpc $xmlrpc                    XML-RPC request handler class
 * @property CI_Xmlrpcs $xmlrpcs                  XML-RPC server class
 * @property CI_Zip $zip                          Zip Compression Class
 * @property CI_Javascript $javascript            Javascript Class
 * @property CI_Jquery $jquery                    Jquery Class
 * @property CI_Utf8 $utf8                        Provides support for UTF-8 environments
 * @property CI_Security $security                Security Class, xss, csrf, etc...
 *
 * @property FT_Form_validation $form_validation
 * @property WebActions $webactions
 */

class PammAPI extends CI_Controller {

    public $gfPammMinCapital = PAMM_MIN_CAPITAL;

    public $gMTTimeZone = "+01:00";

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
        'INITIAL'		    => 1,	// Начальный платеж (платеж, после которого ивенсторский счет стал активным)
        'ADDON'			    => 2,	// Дополнительный платеж (когда пользователь дополнительно вносит деньги на свой инвесторский счет)
        'REINVEST'		    => 3,	// Системный платеж (деньги переносятся на следующий период и учавствуют в пересчете долях)
        'PENALTY'		    => 4,	// Штрафная выплата
        'MANAGER_BONUS'	    => 5,	// Вознаграждение управляющего
        'AGENT_BONUS'	    => 6,	// Вознаграждение агента с инвесторского счета управляющего
        'PROFIT'		    => 7,	// Платеж по выплате прибыли
        'ROLLOVER'		    => 8,	// Фиксация суммы в управлении (в ролловер)
        'FORCED_ROLLOVER'   => 9,	// Внеплановая фиксация суммы в управлении (при суперубытке, когда инвестор может получить доступную сумму для снятия < 0)
    );

    // Список статусов для оферт
    public $gaPammOffersStatuses = array(
        'CLOSED'	=> 0,
        'OPENED'	=> 1,
        'PENDING'	=> 2,
        'CREATED'	=> 3,
    );

    // Список комманд на вывод с ПАММ-счета
    // pamm_money_orders.operation
    public $gaPammWithdrawalOperations = array(
        'DEPOSIT'				=> 0,	// Пополнить счет
        'ALL_AND_CLOSE'			=> 1,	// Снять все деньги и закрыть счет
        'DEFINED_SUM'			=> 2,	// Снять фиксированную сумму
        //'UP_TO_MIN'			=> 3,	// Снять все до минимального остатка			// Устарело. Не используется.
        'DEPOSIT_COMMISSION'	=> 4,	// Пополнение счета управляющего за счет досрочного снятия
        'PRETERM'				=> 5,	// Досрочно снять все (при этом взымается штраф за досрочное снятие)
        'PROFIT'				=> 6,	// Снять только прибыль
        //'CONVERT_TO_PAMM2_0'	=> 7,	// Перевести в ПАММ 2.0
        //'TRANSFER_TO_PAMM2_0'	=> 8,	// Перевести фиксированную сумму в ПАММ 2.0
        'CLOSE_PAMM'			=> 9,	// То же самое что и ALL_AND_CLOSE, но используется только при закрытии ПАММ
    );

    // Список статусов распоряжений по ПАММ-счетам
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
        'OFFLINE'		  => 0,
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

    public $Errors = array();

    private $db;
    private $iPartnerId = PAMM_WL_PARTNER_ID;
    private $iPartnerMTLogin = 0;
    private $aPermissions = array();

    function __construct()
    {
        $CI =& get_instance();
        $CI->load->database();
        $this->db = $CI->db;
        $CI->load->library('email');
        $this->email = $CI->email;
        $CI->load->library('WebActions');
        $this->webactions = $CI->webactions;
    }

    public function setPartnerId($id)
    {
        $this->iPartnerId = $id;
        return true;
    }

    public function setPartnerMTLogin($login)
    {
        $this->iPartnerMTLogin = $login;
        return true;
    }

    public function setPartnerPermissions($array)
    {
        $this->aPermissions = $array;
        return true;
    }

    public function hasErrors()
    {
        return !empty($this->Errors);
    }

    public function getErrors()
    {
        return $this->Errors;
    }

    /* ПАММ-счета */

    /**
     * Создает новый ПАММ-счет
     *
     * @param integer $iAIPamm
     * @param integer $iPammMTLogin     номер MT-счета, который надо зарегистрировать как ПАММ счет
     * @param integer $iInvMTLogin
     * @param float $fAmount
     * @param integer $iUserId          id пользователя
     * @param integer $iAccountId
     * @param integer $iBonus
     * @param integer $iCommission
     * @param integer $iResponsibility  уровень ответственности
     * @param integer $iTradePeriod
     * @param integer $iCondPeriodic
     * @param integer $iMinBalance
     * @param integer $iAllowCopyTrades
     * @param float $fCopyTradesCommission
     * @param integer $iLossLimit
     * @param integer $iMCStopout
     * @param integer $iAgentBonus
     * @param integer $iAgentBonusProfit
     * @param integer $iAgentPayDelay
     * @param integer $iReportLevel
     * @param string $sContactMethod
     * @param string $sTSDesc
     * @return array|bool
     *          при успехе - array:
     * 			    pamm_mt_login - номер mt-счета созданного памм
     * 			    manager_investor_id - номер inv-счета управляющего
     * 			    pmo_id - номер распоряжения пополнения КУ
     */
    function createPamm($iAIPamm, $iPammMTLogin, $iInvMTLogin, $fAmount, $iUserId, $iAccountId, $iBonus, $iCommission, $iResponsibility, $iTradePeriod, $iCondPeriodic, $iMinBalance, $iMCStopout, $iAllowCopyTrades, $fCopyTradesCommission, $iLossLimit, $iAgentBonus, $iAgentBonusProfit, $iAgentPayDelay, $iReportLevel, $sContactMethod, $sTSDesc)
    {
        $aFields = array(
            'responsibility'=> $iResponsibility,
            'partner_id'    => $this->iPartnerId,
            'pamm_mt_login' => $iPammMTLogin,
            'capital'       => 0,
            'min_capital'   => PAMM_MIN_CAPITAL,
        );
        $this->db->insert('pamm_accounts', $aFields);
        $iPammId = $this->db->insert_id();
        if (empty($iPammId))
        {
            $this->Errors[__FUNCTION__] = 'PammAlreadyCreated';
            return false;
        }

        $this->db->set('partner_id', $this->iPartnerId);
        $this->db->set('ai_pamm', $iAIPamm);
        $this->db->set('pamm_mt_login', $iPammMTLogin);
        $this->db->set('create_date', "CURRENT_TIMESTAMP()", FALSE);
        $this->db->set('open_date', "CURRENT_TIMESTAMP()", FALSE);
        $this->db->set('bonus', $iBonus);
        $this->db->set('commission', $iCommission);
        $this->db->set('responsibility', $aFields['responsibility']);
        $this->db->set('trade_period', $iTradePeriod);
        $this->db->set('conditionally_periodic', $iCondPeriodic);
        $this->db->set('adjusting_rollover_date', "DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL (7 - IF(DAYOFWEEK(CURDATE())=7, 0, DAYOFWEEK(CURDATE()))) DAY), '%Y-%m-%d " . PAMM_ROLLOVER_TIME . "')", FALSE);
        $this->db->set('last_rollover_date', "1970-01-01");
        $this->db->set('next_rollover_date', "DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL (7 - IF(DAYOFWEEK(CURDATE())=7, 0, DAYOFWEEK(CURDATE()))) DAY), '%Y-%m-%d " . PAMM_ROLLOVER_TIME . "')", FALSE);
        $this->db->set('min_balance', $iMinBalance);
        $this->db->set('mc_stopout', $iMCStopout);
        $this->db->set('allow_copy_trades', $iAllowCopyTrades);
        $this->db->set('copy_trades_commission', $fCopyTradesCommission);
        $this->db->set('loss_limit', $iLossLimit);
        $this->db->set('status', $this->gaPammOffersStatuses['OPENED']);
        $this->db->set('agent_bonus', $iAgentBonus);
        $this->db->set('agent_bonus_profit', $iAgentBonusProfit);
        $this->db->set('agent_pay_delay', $iAgentPayDelay);
        $this->db->set('report_level', $iReportLevel);
        $this->db->set('contact_method', $sContactMethod);
        $this->db->set('ts_desc', $sTSDesc);
        $this->db->insert('pamm_offers');
        $iOfferId = $this->db->insert_id();

        // Создаем инвесторский счет для управляющего, чтобы он тоже учавствовал в доле ПАММ
        $aFields = array(
            'partner_id'	=> $this->iPartnerId,
            'user_id'		=> $iUserId,
            'account_id'	=> $iAccountId,
            'offer_id'		=> $iOfferId,
            'pamm_mt_login'	=> $iPammMTLogin,
            'inv_mt_login'	=> $iInvMTLogin,
            'created_at'	=> time(),
            'activated_at'	=> time(),
            'current_sum'	=> 0,
            'type'			=> $this->gaPammInvestorTypes['MANAGER'],
        );
        $this->db->insert('pamm_investors', $aFields);
        $iInvestorId = $this->db->insert_id();

        if (!empty($iPammId) && !empty($iOfferId) && !empty($iInvestorId))
        {
            $aFields = array(
                'investor_id' => (int)$iInvestorId,
            );
            $this->db->where('pamm_mt_login', $iPammMTLogin);
            $this->db->update('pamm_offers', $aFields);

            $iPmoId = $this->createMoneyOrder($iInvestorId, $iUserId, $this->gaPammWithdrawalOperations['DEPOSIT'], $fAmount);

            if (!empty($iPmoId))
            {
                $aFields = array(
                    'capital' => $fAmount,
                );
                $this->db->where('pamm_mt_login', $iPammMTLogin);
                $this->db->update('pamm_accounts', $aFields);

                $aFields = array(
                    'initial_capital' => $fAmount,
                );
                $this->db->where('pamm_mt_login', $iPammMTLogin);
                $this->db->update('pamm_offers', $aFields);
            }

            $this->updatePammRating();

            return array(
                'offer_id'      => $iOfferId,
                'investor_id'   => $iInvestorId,
                'pmo_id'        => $iPmoId,
            );
        }

        $this->Errors[__FUNCTION__] = 'CommonError';
        return false;
    }

    /**
     * Функция закрытия ПАММ
     *
     * @param integer $iPammMTLogin
     * @param integer $iUserId
     * @param bool $bAllowCloseTrades
     * @param bool $bAllow
     * @return bool|array
     */
    function closePamm($iPammMTLogin, $iUserId, $bAllowCloseTrades = false, $bAllow = false)
    {
        ignore_user_abort(true);
        set_time_limit(0);

        // Получаем информацию по памм-счету
        $aPammDetails = $this->getPammDetails($iPammMTLogin);
        if ($aPammDetails['user_id'] != $iUserId)
        {
            $this->Errors[__FUNCTION__] = 'WrongUserId';
            return false;
        }

        $this->db
            ->select('ROUND(IFNULL(mtu.MARGIN_FREE, 0), 2) AS MARGIN_FREE', FALSE)
            ->select('ROUND(IFNULL(SUM(pmo.sum), 0), 2) AS withdrawal_sum', FALSE)
            ->from('pamm_offers AS po')
            ->join('MT4_USERS AS mtu', 'mtu.LOGIN = po.pamm_mt_login', 'left')
            ->join('pamm_investors AS ai', 'ai.pamm_mt_login = po.pamm_mt_login AND ai.status = 1 AND ai.for_index = 0')
            ->join('pamm_money_orders AS pmo', 'pmo.investor_id = ai.id AND pmo.status = 0 AND pmo.operation > 0', 'left')
            ->where('po.pamm_mt_login', $iPammMTLogin);
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() == 0)
        {
            $this->Errors[__FUNCTION__] = 'MarginFailed';
            return false;
        }
        $aPammAccountMargins = $rQuery->row_array();
        $this->prn($aPammAccountMargins);

        $sAdminNote = "";
        if ($aPammDetails['mc_stopout'] == 1 && bccomp($aPammDetails['current_capital'], 500, 2) == -1)
        {
            $sAdminNote = json_encode(['mc_stopout' => 1, 'current_capital' => $aPammDetails['current_capital']]);
        }
        if (bccomp($aPammAccountMargins['withdrawal_sum'], $aPammAccountMargins['MARGIN_FREE'], 2) == 1)
        {
            $sAdminNote = json_encode($aPammAccountMargins);
        }
        if (!empty($sAdminNote))
        {
            $this->db->where('pamm_mt_login', $iPammMTLogin);
            $this->db->set('admin_note', $sAdminNote);
            $this->db->update('pamm_offers');
        }

        // Проверка на открытые и отложенные ордера
        $aResult = $this->webactions->getOpenedTrades($iPammMTLogin);
        if (!empty($aResult) && empty($bAllowCloseTrades))
        {
            $this->Errors[__FUNCTION__] = 'OpenedTradesExists';
            return false;
        }

        // Запрещаем создавать какие-либо распоряжения
        $aFields = array(
            'allow_deposit'		=> '0',
            'allow_withdraw'	=> '0',
        );
        $this->db->where('pamm_mt_login', $iPammMTLogin);
        $this->db->update('pamm_offers', $aFields);

        // @todo: Вызов партнерского ролловера - сделать это вне апи

        // Выключаем возможность торговли
        $this->webactions->updateUser($iPammMTLogin, 'ENABLE=0|ENABLE_READ_ONLY=1');

        // Получаем активных инвесторов
        $aInvestors = $this->getInvestorsDetails($iPammMTLogin, 0, 1);
        // Отменяем все невыполненные распоряжения по каждому инвестору
        $aInvestorMoneyOrders = $this->getMoneyOrdersList(0, 0, $iPammMTLogin, -1, $this->gaPammPaymentOrdersStatuses['PENDING'], true);
        if (!empty($aInvestorMoneyOrders))
        {
            foreach ($aInvestorMoneyOrders as $aInvestorMoneyOrder)
            {
                $fNewSum = INF;
                if ($aInvestorMoneyOrder['operation']==$this->gaPammWithdrawalOperations['ALL_AND_CLOSE'])
                {
                    $fNewSum = $aInvestors[$aInvestorMoneyOrder['investor_id']]['availsum'];
                }
                if ($aInvestorMoneyOrder['operation']==$this->gaPammWithdrawalOperations['PROFIT'])
                {
                    $fNewSum = ($aInvestors[$aInvestorMoneyOrder['investor_id']]['period_profit']<0)?0:$aInvestors[$aInvestorMoneyOrder['investor_id']]['period_profit'];
                }
                $this->cancelMoneyOrder($aInvestorMoneyOrder['id'], $aInvestorMoneyOrder['user_id'], $this->gaPammPaymentOrdersStatuses['CANCELED_BY_CLOSING_PAMM'], $fNewSum, true);
            }
        }

        // Закрываем все сделки
        if (!empty($bAllowCloseTrades))
        {
            $this->webactions->closeOrders($iPammMTLogin, 1);
        }

        // Очищаем кеш инвесторов
        $this->db->where('pamm_mt_login', $iPammMTLogin);
        $this->db->delete('pamm_investors_stat');

        // Получаем активных инвесторов
        $aInvestorsList = $this->getInvestorsDetails($iPammMTLogin, 0, 1);

        // Получаем маржинальные данные по ПАММ-счету
        $aMarginInfo = $this->webactions->getTradesMarginInfo($iPammMTLogin);
        if (empty($aMarginInfo))
        {
            $this->Errors[__FUNCTION__] = 'TradesMarginInfoError';
            return false;
        }

        $fAvailSumPayout = $aMarginInfo['equity'];

        // Опеределяем окончательную сумму к выплате
        $aInvestors = array();
        foreach ($aInvestorsList as $aInvestor)
        {
            if ($aInvestor['investor_type'] == $this->gaPammInvestorTypes['INVESTOR'])
            {
                $aInvestor['final_sum'] = $aInvestor['availsum'];

                // Если это ПАММ 2.0 - то надо выплачивать из расчета,
                // что выплачивается либо Застрахованная сумма, либо бОльшая
                if (!empty($aInvestor['responsibility']) && bccomp($aInvestor['final_sum'], $aInvestor['insured_sum'], 2) == -1)
                {
                    $aInvestor['final_sum'] = $aInvestor['insured_sum'];
                }

                // Остаток - управляющему
                $fAvailSumPayout = bcsub($fAvailSumPayout, $aInvestor['final_sum'], 2);
            }

            // Запоминаем
            $aInvestors[$aInvestor['investor_id']] = $aInvestor;
        }
        // Остаток - управляющему
        if ($aInvestors[$aPammDetails['manager_investor_id']]['availsum'] < $fAvailSumPayout)
        {
            $aInvestors[$aPammDetails['manager_investor_id']]['final_sum'] = ($fAvailSumPayout<0)?0:$fAvailSumPayout;
        }
        else
        {
            //$aInvestors[$aPammDetails['manager_investor_id']]['final_sum'] = $aInvestors[$aPammDetails['manager_investor_id']]['availsum'];
            $aInvestors[$aPammDetails['manager_investor_id']]['final_sum'] = $fAvailSumPayout;
        }
        if ($aInvestors[$aPammDetails['manager_investor_id']]['final_sum'] < 0)
        {
            $aInvestors[$aPammDetails['manager_investor_id']]['final_sum'] = 0;
        }
        //$fAvailSumPayout = bcsub($fAvailSumPayout, $aInvestors[$aPammDetails['manager_investor_id']]['final_sum'], 2);
        $this->prn($aInvestors, 'fAvailSumPayout', $fAvailSumPayout, "aInvestors[aPammAccount['manager_investor_id']]['final_sum']", $aInvestors[$aPammDetails['manager_investor_id']]['final_sum']);

        if (!$bAllow) {
            exit("Not allowed");
        }

        $iCloseTime = time();
        if (!empty($aInvestors))
        {
            foreach ($aInvestors as $aInvestor)
            {
                if (bccomp($aInvestor['final_sum'], 0, 2) >= 0)
                {
                    // Собираем данные для pamm_money_orders
                    $aFields = array(
                        'sum'				=> $aInvestor['final_sum'],
                        'mt_login'			=> $aInvestor['inv_mt_login'],
                        'investor_id'		=> $aInvestor['investor_id'],
                        'created_at'		=> $iCloseTime,
                        'confirmed_at'		=> $iCloseTime,
                        'status'			=> $this->gaPammPaymentOrdersStatuses['SUCCESS'],
                        'operation'			=> $this->gaPammWithdrawalOperations['CLOSE_PAMM'],
                        'money_withdrawn'	=> 1,
                    );
                    //$this->prn('pamm_money_orders insert', $aFields);
                    $this->db->insert('pamm_money_orders', $aFields);
                    $iPMOid = $this->db->insert_id();

                    if (!empty($iPMOid) && bccomp($aInvestor['final_sum'], 0, 2) == 1)
                    {
                        $sComment = "PI{$aInvestor['inv_mt_login']}/{$aInvestor['investor_id']}/{$aInvestor['pamm_mt_login']}/{$iPMOid}/C";
                        $iPammMTLogin = $aInvestor['pamm_mt_login'];

                        // Снимаем деньги с памм-счета и переводим на инв-счет
                        // Если это не индексный счет, то необходим перевод. В противном случае - снимаем только с ПАММ-счета
                        if (empty($aInvestor['for_index']))
                        {
                            // Снимаем деньги с памм/инв-счета и переводим на инв/памм-счет
                            $this->webactions->makeTransfer($aInvestor['pamm_mt_login'], $aInvestor['inv_mt_login'], $aInvestor['final_sum'], $sComment, $sComment, true);
                        }
                        else
                        {
                            $this->webactions->makePayment($aInvestor['pamm_mt_login'], bcmul($aInvestor['final_sum'], -1, 2), $sComment, true);
                        }

                        // Собираем данные для pamm_payments
                        $fSum = $aInvestor['final_sum']*-1;
                        $aFields = array(
                            'investor_id'	=> $aInvestor['investor_id'],
                            'sum'			=> $fSum,
                            'created_at'	=> $iCloseTime,
                            'type'			=> 2,
                        );
                        $this->db->insert('pamm_payments', $aFields);

                        // Вносим данные в pamm_investors.total_withdrawal
                        $this->db->where('id', (int)$aInvestor['investor_id']);
                        $this->db->set('total_withdrawal', "total_withdrawal + {$fSum}", FALSE);
                        $this->db->update('pamm_investors');
                    }
                } else {
                    $aFields = array(
                        'investor_id'	=> $aInvestor['investor_id'],
                        'sum'			=> 0,
                        'created_at'	=> $iCloseTime,
                        'type'			=> 2,
                    );
                    $this->db->insert('pamm_payments', $aFields);
                }
            }
        }

        // Закрываем соответствующие инвесторские счета
        $aFields = array(
            'closed_at'		=> $iCloseTime,
            'status'		=> $this->gaPammInvestorAccountStatuses['CLOSED'],
            'current_sum'	=> 0,
            'insured_sum'	=> 0,
        );
        $this->db->where('pamm_mt_login', $iPammMTLogin);
        $this->db->where("status NOT IN ({$this->gaPammInvestorAccountStatuses['CLOSED']}, {$this->gaPammInvestorAccountStatuses['DELETED']})", NULL, FALSE);
        $this->db->update('pamm_investors', $aFields);

        // Закрываем ПАММ-счет
        $aFields = array(
            'status'	=> $this->gaPammAccountStatuses['CLOSED_WPO'],
        );
        $this->db->where('pamm_mt_login', $iPammMTLogin);
        $this->db->update('pamm_accounts', $aFields);

        // Закрываем оферты
        $this->db->where('pamm_mt_login', $iPammMTLogin);
        $this->db->set('close_date', "CURRENT_TIMESTAMP()", FALSE);
        $this->db->set('status', $this->gaPammOffersStatuses['CLOSED']);
        $this->db->update('pamm_offers');

        // Убираем из рейтинга
        $this->db->where('mt_login', $iPammMTLogin);
        $this->db->delete('pamm_rating');

        if (!empty($iPammMTLogin))
        {
            // Выключаем счёт в MT
            $this->webactions->updateUser($iPammMTLogin, 'ENABLE=0|STATE=CLOSED');
            //$this->webactions->updateUser($iPammMTLogin, 'GROUP=TRASH');

            //$this->db->where('LOGIN', $iPammMTLogin);
            //$this->db->delete('MT4_USERS');
        }

        return $aInvestors;
    }

    /**
     * Получает данные о ПАММ-счете
     *
     * @param integer $iPammMTLogin
     *
     * @return array|bool
     */
    function getPammDetails($iPammMTLogin)
    {
        $this->db
            ->select("po.pamm_mt_login AS pamm_mt_login")
            ->select("po.id AS offer_id")
            ->select("po.partner_id AS partner_id")
            ->select("po.create_date AS create_date")
            ->select("po.open_date AS open_date")
            ->select("po.close_date AS close_date")
            ->select("po.bonus AS offer_bonus")
            ->select("po.commission AS offer_commission")
            ->select("po.responsibility AS offer_responsibility")
            ->select("po.initial_capital AS initial_capital")
            ->select("po.trade_period AS trade_period")
            ->select("po.conditionally_periodic AS conditionally_periodic")
            ->select("po.adjusting_rollover_date AS adjusting_rollover_date")
            ->select('IF(po.last_rollover_date < "2000-01-01", "1970-01-01", po.last_rollover_date) AS last_rollover_date', FALSE)
            ->select('IF(po.last_rollover_date < "2000-01-01", 0, UNIX_TIMESTAMP(po.last_rollover_date)) AS last_rollover_unixtime', FALSE)
            ->select("IF(po.adjusting_rollover_date = po.next_rollover_date, DATE_ADD(po.next_rollover_date, INTERVAL po.trade_period*7 DAY), po.next_rollover_date) AS next_rollover_date", FALSE)
            ->select("IF(po.status = {$this->gaPammOffersStatuses['CREATED']}, {$this->gfPammMinCapital}, po.min_balance) AS min_balance", FALSE)
            ->select("IF(po.allow_deposit  = 1 AND (po.status = 1 OR po.status = 3)/* AND gs.value = 0*/, 1, 0) AS allow_deposit", FALSE)
            ->select("IF(po.allow_withdraw = 1 AND (po.status = 1 OR po.status = 3)/* AND gs.value = 0*/, 1, 0) AS allow_withdraw", FALSE)
            ->select("po.mc_stopout AS mc_stopout")
            ->select("po.trade_immunity AS trade_immunity")
            ->select("po.status AS status")
            ->select("po.agent_bonus AS agent_bonus")
            ->select("po.agent_bonus_profit AS agent_bonus_profit")
            ->select("po.agent_pay_delay AS agent_pay_delay")
            ->select("po.report_level AS report_level")
            ->select("po.contact_method AS contact_method")
            ->select("po.ts_desc AS ts_desc")
            ->select("po.admin_note AS admin_note")
            ->select("IF((po.allow_deposit = 0 AND po.allow_withdraw = 0 AND (po.status = 1 OR po.status = 3))/* OR gs.value = 1*/, 1, 0) AS is_rollover", FALSE)
            ->select("aim.id AS manager_investor_id")
            ->select("aim.user_id AS user_id")
            ->select("FROM_UNIXTIME(aim.created_at) AS reg_date")
            ->select("FROM_UNIXTIME(aim.activated_at) AS activate_date")
            ->select("IFNULL(pr.rdrawdown, 0) AS rdrawdown", FALSE)
            ->select("IFNULL(pr.mdrawdown, 0) AS mdrawdown", FALSE)
            ->select("IFNULL(pr.opened_tickets, 0) AS opened_tickets", FALSE)
            ->select("IF(po.status = 0, 0, IFNULL(FLOOR(pmcc.capital*100)/100, 0)) AS current_capital", FALSE)
            ->select("IF(IFNULL(pr.investments, 0) = 0, 0, mtu.BALANCE - IF(IF(po.status = 0, 0, IFNULL(FLOOR(pmcc.capital*100)/100, 0)) > 0, IF(po.status = 0, 0, IFNULL(FLOOR(pmcc.capital*100)/100, 0)), 0)) AS current_invcapital", FALSE)
            ->select("IF(po.status = 0, 0, IFNULL(pr.investments, 0)) AS investments", FALSE)
            ->select("IF(po.responsibility > 0, TRUNCATE(IFNULL(pr.permitted_investments, 0), 2), 9999999) AS permitted_investments", FALSE)
            ->select("IFNULL(pr.insured_sum, 0) AS insured_sum", FALSE)
            ->select("0 AS allow_user_close", FALSE)
            ->from("pamm_offers AS po")
            ->join("pamm_accounts AS pa", "pa.pamm_mt_login = po.pamm_mt_login", 'left')
            ->join("pamm_manager_capital_current AS pmcc", "pmcc.pamm_mt_login = pa.pamm_mt_login", 'left')
            ->join("pamm_investors AS aim", "aim.pamm_mt_login = pa.pamm_mt_login AND aim.type = {$this->gaPammInvestorTypes['MANAGER']}")
            ->join("pamm_rating AS pr", "pr.mt_login = pa.pamm_mt_login", 'left')
            ->join("MT4_USERS AS mtu", "mtu.LOGIN = pa.pamm_mt_login", 'left')
            ->where('po.pamm_mt_login', $iPammMTLogin)
            ->limit(1);
        $rQuery = $this->db->get();

        if ($rQuery->num_rows() == 0)
        {
            $this->Errors[__FUNCTION__] = 'PammNotFound';
            return false;
        }
        $aPammAccount = $rQuery->row_array();
        $rQuery->free_result();

        return $aPammAccount;
    }

    /**
     * Обновляет данные в деталях оферты
     *
     * @param integer $iPammMTLogin
     * @param integer $iUserId
     * @param integer $iAllowCopyTrades
     * @param float $fCopyTradesCommission
     * @param integer $iLossLimit
     * @param integer $iAgentBonus
     * @param integer $iAgentBonusProfit
     * @param integer $iAgentPayDelay
     * @param string $sContactMethod
     * @param string $sTSdesc
     * @return integer
     */
    function сhangePammDetails($iPammMTLogin, $iUserId, $iAllowCopyTrades, $fCopyTradesCommission, $iLossLimit, $iAgentBonus, $iAgentBonusProfit, $iAgentPayDelay, $sContactMethod, $sTSdesc)
    {
        $aPammDetails = $this->getPammDetails($iPammMTLogin);
        if ($aPammDetails['partner_id'] != $this->iPartnerId || $aPammDetails['user_id'] != $iUserId)
        {
            $this->Errors[__FUNCTION__] = 'PammNotFound';
            return false;
        }

        $aFields = array(
            'allow_copy_trades'     => $iAllowCopyTrades,
            'copy_trades_commission'=> $fCopyTradesCommission,
            'loss_limit'            => $iLossLimit,
            'agent_bonus'           => $iAgentBonus,
            'agent_bonus_profit'    => $iAgentBonusProfit,
            'agent_pay_delay'       => $iAgentPayDelay,
            'contact_method'        => $sContactMethod,
            'ts_desc'               => $sTSdesc,
        );
        $this->db->where('pamm_mt_login', $iPammMTLogin);
        $this->db->update('pamm_offers', $aFields);
        $iResult = $this->db->affected_rows();

        return $iResult;
    }

    /**
     * Обновляет данные в деталях оферты
     *
     * @param integer $iPammMTLogin
     * @param integer $iUserId
     * @param integer $iSourcePammMTLogin
     * @param float $fCopyCoefficient
     * @return integer
     */
    function setTradesCopy($iPammMTLogin, $iUserId, $iSourcePammMTLogin, $fCopyCoefficient)
    {
        $aPammDetails = $this->getPammDetails($iPammMTLogin);
        if ($aPammDetails['partner_id'] != $this->iPartnerId || $aPammDetails['user_id'] != $iUserId)
        {
            $this->Errors[__FUNCTION__] = 'PammNotFound';
            return false;
        }

        $aFields = array(
            'pamm_mt_login'         => $iPammMTLogin,
            'source_pamm_mt_login'  => $iSourcePammMTLogin,
            'copy_coefficient'      => $fCopyCoefficient,
            'date_linked'           => "NOW()",
        );
        $this->db->replace('pamm_ai_links', $aFields, FALSE);
        $iResult = $this->db->affected_rows();

        return $iResult;
    }

    /**
     * Обновляет уровень ответственности по указанному ПАММ 2.0
     *
     * @param integer $iPammMTLogin
     * @return boolean
     */
    function updatePammResponsibleLevel($iPammMTLogin)
    {
        // Вычисляем сумму ответственности управляющего
        $this->db
            ->select("ROUND(SUM(ai.insured_sum), 2) AS responsible_level", FALSE)
            ->from('pamm_investors AS ai')
            ->where('ai.pamm_mt_login', $iPammMTLogin)
            ->where('ai.status', $this->gaPammInvestorAccountStatuses['ACTIVATED'])
            ->where('ai.type', $this->gaPammInvestorTypes['INVESTOR']);
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() == 0)
        {
            $fResponsibleLevel = 0;
        }
        else
        {
            $fResponsibleLevel = $rQuery->row()->responsible_level;
        }

        $fResponsibleLevel = round($fResponsibleLevel*1.05, 2); // Страховочные 5%
        //trigger_error("responsible level for {$iPammMTLogin} is {$fResponsibleLevel}", E_USER_NOTICE);

        // Запоминаем сумму в структуре МТ-счета
        if (!empty($fResponsibleLevel))
        {
            $this->webactions->updateUser($iPammMTLogin, "STATE={$fResponsibleLevel}|RESERVED20={$fResponsibleLevel}");
        }

        return true;
    }

    /**
     * Получение списка ПАММ-счетов
     *
     * @param integer $iOfferStatus
     * @param integer $iCloseTime
     * @return array|bool
     */
    function getPammList($iOfferStatus = 1, $iCloseTime = 0)
    {
        if (!in_array('CAN_VIEW_FOREING', $this->aPermissions))
        {
            $this->db->where('pr.partner_id', $this->iPartnerId);
        }

        if (!empty($iCloseTime))
        {
            $this->db->where("po.close_date > FROM_UNIXTIME({$iCloseTime})", NULL, FALSE);
        }
        if (!empty($iOfferStatus))
        {
//            $this->db->where('pr.profitness > ', -100);
            //$this->db->where('pr.profit <> ', 0);
        }
        $this->db
            ->select('po.id AS offer_id')
            ->select('po.partner_id')
            ->select('IFNULL(pi.user_id, 0) AS user_id', FALSE)
            ->select('po.investor_id')
            ->select('po.ai_pamm')
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
            ->select('po.status AS offer_status')
            ->select('po.allow_copy_trades')
            ->select('po.copy_trades_commission')
            ->select('po.loss_limit')
            ->select('po.agent_bonus')
            ->select('po.agent_bonus_profit')
            ->select('po.agent_pay_delay')
            ->select('po.report_level')
            ->select('po.cancel_manager_pmo')
            ->select('HEX(po.contact_method) AS contact_method')
            ->select('HEX(po.ts_desc) AS ts_desc')
            ->select('HEX(po.admin_note) AS admin_note')
            ->select('IFNULL(pr.nickname, 0) AS nickname', FALSE)
            ->select('IFNULL(pr.current_capital, 0) AS current_capital', FALSE)
            ->select('IFNULL(pr.investments, 0) AS investments', FALSE)
            ->select('IFNULL(pr.insured_sum, 0) AS insured_sum', FALSE)
            ->select('IFNULL(pr.offer_exists, 0) AS offer_exists', FALSE)
            ->select('IFNULL(pr.profit, 0) AS profit', FALSE)
            ->select('IF(IFNULL(pr.profitness, 0) > -100, IFNULL(pr.profitness, 0), -100) AS profitness', FALSE)
            ->select('IFNULL(pr.profitness_last_month, 0) AS profitness_last_month', FALSE)
            ->select('IFNULL(pr.activated_at, 0) AS activated_at', FALSE)
            ->select('IFNULL(pr.rdrawdown, 0) AS rdrawdown', FALSE)
            ->select('IFNULL(pr.mdrawdown, 0) AS mdrawdown', FALSE)
            ->select('IFNULL(pr.opened_tickets, 0) AS opened_tickets', FALSE)
            ->select('IFNULL(pr.permitted_investments, 0) AS permitted_investments', FALSE)
            ->select('IFNULL(pr.last_update, 0) AS last_update', FALSE)
            ->from('pamm_offers AS po')
            ->join('pamm_rating AS pr', 'pr.mt_login = po.pamm_mt_login', 'left')
            ->join('pamm_investors AS pi', 'pi.pamm_mt_login = po.pamm_mt_login AND pi.type = 2', 'left')
            ->where('po.status', $iOfferStatus)
            ->order_by('po.pamm_mt_login', 'ASC');
        $rQuery = $this->db->get();
        //$this->prn($this->db->last_query());
        if ($rQuery->num_rows() == 0)
        {
            return false;
        }

        return $rQuery->result_array();
    }

    /**
     * Получает статистику доходности ПАММ-счета
     *
     * @param integer $iPammMTLogin
     * @param integer $iPeriod
     * @param integer $iTimeFrom
     * @param integer $iTimeTo
     * @return bool|array
     */
    function getProfitStatement($iPammMTLogin, $iPeriod=1, $iTimeFrom = 0, $iTimeTo = 0)
    {
        if ($iPeriod == 1)
        {
            $sPeriodStart   = 'DATE_FORMAT(tp.CLOSE_TIME, "%Y-%m-01 00:00:00")';
            $sPeriodEnd     = 'DATE_ADD(DATE_FORMAT(tp.CLOSE_TIME, "%Y-%m-01 00:00:00"), INTERVAL 1 MONTH)';
            $sGroupBy       = 'DATE_FORMAT(tp.CLOSE_TIME, "%Y%m")';
            $iCloseTime     = 1;
        }
        elseif ($iPeriod == 2)
        {
            $sPeriodStart   = 'DATE_SUB(DATE_FORMAT(tp.CLOSE_TIME, "%Y-%m-%d 00:00:00"), INTERVAL WEEKDAY(tp.CLOSE_TIME) DAY)';
            $sPeriodEnd     = 'DATE_ADD(DATE_SUB(DATE_FORMAT(tp.CLOSE_TIME, "%Y-%m-%d 00:00:00"), INTERVAL WEEKDAY(tp.CLOSE_TIME) DAY), INTERVAL 6 DAY)';
            $sGroupBy       = 'YEARWEEK(tp.CLOSE_TIME, 3)';
            $iCloseTime     = 1;
        }
        elseif ($iPeriod == 3)
        {
            $sPeriodStart   = 'tp.CLOSE_TIME';
            $sPeriodEnd     = 'tp.CLOSE_TIME';
            $sGroupBy       = 'tp.TICKET';
            $iCloseTime     = 1290376800;
            $this->db->select('tp.SYMBOL AS symbol');
        }
        else
        {
            $this->Errors[__FUNCTION__] = 'InvalidParams';
            return false;
        }

        if (!empty($iTimeFrom))
        {
            $this->db->where("tp.CLOSE_TIME > FROM_UNIXTIME({$iTimeFrom})", NULL, FALSE);
        }
        if (!empty($iTimeTo))
        {
            $this->db->where("tp.CLOSE_TIME < FROM_UNIXTIME({$iTimeTo})", NULL, FALSE);
        }
        $this->db
            ->select('LOGIN AS pamm_mt_login')
            ->select('SUM(tp.LOTS) AS total_lots')
            ->select('ROUND(SUM(tp.TOTAL_PROFIT), 2) AS total_profit_sum', FALSE)
            ->select('ROUND(SUM(tp.TOTAL_PROFIT/tp.BALANCE)*100, 2) AS total_profit_percent', FALSE)
            ->select("{$sPeriodStart} AS period_start", FALSE)
            ->select("{$sPeriodEnd} AS period_end", FALSE)
            ->from('pamm_tickets_profit_stat AS tp')
            ->where('tp.LOGIN', $iPammMTLogin)
            ->where("tp.CLOSE_TIME > FROM_UNIXTIME({$iCloseTime})", NULL, FALSE)
            ->group_by($sGroupBy, FALSE)
            ->order_by('tp.CLOSE_TIME');
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() == 0)
        {
            return false;
        }

        return $rQuery->result_array();
    }

    /**
     * Получает список пользователей, которые вложили средства в ПАММ-счет
     *
     * @param integer $iPammMTLogin
     * @return bool|array
     */
    function getPammUsers($iPammMTLogin)
    {
        $this->db
            ->select('partner_id')
            ->select('user_id')
            ->from('pamm_investors_list')
            ->where('pamm_mt_login', $iPammMTLogin)
            ->group_by(array('partner_id', 'user_id'));
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() == 0)
        {
            return false;
        }

        return $rQuery->result_array();
    }

    /**
     * Получает список зафиксированных значений максимальной просадки на ПАММ-счете
     *
     * @param integer $iPammMTLogin
     * @param integer $iFromUnixTime
     * @return array|bool
     */
    function getPammMaximumDrawdowns($iPammMTLogin, $iFromUnixTime=0)
    {
        $this->db
            ->select('statement_date AS drawdown_date')
            ->select('max_drawdown_ft AS max_drawdown')
            ->from('pamm_detailed_statements')
            ->where('pamm_mt_login', $iPammMTLogin)
            ->where("statement_date >= FROM_UNIXTIME({$iFromUnixTime})", NULL, FALSE)
            ->order_by('drawdown_date', 'ASC');
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() == 0)
        {
            return false;
        }

        return $rQuery->result_array();
    }

    /**
     * Получает список открытых сделок на ПАММ-счете, если режим отчетности "Онлайн"
     *
     * @param integer $iPammMTLogin
     * @param integer $iUserId
     * @return array|bool
     */
    function getPammOpenedTickets($iPammMTLogin, $iUserId)
    {
        $this->db
            ->select('IF(mtt.CMD = 0, "buy", "sell") AS type')
            ->select('mtt.OPEN_TIME AS open_time')
            ->select('mtt.SYMBOL AS symbol')
            ->select('mtt.VOLUME/100 AS lots', FALSE)
            ->select('mtt.OPEN_PRICE AS open_price')
            ->select('mtt.COMMISSION AS commisssion')
            ->select('mtt.SWAPS AS swaps')
            ->select('mtt.PROFIT AS profit')
            ->from('pamm_offers AS po')
            ->join('MT4_USERS AS mtu', 'mtu.LOGIN = po.pamm_mt_login AND mtu.ENABLE = 1 AND (mtu.GROUP NOT LIKE "TRYPAMM%" OR mtu.LOGIN = 501149) AND mtu.LOGIN NOT IN (10105)', '', FALSE)
            ->join('pamm_investors AS ai', "ai.pamm_mt_login = po.pamm_mt_login AND ai.user_id = {$iUserId} AND ai.partner_id = {$this->iPartnerId} AND ai.status = {$this->gaPammInvestorAccountStatuses['ACTIVATED']}")
            ->join('MT4_TRADES AS mtt', 'mtt.LOGIN = mtu.LOGIN AND mtt.CMD < 2 AND mtt.CLOSE_TIME = "1970-01-01 00:00:00"', '', FALSE)
            ->where('po.pamm_mt_login', $iPammMTLogin)
            ->where('po.report_level', $this->gaReportLevels['ONLINE']);
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() == 0)
        {
            return false;
        }

        return $rQuery->result_array();
    }

    /**
     * Закрывает сделки по памму
     *
     * @param integer $iPammMTLogin
     * @return boolean
     */
    function closePammOpenedTickets($iPammMTLogin)
    {
        // Этим ПАММам сделки если и закрывать - то только вручную
        if (in_array($iPammMTLogin, array(5995, 6482, 7031, 7061, 7093, 7165, 7187, 9035, 10253, 11402, 11695, 18550, 26654, 500775, 501149, 503924, 505404)))
        {
            return false;
        }

        $bResult = $this->webactions->closeOrders($iPammMTLogin);
        if ($bResult)
        {
            $aFields = array(
                'cancel_manager_pmo' => 1,
            );
            $this->db->where('pamm_mt_login', $iPammMTLogin);
            $this->db->update('pamm_offers', $aFields);
        }

        return $bResult;
    }

    /* Инвесторские счета */

    /**
     * Создает инвесторский аккаунт для ПАММ-счета
     *
     * @param integer $iUserId
     * @param integer $iAccountId
     * @param integer $iPammMTLogin
     * @param float $fAmount
     * @param integer $iForIndex
     * @param integer $iInvMTLogin
     * @param integer $iForIC
     * @param integer $iAgentPayout
     * @param integer $iOverridePartnerId
     * @return array|boolean
     */
    function createInvestor($iUserId, $iAccountId, $iPammMTLogin, $fAmount, $iInvMTLogin, $iForIndex=0, $iForIC=0, $iAgentPayout=0, $iOverridePartnerId=0)
    {
        if (empty($iOverridePartnerId)) {
            $iOverridePartnerId = $this->iPartnerId;
        }
        // Если партнер, а инвестировать пытаются у нас - обрубить
        // или если партнеру запрещено инвестировать не у себя
        $aPammDetails = $this->getPammDetails($iPammMTLogin);
        if (($aPammDetails['partner_id'] != PAMM_WL_PARTNER_ID && $iOverridePartnerId == PAMM_WL_PARTNER_ID && empty($iForIndex))
         || ($aPammDetails['partner_id'] != $iOverridePartnerId && !in_array('CAN_USE_FOREING', $this->aPermissions)))
        {
            $this->Errors[__FUNCTION__] = 'PermissionsRequired';
            return false;
        }

        /*if (!empty($aPammDetails['offer_responsibility']))
        {
            $fPermittedInvestments = $this->getAllowedInvestmentsToPamm2_0($iPammMTLogin);
            if (bccomp($fAmount, $fPermittedInvestments, 2) == 1)
            {
                $this->Errors[__FUNCTION__] = 'AmountGreaterThanPermittedInvestments';
                return false;
            }
        }*/

        // Проверяем есть ли уже такой инв счет. Если есть - то сразу возвращаем его.
        $this->db
            ->select('ai.id')
            ->from('pamm_investors AS ai')
            ->where('ai.pamm_mt_login', $iPammMTLogin)
            ->where('ai.inv_mt_login', $iInvMTLogin)
            ->where('ai.account_id', $iAccountId)
            ->where('ai.user_id', $iUserId)
            ->where('ai.partner_id', $iOverridePartnerId)
            ->where('ai.status !=', $this->gaPammInvestorAccountStatuses['DELETED'])
            ->where('ai.for_index', $iForIndex)
            ->where('ai.for_ic', $iForIC);
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() > 0)
        {
            $this->Errors[__FUNCTION__] = 'InvestorAlreadyCreated';
            return $rQuery->row()->id;
        }

        if (empty($iForIndex) && empty($iForIC))
        {
            // Проверка на существование inv-mt-счета
            $aInvAccountInfo = $this->webactions->getUserInfo($iInvMTLogin);
            if (empty($aInvAccountInfo))
            {
                $this->Errors[__FUNCTION__] = "InvMTLoginNotFound";
                return false;
            }
            if (bccomp($aInvAccountInfo['equity'], $fAmount, 2) == -1)
            {
                $this->Errors[__FUNCTION__] = "NotEnoughEquity";
                return false;
            }
            if (bccomp($fAmount, $aPammDetails['min_balance'], 2) == -1)
            {
                $this->Errors[__FUNCTION__] = "AmountLessThanMinBalance";
                return false;
            }
        }

        $aFields = array(
            'partner_id'	=> $iOverridePartnerId,
            'user_id'		=> $iUserId,
            'account_id'	=> $iAccountId,
            'offer_id'		=> $aPammDetails['offer_id'],
            'pamm_mt_login'	=> $iPammMTLogin,
            'inv_mt_login'	=> $iInvMTLogin,
            'created_at'	=> time(),
            'for_index'	    => $iForIndex,
            'for_ic'	    => $iForIC,
        );
        $this->db->insert('pamm_investors', $aFields);
        $iInvestorId = $this->db->insert_id();

        // Проставляем агентские выплаты
        $this->changeAgentPaymentsStatus($iUserId, $iAgentPayout, $iOverridePartnerId);

        // Для индексных инв счетов не выполняем создание распоряжения
        if (empty($iForIndex) && empty($iForIC))
        {
            $bResult = $this->createMoneyOrder($iInvestorId, $iUserId, $this->gaPammWithdrawalOperations['DEPOSIT'], $fAmount);
            if (!$bResult)
            {
                $aFields = array(
                    'status'    => $this->gaPammInvestorAccountStatuses['DELETED'],
                );
                $this->db->where('id', $iInvestorId);
                $this->db->update('pamm_investors', $aFields);

                return false;
            }
        }

        return $iInvestorId;
    }

    /**
     * Получает данные по инвесторскому счету
     *
     * @param integer $iUserId
     * @param integer $iInvestorId
     * @param integer $iOverridePartnerId
     * @return array|bool
     */
    function getInvestorDetails($iUserId, $iInvestorId, $iOverridePartnerId=0)
    {
        $fPammMinCapital = PAMM_MIN_CAPITAL;

        // Получаем данные по РО
        /*$this->db
            ->select('value')
            ->from('global_settings')
            ->where('variable', "is_rollover");
        $rQuery = $this->db->get();
        $bRollover = ($rQuery->num_rows() > 0)?$rQuery->row()->value:0;
        $rQuery->free_result();*/
        $bRollover = 0;

        $this->db
            ->select('ai.id AS investor_id')
            ->select('ai.type AS investor_type')
            ->select('ai.partner_id')
            ->select('ai.user_id')
            ->select('ai.account_id')
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
            ->select('po.min_balance AS offer_min_balance')
            ->select('po.commission AS offer_commission')
            ->select('po.responsibility')
            ->select('UNIX_TIMESTAMP(DATE_SUB(DATE_FORMAT(DATE_ADD(po.last_rollover_date, INTERVAL 7-WEEKDAY(po.last_rollover_date) DAY), "%Y-%m-%d 00:00:00"), INTERVAL 1 HOUR)) AS trade_session_start_unixtime', FALSE)
            ->select("IF(po.allow_deposit  = 0 OR (po.status != 1 AND po.status != 3) OR {$bRollover} = 1 OR ai.status = 3 OR pmo0.id IS NOT NULL, 0, 1) AS allow_0", FALSE)
            ->select("IF(po.allow_withdraw = 0 OR (po.status != 1 AND po.status != 3) OR {$bRollover} = 1 OR ai.status != 1 OR pmo1.id IS NOT NULL OR (ai.type = {$this->gaPammInvestorTypes['MANAGER']} AND NOW() > DATE_SUB(po.next_rollover_date, INTERVAL 630 MINUTE)), 0, 1) AS allow_1", FALSE)
            ->select("IF(po.allow_withdraw = 0 OR (po.status != 1 AND po.status != 3) OR {$bRollover} = 1 OR ai.status != 1 OR pmo2.id IS NOT NULL OR (ai.type = {$this->gaPammInvestorTypes['MANAGER']} AND NOW() > DATE_SUB(po.next_rollover_date, INTERVAL 630 MINUTE)), 0, 1) AS allow_2", FALSE)
            ->select("IF(po.allow_withdraw = 0 OR (po.status != 1 AND po.status != 3) OR {$bRollover} = 1 OR ai.status != 1 OR pmo5.id IS NOT NULL OR ai.type = {$this->gaPammInvestorTypes['MANAGER']}, 0, 1) AS allow_5", FALSE)
            ->select("IF(po.allow_withdraw = 0 OR (po.status != 1 AND po.status != 3) OR {$bRollover} = 1 OR ai.status != 1 OR pmo6.id IS NOT NULL OR (ai.type = {$this->gaPammInvestorTypes['MANAGER']} AND NOW() > DATE_SUB(po.next_rollover_date, INTERVAL 630 MINUTE)), 0, 1) AS allow_6", FALSE)
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
            ->join('pamm_investors_stat AS pis', 'pis.investor_id = ai.id', 'left')
            ->join('pamm_money_orders AS pmo0', "pmo0.investor_id = ai.id AND (pmo0.status = {$this->gaPammPaymentOrdersStatuses['PENDING']} AND pmo0.operation IN ({$this->gaPammWithdrawalOperations['ALL_AND_CLOSE']}, {$this->gaPammWithdrawalOperations['PRETERM']}))", 'left', FALSE)
            ->join('pamm_money_orders AS pmo1', "pmo1.investor_id = ai.id AND ((pmo1.status = {$this->gaPammPaymentOrdersStatuses['PENDING']} AND pmo1.operation IN ({$this->gaPammWithdrawalOperations['ALL_AND_CLOSE']}, {$this->gaPammWithdrawalOperations['PRETERM']})) OR (pmo1.operation = 0 AND pmo1.status = 1 AND pmo1.confirmed_at > UNIX_TIMESTAMP(DATE(DATE_ADD(po.last_rollover_date, INTERVAL 7-WEEKDAY(po.last_rollover_date) DAY)))))", 'left', FALSE)
            ->join('pamm_money_orders AS pmo2', "pmo2.investor_id = ai.id AND (pmo2.status = {$this->gaPammPaymentOrdersStatuses['PENDING']} AND pmo2.operation IN ({$this->gaPammWithdrawalOperations['ALL_AND_CLOSE']}, {$this->gaPammWithdrawalOperations['DEFINED_SUM']}, {$this->gaPammWithdrawalOperations['PRETERM']}))", 'left', FALSE)
            ->join('pamm_money_orders AS pmo5', "pmo5.investor_id = ai.id AND ((pmo5.status = {$this->gaPammPaymentOrdersStatuses['PENDING']} AND pmo5.operation = {$this->gaPammWithdrawalOperations['PRETERM']}) OR (pmo5.status = {$this->gaPammPaymentOrdersStatuses['SUCCESS']} AND pmo5.operation = {$this->gaPammWithdrawalOperations['DEPOSIT']} AND pmo5.confirmed_at > UNIX_TIMESTAMP(DATE(DATE_ADD(po.last_rollover_date, INTERVAL 7-WEEKDAY(po.last_rollover_date) DAY))) AND CURRENT_TIMESTAMP() > DATE(DATE_ADD(po.last_rollover_date, INTERVAL 7-WEEKDAY(po.last_rollover_date) DAY))) OR (pmo5.status = {$this->gaPammPaymentOrdersStatuses['SUCCESS']} AND pmo5.operation = {$this->gaPammWithdrawalOperations['DEPOSIT']} AND pmo5.confirmed_at > UNIX_TIMESTAMP(DATE(DATE_SUB(DATE_ADD(po.last_rollover_date, INTERVAL 7-WEEKDAY(po.last_rollover_date) DAY), INTERVAL po.trade_period WEEK))) AND CURRENT_TIMESTAMP() < DATE(DATE_ADD(po.last_rollover_date, INTERVAL 7-WEEKDAY(po.last_rollover_date) DAY))) OR (pmo5.status = {$this->gaPammPaymentOrdersStatuses['SUCCESS']} AND pmo5.operation = {$this->gaPammWithdrawalOperations['PRETERM']} AND pmo5.confirmed_at > UNIX_TIMESTAMP(DATE(DATE_ADD(po.last_rollover_date, INTERVAL 7-WEEKDAY(po.last_rollover_date) DAY))) AND CURRENT_TIMESTAMP() > DATE(DATE_ADD(po.last_rollover_date, INTERVAL 7-WEEKDAY(po.last_rollover_date) DAY))) OR (pmo5.status = {$this->gaPammPaymentOrdersStatuses['SUCCESS']} AND pmo5.operation = {$this->gaPammWithdrawalOperations['PRETERM']} AND pmo5.confirmed_at > UNIX_TIMESTAMP(DATE(DATE_SUB(DATE_ADD(po.last_rollover_date, INTERVAL 7-WEEKDAY(po.last_rollover_date) DAY), INTERVAL po.trade_period WEEK))) AND CURRENT_TIMESTAMP() < DATE(DATE_ADD(po.last_rollover_date, INTERVAL 7-WEEKDAY(po.last_rollover_date) DAY))))", 'left', FALSE)
            ->join('pamm_money_orders AS pmo6', "pmo6.investor_id = ai.id AND (pmo6.status = {$this->gaPammPaymentOrdersStatuses['PENDING']} AND pmo6.operation IN ({$this->gaPammWithdrawalOperations['ALL_AND_CLOSE']}, {$this->gaPammWithdrawalOperations['PRETERM']}, {$this->gaPammWithdrawalOperations['PROFIT']}))", 'left', FALSE)
            //->join('pamm_payments AS pp', 'pp.investor_id = ai.id AND pp.created_at >= ai.activated_at AND pp.type != 8', 'left')
            ->where('ai.id', (int)$iInvestorId)
            ->where('ai.user_id', (int)$iUserId)
            ->where('ai.status !=', $this->gaPammInvestorAccountStatuses['DELETED'])
            ->where('ai.partner_id', empty($iOverridePartnerId)?$this->iPartnerId:$iOverridePartnerId)
            ->having('ai.id IS NOT NULL');
        $rQuery = $this->db->get();

        if ($rQuery->num_rows() > 0)
        {
            $aAccount = $rQuery->row_array();
            $rQuery->free_result();
        }
        else
        {
            $this->Errors[__FUNCTION__] = 'InvestorNotFound';
            return false;
        }

        // Если инвесторский счёт активен и нужно сделать обновление pamm_investors_stat - то нужно произвести подсчёты
        if (!empty($aAccount) && $aAccount['status'] == $this->gaPammInvestorAccountStatuses['ACTIVATED'] && !empty($aAccount['actual_datetime']))
        {
            // Подстраховка
            $aAccount['current_sum'] = bcadd($aAccount['current_sum'], 0, 2);
            // Вытягиваем полную прибыль за период
            $fPeriodProfit = $this->getInvestorsTotalProfitByPeriod($aAccount['investor_id'], $aAccount['trade_session_start_unixtime'], time(), $aAccount['pamm_mt_login']);
            $aAccount['period_profit']  = bcdiv(bcmul($fPeriodProfit, 100, 0), 100, 2);
            //$this->prn($fPeriodProfit, $aAccount['period_profit']);
            if ($aAccount['investor_type'] == $this->gaPammInvestorTypes['INVESTOR'])
            {
                $aAccount['availsum']   = bcadd($aAccount['current_sum'], $aAccount['period_profit'], 2);
            }
            else
            {
                $aAccount['availsum']   = floor(bcmul($aAccount['current_capital'], 100, 0))/100;
            }
            $aAccount['pretermsum'] = bcmul(bcadd($aAccount['current_sum'], $aAccount['period_profit']<0?$aAccount['period_profit']:0, 2), bcsub(1, bcdiv($aAccount['offer_commission'], 100, 2), 2), 2);
            $aAccount['profit_percent'] = (!empty($aAccount['investors_deposit']))?bcmul(bcsub(bcdiv(bcadd($aAccount['investors_withdraw'], $aAccount['availsum'], 2), $aAccount['investors_deposit'], 4), 1, 4), 100, 2):0;

            // Обновляем данные в таблице
            $aFields = array(
                'investor_id'	=> $aAccount['investor_id'],
                'pamm_mt_login'	=> $aAccount['pamm_mt_login'],
                'deposit'		=> bcadd($aAccount['current_sum'], 0, 2),
                'profit_percent'=> $aAccount['profit_percent'],
                'period_profit'	=> $aAccount['period_profit'],
                'availsum'		=> $aAccount['availsum'],
                'pretermsum'	=> $aAccount['pretermsum'],
                'insured_sum'	=> $aAccount['insured_sum'],
                'last_ticket_ct'=> $aAccount['actual_datetime'],
            );
            $this->db->replace('pamm_investors_stat', $aFields);

            // Важные проверки для инвестора на < 0 и < -100%
            $aAccount['availsum']		= (bccomp($aAccount['availsum'], 0, 2) == -1)?0:$aAccount['availsum'];
            $aAccount['profit_percent']	= (bccomp($aAccount['profit_percent'], -100, 2) == -1)?-100:$aAccount['profit_percent'];

            // Получаем наибольшую из сумм:
            $fMaxDefinedSumMin = ($aAccount['investor_type']==$this->gaPammInvestorTypes['MANAGER']&&$aAccount['offer_min_balance']<$fPammMinCapital)?$fPammMinCapital:$aAccount['offer_min_balance'];
            $aAccount['max_defined_sum'] = bcsub($aAccount['availsum'], (bccomp($aAccount['trade_session_payments'], $fMaxDefinedSumMin, 2) == 1)?$aAccount['trade_session_payments']:$fMaxDefinedSumMin, 2);
        }

        return $aAccount;
    }

    /**
     * Получает данные по инвесторским счетам пользователя
     *
     * @param integer $iUserId
     * @param integer $iOverridePartnerId
     * @return array|bool
     */
    function getUserInvestorsInfo($iUserId, $iOverridePartnerId=0)
    {
        $fPammMinCapital = PAMM_MIN_CAPITAL;

        $this->db
            ->select('ai.id AS investor_id')
            ->select('ai.type AS investor_type')
            ->select('ai.partner_id')
            ->select('ai.user_id')
            ->select('ai.account_id')
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
            ->select('po.min_balance AS offer_min_balance')
            ->select('po.commission AS offer_commission')
            ->select('UNIX_TIMESTAMP(DATE_SUB(DATE_FORMAT(DATE_ADD(po.last_rollover_date, INTERVAL 7-WEEKDAY(po.last_rollover_date) DAY), "%Y-%m-%d 00:00:00"), INTERVAL 1 HOUR)) AS trade_session_start_unixtime', FALSE)
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
            ->join('pamm_investors_stat AS pis', 'pis.investor_id = ai.id', 'left')
            //->join('pamm_payments AS pp', 'pp.investor_id = ai.id AND pp.created_at >= ai.activated_at AND pp.type != 8', 'left')
            ->where('ai.user_id', (int)$iUserId)
            ->where('ai.status !=', $this->gaPammInvestorAccountStatuses['DELETED'])
            ->where('ai.partner_id', empty($iOverridePartnerId)?$this->iPartnerId:$iOverridePartnerId);
            //->group_by('ai.id');
        $rQuery = $this->db->get();

        if ($rQuery->num_rows() > 0)
        {
            //$this->prn($this->db->last_query());
            $aAccounts = $rQuery->result_array();
            $rQuery->free_result();

            foreach ($aAccounts as &$aAccount)
            {
                // Если инвесторский счёт активен и нужно сделать обновление pamm_investors_stat - то нужно произвести подсчёты
                if (!empty($aAccount) && $aAccount['status'] == $this->gaPammInvestorAccountStatuses['ACTIVATED'] && !empty($aAccount['actual_datetime']))
                {
                    // Подстраховка
                    $aAccount['current_sum'] = bcadd($aAccount['current_sum'], 0, 2);
                    // Вытягиваем полную прибыль за период
                    $fPeriodProfit = $this->getInvestorsTotalProfitByPeriod($aAccount['investor_id'], $aAccount['trade_session_start_unixtime'], time(), $aAccount['pamm_mt_login']);
                    $aAccount['period_profit']  = bcdiv(bcmul($fPeriodProfit, 100, 0), 100, 2);
                    //$this->prn($fPeriodProfit, $aAccount['period_profit']);
                    if ($aAccount['investor_type'] == $this->gaPammInvestorTypes['INVESTOR'])
                    {
                        $aAccount['availsum']   = bcadd($aAccount['current_sum'], $aAccount['period_profit'], 2);
                    }
                    else
                    {
                        $aAccount['availsum']   = floor(bcmul($aAccount['current_capital'], 100, 0))/100;
                    }
                    $aAccount['pretermsum'] = bcmul(bcadd($aAccount['current_sum'], $aAccount['period_profit']<0?$aAccount['period_profit']:0, 2), bcsub(1, bcdiv($aAccount['offer_commission'], 100, 2), 2), 2);
                    $aAccount['profit_percent'] = (!empty($aAccount['investors_deposit']))?bcmul(bcsub(bcdiv(bcadd($aAccount['investors_withdraw'], $aAccount['availsum'], 2), $aAccount['investors_deposit'], 4), 1, 4), 100, 2):0;

                    // Обновляем данные в таблице
                    $aFields = array(
                        'investor_id'	=> $aAccount['investor_id'],
                        'pamm_mt_login'	=> $aAccount['pamm_mt_login'],
                        'deposit'		=> bcadd($aAccount['current_sum'], 0, 2),
                        'profit_percent'=> $aAccount['profit_percent'],
                        'period_profit'	=> $aAccount['period_profit'],
                        'availsum'		=> $aAccount['availsum'],
                        'pretermsum'	=> $aAccount['pretermsum'],
                        'insured_sum'	=> $aAccount['insured_sum'],
                        'last_ticket_ct'=> $aAccount['actual_datetime'],
                    );
                    $this->db->replace('pamm_investors_stat', $aFields);

                    // Важные проверки для инвестора на < 0 и < -100%
                    $aAccount['availsum']		= (bccomp($aAccount['availsum'], 0, 2) == -1)?0:$aAccount['availsum'];
                    $aAccount['profit_percent']	= (bccomp($aAccount['profit_percent'], -100, 2) == -1)?-100:$aAccount['profit_percent'];

                    // Получаем наибольшую из сумм:
                    $fMaxDefinedSumMin = ($aAccount['investor_type']==$this->gaPammInvestorTypes['MANAGER']&&$aAccount['offer_min_balance']<$fPammMinCapital)?$fPammMinCapital:$aAccount['offer_min_balance'];
                    $aAccount['max_defined_sum'] = bcsub($aAccount['availsum'], (bccomp($aAccount['trade_session_payments'], $fMaxDefinedSumMin, 2) == 1)?$aAccount['trade_session_payments']:$fMaxDefinedSumMin, 2);
                }
            }

            return $aAccounts;
        }
        else
        {
            $this->Errors[__FUNCTION__] = 'InvestorsNotFound';
            return false;
        }
    }

    /**
     * Получает список посделочной доходности по инвесторскому счету
     *
     * @param integer $iInvestorId
     * @param integer $iPammMTLogin
     * @param integer $iTimeFrom
     * @param integer $iTimeTo
     * @param bool $bForceDateFrom
     * @return bool|array
     */
    function getInvestorTicketsProfitList($iInvestorId, $iPammMTLogin, $iTimeFrom, $iTimeTo, $bForceDateFrom = false)
    {
        // 1. Сформировать таблицу всех шар инв. счета
        $sQuery = "CREATE TEMPORARY TABLE pp{$iInvestorId} (investor_id INT(10) UNSIGNED NOT NULL, created_at INT(10) UNSIGNED NOT NULL, investor_sum DOUBLE NOT NULL, INDEX investor_id (investor_id)) SELECT investor_id, created_at, investor_sum FROM pamm_payments WHERE investor_id = {$iInvestorId} GROUP BY created_at ORDER BY created_at DESC";
        $this->db->query($sQuery);

        // 2. Получить всю прибыть за всю историю инв. счета
        if (empty($bForceDateFrom))
        {
            $this->db->where('pmcs.unix_close_time > ai.activated_at', NULL);
        };
        $this->db
            ->select('pmcs.ticket')
            ->select('pmcs.cmd')
            ->select('pmcs.profit')
            ->select('pmcs.manager_profit')
            ->select('pmcs.new_capital')
            ->select('pmcs.manager_share')
            ->select('pmcs.ceiling')
            ->select('pmcs.pamm_mt_login')
            ->select('pmcs.symbol')
            ->select('pmcs.lots')
            ->select('pmcs.close_time')
            ->select('pmcs.unix_close_time')
            ->select('pmcs.total_sum')
            ->select("IF(ai.type = 1, (pmcs.profit - pmcs.manager_profit * pmcs.ceiling) * IFNULL((SELECT pp.investor_sum/pmcs.total_sum/(1-pmcs.manager_share) FROM pp{$iInvestorId} AS pp WHERE pp.investor_id = {$iInvestorId} AND pp.created_at < pmcs.unix_close_time ORDER BY pp.created_at DESC LIMIT 1), 0), pmcs.manager_profit) AS inv_profit", FALSE)
            ->from('pamm_manager_capital_stat AS pmcs')
            ->join('pamm_investors AS ai', "ai.id = {$iInvestorId} AND ai.partner_id = {$this->iPartnerId}")
            ->where('pmcs.pamm_mt_login', $iPammMTLogin)
            ->where('(pmcs.cmd = 1 OR ai.type = 2)', NULL, FALSE)
            ->where('pmcs.unix_close_time >= ', $iTimeFrom)
            ->where('pmcs.unix_close_time < ', $iTimeTo)
            ->group_by(array('pmcs.ticket', 'ai.id'))
            ->order_by('pmcs.unix_close_time', 'ASC')
            ->order_by('pmcs.ticket', 'ASC');
        $rQuery = $this->db->get();

        // 3. Зачистить темплатовую таблицу
        $this->db->query("DROP TEMPORARY TABLE pp{$iInvestorId}");

        if ($rQuery->num_rows() > 0)
        {
            return $rQuery->result_array();
        }

        return false;
    }

    /**
     * Изменяет данные по инвесторскому счету такие как отображение счета и автовывод прибыли
     *
     * @param integer $iInvestorId
     * @param integer $iUserId
     * @param integer $iShowMode
     * @param integer $iAutoWithdrawal
     * @return array|boolean
     */
    function changeInvestorDetails($iInvestorId, $iUserId, $iShowMode, $iAutoWithdrawal)
    {
        $this->db->query("SELECT GET_LOCK('cid_{$iUserId}', 120) as lockstatus");

        $aFields = array(
            'show_mode'             => $iShowMode,
            'auto_withdrawal_profit'=> $iAutoWithdrawal,
        );
        $this->db->where('id', $iInvestorId);
        $this->db->where('partner_id', $this->iPartnerId);
        $this->db->where('user_id', $iUserId);
        $this->db->where("({$iAutoWithdrawal} = 0 OR for_index = 0)", null);
        $this->db->update('pamm_investors', $aFields);
        $iResult = $this->db->affected_rows();

        if ($iAutoWithdrawal == 1 && $iResult == 1)
        {
            // Создаем раcпоряжение. Если распоряжение на вывод только прибыли уже есть - это просто отвалится с ошибкой
            $this->createMoneyOrder($iInvestorId, $iUserId, $this->gaPammWithdrawalOperations['PROFIT'], 0);
        }

        $this->db->query("SELECT RELEASE_LOCK('cid_{$iUserId}') as lockstatus");

        return $iResult;
    }

    /**
     * Получает данные по инвесторским счетам ПАММ-счета
     *
     * @param integer $iPammMTLogin
     * @param integer $iUserId
     * @param integer $iInvestorStatus
     * @param integer $iOverridePartnerId
     * @return array|bool
     */
    function getInvestorsDetails($iPammMTLogin, $iUserId = 0, $iInvestorStatus=-1, $iOverridePartnerId=0)
    {
        $fPammMinCapital = PAMM_MIN_CAPITAL;

        // Получаем информацию по памм-счету
        $rQuery = $this->db->select('allow_deposit, allow_withdraw, commission AS offer_commission, min_balance')->from('pamm_offers')->where('pamm_mt_login', $iPammMTLogin)->limit(1)->get();
        if ($rQuery->num_rows() > 0)
        {
            $aPammDetails = $rQuery->row_array();
        }
        else
        {
            $this->Errors[__FUNCTION__] = 'PammNotFound';
            return false;
        }

        if (!empty($iUserId))
        {
            $this->db->where('ai.user_id', $iUserId);
        }
        if ($iInvestorStatus != -1)
        {
            $this->db->where('ai.status', $iInvestorStatus);
        }

        $this->db
            ->select('ai.id')
            ->select('ai.id AS investor_id')
            ->select('ai.type AS investor_type')
            ->select('ai.partner_id')
            ->select('ai.user_id')
            ->select('ai.offer_id')
            ->select('ai.pamm_mt_login')
            ->select('ai.inv_mt_login')
            ->select('ai.created_at')
            ->select('ai.activated_at')
            ->select('ai.closed_at')
            ->select('ai.status')
            ->select('ai.current_sum')
            ->select('ai.insured_sum')
            ->select('ai.for_index')
            ->select('ai.for_ic')
            ->select('ai.auto_withdrawal_profit')
            ->select('ai.show_mode')
            ->select('UNIX_TIMESTAMP(DATE_SUB(DATE_FORMAT(DATE_ADD(po.last_rollover_date, INTERVAL 7-WEEKDAY(po.last_rollover_date) DAY), "%Y-%m-%d 00:00:00"), INTERVAL 1 HOUR)) AS trade_session_start_unixtime', FALSE)
            //->select("IF({$aPammDetails['allow_deposit']} = 0 OR ai.status = 3 OR pmo0.id IS NOT NULL, 0, 1) AS allow_0")
            //->select("IF({$aPammDetails['allow_withdraw']} = 0 OR ai.status != 1 OR pmo1.id IS NOT NULL OR (ai.type = {$this->gaPammInvestorTypes['MANAGER']} AND NOW() > DATE_SUB(po.next_rollover_date, INTERVAL 630 MINUTE)), 0, 1) AS allow_1")
            //->select("IF({$aPammDetails['allow_withdraw']} = 0 OR ai.status != 1 OR pmo2.id IS NOT NULL OR (ai.type = {$this->gaPammInvestorTypes['MANAGER']} AND NOW() > DATE_SUB(po.next_rollover_date, INTERVAL 630 MINUTE)), 0, 1) AS allow_2")
            //->select("IF({$aPammDetails['allow_withdraw']} = 0 OR ai.status != 1 OR pmo5.id IS NOT NULL OR ai.type = {$this->gaPammInvestorTypes['MANAGER']}, 0, 1) AS allow_5")
            //->select("IF({$aPammDetails['allow_withdraw']} = 0 OR ai.status != 1 OR pmo6.id IS NOT NULL OR (ai.type = {$this->gaPammInvestorTypes['MANAGER']} AND NOW() > DATE_SUB(po.next_rollover_date, INTERVAL 630 MINUTE)), 0, 1) AS allow_6")
            ->select('ai.total_deposits AS investors_deposit', FALSE)
            ->select('ABS(ai.total_withdrawal) AS investors_withdraw', FALSE)
            ->select('ai.trade_session_deposits AS trade_session_payments', FALSE)
            //->select("IF(ai.status = {$this->gaPammInvestorAccountStatuses['ACTIVATED']}, IFNULL(pis.profit_left, 0), 0) AS profit_left")
            ->select("IF(ai.status = {$this->gaPammInvestorAccountStatuses['ACTIVATED']}, IFNULL(pis.period_profit, 0), 0) AS period_profit", FALSE)
            ->select("IF(ai.status = {$this->gaPammInvestorAccountStatuses['ACTIVATED']}, IF(IF(ai.type = {$this->gaPammInvestorTypes['MANAGER']}, FLOOR(IFNULL(pmcc.capital, 0)*100)/100, IFNULL(pis.availsum, 0))<0, 0, IF(ai.type = {$this->gaPammInvestorTypes['MANAGER']}, FLOOR(IFNULL(pmcc.capital, 0)*100)/100, IFNULL(pis.availsum, 0))), 0) AS availsum", FALSE)
            ->select("IF(ai.status = {$this->gaPammInvestorAccountStatuses['ACTIVATED']}, IFNULL(pis.pretermsum, 0), 0) AS pretermsum", FALSE)
            ->select("IF(ai.status = {$this->gaPammInvestorAccountStatuses['ACTIVATED']}, ROUND(IF(ai.type= {$this->gaPammInvestorTypes['MANAGER']}, FLOOR(IFNULL(pmcc.capital, 0)*100)/100, IFNULL(pis.availsum, 0)) - IF(IF(ai.activated_at > UNIX_TIMESTAMP(DATE_SUB(DATE_FORMAT(DATE_ADD(po.last_rollover_date, INTERVAL 9-DAYOFWEEK(po.last_rollover_date) DAY), '%Y-%m-%d 00:00:00'), INTERVAL 1 HOUR)), ai.activated_at, UNIX_TIMESTAMP(DATE_SUB(DATE_FORMAT(DATE_ADD(po.last_rollover_date, INTERVAL 9-DAYOFWEEK(po.last_rollover_date) DAY), '%Y-%m-%d 00:00:00'), INTERVAL 1 HOUR))) > IF(ai.type = {$this->gaPammInvestorTypes['MANAGER']} AND po.min_balance < {$fPammMinCapital}, {$fPammMinCapital}, po.min_balance), IF(ai.activated_at > UNIX_TIMESTAMP(DATE_SUB(DATE_FORMAT(DATE_ADD(po.last_rollover_date, INTERVAL 9-DAYOFWEEK(po.last_rollover_date) DAY), '%Y-%m-%d 00:00:00'), INTERVAL 1 HOUR)), ai.activated_at, UNIX_TIMESTAMP(DATE_SUB(DATE_FORMAT(DATE_ADD(po.last_rollover_date, INTERVAL 9-DAYOFWEEK(po.last_rollover_date) DAY), '%Y-%m-%d 00:00:00'), INTERVAL 1 HOUR))), IF(ai.type = {$this->gaPammInvestorTypes['MANAGER']} AND po.min_balance < {$fPammMinCapital}, {$fPammMinCapital}, po.min_balance)), 2), 0) AS max_defined_sum", FALSE)
            ->select("IF(IFNULL(pis.last_ticket_ct, '1970-01-01') < IFNULL(pmcc.update_time, '1970-01-01'), IFNULL(pmcc.update_time, '1970-01-01'), 0) AS actual_datetime", FALSE)
            ->from('pamm_investors AS ai')
            ->join('pamm_offers AS po', 'po.pamm_mt_login = ai.pamm_mt_login')
            ->join('pamm_manager_capital_current AS pmcc', 'pmcc.pamm_mt_login = ai.pamm_mt_login', 'left')
            ->join('pamm_investors_stat AS pis', 'pis.investor_id = ai.id', 'left')
            //->join('pamm_money_orders AS pmo0', "pmo0.investor_id = ai.id AND ((pmo0.status = {$this->gaPammPaymentOrdersStatuses['PENDING']} AND pmo0.operation IN ({$this->gaPammWithdrawalOperations['ALL_AND_CLOSE']}, {$this->gaPammWithdrawalOperations['PRETERM']})) OR (pmo0.status = {$this->gaPammPaymentOrdersStatuses['SUCCESS']} AND pmo0.operation = {$this->gaPammWithdrawalOperations['PRETERM']} AND pmo0.confirmed_at > UNIX_TIMESTAMP(po.last_rollover_date)))", 'left')
            //->join('pamm_money_orders AS pmo1', "pmo1.investor_id = ai.id AND (pmo1.status = {$this->gaPammPaymentOrdersStatuses['PENDING']} AND pmo1.operation IN ({$this->gaPammWithdrawalOperations['ALL_AND_CLOSE']}, {$this->gaPammWithdrawalOperations['PRETERM']}))", 'left')
            //->join('pamm_money_orders AS pmo2', "pmo2.investor_id = ai.id AND (pmo2.status = {$this->gaPammPaymentOrdersStatuses['PENDING']} AND pmo2.operation IN ({$this->gaPammWithdrawalOperations['ALL_AND_CLOSE']}, {$this->gaPammWithdrawalOperations['DEFINED_SUM']}, {$this->gaPammWithdrawalOperations['PRETERM']}))", 'left')
            //->join('pamm_money_orders AS pmo5', "pmo5.investor_id = ai.id AND (pmo5.status = {$this->gaPammPaymentOrdersStatuses['PENDING']} AND pmo5.operation IN ({$this->gaPammWithdrawalOperations['ALL_AND_CLOSE']}, {$this->gaPammWithdrawalOperations['PRETERM']}))", 'left')
            //->join('pamm_money_orders AS pmo6', "pmo6.investor_id = ai.id AND (pmo6.status = {$this->gaPammPaymentOrdersStatuses['PENDING']} AND pmo6.operation IN ({$this->gaPammWithdrawalOperations['ALL_AND_CLOSE']}, {$this->gaPammWithdrawalOperations['PRETERM']}, {$this->gaPammWithdrawalOperations['PROFIT']}))", 'left')
            //->join('pamm_payments AS pp', 'pp.investor_id = ai.id AND pp.created_at >= ai.activated_at AND pp.type != 8', 'left')
            ->where('ai.pamm_mt_login', $iPammMTLogin)
            ->where('ai.status != ', $this->gaPammInvestorAccountStatuses['DELETED'])
            ->where('ai.partner_id', empty($iOverridePartnerId)?$this->iPartnerId:$iOverridePartnerId);
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() > 0)
        {
            $aResult = $rQuery->result_array();
        }
        else
        {
            $this->Errors[__FUNCTION__] = 'InvestorsNotFound';
            return false;
        }

        $aAccounts = array();
        foreach ($aResult as $aAccount)
        {
            $aAccounts[$aAccount['investor_id']] = $aAccount;
        }
        unset($aResult);

        foreach ($aAccounts as $iKey => $aAccount)
        {
            // Если инвесторский счёт активен и нужно сделать обновление pamm_investors_stat - то нужно произвести подсчёты
            if ($aAccount['status'] == $this->gaPammInvestorAccountStatuses['ACTIVATED'] && !empty($aAccount['actual_datetime']))
            {
                // Вытягиваем полную прибыль за период
                $fPeriodProfit = $this->getInvestorsTotalProfitByPeriod($aAccount['id'], $aAccount['trade_session_start_unixtime'], time(), $aAccount['pamm_mt_login']);

                $aAccounts[$iKey]['period_profit']  = bcdiv(bcmul($fPeriodProfit, 100, 0), 100, 2);
                $aAccounts[$iKey]['profit_percent'] = (!empty($aAccounts[$iKey]['investors_deposit']))?bcmul(bcsub(bcdiv(bcadd($aAccounts[$iKey]['investors_withdraw'], $aAccounts[$iKey]['availsum'], 2), $aAccounts[$iKey]['investors_deposit'], 4), 1, 4), 100, 2):0;
                $aAccounts[$iKey]['availsum']       = $aAccount['availsum'];
                $aAccounts[$iKey]['pretermsum']     = $aAccount['pretermsum'];
                if ($aAccount['investor_type'] == $this->gaPammInvestorTypes['INVESTOR'])
                {
                    $aAccounts[$iKey]['availsum']   = bcadd($aAccount['current_sum'], $aAccounts[$iKey]['period_profit'], 2);
                    $aAccounts[$iKey]['pretermsum'] = bcmul(bcadd($aAccount['current_sum'], $aAccounts[$iKey]['period_profit']<0?$aAccounts[$iKey]['period_profit']:0, 2), bcsub(1, bcdiv($aPammDetails['offer_commission'], 100, 2), 2), 2);
                }

                // Обновляем данные в таблице
                $aFields = array(
                    'investor_id'	=> $aAccount['id'],
                    'pamm_mt_login'	=> $aAccount['pamm_mt_login'],
                    'deposit'		=> $aAccount['current_sum'],
                    //'profit_left'	=> 0,
                    'profit_percent'=> $aAccounts[$iKey]['profit_percent'],
                    'period_profit'	=> $aAccounts[$iKey]['period_profit'],
                    'availsum'		=> $aAccounts[$iKey]['availsum'],
                    'pretermsum'	=> $aAccounts[$iKey]['pretermsum'],
                    'insured_sum'	=> $aAccount['insured_sum'],
                    'last_ticket_ct'=> $aAccount['actual_datetime'],
                );
                $this->db->replace('pamm_investors_stat', $aFields);

                // Важные проверки для инвестора на < 0 и < -100%
                $aAccounts[$iKey]['availsum']		= (bccomp($aAccounts[$iKey]['availsum'], 0, 2) == -1)?0:$aAccounts[$iKey]['availsum'];
                $aAccounts[$iKey]['profit_percent']	= (bccomp($aAccounts[$iKey]['profit_percent'], -100, 2) == -1)?-100:$aAccounts[$iKey]['profit_percent'];

                // Получаем наибольшую из сумм:
                $fMaxDefinedSumMin = ($aAccount['investor_type']==$this->gaPammInvestorTypes['MANAGER']&&$aPammDetails['min_balance']<$fPammMinCapital)?$fPammMinCapital:$aPammDetails['min_balance'];
                $aAccounts[$iKey]['max_defined_sum'] = bcsub($aAccounts[$iKey]['availsum'], ($aAccount['trade_session_payments']>$fMaxDefinedSumMin)?$aAccount['trade_session_payments']:$fMaxDefinedSumMin, 2);
            }
        }

        return $aAccounts;
    }

    /* Распоряжения */

    /**
     * Изменяет данные по инвесторскому счету такие как отображение счета и автовывод прибыли
     *
     * @param integer $iInvestorId
     * @param integer $iUserId
     * @param integer $iOperation
     * @param float $fAmount
     * @param boolean $bMoneyWithdrawn
     * @param boolean $bIgnoreOffer
     * @param boolean $bForce
     * @param integer $iOverridePartnerId
     * @return array|boolean
     */
    function createMoneyOrder($iInvestorId, $iUserId, $iOperation, $fAmount, $bMoneyWithdrawn = false, $bIgnoreOffer = false, $bForce = false, $iOverridePartnerId = 0)
    {
        ignore_user_abort(true);
        set_time_limit(0);

        // Получаем детали по инвесторскому счету
        if (empty($iOverridePartnerId))
        {
            $iOverridePartnerId = $this->iPartnerId;
        }
        $aInvestorDetails = $this->getInvestorDetails($iUserId, $iInvestorId, $iOverridePartnerId);
        if (empty($aInvestorDetails))
        {
            $this->Errors[__FUNCTION__] = 'InvestorNotFound';
            return false;
        }

        // Получаем детали по ПАММ счету
        $aPammDetails = $this->getPammDetails($aInvestorDetails['pamm_mt_login']);

        // Проверка для PAMM API
        if ($aPammDetails['partner_id'] != $iOverridePartnerId && !in_array('CAN_USE_FOREING', $this->aPermissions))
        {
            $this->Errors[__FUNCTION__] = 'PermissionDenied';
            return false;
        }

        // Проверяем разрешена ли операция для данного инвесторского счета для нас
        if ((empty($aInvestorDetails["allow_{$iOperation}"]) || !empty($aInvestorDetails['for_index'])) && !$bIgnoreOffer)
        {
            $this->Errors[__FUNCTION__] = 'OperationNotAllowed';
            return false;
        }

        // Проверяем, можно ли сделать инвестору то, что он хочет
        // Сначала проверим варианты с пополнением
        if ($iOperation == $this->gaPammWithdrawalOperations['DEPOSIT'])
        {
            // Можно ли проверять правила оферты?
            if (($aInvestorDetails['status'] == $this->gaPammInvestorAccountStatuses['NOT_ACTIVE'] || $aInvestorDetails['status'] == $this->gaPammInvestorAccountStatuses['CLOSED']) &&
                bccomp($fAmount, $aInvestorDetails['offer_min_balance'], 2) == -1 && !$bIgnoreOffer)
            {
                $this->Errors[__FUNCTION__] = 'AmountLessThanMinBalance';
                return false;
            }
            // Пополнение инв счета управляющего не входящего в индексы при отрицательном КУ запрещено
            if ($aInvestorDetails['investor_type'] == $this->gaPammInvestorTypes['MANAGER'])
            {
                $this->db
                    ->select('pis.pamm_mt_login')
                    ->from('pamm_idx_shares AS pis')
                    ->where('pis.pamm_mt_login', $aPammDetails['pamm_mt_login']);
                $rQuery = $this->db->get();
                if ($rQuery->num_rows() == 0 && bccomp($aInvestorDetails['current_capital'], 0, 2) == -1)
                {
                    $this->Errors[__FUNCTION__] = 'BadMCDepositDenied';
                    return false;
                }
            }
            // Для ПАММ2.0 проверяем разрешены ли инвестиции так чтобы управляющий мог обеспечить их сохранность
            /*if (!empty($aPammDetails['offer_responsibility']) && $aInvestorDetails['investor_type'] == $this->gaPammInvestorTypes['INVESTOR'] &&
                bccomp($fAmount, $aPammDetails['permitted_investments'], 2) == 1 && !$bIgnoreOffer)
            {
                $this->Errors[__FUNCTION__] = 'AmountGreaterThanPermittedInvestments';
                return false;
            }*/
            // Если это управляющий и это его первый платеж - то он не должен быть меньше 500 USD
            if ($aInvestorDetails['investor_type'] == $this->gaPammInvestorTypes['MANAGER'] && $aPammDetails['status'] == $this->gaPammOffersStatuses['CREATED'] && $aPammDetails['current_capital'] == 0 && $fAmount < PAMM_MIN_CAPITAL)
            {
                $this->Errors[__FUNCTION__] = 'AmountLessThanMinCapital';
                return false;
            }
        }
        // Теперь проверяем всякое снятие
        else
        {
            if ($iOperation == $this->gaPammWithdrawalOperations['DEFINED_SUM'] && empty($fAmount))
            {
                $this->Errors[__FUNCTION__] = 'AmountEmpty';
                return false;
            }
            // Если инвестор не активен, либо досрочка с ПАММ 2.0, либо если это инвестор управляющего и выполняет неположенную операцию - то точно чтото не так
            if ($aInvestorDetails['status'] != $this->gaPammInvestorAccountStatuses['ACTIVATED']
             || ($iOperation == $this->gaPammWithdrawalOperations['PRETERM'] && !empty($aPammDetails['offer_responsibility']))
             || ($aInvestorDetails['investor_type']==$this->gaPammInvestorTypes['MANAGER'] && !in_array($iOperation, array($this->gaPammWithdrawalOperations['DEFINED_SUM'], $this->gaPammWithdrawalOperations['PROFIT']))))
            {
                $this->Errors[__FUNCTION__] = 'OperationDenied';
                return false;
            }
            // Управляющему положено делать распоряжения только когда нет открытых убыточных сделок
            if ($aInvestorDetails['investor_type']==$this->gaPammInvestorTypes['MANAGER'])
            {
                $aOpenedTickets = $this->webactions->getOpenedTradesCount($aInvestorDetails['pamm_mt_login']);
                if ($aOpenedTickets['opened'] > 0)
                {
                    $this->Errors[__FUNCTION__] = 'OpenedTicketsExist';
                    return false;
                }
            }
            // Управляющему положено снимать чтобы оставалось >= 500 USD
            if ($aInvestorDetails['investor_type'] == $this->gaPammInvestorTypes['MANAGER'])
            {
                $fNewAvailSum = bcsub($aInvestorDetails['availsum'], $fAmount, 2);
                if (bccomp($fNewAvailSum, PAMM_MIN_CAPITAL, 2) == -1)   // $fNewAvailSum < PAMM_MIN_CAPITAL
                {
                    $this->Errors[__FUNCTION__] = 'CapitalLessThan500';
                    return false;
                }
            }
            // Если происходит операция по выводу всех денег и закрытию счета - то все не обработанные операции должны быть отменены
            if (in_array($iOperation, array($this->gaPammWithdrawalOperations['ALL_AND_CLOSE'], $this->gaPammWithdrawalOperations['PRETERM'])))
            {
                $this->db
                    ->select('id')
                    ->from('pamm_money_orders')
                    ->where('investor_id', $iInvestorId)
                    ->where('status', $this->gaPammPaymentOrdersStatuses['PENDING']);
                $rQuery = $this->db->get();
                if ($rQuery->num_rows() > 0)
                {
                    $aPMOrders = $rQuery->result_array();
                    foreach ($aPMOrders as $aPMOrder)
                    {
                        $this->cancelMoneyOrder($aPMOrder['id'], $iUserId);
                    }
                }
            }
        }

        // Если тип вывода - трансфер на какой-то ПАММ 2.0: то сначала вставляем распоряжение на снятие с указанного инв счета
        $aFields = array(
            'sum'               => $fAmount,
            'mt_login'          => $aInvestorDetails['inv_mt_login'],
            'investor_id'       => $iInvestorId,
            'created_at'        => time(),
            'operation'         => $iOperation,
            'money_withdrawn'   => $bMoneyWithdrawn,
        );
        $this->db->insert("pamm_money_orders", $aFields);
        $iPMOId = $this->db->insert_id();

        if ($bForce || $iOperation == $this->gaPammWithdrawalOperations['DEPOSIT'] || ($iOperation == $this->gaPammWithdrawalOperations['PRETERM'] && $aInvestorDetails['responsibility'] == 0))
        {
            $bResult = $this->processMoneyOrder($iPMOId, $bIgnoreOffer);
            if (!$bResult)
            {
                return false;
            }
        }

        return $iPMOId;
    }

    /**
     * Отменяет распоряжение на ввод/вывод средств
     *
     * @param integer $iOrderId
     * @param integer $iUserId
     * @param integer $iNewOrderStatus
     * @param float $fNewOrderSum
     * @param bool $bIgnorePartnerId
     * @return bool
     */
    function cancelMoneyOrder($iOrderId, $iUserId, $iNewOrderStatus = 4, $fNewOrderSum = INF, $bIgnorePartnerId=false)
    {
        if (empty($bIgnorePartnerId))
        {
            $this->db->where('ai.partner_id', $this->iPartnerId);
        }
        $this->db
            ->select('pmo.*')
            ->from('pamm_money_orders AS pmo')
            ->join('pamm_investors AS ai', "ai.id = pmo.investor_id AND ai.user_id = {$iUserId}")
            ->where('pmo.id', $iOrderId)
            ->where('pmo.status', $this->gaPammPaymentOrdersStatuses['PENDING']);
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() == 0)
        {
            $this->Errors[__FUNCTION__] = 'OrderNotFound';
            return false;
        }

        // Если существует другое распоряжение на которое ссылается то,
        // которое мы будем отменять - надо отменить и этот
        $aFields = array(
            'status'		=> $iNewOrderStatus,
            'confirmed_at'	=> time(),
        );
        if ($fNewOrderSum != INF && is_numeric($fNewOrderSum))
        {
            $aFields['sum'] = $fNewOrderSum;
        }
        $this->db->where('id', $iOrderId);
        $this->db->update('pamm_money_orders', $aFields);

        return true;
    }

    /**
     * Выполняет распоряжение на ввод/вывод средств
     *
     * @param integer $iPMOId
     * @param boolean $bIgnoreOffer
     * @return array|bool
     * 			investor_id - инвесторский счет, с которым проводилась работа;
     * 			sum - сумма выполненного распоряжения или 0, если распоряжение не удалось обработать;
     */
    function processMoneyOrder($iPMOId, $bIgnoreOffer = false)
    {
        ignore_user_abort(true);
        set_time_limit(0);

        // Выбираем данные о распоряжении
        $this->db
            ->select('pmo.id')
            ->select('pmo.sum')
            ->select('pmo.investor_id')
            ->select('pmo.operation')
            ->select('pmo.money_withdrawn')
            ->select('ai.inv_mt_login')
            ->select('ai.pamm_mt_login')
            ->select('ai.activated_at')
            ->select('ai.current_sum')
            ->select('ai.status AS investor_status')
            ->select('ai.type AS investor_type')
            ->select('ai.for_index AS investor_index')
            ->select('ai.for_ic AS investor_ic')
            ->select('po.admin_note AS pamm_admin_note')
            ->select('po.commission/100 AS pamm_commission', FALSE)
            ->select('po.min_balance AS pamm_min_balance')
            ->select('po.responsibility AS pamm_responsibility')
            ->select('UNIX_TIMESTAMP(po.last_rollover_date) AS last_rollover', FALSE)
            ->select('IFNULL(TRUNCATE(pmcc.capital, 2), 0) AS current_capital', FALSE)
            ->select('UNIX_TIMESTAMP(DATE_SUB(DATE_FORMAT(DATE_ADD(po.last_rollover_date, INTERVAL 7-WEEKDAY(po.last_rollover_date) DAY), "%Y-%m-%d 00:00:00"), INTERVAL 1 HOUR)) AS trade_session_start_unixtime', FALSE)
            ->from('pamm_money_orders AS pmo')
            ->join('pamm_investors AS ai', 'ai.id = pmo.investor_id')
            ->join('pamm_offers AS po', 'po.pamm_mt_login = ai.pamm_mt_login')
            ->join('pamm_manager_capital_current AS pmcc', 'pmcc.pamm_mt_login = ai.pamm_mt_login', 'left')
            ->where('pmo.id', $iPMOId)
            ->where('pmo.status', $this->gaPammPaymentOrdersStatuses['PENDING']);
        $rQuery = $this->db->get();
        //var_dump($this->db->last_query());
        if ($rQuery->num_rows() > 0)
        {
            $aOrder = $rQuery->row_array();
        }
        else
        {
            $this->Errors[__FUNCTION__] = 'OrderNotFound';
            return false;
        }

        if (empty($aOrder['sum']) && empty($aOrder['operation']))
        {
            $this->Errors[__FUNCTION__] = 'WrongOrderAmount';
            return false;
        }

        // Инициализируемся
        $aInvestorFields	    = array();
        $sErrorMsg			    = '';
        $iErrorCode			    = 0;
        $iRolloverTime		    = time();
        $bResult			    = false;
        $bInvestorJustActivated = false;
        $aInvestorData = array(
            'investor_sum'      => $aOrder['current_sum'],
            'available_sum' 	=> $aOrder['current_sum'],
            'period_profit'     => 0,
            'deposit_sum'	    => 0,
            'withdrawal_sum'	=> 0,
            'penalty_sum'	    => 0,
        );

        // Если операция не пополнения - надо еще подсчитать прибыль за незакрытый период
        if ($aOrder['operation'] != $this->gaPammWithdrawalOperations['DEPOSIT'])
        {
            // Подсчитываем прибыль по данному инвестору
            $fInvProfit = $this->getInvestorsTotalProfitByPeriod($aOrder['investor_id'], $aOrder['last_rollover'], $iRolloverTime, $aOrder['pamm_mt_login']);

            // Теперь надо перестраховаться - подсчитать сколько он прибыли вынял за указанный период
            $this->db
                ->select("IFNULL(SUM(pp.sum), 0) AS total_sum", FALSE)
                ->from("pamm_payments AS pp")
                ->where("pp.investor_id", (int)$aOrder['investor_id'])
                ->where("pp.type", $this->gaPammPaymentsTypes['PROFIT'])
                ->where("pp.created_at > ", $aOrder['last_rollover']+86400)
                ->where("pp.created_at < ", $iRolloverTime);
            $rQuery = $this->db->get();
            //var_dump($this->db->last_query());
            if ($rQuery->num_rows() > 0)
            {
                $fProfitWithdrawn = $rQuery->row()->total_sum;
            }
            else
            {
                $fProfitWithdrawn = 0;
            }

            // Калькулируем и запоминаем окончательную прибыль
            $fInvPeriodProfit = bcadd($fInvProfit, $fProfitWithdrawn, 2);

            $aInvestorData['period_profit'] = $fInvPeriodProfit;
            $aInvestorData['available_sum']	= bcadd($aInvestorData['available_sum'], $fInvPeriodProfit, 2);
        }

        // Проводим проверки связанные с пополнением
        if ($aOrder['operation'] == $this->gaPammWithdrawalOperations['DEPOSIT'])
        {
            // Проверяем можно ли снять с mt-счета инвестора сумму, которую он хочет
            if (empty($aOrder['money_withdrawn']))
            {
                $aMarginInfo = $this->webactions->getTradesMarginInfo($aOrder['inv_mt_login']);
                $fNewEquity = bcsub($aMarginInfo['equity'], $aOrder['sum'], 2);
                if (bccomp($fNewEquity, 0, 2) == -1) //if ($fNewEquity < 0)
                {
                    $sErrorMsg  = 'На инвесторском торговом счету не достаточно средств';
                    $iErrorCode = 13;
                }
            }
            $aInvestorData['deposit_sum'] = $aOrder['sum'];
        }

        // Проводим проверки связанные со снятием
        if ($aOrder['operation'] != $this->gaPammWithdrawalOperations['DEPOSIT'])
        {
            // Рассчитываем сумму для снятия в завимости от операции
            switch ($aOrder['operation'])
            {
                case $this->gaPammWithdrawalOperations['ALL_AND_CLOSE']:
                    $aInvestorData['withdrawal_sum'] = $aInvestorData['available_sum'];
                    break;
                case $this->gaPammWithdrawalOperations['DEFINED_SUM']:
                    $aInvestorData['withdrawal_sum'] = $aOrder['sum'];
                    $fNewAvailableSum = bcsub($aInvestorData['available_sum'], $aInvestorData['withdrawal_sum'], 2);
                    // Если остаток после снятия будет меньше мин допустимого баланса - то это ошибка.
                    if ($aOrder['investor_status'] == $this->gaPammInvestorAccountStatuses['ACTIVATED'] && bccomp($fNewAvailableSum, $aOrder['pamm_min_balance'], 2) == -1) //$fNewAvailableSum < $aOrder['pamm_min_balance']
                    {
                        $sErrorMsg	= 'После снятия данной суммы, остаток на счету будет меньше минимально допустимого';
                        $iErrorCode	= 3;
                    }
                    break;
                case $this->gaPammWithdrawalOperations['PROFIT']: // Снимаем только прибыль
                    $aInvestorData['withdrawal_sum'] = $aInvestorData['period_profit'];
                    // Если прибыль за торговый период меньше или = 0
                    if (bccomp($aInvestorData['period_profit'], 0, 2) <= 0) //$aInvestorData['period_profit'] <= 0
                    {
                        $sErrorMsg	= 'Прибыль за торговый период отсутствует';
                        $iErrorCode	= 10;
                    }
                    break;
                case $this->gaPammWithdrawalOperations['PRETERM']:
                    if (bccomp($aInvestorData['available_sum'], 0, 2) <= 0) //$aInvestorData['available_sum'] <= 0
                    {
                        $aInvestorData['withdrawal_sum'] = 0;
                    }
                    else
                    {
                        $fInvestorSum = $aInvestorData['investor_sum'];
                        // Если прибыль за период отрицательна или комиссия = 0 и что-то есть в поле admin_note - нужно прибыль учесть!
                        $sAdminNote = trim($aOrder['pamm_admin_note']);
                        if ($aInvestorData['period_profit'] < 0 || (empty($aOrder['pamm_commission']) && !empty($sAdminNote)))
                        {
                            $fInvestorSum = bcadd($fInvestorSum, $aInvestorData['period_profit'], 2);
                        }
                        // Теперь всё подсчитываем
                        $aInvestorData['withdrawal_sum'] = bcmul($fInvestorSum, bcsub(1, $aOrder['pamm_commission'], 2), 2);
                        $aInvestorData['penalty_sum'] = bcsub($fInvestorSum, $aInvestorData['withdrawal_sum'], 2);
                        if ($aInvestorData['period_profit'] > 0) {
                            $aInvestorData['penalty_sum'] = bcadd($aInvestorData['penalty_sum'], $aInvestorData['period_profit'], 2);
                        }
                    }
                    break;
                default:
                    $sErrorMsg	= 'Некорректная операция для данного типа ПАММ';
                    $iErrorCode	= 11;
                    break;
            }

            // Проводим проверки
            // Если сумма снятия превышает сумму на счету - то это ошибка
            if ($aOrder['operation'] != $this->gaPammWithdrawalOperations['PROFIT'] && (bccomp($aInvestorData['withdrawal_sum'], $aInvestorData['available_sum'], 2) == 1 || bccomp($aInvestorData['withdrawal_sum'], 0, 2) <= 0)) // ($aInvestorData['withdrawal_sum'] > $aInvestorData['available_sum'] || $aInvestorData['withdrawal_sum'] <= 0)
            {
                $sErrorMsg	= 'Сумма для снятия превышает сумму на счету';
                $iErrorCode	= 2;
            }

            if ($aOrder['investor_status'] == $this->gaPammInvestorAccountStatuses['CLOSED'])
            {
                $sErrorMsg	= 'Инвесторский счет закрыт';
                $iErrorCode	= 4;
            }

            // Если уже какая-то ошибка есть - незачем делать эту проверку и лишний раз дергать телнет
            if (empty($sErrorMsg))
            {
                // Если на ПАММ-счету не достаточно средств - это плохо
                $aPammMarginInfo = $this->webactions->getTradesMarginInfo($aOrder['pamm_mt_login']);
                if (bccomp($aInvestorData['withdrawal_sum'], $aPammMarginInfo['equity'], 2) == 1)   //if ($aInvestorData['withdrawal_sum'] > $aPammMarginInfo['equity'])
                {
                    $sErrorMsg	= 'На ПАММ-счету не хватает средств';
                    $iErrorCode	= 5;
                }
            }

            // Для корректного выполнения последующих операций снятия/зачисления, сумме для снятия ставим отрицательный знак
            $aOrder['sum'] = -$aInvestorData['withdrawal_sum'];
        }

        // Если есть ошибки - записываем их в таблицу распоряжений, ставим статус FAILED и выходим
        if (!empty($sErrorMsg))
        {
            $aFields = array(
                'confirmed_at'	=> $iRolloverTime,
                'status'		=> $this->gaPammPaymentOrdersStatuses['FAILED'],
                'error_code'	=> $iErrorCode,
            );
            $this->db->where('id', (int)$aOrder['id']);
            $this->db->update('pamm_money_orders', $aFields);

            $this->Errors[__FUNCTION__] = "PAMM_Error_{$iErrorCode}";
            return false;
        }

        // Проверяем если инвесторский счет не активен, сумма на нем достаточного размера для активации - то активируем его
        if ($aOrder['investor_status'] != $this->gaPammInvestorAccountStatuses['ACTIVATED'] && ($bIgnoreOffer || bccomp($aOrder['sum'], $aOrder['pamm_min_balance'], 2) >= 0)) // $aOrder['sum'] >= $aOrder['pamm_min_balance']
        {
            $aOrder['investor_status']		= $this->gaPammInvestorAccountStatuses['ACTIVATED'];
            $aInvestorFields['status']		= $this->gaPammInvestorAccountStatuses['ACTIVATED'];
            $aInvestorFields['activated_at']= $iRolloverTime;
            $aInvestorFields['closed_at']	= 0;
            $bInvestorJustActivated			= true;

            // Вносим данные в pamm_investors.total_deposits/withdrawal
            $this->db->where('id', (int)$aOrder['investor_id']);
            $this->db->set('total_deposits', 0);
            $this->db->set('total_withdrawal', 0);
            $this->db->update('pamm_investors');
        }

        $sPrefix = (!empty($aOrder['investor_index'])?"Pi":"PI");
        $sComment = "{$sPrefix}{$aOrder['inv_mt_login']}/{$aOrder['investor_id']}/{$aOrder['pamm_mt_login']}/{$aOrder['id']}";

        // Если есть что снимать - выполняем
        if (!empty($aInvestorData['withdrawal_sum']) || !empty($aInvestorData['deposit_sum']))
        {
            // Если надо снять с каждого - то делаем это одной операцией
            if (empty($aOrder['investor_index']) && $aOrder['investor_status'] == $this->gaPammInvestorAccountStatuses['ACTIVATED'] && $aOrder['money_withdrawn'] == 0 /*&& ($aOrder['investor_type'] == $this->gaPammInvestorTypes['INVESTOR'] || $aOrder['current_capital'] >= 0)*/)
            {
                // Снимаем деньги с памм/инв-счета и переводим на инв/памм-счет
                $iAccountFrom   = ($aOrder['operation'] == $this->gaPammWithdrawalOperations['DEPOSIT'])?$aOrder['inv_mt_login']:$aOrder['pamm_mt_login'];
                $iAccountTo     = ($aOrder['operation'] == $this->gaPammWithdrawalOperations['DEPOSIT'])?$aOrder['pamm_mt_login']:$aOrder['inv_mt_login'];
                $bResult = $this->webactions->makeTransfer($iAccountFrom, $iAccountTo, abs(bcadd($aOrder['sum'], 0, 2)), $sComment, $sComment);
            }
            else
            {
                // Если инвесторский счет активирован то снимаем/зачисляем деньги на Памм-счет
                if ($aOrder['investor_status'] == $this->gaPammInvestorAccountStatuses['ACTIVATED'])
                {
                    $bResult = $this->webactions->makePayment($aOrder['pamm_mt_login'], bcadd($aOrder['sum'], 0, 2), $sComment);
                }

                // Переводим/снимаем деньги на счет-получатель (торговый счет)
                if (empty($aOrder['investor_index']) && $aOrder['money_withdrawn'] == 0)
                {
                    $bResult = $this->webactions->makePayment($aOrder['inv_mt_login'], bcmul($aOrder['sum'], -1, 2), $sComment);
                    $aOrder['money_withdrawn'] = 1;
                }
            }
        }
        else
        {
            $bResult = true;
        }
        if (!empty($aInvestorData['penalty_sum']))
        {
            $sComment = "PMCC/" . sprintf("%.2f", $aInvestorData['penalty_sum']);
            $this->webactions->makePayment($aOrder['pamm_mt_login'], 0, $sComment, true);
        }

        if ($bResult == true)
        {
            // Обновляем инвесторский счет
            // Если распоряжение ALL_AND_CLOSE - то инвесторский счет надо закрыть
            if ($aOrder['operation'] == $this->gaPammWithdrawalOperations['ALL_AND_CLOSE'] || $aOrder['operation'] == $this->gaPammWithdrawalOperations['PRETERM'])
            {
                $aInvestorFields['closed_at']	= $iRolloverTime;
                $aInvestorFields['status']		= $this->gaPammInvestorAccountStatuses['CLOSED'];
                $aInvestorFields['current_sum']	= 0;
                $aInvestorFields['insured_sum']	= 0;
                $aInvestorData['available_sum'] = 0;
                $aInvestorData['investor_sum'] = 0;
            }
            else
            {
                // Обновляем сумму в управлении
                $aInvestorData['investor_sum']  = bcadd($aInvestorData['investor_sum'], $aOrder['sum'], 2);
                $aInvestorData['available_sum'] = bcadd($aInvestorData['available_sum'], $aOrder['sum'], 2);
                $this->db->where('id', (int)$aOrder['investor_id']);
                $this->db->set('current_sum', "current_sum+{$aOrder['sum']}", FALSE);
                $this->db->update('pamm_investors');
            }

            // После всех переводов, сохраняем запись в таблицу платежей
            $aFields = array(
                'investor_id'   => (int)$aOrder['investor_id'],
                'sum'           => $aOrder['sum'],
                'type'          => ($bInvestorJustActivated ? $this->gaPammPaymentsTypes['INITIAL'] : (($aOrder['operation'] != $this->gaPammWithdrawalOperations['PROFIT']) ? $this->gaPammPaymentsTypes['ADDON'] : $this->gaPammPaymentsTypes['PROFIT'])),
                'created_at'    => $iRolloverTime,
                'investor_sum'  => $aInvestorData['investor_sum'],
            );
            // Создаем заглушку для второго (возможного) платежа
            $aFieldsSecond = array();
            // Проверяем надо ли этот платёж провести как вывод прибыли
            if ($aOrder['operation'] == $this->gaPammWithdrawalOperations['DEFINED_SUM'])
            {
                if ($aInvestorData['period_profit'] >= ($aOrder['sum']*-1))
                {
                    $aFields['type'] = $this->gaPammPaymentsTypes['PROFIT'];
                }
                elseif ($aInvestorData['period_profit'] > 0 && $aInvestorData['period_profit'] < ($aOrder['sum']*-1))
                {
                    // Создаём новый платеж с новыми значениями
                    $aFieldsSecond = $aFields;
                    $aFieldsSecond['sum'] = bcadd($aFieldsSecond['sum'], $aInvestorData['period_profit'], 2);

                    // Подправляем данные для старого платежа и проводим его обновление (с 2 на 7)
                    $aFields['sum']		= $aInvestorData['period_profit'] * -1;
                    $aFields['type']	= $this->gaPammPaymentsTypes['PROFIT'];
                }
            }
            elseif ($aOrder['operation'] == $this->gaPammWithdrawalOperations['PRETERM'])
            {
                $aFieldsSecond = array(
                    'investor_id'   => (int)$aOrder['investor_id'],
                    'sum'           => bcmul($aInvestorData['penalty_sum'], -1, 2),
                    'type'          => $this->gaPammPaymentsTypes['PENALTY'],
                    'created_at'    => $iRolloverTime,
                    'investor_sum'  => $aInvestorData['investor_sum'],
                );
            }

            // Добавляем платеж
            $this->db->insert('pamm_payments', $aFields);
            // Вносим данные в pamm_investors.total_withdrawal
            $sFields = ($aFields['sum']>0)?'total_deposits':'total_withdrawal';
            $this->db->where('id', (int)$aOrder['investor_id']);
            $this->db->set($sFields, "{$sFields} + {$aFields['sum']}", FALSE);
            $this->db->update('pamm_investors');
            // А если есть второй платеж - проводим и его
            if (!empty($aFieldsSecond))
            {
                $this->db->insert('pamm_payments', $aFieldsSecond);
                // Вносим данные в pamm_investors.total_deposits/withdrawal, если это не досрочка
                if ($aOrder['operation'] != $this->gaPammWithdrawalOperations['PRETERM'])
                {
                    $sFields = ($aFieldsSecond['sum']>0)?'total_deposits':'total_withdrawal';
                    $this->db->where('id', (int)$aOrder['investor_id']);
                    $this->db->set($sFields, "{$sFields} + {$aFieldsSecond['sum']}", FALSE);
                    $this->db->update('pamm_investors');
                }
            }

            // Обновляем застрахованную сумму, если нет раздвоения платежа + единый платеж не PROFIT или раздвоение всё же есть, что говорит о том, что тело таки выводят.
            if ($aOrder['pamm_responsibility'] > 0 && ((empty($aFieldsSecond) && $aFields['type'] != $this->gaPammPaymentsTypes['PROFIT']) || !empty($aFieldsSecond)))
            {
                $fResposibilitySum = floor(bcmul(((empty($aFieldsSecond))?$aFields['sum']:$aFieldsSecond['sum']), $aOrder['pamm_responsibility'], 0)) / 100;
                $this->db->where('id', $aOrder['investor_id']);
                $this->db->set('insured_sum', "insured_sum+{$fResposibilitySum}", FALSE);
                $this->db->update('pamm_investors');
            }

            // Обновляем статус заявки, и время конфирма
            $aFields = array(
                'sum'			 => abs($aOrder['sum']),
                'status'		 => $this->gaPammPaymentOrdersStatuses['SUCCESS'],
                'confirmed_at'	 => $iRolloverTime,
                'error_code'	 => 0,
            );
            $this->db->where('id', $aOrder['id']);
            $this->db->update('pamm_money_orders', $aFields);

            // Выплата PROFIT вознаграждения для индексных счетов
            if ($aOrder['operation'] == $this->gaPammWithdrawalOperations['ALL_AND_CLOSE'] && !empty($aOrder['investor_index']))
            {
                $fAgentInvIdxProfit = $this->getInvestorsTotalProfitByPeriod($aOrder['investor_id'], $aOrder['activated_at'], time(), $aOrder['pamm_mt_login'], true);
                $this->doAgentPayout($aOrder['investor_id'], PAMMAGENTS_COMISSIONTYPE_PROFIT, $fAgentInvIdxProfit, $aOrder['current_capital'], $aOrder['id']);
            }

            if (!empty($aInvestorFields))
            {
                $this->db->where('id', $aOrder['investor_id']);
                $this->db->update('pamm_investors', $aInvestorFields);
            }

            // Затираем кеш, чтобы он обновился с новой суммой
            $this->db->where('investor_id', $aOrder['investor_id']);
            $this->db->delete('pamm_investors_stat');

            // Обновляем уровень ответственности ПАММ 2.0 если всё ок
            if (!empty($aOrder['pamm_responsibility']))
            {
                $this->updatePammResponsibleLevel($aOrder['pamm_mt_login']);
            }

            // Обновляем платежи внутри ТС если требуется
            if ($aOrder['operation'] == $this->gaPammWithdrawalOperations['DEPOSIT'] && $aOrder['trade_session_start_unixtime'] < time())
            {
                // Вносим данные в pamm_investors.trade_session_deposits
                $this->db->where('id', (int)$aOrder['investor_id']);
                $this->db->set('trade_session_deposits', "trade_session_deposits + {$aOrder['sum']}", FALSE);
                $this->db->update('pamm_investors');
            }

            // Выплата WELCOME вознаграждения - ТОЛЬКО НЕ ИНДЕКСНЫМ И НЕ ИК
            /*if ($aOrder['operation'] == $this->gaPammWithdrawalOperations['DEPOSIT'] && $aOrder['investor_type'] == $this->gaPammInvestorTypes['INVESTOR'] && empty($aOrder['investor_index']) && empty($aOrder['investor_ic']))
            {
                $this->doAgentPayout($aOrder['investor_id'], PAMMAGENTS_COMISSIONTYPE_WELCOME, abs($aOrder['sum']), $aOrder['current_capital'], $aOrder['id']);
            }*/

            $aResult = array(
                'order_id'	    => $aOrder['id'],
                'investor_id'	=> $aOrder['investor_id'],
                'sum'			=> $aOrder['sum'],
            );

            return $aResult;
        }
        else
        {
            // Обновляем статус заявки, и время конфирма
            $aFields = array(
                'status'		 => $this->gaPammPaymentOrdersStatuses['FAILED'],
                'confirmed_at'	 => $iRolloverTime,
                'error_code'	 => 16,
            );
            $this->db->where('id', $aOrder['id']);
            $this->db->update('pamm_money_orders', $aFields);
        }

        $this->Errors[__FUNCTION__] = 'MTError';
        return false;
    }

    /**
     * Получает список распоряжений
     *
     * @param integer $iOrderId - если указан, то ф-ция возвращает все распоряжение по его id
     * @param integer $iInvestorId - если указан, то ф-ция возвращает все распоряжения по данному инвестору
     * @param integer $iUserId - если указан, то ф-ция возвращает все распоряжения по данному user_id
     * @param integer $iPammMTLogin - если указан, то ф-ция возвращает все распоряжения по данному ПАММ-счету
     * @param integer $iOrderType
     * @param integer $iOrderStatus
     * @param bool $bIgnorePartnerId - если указан, то выбираются все распоряжения не смотря на id партнера
     * @return bool|array
     */
    function getMoneyOrdersList($iOrderId = 0, $iInvestorId=0, $iUserId=0, $iPammMTLogin=0, $iOrderType=-1, $iOrderStatus=-1, $bIgnorePartnerId=false)
    {
        if (empty($iOrderId) && empty($iInvestorId) && empty($iUserId) && empty($iPammMTLogin))
        {
            $this->Errors[__FUNCTION__] = 'InvalidParams';
            return false;
        }

        if (!empty($iOrderId))
        {
            $this->db->where('pmo.id', $iOrderId);
        }
        if (!empty($iInvestorId))
        {
            $this->db->where('ai.id', $iInvestorId);
        }
        if (!empty($iUserId))
        {
            $this->db->where('ai.user_id', $iUserId);
        }
        if (!empty($iPammMTLogin))
        {
            $this->db->where('ai.pamm_mt_login', $iPammMTLogin);
        }
        if (empty($bIgnorePartnerId))
        {
            $this->db->where('ai.partner_id', $this->iPartnerId);
        }
        if ($iOrderType != -1)
        {
            $this->db->where('pmo.operation', $iOrderType);
        }
        if ($iOrderStatus != -1)
        {
            $this->db->where('pmo.status', $iOrderStatus);
        }
        $this->db
            ->select('pmo.id AS order_id')
            ->select('pmo.investor_id')
            ->select('ai.user_id')
            ->select('ai.pamm_mt_login')
            ->select('ai.inv_mt_login')
            ->select('pmo.operation')
            ->select("IF(pmo.status = {$this->gaPammPaymentOrdersStatuses['SUCCESS']} OR pmo.status = {$this->gaPammPaymentOrdersStatuses['PENDING']}, pmo.sum, 0) AS sum", FALSE)
            ->select('pmo.status')
            ->select('pmo.error_code')
            ->select('pmo.created_at')
            ->select('IF(pmo.status = 0, UNIX_TIMESTAMP(IF(po.adjusting_rollover_date = po.next_rollover_date, DATE_ADD(po.next_rollover_date, INTERVAL po.trade_period*7 DAY), po.next_rollover_date)), pmo.confirmed_at) AS confirmed_at', FALSE)
            ->from('pamm_investors AS ai')
            ->join('pamm_money_orders AS pmo', 'pmo.investor_id = ai.id')
            ->join('pamm_offers AS po', 'po.pamm_mt_login = ai.pamm_mt_login')
            ->order_by('pmo.created_at', 'DESC');
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() == 0)
        {
            return false;
        }

        return $rQuery->result_array();
    }

    /* Ролловер */

    /**
     * Функция предзапуска ролловера, проставляет суммы в распоряжениях и отменяет неугодные
     */
    function runPreRollover()
    {
        // Отменяем распоряжения на вывод прибыли, если прибыли нет
        $sQuery = "UPDATE pamm_money_orders AS pmo INNER JOIN pamm_investors AS ai ON ai.id = pmo.investor_id INNER JOIN pamm_offers AS po ON po.pamm_mt_login = ai.pamm_mt_login AND po.next_rollover_date < CURRENT_TIMESTAMP AND po.status = 1 LEFT JOIN pamm_investors_stat AS pis ON pis.investor_id = pmo.investor_id SET pmo.status = 2, pmo.confirmed_at = UNIX_TIMESTAMP(), pmo.error_code = 10 WHERE pmo.operation = 6 AND pmo.status = 0 AND IFNULL(pis.period_profit, 0) <= 0";
        $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // Отменяем распоряжения, если сумма доступная для снятия ниже или равна нулю
        $sQuery = "UPDATE pamm_money_orders AS pmo INNER JOIN pamm_investors AS ai ON ai.id = pmo.investor_id INNER JOIN pamm_offers AS po ON po.pamm_mt_login = ai.pamm_mt_login AND po.next_rollover_date < CURRENT_TIMESTAMP AND po.status = 1 LEFT JOIN pamm_investors_stat AS pis ON pis.investor_id = pmo.investor_id SET pmo.status = 2, pmo.confirmed_at = UNIX_TIMESTAMP(), pmo.error_code = 2 WHERE pmo.operation IN (1, 2) AND pmo.status = 0 AND (ai.current_sum+IFNULL(pis.period_profit, 0)) <= 0";
        $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // Проставляем суммы
        $sQuery = "UPDATE pamm_money_orders AS pmo INNER JOIN pamm_investors AS ai ON ai.id = pmo.investor_id AND ai.type = 1 INNER JOIN pamm_offers AS po ON po.pamm_mt_login = ai.pamm_mt_login AND po.next_rollover_date < CURRENT_TIMESTAMP AND po.status = 1 LEFT JOIN pamm_investors_stat AS pis ON pis.investor_id = pmo.investor_id SET pmo.sum = ROUND(ai.current_sum+IFNULL(pis.period_profit, 0), 2) WHERE pmo.operation = 1 AND pmo.status = 0";
        $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        $sQuery = "UPDATE pamm_money_orders AS pmo INNER JOIN pamm_investors AS ai ON ai.id = pmo.investor_id INNER JOIN pamm_offers AS po ON po.pamm_mt_login = ai.pamm_mt_login AND po.next_rollover_date < CURRENT_TIMESTAMP AND po.status = 1 LEFT JOIN pamm_investors_stat AS pis ON pis.investor_id = pmo.investor_id SET pmo.sum = IFNULL(pis.period_profit, 0) WHERE pmo.operation = 6 AND pmo.status = 0";
        $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        $sQuery = "UPDATE pamm_money_orders AS pmo INNER JOIN pamm_investors AS ai ON ai.id = pmo.investor_id INNER JOIN pamm_offers AS po ON po.pamm_mt_login = ai.pamm_mt_login AND po.next_rollover_date < CURRENT_TIMESTAMP AND po.status = 1 LEFT JOIN pamm_money_orders AS pmo6 ON pmo6.investor_id = pmo.investor_id AND pmo6.operation = 6 AND pmo6.status = 0 LEFT JOIN pamm_investors_stat AS pis ON pis.investor_id = pmo.investor_id SET pmo.sum = ROUND(ai.current_sum+IF(pmo6.id IS NULL, IFNULL(pis.period_profit, 0), 0), 2) WHERE pmo.operation = 2 AND pmo.status = 0 AND pmo.sum > ROUND(ai.current_sum+IF(pmo6.id IS NULL, IFNULL(pis.period_profit, 0), 0), 2)";
        $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // Отменяем распоряжения управляющего:
        //  - если дилер позакрывал сделки или есть открытые сделки
        $sQuery = "UPDATE pamm_money_orders AS pmo INNER JOIN pamm_investors AS ai ON ai.id = pmo.investor_id AND ai.type = 2 INNER JOIN pamm_offers AS po ON po.pamm_mt_login = ai.pamm_mt_login AND po.next_rollover_date < CURRENT_TIMESTAMP AND po.status = 1 AND po.cancel_manager_pmo = 1 INNER JOIN pamm_rating AS pr ON pr.mt_login = po.pamm_mt_login AND pr.opened_tickets > 0 SET pmo.confirmed_at = UNIX_TIMESTAMP(), pmo.status = 9 WHERE pmo.status = 0";
        $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        //  - если управляющий снимает прибыль при отрицательном КУ
        $sQuery = "UPDATE pamm_money_orders AS pmo INNER JOIN pamm_investors AS ai ON ai.id = pmo.investor_id AND ai.type = 2 INNER JOIN pamm_offers AS po ON po.pamm_mt_login = ai.pamm_mt_login AND po.next_rollover_date < CURRENT_TIMESTAMP AND po.status = 1 LEFT JOIN pamm_investors_stat AS pis ON pis.investor_id = pmo.investor_id LEFT JOIN pamm_manager_capital_current AS pmcc ON pmcc.pamm_mt_login = ai.pamm_mt_login SET pmo.status = 2, pmo.confirmed_at = UNIX_TIMESTAMP(), pmo.error_code = 2 WHERE pmo.operation = 6 AND pmo.status = 0 AND IFNULL(pis.period_profit, 0) >= 0 AND (IFNULL(pmcc.capital, 0)-IFNULL(pis.period_profit, 0)) < 500";
        $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        //  - если управляющий не прошел процедуру идентификации
        /*$sQuery = "UPDATE pamm_money_orders AS pmo INNER JOIN pamm_investors AS ai ON ai.id = pmo.investor_id AND ai.type = 2 AND ai.partner_id = 0 INNER JOIN pamm_offers AS po ON po.pamm_mt_login = ai.pamm_mt_login AND po.next_rollover_date < CURRENT_TIMESTAMP AND po.status = 1 INNER JOIN users AS u ON u.id = ai.user_id AND u.is_real = 0 SET pmo.status = 2, pmo.error_code = 17 WHERE pmo.status = 0";
        $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);*/

        // Отменяем распоряжения которые не проходят базовые вводные
        $sQuery = "UPDATE pamm_money_orders AS pmo INNER JOIN pamm_investors AS ai ON ai.id = pmo.investor_id INNER JOIN pamm_offers AS po ON po.pamm_mt_login = ai.pamm_mt_login AND po.next_rollover_date < CURRENT_TIMESTAMP AND po.status = 1 SET pmo.status = 2, pmo.confirmed_at = UNIX_TIMESTAMP(), pmo.error_code = 1 WHERE pmo.status = 0 AND (ai.inv_mt_login = 0 OR pmo.investor_id = 0 OR (pmo.sum=0 AND pmo.operation=0))";
        $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // Обработка 15 ошибки только для наших ПАММов
        $sQuery = "UPDATE pamm_money_orders AS pmo INNER JOIN pamm_investors AS ai ON ai.id = pmo.investor_id AND ai.type = 1 INNER JOIN pamm_offers AS po ON po.pamm_mt_login = ai.pamm_mt_login AND po.next_rollover_date < CURRENT_TIMESTAMP AND po.status = 1 AND po.partner_id = 0 LEFT JOIN pamm_money_orders AS pmo6 ON pmo6.investor_id = pmo.investor_id AND pmo6.operation = 6 AND pmo6.status = 0 LEFT JOIN pamm_investors_stat AS pis ON pis.investor_id = pmo.investor_id SET pmo.status = 2, pmo.confirmed_at = UNIX_TIMESTAMP(), pmo.error_code = 15 WHERE pmo.operation = 2 AND pmo.status = 0 AND ROUND(ai.current_sum + IF(pmo6.id IS NULL, IFNULL(pis.period_profit, 0), 0) - GREATEST(ai.trade_session_deposits, IF(ai.type = 1, po.min_balance, GREATEST(po.min_balance, 500))), 2) <= 0";
        $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        $sQuery = "UPDATE pamm_money_orders AS pmo INNER JOIN pamm_investors AS ai ON ai.id = pmo.investor_id AND ai.type = 1 INNER JOIN pamm_offers AS po ON po.pamm_mt_login = ai.pamm_mt_login AND po.next_rollover_date < CURRENT_TIMESTAMP AND po.status = 1 AND po.partner_id = 0 LEFT JOIN pamm_money_orders AS pmo6 ON pmo6.investor_id = pmo.investor_id AND pmo6.operation = 6 AND pmo6.status = 0 LEFT JOIN pamm_investors_stat AS pis ON pis.investor_id = pmo.investor_id SET pmo.sum = ROUND(ai.current_sum + IF(pmo6.id IS NULL, IFNULL(pis.period_profit, 0), 0) - GREATEST(ai.trade_session_deposits, IF(ai.type = 1, po.min_balance, GREATEST(po.min_balance, 500))), 2) WHERE pmo.operation = 2 AND pmo.status = 0 AND pmo.sum > ROUND(ai.current_sum + IF(pmo6.id IS NULL, IFNULL(pis.period_profit, 0), 0) - GREATEST(ai.trade_session_deposits, IF(ai.type = 1, po.min_balance, GREATEST(po.min_balance, 500))), 2)";
        $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // Отменяем распоряжения, если сумма снятия превышает сумму на счету
        $sQuery = "UPDATE pamm_money_orders AS pmo INNER JOIN pamm_investors AS ai ON ai.id = pmo.investor_id INNER JOIN pamm_offers AS po ON po.pamm_mt_login = ai.pamm_mt_login AND po.next_rollover_date < CURRENT_TIMESTAMP AND po.status = 1 LEFT JOIN pamm_money_orders AS pmo6 ON pmo6.investor_id = pmo.investor_id AND pmo6.operation = 6 AND pmo6.status = 0 LEFT JOIN pamm_investors_stat AS pis ON pis.investor_id = pmo.investor_id SET pmo.status = 2, pmo.confirmed_at = UNIX_TIMESTAMP(), pmo.error_code = 2 WHERE pmo.operation IN (1, 2) AND pmo.status = 0 AND pmo.sum > ROUND((ai.current_sum+IF(pmo6.id IS NULL, IFNULL(pis.period_profit, 0), 0)), 2)";
        $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // Отменяем распоряжения, если остаток после снятия будет меньше мин допустимого баланса
        // @todo: В прошлый раз выбрало 0, обновило - 21. Разобраться! Подозрение на пантеон
        $sQuery = "UPDATE pamm_money_orders AS pmo INNER JOIN pamm_investors AS ai ON ai.id = pmo.investor_id INNER JOIN pamm_offers AS po ON po.pamm_mt_login = ai.pamm_mt_login AND po.next_rollover_date < CURRENT_TIMESTAMP AND po.status = 1 LEFT JOIN pamm_money_orders AS pmo6 ON pmo6.investor_id = pmo.investor_id AND pmo6.operation = 6 AND pmo6.status = 0 LEFT JOIN pamm_investors_stat AS pis ON pis.investor_id = pmo.investor_id SET pmo.status = 2, pmo.confirmed_at = UNIX_TIMESTAMP(), pmo.error_code = 3 WHERE pmo.operation = 2 AND pmo.status = 0 AND po.min_balance > ROUND(ai.current_sum+IF(pmo6.id IS NULL, IFNULL(pis.period_profit, 0), 0) - pmo.sum, 2)";
        $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // Обработка 6 ошибки для управляющих ПАММов 2.0
        // WARNING: Должно быть ниже всех
        /*$sQuery = "CREATE TEMPORARY TABLE exceed_insured_sums SELECT ai.pamm_mt_login, ROUND(pmcc.capital-SUM(aii.insured_sum), 2) AS insured_sum_exceed FROM pamm_money_orders AS pmo INNER JOIN pamm_investors AS ai ON ai.id = pmo.investor_id AND ai.type = 2 INNER JOIN pamm_offers AS po ON po.pamm_mt_login = ai.pamm_mt_login AND po.next_rollover_date < CURRENT_TIMESTAMP AND po.status = 1 AND po.responsibility > 0 INNER JOIN pamm_investors AS aii ON aii.pamm_mt_login = ai.pamm_mt_login AND aii.status = 1 AND aii.type = 1 LEFT JOIN pamm_manager_capital_current AS pmcc ON pmcc.pamm_mt_login = ai.pamm_mt_login WHERE pmo.operation = 2 AND pmo.status = 0";
        $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        $sQuery = "UPDATE pamm_money_orders AS pmo INNER JOIN pamm_investors AS ai ON ai.id = pmo.investor_id AND ai.type = 2 INNER JOIN pamm_offers AS po ON po.pamm_mt_login = ai.pamm_mt_login AND po.next_rollover_date < CURRENT_TIMESTAMP AND po.status = 1 AND po.responsibility > 0 LEFT JOIN exceed_insured_sums AS eis ON eis.pamm_mt_login = ai.pamm_mt_login SET pmo.sum = IF(pmo.sum > eis.insured_sum_exceed AND eis.insured_sum_exceed > 0, eis.insured_sum_exceed, pmo.sum), pmo.status = IF(pmo.sum > eis.insured_sum_exceed AND eis.insured_sum_exceed <= 0, 2, pmo.status), pmo.error_code = IF(pmo.sum > eis.insured_sum_exceed AND eis.insured_sum_exceed <= 0, 6, pmo.error_code) WHERE pmo.operation = 2 AND pmo.status = 0";
        $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        $sQuery = "DROP TEMPORARY TABLE exceed_insured_sums";
        $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);*/

        return true;
    }

    /**
     * Запускает цикл по ролловерам
     *
     * @param bool|false $bAllow
     * @return boolean
     */
    function runPeriodicRollover($bAllow = false)
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_implicit_flush();

        // Собираем необходимую инфу
        $this->db->query("CREATE TEMPORARY TABLE tt_pa
        SELECT
         po.pamm_mt_login AS pamm_mt_login,
         po.responsibility/100 AS responsibility_percent,
         po.trade_period AS trade_period,
         IFNULL(mtu.ENABLE, 0) AS ENABLE,
         IFNULL(mtu.STATE, '') AS STATE,
         IFNULL(mtu.EQUITY, 0) AS EQUITY,
         IFNULL(mtu.MARGIN_FREE, 0) AS MARGIN_FREE,
         COUNT(mtt.TICKET) AS ot_count,
         IF(po.conditionally_periodic = 0 AND po.status = 1 AND mtt.TICKET IS NOT NULL AND po.trade_immunity = 0 AND (SELECT COUNT(*) FROM pamm_rollovers WHERE pamm_mt_login = po.pamm_mt_login) > 0 AND DATE_ADD(DATE_FORMAT(po.open_date, '%Y-%m-%d 23:59:59'), INTERVAL po.trade_period*7 DAY) < CURDATE(), 1, 0) AS ot_close,
         po.next_rollover_date AS next_rollover_date,
         po.last_rollover_date AS last_rollover_date,
         (SELECT COUNT(*) FROM pamm_rollovers WHERE pamm_mt_login = po.pamm_mt_login AND rollover_date > UNIX_TIMESTAMP(IFNULL(po.open_date, 0))) AS total_rollovers,
         IF(po.mc_stopout = 1 AND IFNULL((SELECT capital FROM pamm_manager_capital_current WHERE pamm_mt_login = po.pamm_mt_login LIMIT 1), 0) < 500, 1, 0) AS mc_stopout
        FROM pamm_offers AS po
        INNER JOIN pamm_investors AS ai ON ai.pamm_mt_login = po.pamm_mt_login AND ai.type = 2
        LEFT JOIN MT4_USERS AS mtu ON mtu.LOGIN = po.pamm_mt_login
        LEFT JOIN MT4_TRADES AS mtt ON mtt.LOGIN = po.pamm_mt_login AND mtt.CMD < 2 AND mtt.CLOSE_TIME = '1970-01-01 00:00:00'
        WHERE po.status = 1 AND po.next_rollover_date < CURRENT_TIMESTAMP
        GROUP BY po.pamm_mt_login");
        $this->prn($this->db->last_query());

        $this->db
            ->select('tt_pa.*')
            ->select('IFNULL(SUM(pmo.sum), 0) AS withdrawal_sum', FALSE)
            ->select('IF(tt_pa.ENABLE = 1 AND tt_pa.mc_stopout = 0 AND (tt_pa.trade_period = 0 OR tt_pa.total_rollovers = 0 OR GREATEST(tt_pa.MARGIN_FREE, 0) >= IFNULL(SUM(pmo.sum), 0)), 1, 0) AS rollover', FALSE)
            ->from('tt_pa')
            ->join('pamm_investors AS ai', 'ai.pamm_mt_login = tt_pa.pamm_mt_login AND ai.status = 1 AND ai.for_index = 0', 'left')
            ->join('pamm_money_orders AS pmo', 'pmo.investor_id = ai.id AND pmo.status = 0 AND pmo.operation > 0', 'left')
            ->order_by('rollover')
            ->order_by('tt_pa.mc_stopout', 'DESC')
            ->order_by('tt_pa.pamm_mt_login', 'ASC')
            ->group_by('tt_pa.pamm_mt_login');
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() == 0)
        {
            return false;
        }
        $aPammAccounts = $rQuery->result_array();
        $this->prn($aPammAccounts);

        if (!$bAllow) {
            exit("Not allowed");
        }

        foreach ($aPammAccounts as $aPamm)
        {
            // Проводим ролловер если можно
            if ($aPamm['rollover'] == 1)
            {
                $this->runRollover($aPamm['pamm_mt_login'], $bAllow);
            }
        }

        $bResult = $this->webactions->updateIndexPluginSettings("Allow Close Orders", '1');
        $this->prn("Allow Close Index Orders", $bResult);

        // Снимаем с ПАММ-счета readonly (если у него выставлен loss_limit)
        $this->db
            ->select('pamm_mt_login')
            ->from('pamm_offers')
            ->where('status', $this->gaPammOffersStatuses['OPENED'])
            ->where('loss_limit > ', 0)
            ->order_by('pamm_mt_login', 'ASC');
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() > 0)
        {
            $aPammAccounts = $rQuery->result_array();
            foreach ($aPammAccounts as $aPamm)
            {
                $this->webactions->updateUser($aPamm['pamm_mt_login'], 'ENABLE_READ_ONLY=0');
            }
            $this->prn("Loss Limit readonly disabled");
        }

        return true;
    }

    /**
     * Рассчитывает итоги работы ПАММ-счета, выполняет распоряжения на вывод/зачисление денег
     *
     * @param integer $iPammMTLogin
     * @param boolean $bAllow
     * @return bool
     */
    function runRollover($iPammMTLogin, $bAllow = false)
    {
        if (empty($iPammMTLogin)) {
            exit("No pamm login");
        }

        // Запоминаем время ролловера по этому счету
        $iRolloverTime = time();

        // Получаем данные о памме
        $this->db
            ->select('po.id AS offer_id', FALSE)
            ->select('po.status AS offer_status', FALSE)
            ->select('po.pamm_mt_login')
            ->select('po.investor_id AS manager_investor_id', FALSE)
            ->select('UNIX_TIMESTAMP(IF(po.last_rollover_date < "2000-01-01 00:00:00", po.create_date, po.last_rollover_date)) AS last_rollover_time', FALSE)
            ->select('po.responsibility/100 AS responsibility_percent', FALSE)
            ->select('IFNULL(pr.opened_tickets, 0) AS opened_tickets', FALSE)
            ->select('po.agent_bonus_profit')
            ->select('po.loss_limit')
            ->from('pamm_offers AS po')
            ->join('pamm_rating AS pr', 'pr.mt_login = po.pamm_mt_login', 'left')
            ->where('po.pamm_mt_login', $iPammMTLogin)
            ->group_by('po.pamm_mt_login');
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() == 0)
        {
            $this->prn('No PAMM Info', $iPammMTLogin);
            return false;
        }
        $aPammInfo = $rQuery->row_array();
        $this->prn('PAMM Info', $aPammInfo, $iRolloverTime, $bAllow);

        // Получаем список инв счетов, который и будем использовать для выполнения разных распоряжений
        $this->db
            ->select('ai.id AS investor_id')
            ->select('IF(ai.`type` = 1, ai.current_sum, FLOOR(IFNULL(pmcc.capital, 0)*100)/100)+IF(ai.`type` = 1, IFNULL(pis.period_profit, 0), 0) AS current_sum', FALSE)
            ->select('ai.for_index')
            ->select('IFNULL(pis.period_profit, 0) AS period_profit', FALSE)
            ->from('pamm_investors AS ai')
            ->join('pamm_manager_capital_current AS pmcc', 'pmcc.pamm_mt_login = ai.pamm_mt_login', 'left')
            ->join('pamm_investors_stat AS pis', 'pis.investor_id = ai.id', 'left')
            ->where('ai.pamm_mt_login', $iPammMTLogin)
            ->where('ai.status', $this->gaPammInvestorAccountStatuses['ACTIVATED'])
            ->order_by('ai.id', 'ASC');
        $rQuery = $this->db->get();
        $aInvestors = $rQuery->result_array();
        if (empty($aInvestors))
        {
            $aInvestors = array(
                0 => array(
                    'current_sum'   => 0,
                ),
            );
        }
        else
        {
            $sQuery = "UPDATE pamm_investors SET current_sum = {$aInvestors[0]['current_sum']} WHERE id = {$aPammInfo['manager_investor_id']}";
            if ($bAllow) $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);
        }

        $this->prn("{$aPammInfo['pamm_mt_login']} Investors Dump", $aInvestors);

        $sQuery = "UPDATE pamm_investors AS ai, pamm_investors_stat AS pis SET ai.current_sum = GREATEST(ai.current_sum + pis.period_profit, 0) WHERE ai.pamm_mt_login = {$iPammMTLogin} AND ai.id = pis.investor_id AND ai.type = {$this->gaPammInvestorTypes['INVESTOR']} AND ai.status = {$this->gaPammInvestorAccountStatuses['ACTIVATED']}";
        if ($bAllow) $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // Выплата PROFIT агентских в ролловер, если поле pamm_offers.agent_bonus_profit != 0: платим ТОЛЬКО не индексным inv счетам
        if (!empty($aPammInfo['agent_bonus_profit']))
        {
            foreach ($aInvestors as $aInvestor)
            {
                if ($bAllow && empty($aInvestor['for_index']) && $aInvestor['investor_id'] != $aPammInfo['manager_investor_id'])
                {
                    // Раздаем щедрость агентам
                    $fPaySum = $this->doAgentPayout($aInvestor['investor_id'], PAMMAGENTS_COMISSIONTYPE_PROFIT, $aInvestor['period_profit'], $aInvestors[0]['current_sum']);
                    // Если выплатить получилось - то надо сразу же снять с упр.
                    if (!empty($fPaySum))
                    {
                        $aInvestors[0]['current_sum'] = bcsub($aInvestors[0]['current_sum'], $fPaySum, 2);
                    }
                }
            }

            $sQuery = "SELECT SUM(bonus_received) AS total_sum FROM pamm_agents_payments WHERE pamm_mt_login = {$iPammMTLogin} AND status = 2";
            $rQuery = $this->db->query($sQuery);
            $fTotalSum = ($rQuery->num_rows() > 0)?$rQuery->row()->total_sum:0;
            $this->prn($sQuery, $fTotalSum);
            // Если инвесторский счет активирован то снимаем/зачисляем деньги на Памм-счет
            if ($bAllow && !empty($fTotalSum))
            {
                $sComment = "PAp/".date("Y-m-d", $aPammInfo['last_rollover_time'])."/".date("Y-m-d", $iRolloverTime);
                $bResult = $this->webactions->makePayment($iPammMTLogin, bcmul($fTotalSum, -1, 2), $sComment);
                $this->prn('fapi::MakePayment PAMM', $iPammMTLogin, $sComment, $bResult);
            }

            $sQuery = "UPDATE pamm_agents_payments SET status = 1 WHERE pamm_mt_login = {$iPammMTLogin} AND status = 2";
            if ($bAllow) $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);
        }

        // Обновляем данные по управу после выплаты агентских
        $sQuery = "UPDATE pamm_investors SET current_sum = {$aInvestors[0]['current_sum']} WHERE id = {$aPammInfo['manager_investor_id']}";
        if ($bAllow) $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // Все распоряжения где status = PENDING ставим в очередь на fapi
        $sT_PLUGIN_MASTER = "HwtqxuLE6cj5";
        $sQuery = "INSERT INTO fapi_queries_dispatcher (server_ip, query, create_date, process_date) SELECT '' AS server_ip, IF(ai.type = 2, CONCAT('CLIENTSTRANSFERBALANCE-MASTER={$sT_PLUGIN_MASTER}|FROM=', ai.pamm_mt_login, '|TO=', ai.inv_mt_login, '|VALUE=', pmo.sum, '|COMMENTFROM=PI', ai.inv_mt_login, '/', ai.id, '/', ai.pamm_mt_login, '/', pmo.id, '|COMMENTTO=PI', ai.inv_mt_login, '/', ai.id, '/', ai.pamm_mt_login, '/', pmo.id), CONCAT('CLIENTSCHANGEBALANCE-MASTER={$sT_PLUGIN_MASTER}|LOGIN=', ai.inv_mt_login, '|VALUE=', pmo.sum, '|COMMENT=PI', ai.inv_mt_login, '/', ai.id, '/', ai.pamm_mt_login, '/', pmo.id)) AS query, NOW() AS create_date, NOW() AS process_date FROM pamm_investors AS ai INNER JOIN pamm_money_orders AS pmo ON pmo.investor_id = ai.id AND pmo.status = 0 AND pmo.operation IN (1, 2, 6) WHERE ai.pamm_mt_login = {$iPammMTLogin} AND ai.status = {$this->gaPammInvestorAccountStatuses['ACTIVATED']} AND ai.for_index = 0 ORDER BY ai.type ASC, pmo.operation DESC";
        if ($bAllow) $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // Всем распоряжениям где status = PENDING проставляем SUCCESS
        $sQuery = "UPDATE pamm_money_orders AS pmo INNER JOIN pamm_investors AS ai ON ai.id = pmo.investor_id AND ai.pamm_mt_login = {$iPammMTLogin} AND ai.status = 1 AND ai.for_index = 0 SET pmo.status = 1, pmo.confirmed_at = {$iRolloverTime}, pmo.error_code = 0 WHERE pmo.status = 0 AND pmo.operation IN (1, 2, 6)";
        if ($bAllow) $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // Добавляем платежы в pamm_payments
        // - Платежи вывода прибыли
        $sQuery = "INSERT INTO pamm_payments (investor_id, `sum`, `type`, created_at, investor_sum) SELECT ai.id AS investor_id, pmo.sum*-1 AS `sum`, 7 AS `type`, {$iRolloverTime} AS created_at, ai.current_sum-pmo.sum AS investor_sum FROM pamm_investors AS ai INNER JOIN pamm_money_orders AS pmo ON pmo.investor_id = ai.id AND pmo.operation = 6 AND pmo.status = 1 AND pmo.confirmed_at >= {$iRolloverTime} WHERE ai.pamm_mt_login = {$iPammMTLogin} AND ai.status = 1 AND ai.for_index = 0 ORDER BY ai.type ASC";
        if ($bAllow) $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // - Платежи DEFINED_SUM и ALL_AND_CLOSE
        $sQuery = "INSERT INTO pamm_payments (investor_id, `sum`, `type`, created_at, investor_sum) SELECT investor_id, `sum`*-1 AS `sum`, `type`, created_at, investor_sum FROM (SELECT pmo.id, pmo.operation, ai.id AS investor_id, LEAST(GREATEST(0, IFNULL(pis.period_profit, 0))-IFNULL(pmo6.sum, 0), pmo.sum) AS `sum`, 7 AS `type`, {$iRolloverTime} AS created_at, ai.current_sum-IFNULL(pmo6.sum, 0)-pmo.sum AS investor_sum FROM pamm_investors AS ai LEFT JOIN pamm_investors_stat AS pis ON pis.investor_id = ai.id LEFT JOIN pamm_money_orders AS pmo6 ON pmo6.investor_id = ai.id AND pmo6.operation = 6 AND pmo6.status = 1 AND pmo6.confirmed_at >= {$iRolloverTime} INNER JOIN pamm_money_orders AS pmo ON pmo.investor_id = ai.id AND pmo.operation IN (1, 2) AND pmo.status = 1 AND pmo.confirmed_at >= {$iRolloverTime} WHERE ai.pamm_mt_login = {$iPammMTLogin} AND ai.status = 1 AND ai.for_index = 0 UNION ALL SELECT pmo.id, pmo.operation, ai.id AS investor_id, GREATEST(0, pmo.sum-(IFNULL(pis.period_profit, 0)-IFNULL(pmo6.sum, 0))) AS `sum`, 2 AS `type`, {$iRolloverTime} AS created_at, ai.current_sum-IFNULL(pmo6.sum, 0)-pmo.sum AS investor_sum FROM pamm_investors AS ai LEFT JOIN pamm_investors_stat AS pis ON pis.investor_id = ai.id LEFT JOIN pamm_money_orders AS pmo6 ON pmo6.investor_id = ai.id AND pmo6.operation = 6 AND pmo6.status = 1 AND pmo6.confirmed_at >= {$iRolloverTime} INNER JOIN pamm_money_orders AS pmo ON pmo.investor_id = ai.id AND pmo.operation IN (1, 2) AND pmo.status = 1 AND pmo.confirmed_at >= {$iRolloverTime} WHERE ai.pamm_mt_login = {$iPammMTLogin} AND ai.status = 1 AND ai.for_index = 0) AS a WHERE a.sum > 0 ORDER BY operation DESC, id, investor_id, `type` DESC";
        if ($bAllow) $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // Создаем итоговую темповую таблицу суммы снятий по распоряжениям
        $sQuery = "CREATE TEMPORARY TABLE pmos SELECT ai.id AS investor_id, pmo.operation, SUM(pmo.sum) AS total_pmo_sum, IFNULL(pmo6.sum, 0) AS pmo_profit_sum FROM pamm_investors AS ai INNER JOIN pamm_offers AS po ON po.pamm_mt_login = ai.pamm_mt_login AND po.next_rollover_date < CURRENT_TIMESTAMP AND po.status = 1 INNER JOIN pamm_money_orders AS pmo ON pmo.investor_id = ai.id AND pmo.status = 1 AND pmo.confirmed_at >= {$iRolloverTime} LEFT JOIN pamm_money_orders AS pmo6 ON pmo6.investor_id = pmo.investor_id AND pmo6.operation = 6 AND pmo6.status = 1 AND pmo6.confirmed_at >= {$iRolloverTime} WHERE ai.pamm_mt_login = {$iPammMTLogin} AND ai.status = 1 AND ai.for_index = 0 GROUP BY ai.id";
        if ($bAllow) $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // Обновляем поля инвесторского счета в pamm_investors
        $sQuery = "UPDATE pamm_investors AS ai INNER JOIN pamm_offers AS po ON po.pamm_mt_login = ai.pamm_mt_login INNER JOIN pmos ON pmos.investor_id = ai.id SET ai.total_withdrawal = ai.total_withdrawal - pmos.total_pmo_sum, ai.closed_at = IF(pmos.operation = 1, {$iRolloverTime}, ai.closed_at), ai.status = IF(pmos.operation = 1, 2, ai.status), ai.current_sum = IF(pmos.operation = 1, 0, ai.current_sum - pmos.total_pmo_sum), ai.insured_sum = IF(pmos.operation = 1 OR po.responsibility = 0, 0, ai.insured_sum - (pmos.total_pmo_sum-pmos.pmo_profit_sum)*po.responsibility/100), ai.auto_withdrawal_profit = IF(pmos.operation = 1, 0, ai.auto_withdrawal_profit) WHERE ai.pamm_mt_login = {$iPammMTLogin} AND ai.status = 1 AND ai.for_index = 0";
        if ($bAllow) $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        $sQuery = "DROP TEMPORARY TABLE pmos";
        if ($bAllow) $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // Теперь выполняем списание с ПАММ-счета одной суммой для всех инвесторов
        $sQuery = "INSERT INTO fapi_queries_dispatcher (server_ip, query, create_date, process_date) SELECT '' AS server_ip, CONCAT('CLIENTSCHANGEBALANCE-MASTER={$sT_PLUGIN_MASTER}|LOGIN=', ai.pamm_mt_login, '|VALUE=', ROUND(SUM(pmo.sum), 2)*-1, '|COMMENT=PIw/', DATE_FORMAT(FROM_UNIXTIME({$aPammInfo['last_rollover_time']}), '%Y-%m-%d'), '/', DATE_FORMAT(FROM_UNIXTIME({$iRolloverTime}), '%Y-%m-%d')) AS query, NOW() AS create_date, NOW() AS process_date FROM pamm_investors AS ai INNER JOIN pamm_offers AS po ON po.pamm_mt_login = ai.pamm_mt_login INNER JOIN pamm_money_orders AS pmo ON pmo.investor_id = ai.id AND pmo.confirmed_at >= {$iRolloverTime} AND pmo.operation != 0 AND pmo.status = 1 WHERE ai.pamm_mt_login = {$iPammMTLogin} AND ai.type = {$this->gaPammInvestorTypes['INVESTOR']} HAVING LENGTH(query) > 0";
        if ($bAllow) $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // Обновляем метку времени РО
        $iRolloverTime = time();

        // Обновляем доли инвесторов
        $sQuery = "INSERT INTO pamm_payments (`investor_id`, `sum`, `created_at`, `type`, `investor_sum`) SELECT ai.id AS investor_id, IFNULL(pis.period_profit, 0) AS `sum`, {$iRolloverTime} AS created_at, 8 AS `type`, ROUND(ai.current_sum, 2) AS investor_sum FROM pamm_investors AS ai INNER JOIN pamm_offers AS po ON po.pamm_mt_login = ai.pamm_mt_login LEFT JOIN pamm_investors_stat AS pis ON pis.investor_id = ai.id WHERE ai.pamm_mt_login = {$iPammMTLogin} AND (ai.status = 1 OR ai.closed_at > UNIX_TIMESTAMP(DATE_SUB(po.last_rollover_date, INTERVAL 1 DAY)))";
        if ($bAllow) $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        $sQuery = "INSERT INTO pamm_rollovers (pamm_mt_login, rollover_date) VALUES ({$aPammInfo['pamm_mt_login']}, {$iRolloverTime})";
        if ($bAllow) $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // А также делаем обновления в pamm_offers и pamm_rating метку нового ролловера
        $iMinusDays = 0;
        $sQuery = "UPDATE pamm_offers SET last_rollover_date = FROM_UNIXTIME({$iRolloverTime}), next_rollover_date = DATE_FORMAT(DATE_ADD(last_rollover_date, INTERVAL IF(status = 3, 1, trade_period)*7-{$iMinusDays} DAY), '%Y-%m-%d 10:00:00'), cancel_manager_pmo = 0 WHERE pamm_mt_login = {$aPammInfo['pamm_mt_login']} AND next_rollover_date < NOW()";
        if ($bAllow) $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);
        $sQuery = "UPDATE pamm_rating SET next_rollover_date = DATE_FORMAT(DATE_ADD(FROM_UNIXTIME({$iRolloverTime}), INTERVAL IF(offer_status = 3, 1, offer_trade_period)*7-{$iMinusDays} DAY), '%Y-%m-%d 10:00:00') WHERE mt_login = {$aPammInfo['pamm_mt_login']} AND next_rollover_date < NOW()";
        if ($bAllow) $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // Теперь выбираем активных инвесторов у которых включены автовыплаты прибыли и создаём новые распоряжения
        $sQuery = "INSERT INTO pamm_money_orders (mt_login, investor_id, created_at, operation) SELECT ai.inv_mt_login AS mt_login, ai.id AS investor_id, UNIX_TIMESTAMP() AS created_at, 6 AS operation FROM pamm_investors AS ai LEFT JOIN pamm_money_orders AS pmo ON pmo.investor_id = ai.id AND pmo.operation = 6 AND pmo.status = 0 WHERE ai.pamm_mt_login = {$iPammMTLogin} AND ai.status = 1 AND ai.auto_withdrawal_profit = 1 AND ai.for_index = 0 AND pmo.id IS NULL";
        if ($bAllow) $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // Затираем кеш, чтобы он обновился с новой суммой
        $sQuery = "DELETE FROM pamm_investors_stat WHERE pamm_mt_login = {$iPammMTLogin}";
        if ($bAllow) $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // Затираем кеш, чтобы он обновился с новой суммой
        $sQuery = "UPDATE pamm_investors SET trade_session_deposits = 0 WHERE pamm_mt_login = {$iPammMTLogin}";
        if ($bAllow) $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        // Теперь обновляем уровень ответственности, если это ПАММ 2.0
        // @todo: Уровень ответственности переписать
        /*if (!empty($aPammInfo['responsibility_percent']))
        {
            Call("pamm::UpdateResponsibleLevel", $aPammInfo);
        }*/

        return true;
    }

    /* Индексы */

    /**
     * Получение списка ПАММ-индексов
     *
     * @param none
     * @return array|bool
     */
    function getPammIndexList()
    {
        $this->db
            ->select('pis.symbol')
            ->select('pis.start_time')
            ->select('pis.first_tick')
            ->select('pis.last_tick_time')
            ->select('(mtp.BID-pis.first_tick)*100 AS bid')
            ->from('pamm_idx_symbols AS pis')
            ->join('MT4_PRICES AS mtp', 'pis.symbol = mtp.SYMBOL')
            ->where('pis.visible != ', 0)
            ->order_by('pis.symbol', 'ASC');
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() == 0)
        {
            return false;
        }

        return $rQuery->result_array();
    }

    /**
     * Получение деталей ПАММ-индекса (доли управляющих)
     *
     * @param none
     * @return array|bool
     */
    function getPammIndexDetails($sSymbol)
    {
        $this->db
            ->select('symbol')
            ->select('visible')
            ->from('pamm_idx_symbols')
            ->where('symbol', $sSymbol)
            ->where('visible != ', 0);
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() == 0)
        {
            return false;
        }

        $aResult = $rQuery->row_array();
        if ($aResult['visible'] == 2) {
            return [
                [
                    'symbol'       => $aResult['symbol'],
                    'pamm_mt_login'=> 0,
                    'share'        => 1,
                ],
            ];
        }

        $this->db
            ->select('pis.symbol')
            ->select('pish.pamm_mt_login')
            ->select('pish.share')
            ->from('pamm_idx_symbols AS pis')
            ->join('pamm_idx_shares AS pish', 'pish.symbol = pis.symbol')
            ->where('pis.symbol', $sSymbol)
            ->where('pis.visible', 1)
            ->order_by('pish.pamm_mt_login', 'ASC');
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() == 0)
        {
            return false;
        }

        return $rQuery->result_array();
    }

    /**
     * Получает доходности ПАММ-индекса по неделям
     *
     * @param string $sSymbol
     * @return array|bool
     */
    function getPammIndexTickStat($sSymbol)
    {
        $this->db->query("CREATE TEMPORARY TABLE tt_pits1 (symbol CHAR(16) NOT NULL, PERIOD_START DATETIME NOT NULL, PERIOD_END DATETIME NOT NULL, PRIMARY KEY (symbol, PERIOD_START)) SELECT  pits.symbol as symbol, DATE_SUB(DATE_FORMAT(pits.tick_time, \"%Y-%m-%d 00:00:00\"), INTERVAL WEEKDAY(pits.tick_time) DAY) as PERIOD_START, DATE_ADD(DATE_SUB(DATE_FORMAT(pits.tick_time, \"%Y-%m-%d 00:00:00\"), INTERVAL WEEKDAY(pits.tick_time) DAY), INTERVAL 6 DAY) as PERIOD_END FROM pamm_idx_tick_stat AS pits WHERE pits.symbol = '{$sSymbol}'  GROUP BY YEARWEEK(pits.tick_time, 3)");
        $this->db->query("CREATE TEMPORARY TABLE tt_pits01 (tick_time DATETIME NOT NULL, bid DOUBLE NOT NULL, KEY tick_time (tick_time)) SELECT  tick_time, bid  FROM pamm_idx_tick_stat WHERE symbol = '{$sSymbol}'  ORDER BY tick_time DESC");
        $this->db->query("CREATE TEMPORARY TABLE tt_pits02 (tick_time DATETIME NOT NULL, bid DOUBLE NOT NULL, KEY tick_time (tick_time)) SELECT  tick_time, bid  FROM pamm_idx_tick_stat WHERE symbol = '{$sSymbol}'  ORDER BY tick_time DESC");
        $this->db->query("CREATE TEMPORARY TABLE tt_pits03 (tick_time DATETIME NOT NULL, bid DOUBLE NOT NULL, KEY tick_time (tick_time)) SELECT  tick_time, bid  FROM pamm_idx_tick_stat WHERE symbol = '{$sSymbol}'  ORDER BY tick_time DESC");
        $this->db->query("CREATE TEMPORARY TABLE tt_pits2 (symbol CHAR(16) NOT NULL, PERIOD_START DATETIME NOT NULL, PERIOD_END DATETIME NOT NULL, bid_start DOUBLE NOT NULL, bid_end DOUBLE NOT NULL, PRIMARY KEY (symbol, PERIOD_START)) SELECT  tt_pits1.*, IFNULL((SELECT bid FROM tt_pits01 WHERE tick_time < tt_pits1.PERIOD_START ORDER BY tick_time DESC LIMIT 1), (SELECT bid FROM tt_pits02 WHERE tick_time > tt_pits1.PERIOD_START ORDER BY tick_time ASC LIMIT 1)) as bid_start, (SELECT bid FROM tt_pits03 WHERE tick_time < tt_pits1.PERIOD_END ORDER BY tick_time DESC LIMIT 1) as bid_end FROM tt_pits1 WHERE symbol = '{$sSymbol}' AND PERIOD_START > '2010-01-01'");

        $this->db
            ->select('tt_pits2.*')
            ->select('ROUND((bid_end-IFNULL(bid_start, bid_end))*100, 2) AS bid_diff', FALSE)
            ->from('tt_pits2')
            ->where('symbol', $sSymbol)
            ->where('PERIOD_START > ', '2010-01-01');
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() == 0)
        {
            return false;
        }

        return $rQuery->result_array();
    }

    function getPammIndexChangesForWeek($sWeekDate)
    {
        $this->db
            ->select('pish.symbol')
            ->select('pis.visible')
            ->select("(SELECT date_created FROM api.pamm_idx_shares_history WHERE symbol = pish.symbol AND DATE(date_created) < DATE('{$sWeekDate}') ORDER BY date_created DESC LIMIT 1) AS previous_change_date", FALSE)
            ->from('pamm_idx_shares_history AS pish')
            ->join('pamm_idx_symbols AS pis', 'pis.symbol = pish.symbol')
            ->where("DATE(pish.date_created) = DATE('{$sWeekDate}')", NULL, FALSE)
            ->group_by('pish.symbol');
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() == 0)
        {
            return false;
        }
        $aResult = $rQuery->result_array();
        foreach ($aResult as $iKey => $aItem) {
            $aOldResult = array();
            $aOld = $this->db
                ->select('pish.pamm_mt_login')
                ->from('pamm_idx_shares_history AS pish')
                ->where('pish.symbol', $aItem['symbol'])
                ->where("DATE(pish.date_created) = DATE('{$aItem['previous_change_date']}')", NULL, FALSE)
                ->get()
                ->result_array();
            foreach ($aOld as $aOldItem) {
                $aOldResult[] = $aOldItem['pamm_mt_login'];
            }

            $aNewResult = array();
            $aNew = $this->db
                ->select('pish.pamm_mt_login')
                ->from('pamm_idx_shares_history AS pish')
                ->where('pish.symbol', $aItem['symbol'])
                ->where("DATE(pish.date_created) = DATE('{$sWeekDate}')", NULL, FALSE)
                ->get()
                ->result_array();
            foreach ($aNew as $aNewItem) {
                $aNewResult[] = $aNewItem['pamm_mt_login'];
            }

            $aDeleted = array_diff($aOldResult, $aNewResult);
            $aAdded = array_diff($aNewResult, $aOldResult);

            $aResult[$iKey]['changes'] = array(
                'deleted'   => $aDeleted,
                'added'     => $aAdded,
            );
        }

        return $aResult;
    }

    /**
     * Выполняет инвестирование по индекс-сделке
     *
     * @param integer $iTicket
     * @return bool
     */
    function doPammIndexDeposit($iTicket)
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $this->db->query("SELECT GET_LOCK('pii_{$iTicket}', 120) as lockstatus");

        $this->db
            ->select('mtt.LOGIN AS inv_mt_login')
            ->select('pis.pamm_mt_login AS pamm_mt_login')
            ->select('po.id AS offer_id')
            ->select('ROUND(mtt.VOLUME * 10 * pis.share, 2) AS deposit', FALSE)
            ->select('IFNULL(mtu.ID, 0) AS user_id', FALSE)
            ->select('IFNULL(wlp.id, 0) AS partner_id', FALSE)
            ->select('IFNULL(pa.id, 0) AS account_id', FALSE)
            ->select('IFNULL(pia.enabled, 0) AS partner_enabled', FALSE)
            ->from('MT4_TRADES AS mtt')
            ->join('pamm_idx_investments AS pii', 'pii.ticket = mtt.TICKET AND pii.status = 1')
            ->join('pamm_idx_shares AS pis', 'pis.symbol = mtt.SYMBOL AND pis.pamm_mt_login = pii.pamm_mt_login')
            ->join('pamm_offers AS po', 'po.pamm_mt_login = pis.pamm_mt_login')
            ->join('processing.accounts AS a', 'a.foreign_account = mtt.LOGIN', 'left')
            ->join('processing.accounts AS pa', 'pa.user_id = a.user_id AND pa.account_type = CASE a.account_type WHEN 17 THEN 14 WHEN 24 THEN 18 ELSE 1 END', 'left', FALSE)
            ->join('MT4_USERS AS mtu', 'mtu.LOGIN = mtt.LOGIN', 'left')
            ->join('pammapi_partners AS wlp', 'wlp.mt_range_start < mtt.LOGIN AND wlp.mt_range_end > mtt.LOGIN', 'left')
            ->join('pamm_investors_agents AS pia', 'pia.partner_id = IFNULL(wlp.id, 0) AND pia.user_id = IFNULL(mtu.ID, 0)', 'left', FALSE)
            ->where('mtt.TICKET', $iTicket)
            ->where('mtt.CLOSE_TIME < "2000-01-01 00:00:00"', NULL, FALSE)
            ->group_by('pii.pamm_mt_login');
        $rQuery = $this->db->get();
        $aDeposits = array();
        if ($rQuery->num_rows() > 0)
        {
            $aDeposits = $rQuery->result_array();
        }
        $this->prn('deposit', $iTicket, $aDeposits);

        foreach ($aDeposits as $aDeposit)
        {
            // Пытаемся создать инвесторский счет
            $iInvestorId = $this->createInvestor($aDeposit['user_id'], $aDeposit['account_id'], $aDeposit['pamm_mt_login'], 0, $aDeposit['inv_mt_login'], $iTicket, 0, $aDeposit['partner_enabled'], $aDeposit['partner_id']);
            $this->prn($iInvestorId);

            $iPMOId = 0;
            if (!empty($iInvestorId))
            {
                // Создаем раcпоряжение так, чтобы с инвесторского мт-счета не сняло денег
                //$iPMOId = $this->createMoneyOrder($iInvestorId, $aDeposit['user_id'], $this->gaPammWithdrawalOperations['DEPOSIT'], $aDeposit['deposit'], true, true, $aDeposit['partner_id']);
                $aParams = array(
                    'sum'               => $aDeposit['deposit'],
                    'mt_login'          => $aDeposit['inv_mt_login'],
                    'investor_id'       => $iInvestorId,
                    'created_at'        => time(),
                    'operation'         => $this->gaPammWithdrawalOperations['DEPOSIT'],
                    //'money_withdrawn'   => 1,
                );
                $this->db->insert('pamm_money_orders', $aParams);
                $iPMOId = $this->db->insert_id();
                if (!empty($iPMOId))
                {
                    $bResult = $this->processMoneyOrder($iPMOId, true);
                    $this->prn($bResult);
                }
            }

            $this->prn($iPMOId, $this->Errors);

            $aFields = array(
                'open_pmo_id'   => $iPMOId,
                'status'        => 2,
            );
            $this->db->where('ticket', $iTicket);
            $this->db->where('pamm_mt_login', $aDeposit['pamm_mt_login']);
            $this->db->update('pamm_idx_investments', $aFields);
        }

        $this->db->query("SELECT RELEASE_LOCK('pii_{$iTicket}') as lockstatus");

        return true;
    }

    /**
     * Выполняет снятие инвестиций по индекс-сделке
     *
     * @param integer $iTicket
     * @return bool
     */
    function doPammIndexWithdrawal($iTicket)
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $this->db->query("SELECT GET_LOCK('pii_{$iTicket}', 120) as lockstatus");

        $this->db
            ->select('ai.inv_mt_login')
            ->select('ai.pamm_mt_login')
            ->select('ai.id AS investor_id')
            ->select('ai.user_id AS user_id')
            ->select('ai.partner_id AS partner_id')
            ->select('pii.open_pmo_id')
            ->from('pamm_investors AS ai')
            ->join('pamm_idx_investments AS pii', 'pii.ticket = ai.for_index AND pii.pamm_mt_login = ai.pamm_mt_login AND pii.status = 3') // Инвестиции зарегистрированы и признаны (2) + в очереди на закрытие (3)
            ->where('ai.for_index', $iTicket)
            ->where('ai.status', $this->gaPammInvestorAccountStatuses['ACTIVATED']);
        $rQuery = $this->db->get();
        $aWithdrawals = array();
        if ($rQuery->num_rows() > 0)
        {
            $aWithdrawals = $rQuery->result_array();
        }
        $this->prn('withdrawal', $iTicket, $aWithdrawals);

        foreach ($aWithdrawals as $aWithdrawal)
        {
            $iPMOId     = 0;
            $bResult    = false;

            if (!empty($aWithdrawal['open_pmo_id']))
            {
                // Создаем раcпоряжение так, чтобы с инвесторского мт-счета не зачислило деньги
                $aParams = array(
                    'sum'               => 0,
                    'mt_login'          => $aWithdrawal['inv_mt_login'],
                    'investor_id'       => $aWithdrawal['investor_id'],
                    'created_at'        => time(),
                    'operation'         => $this->gaPammWithdrawalOperations['ALL_AND_CLOSE'],
                    'money_withdrawn'   => 1,
                );
                $this->db->insert('pamm_money_orders', $aParams);
                $iPMOId = $this->db->insert_id();
                $this->prn($aWithdrawal['investor_id'], $aParams, $iPMOId);

                if (!empty($iPMOId))
                {
                    $bResult = $this->processMoneyOrder($iPMOId, true);
                    $this->prn($bResult);
                }
            }
            else
            {
                $bResult    = true;
            }

            if ($bResult)
            {
                $aFields = array(
                    'close_pmo_id'  => $iPMOId,
                    'status'        => 4,
                );
                $this->db->where('ticket', $iTicket);
                $this->db->where('pamm_mt_login', $aWithdrawal['pamm_mt_login']);
                $this->db->update('pamm_idx_investments', $aFields);
            }
        }

        $this->db->query("SELECT RELEASE_LOCK('pii_{$iTicket}') as lockstatus");

        return true;
    }

    /**
     * Изменяет ПАММ-счет в ПАММ-индексе. Позволяет сменить один ПАММ на другой без изменения его доли.
     *
     * @param string $sSymbol
     * @param integer $iOldPammMtLogin
     * @param integer $iNewPammMtLogin
     * @return array|bool
     */
    function changePammIndexPamm($sSymbol, $iOldPammMtLogin, $iNewPammMtLogin)
    {
        $this->db
            ->select('pis.symbol')
            ->select('pish.pamm_mt_login')
            ->select('pish.share')
            ->from('pamm_idx_symbols AS pis')
            ->join('pamm_idx_shares AS pish', 'pish.symbol = pis.symbol')
            ->where('pis.symbol', $sSymbol)
            ->order_by('pish.pamm_mt_login', 'ASC');
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() == 0)
        {
            return false;
        }
        $aResult = $rQuery->result_array();
        if (!empty($aResult)) {
            $bResult = false;
            foreach ($aResult as $aItem) {
                if ($aItem['pamm_mt_login'] == $iOldPammMtLogin) {
                    $bResult = true;
                }
                if ($aItem['pamm_mt_login'] == $iNewPammMtLogin) {
                    $this->Errors[__FUNCTION__] = 'NewPammFound';
                    return false;
                }
            }
            if (!$bResult) {
                $this->Errors[__FUNCTION__] = 'OldPammNotFound';
                return false;
            }

            // 1. Проставляем отметку на закрытие по тем инвестициям, которые были сделаны по заданному индексу по старому ПАММ-счету
            $sQuery = "UPDATE MT4_TRADES AS mtt, pamm_idx_investments AS pii SET pii.status = 3, close_pmo_id = 0 WHERE mtt.SYMBOL = '{$sSymbol}' AND mtt.CLOSE_TIME < '2000-01-01' AND pii.ticket = mtt.TICKET AND pii.pamm_mt_login = {$iOldPammMtLogin} AND pii.status IN (1, 2)";
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            // 2. Добавляем торговую статистику по новому ПАММ-счету
            $sQuery = "INSERT IGNORE INTO pamm_idx_tickets_profit_stat SELECT ticket, pamm_mt_login AS login, symbol, lots, close_time, profit-manager_profit*ceiling AS total_profit, total_sum-manager_sum AS balance FROM pamm_manager_capital_stat WHERE pamm_mt_login = {$iNewPammMtLogin} AND cmd = 1";
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            // 3. Создаем переменную для хранения старой котировки
            $sQuery = "SET @Quote_old = 0.00000";
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            // 4. Создаем переменную для хранения новой котировки
            $sQuery = "SET @Quote_new = 0.00000";
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            // 5. Запоминаем старую котировку
            $sQuery = "SELECT TRUNCATE(pisd.start_tick + SUM(IF(pitps.balance>0, pis.share*pitps.total_profit/pitps.balance, 0)), 5) INTO @Quote_old FROM `pamm_idx_shares` AS pis LEFT JOIN `pamm_idx_symbols` AS pisd ON pisd.symbol = pis.symbol LEFT JOIN `pamm_idx_tickets_profit_stat` AS pitps ON pitps.login = pis.pamm_mt_login WHERE pis.symbol = '{$sSymbol}' GROUP BY pis.symbol";
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            // 6. Меняем старый ПАММ-счет на новый
            $sQuery = "UPDATE pamm_idx_shares SET pamm_mt_login = {$iNewPammMtLogin} WHERE symbol = '{$sSymbol}' AND pamm_mt_login = {$iOldPammMtLogin}";
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            // 7. Запоминаем новую котировку
            $sQuery = "SELECT TRUNCATE(pisd.start_tick + SUM(IF(pitps.balance>0, pis.share*pitps.total_profit/pitps.balance, 0)), 5) INTO @Quote_new FROM `pamm_idx_shares` AS pis LEFT JOIN `pamm_idx_symbols` AS pisd ON pisd.symbol = pis.symbol LEFT JOIN `pamm_idx_tickets_profit_stat` AS pitps ON pitps.login = pis.pamm_mt_login WHERE pis.symbol = '{$sSymbol}' GROUP BY pis.symbol";
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            // 8. Обновляем стартовый тик, чтобы новые котировки продолжились со старого места
            $sQuery = "UPDATE pamm_idx_symbols SET start_tick = start_tick+@Quote_old-@Quote_new WHERE symbol = '{$sSymbol}'";
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            // 9. Создаем новые известиции по открытым сделкам этого ПАММ-индекса, взамен закрытых
            $sQuery = "INSERT INTO pamm_idx_investments (ticket, pamm_mt_login, status) SELECT mtt.TICKET AS ticket, {$iNewPammMtLogin} AS pamm_mt_login, 1 AS status FROM MT4_TRADES AS mtt WHERE mtt.SYMBOL = '{$sSymbol}' AND mtt.CLOSE_TIME < '2000-01-01' ON DUPLICATE KEY UPDATE status = 1, open_pmo_id = 0, close_pmo_id = 0";
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            // 10. Запоминаем в истории составов ПАММ-индексов
            $sQuery = "DELETE FROM pamm_idx_shares_history WHERE symbol = '{$sSymbol}' AND date_created = CURDATE()";
            $this->db->query($sQuery);
            $sQuery = "INSERT INTO pamm_idx_shares_history SELECT symbol, pamm_mt_login, share, CURDATE() AS date_created FROM pamm_idx_shares WHERE symbol = '{$sSymbol}'";
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            return true;
        } else {
            $this->Errors[__FUNCTION__] = 'SymbolNotFound';
            return false;
        }
    }

    /**
     * Изменяет структуру ПАММ-индекса. Позволяет сменить один ПАММ на другой без изменения его доли.
     *
     * @param string $sSymbol
     * @param array $aConfig
     * @return array|bool
     */
    function changePammIndexStructure($sSymbol, $aConfig)
    {
        $aResult = $this->getPammIndexDetails($sSymbol);
        if (!empty($aResult)) {
            // Добавляем торговую статистику по новому ПАММ-счету
            foreach ($aConfig as $iPammMtLogin => $fShare) {
                $sQuery = "INSERT IGNORE INTO pamm_idx_tickets_profit_stat SELECT ticket, pamm_mt_login AS login, symbol, lots, close_time, profit-manager_profit*ceiling AS total_profit, total_sum-manager_sum AS balance FROM pamm_manager_capital_stat WHERE pamm_mt_login = {$iPammMtLogin} AND cmd = 1";
                $this->db->query($sQuery);
                $iAffected = $this->db->affected_rows();
                $this->prn($sQuery, $iAffected);
            }

            // 1. Проставляем отметку на закрытие по тем инвестициям, которые были сделаны по заданному индексу по старому ПАММ-счету
            $sQuery = "UPDATE MT4_TRADES AS mtt, pamm_idx_investments AS pii, pamm_idx_shares AS pis SET pii.status = 3, close_pmo_id = 0 WHERE mtt.SYMBOL = '{$sSymbol}' AND mtt.CLOSE_TIME < '2000-01-01' AND pii.ticket = mtt.TICKET AND pis.symbol = mtt.SYMBOL AND pii.pamm_mt_login = pis.pamm_mt_login AND pii.status IN (1, 2)";
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            // 2. Создаем переменную для хранения старой котировки
            $sQuery = "SET @Quote_old = 0.00000";
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            // 3. Создаем переменную для хранения новой котировки
            $sQuery = "SET @Quote_new = 0.00000";
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            // 4. Запоминаем старую котировку
            $sQuery = "SELECT TRUNCATE(pisd.start_tick + SUM(IF(pitps.balance>0, pis.share*pitps.total_profit/pitps.balance, 0)), 5) INTO @Quote_old FROM `pamm_idx_shares` AS pis LEFT JOIN `pamm_idx_symbols` AS pisd ON pisd.symbol = pis.symbol LEFT JOIN `pamm_idx_tickets_profit_stat` AS pitps ON pitps.login = pis.pamm_mt_login WHERE pis.symbol = '{$sSymbol}' GROUP BY pis.symbol";
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            // 5. Меняем состав ПАММ-индекса
            $sQuery = "DELETE FROM pamm_idx_shares WHERE symbol = '{$sSymbol}'";
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            $sQuery = "INSERT INTO pamm_idx_shares VALUES ";
            $aQueryValues = [];
            foreach ($aConfig as $iPammMtLogin => $fShare) {
                $aQueryValues[] = "('{$sSymbol}', {$iPammMtLogin}, {$fShare})";
            }
            $sQuery .= implode(", ", $aQueryValues);
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            // 6. Запоминаем новую котировку
            $sQuery = "SELECT TRUNCATE(pisd.start_tick + SUM(IF(pitps.balance>0, pis.share*pitps.total_profit/pitps.balance, 0)), 5) INTO @Quote_new FROM `pamm_idx_shares` AS pis LEFT JOIN `pamm_idx_symbols` AS pisd ON pisd.symbol = pis.symbol LEFT JOIN `pamm_idx_tickets_profit_stat` AS pitps ON pitps.login = pis.pamm_mt_login WHERE pis.symbol = '{$sSymbol}' GROUP BY pis.symbol";
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            // 7. Обновляем стартовый тик, чтобы новые котировки продолжились со старого места
            $sQuery = "UPDATE pamm_idx_symbols SET start_tick = start_tick+@Quote_old-@Quote_new WHERE symbol = '{$sSymbol}'";
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            // 8. Создаем новые известиции по открытым сделкам этого ПАММ-индекса, взамен закрытых
            $sQuery = "INSERT INTO pamm_idx_investments (ticket, pamm_mt_login, status) SELECT mtt.TICKET AS ticket, pis.pamm_mt_login AS pamm_mt_login, 1 AS status FROM MT4_TRADES AS mtt INNER JOIN pamm_idx_shares AS pis ON pis.symbol = mtt.SYMBOL WHERE mtt.SYMBOL = '{$sSymbol}' AND mtt.CLOSE_TIME < '2000-01-01' ON DUPLICATE KEY UPDATE status = 1, open_pmo_id = 0, close_pmo_id = 0";
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            // 9. Запоминаем в истории составов ПАММ-индексов
            $sQuery = "DELETE FROM pamm_idx_shares_history WHERE symbol = '{$sSymbol}' AND date_created = CURDATE()";
            $this->db->query($sQuery);
            $sQuery = "INSERT INTO pamm_idx_shares_history SELECT symbol, pamm_mt_login, share, CURDATE() AS date_created FROM pamm_idx_shares WHERE symbol = '{$sSymbol}'";
            $this->db->query($sQuery);
            $iAffected = $this->db->affected_rows();
            $this->prn($sQuery, $iAffected);

            return true;
        } else {
            $this->Errors[__FUNCTION__] = 'SymbolNotFound';
            return false;
        }
    }

    /* Агентские выплаты */

    /**
     * Получение списка агентских выплат
     *
     * @param integer $iTimeFrom
     * @return array|bool
     */
    function getAgentPaymentsList($iTimeFrom=0)
    {
        $this->db
            ->select('id')
            ->select('user_id')
            ->select('investor_id')
            ->select('pmo_id')
            ->select('amount_full')
            ->select('amount_basis')
            ->select('bonus_comission')
            ->select('bonus_type')
            ->select('bonus_received')
            ->select('status')
            ->select('date_created')
            ->select('date_updated')
            ->from('pamm_agents_payments')
            ->where('partner_id', $this->iPartnerId)
            ->where("date_updated > FROM_UNIXTIME({$iTimeFrom})", NULL, FALSE)
            ->order_by('id', 'ASC');
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() == 0)
        {
            return false;
        }

        return $rQuery->result_array();
    }

    /**
     * Функция вкл/выкл выплаты агентских
     *
     * @param string $sUserIds
     * @param integer $iStatus
     * @param integer $iOverridePartnerId
     * @return array|bool
     */
    function changeAgentPaymentsStatus($sUserIds, $iStatus, $iOverridePartnerId=0)
    {
        if (empty($iOverridePartnerId)) {
            $iOverridePartnerId = $this->iPartnerId;
        }

        if (is_numeric($sUserIds))
        {
            $aUsersCleared = array($sUserIds);
        }
        else
        {
            $aUsers = explode(",", $sUserIds);
            $aUsersCleared = array();
            foreach ($aUsers as $sUser)
            {
                $iUser = trim($sUser);
                if (is_numeric($iUser))
                {
                    $aUsersCleared[] = $iUser;
                }
            }
        }

        $iAffectedRows = 0;
        if (!empty($aUsersCleared))
        {
            foreach ($aUsersCleared as $iUserId)
            {
                $this->db->query("INSERT INTO pamm_investors_agents (partner_id, user_id, enabled) VALUES ({$iOverridePartnerId}, {$iUserId}, {$iStatus}) ON DUPLICATE KEY UPDATE enabled = VALUES(enabled)");
                $iAffectedRows += $this->db->affected_rows();
            }
        }

        return $iAffectedRows;
    }

    /**
     * Добавление алиаса id пользователя
     *
     * @param integer $iUserId
     * @param integer $iAliasUserId
     * @return bool
     */
    function addUserAlias($iUserId, $iAliasUserId)
    {
        $this->db->query("INSERT IGNORE INTO pammapi_users_aliases (partner_id, main_user_id, alias_user_id) VALUES ({$this->iPartnerId}, {$iUserId}, {$iAliasUserId})");
        $iInsertedId = $this->db->insert_id();

        return (!empty($iInsertedId));
    }

    /**
     * Функция обновления агентских связей. Нужно запускать перед РО
     */
    function updateAgentsLinks()
    {
        // Отменяем распоряжения на вывод прибыли, если прибыли нет
        $sQuery = "INSERT IGNORE INTO api.pamm_investors_agents SELECT 1 AS partner_id, user_id, partner_user_id, status AS enabled FROM web.partners_users ON DUPLICATE KEY UPDATE partner_user_id = VALUES(partner_user_id), enabled = VALUES(enabled)";
        $this->db->query($sQuery);
        $iAffected = $this->db->affected_rows();
        $this->prn($sQuery, $iAffected);

        return true;
    }

    /**
     * Выполняет выплату агентского вознаграждения
     *
     * @param integer $iInvestorId
     * @param integer $iBonusType
     * @param float $fAmount
     * @param float $fCapital
     * @param integer $iPMOId
     * @return float
     */
    function doAgentPayout($iInvestorId, $iBonusType, $fAmount, $fCapital, $iPMOId=0)
    {
        $this->db
            ->select('ai.partner_id')
            ->select('ai.inv_mt_login')
            ->select('ai.pamm_mt_login')
            ->select('ai.user_id')
            ->select('ai.for_index')
            ->select("IF({$iBonusType} = 1, po.agent_bonus, po.agent_bonus_profit) AS bonus_commission", FALSE)
            ->select("IFNULL(a.account_type, 0) AS account_type", FALSE)
            ->select("IFNULL(pap.agent_mt_login, 200) AS agent_mt_login", FALSE)
            ->from('pamm_investors AS ai')
            ->join('pamm_offers AS po', 'po.pamm_mt_login = ai.pamm_mt_login')
            ->join('pamm_investors_agents AS pia', 'pia.user_id = ai.user_id AND pia.enabled = 1')
            ->join('processing.accounts AS a', 'a.id = ai.account_id', 'left')
            ->join('pammapi_partners AS pap', 'pap.id = ai.partner_id', 'left')
            ->where('ai.id', (int)$iInvestorId);
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() == 0)
        {
            return false;
        }
        $aPayoutInfo = $rQuery->row_array();

        // Подсчитываем базис
        if ($iBonusType == PAMMAGENTS_COMISSIONTYPE_WELCOME)
        {
            $rQuery = $this->db->select('*')->from('pamm_money_orders')->where('id', $iPMOId)->get();
            if ($rQuery->num_rows() == 0)
            {
                return false;
            }
            $aPMO = $rQuery->row_array();

            $this->db->query("CREATE TEMPORARY TABLE basis_{$iInvestorId} (pamm_mt_login INT(10) NOT NULL, basis DECIMAL(14, 2) NOT NULL, PRIMARY KEY (pamm_mt_login))");
            $this->db->query("INSERT INTO basis_{$iInvestorId} SELECT ai.pamm_mt_login, IF(pmo.operation=0, 1, -1)*pmo.sum AS basis FROM pamm_investors AS ai INNER JOIN pamm_money_orders AS pmo ON pmo.investor_id = ai.id AND pmo.status = {$this->gaPammPaymentOrdersStatuses['SUCCESS']} WHERE ai.user_id = {$aPayoutInfo['user_id']} AND ai.pamm_mt_login = {$aPayoutInfo['pamm_mt_login']} AND ai.partner_id = {$aPayoutInfo['partner_id']} AND ai.for_index = 0 AND pmo.confirmed_at <= {$aPMO['confirmed_at']} ORDER BY pmo.confirmed_at ASC ON DUPLICATE KEY UPDATE basis = IF(basis > 0, 0, basis) + VALUES(basis)");

            $rQuery = $this->db->select('*')->from("basis_{$iInvestorId}")->get();
            if ($rQuery->num_rows() == 0)
            {
                $fAmountBasis = $fAmount;
            }
            else
            {
                $aBasisData = $rQuery->row_array();

                $this->db->query("DROP TEMPORARY TABLE basis_{$iInvestorId}");

                $fAmountBasis = $aBasisData['basis'];
            }
        }
        else
        {
            $fAmountBasis = $fAmount;
        }

        $iStatus = 2;
        if (bccomp($fCapital, PAMM_MIN_CAPITAL, 2) == -1)
        {
            $iStatus = 3;
        }

        if (!in_array($aPayoutInfo['account_type'], [1, 14, 18]))
        {
            $iStatus = 4;
        }

        $fBonusReceived = bcdiv(bcmul($fAmountBasis, $aPayoutInfo['bonus_commission'], 2), 100, 2);
        if (bccomp($fBonusReceived, "0.01", 2) == -1)
        {
            $fBonusReceived = 0;
            $iStatus = 5;
        }

        $this->db->set('partner_id', $aPayoutInfo['partner_id']);
        $this->db->set('pamm_mt_login', $aPayoutInfo['pamm_mt_login']);
        $this->db->set('user_id', $aPayoutInfo['user_id']);
        $this->db->set('investor_id', $iInvestorId);
        $this->db->set('pmo_id', $iPMOId);
        $this->db->set('amount_full', $fAmount);
        $this->db->set('amount_basis', $fAmountBasis);
        $this->db->set('bonus_comission', $aPayoutInfo['bonus_commission']);
        $this->db->set('bonus_type', $iBonusType);
        $this->db->set('bonus_received', $fBonusReceived);
        $this->db->set('status', $iStatus);
        $this->db->set('date_created', "NOW()", FALSE);
        $this->db->set('date_updated', "NOW()", FALSE);
        $this->db->insert('pamm_agents_payments');
        $iPayoutId = $this->db->insert_id();

        // Сделать выплату, если необходимо (т.е. если это приветственное, или индексная инвестиция)
        if ($iStatus == 2 && ($iBonusType == PAMMAGENTS_COMISSIONTYPE_WELCOME || !empty($aPayoutInfo['for_index'])))
        {
            $sComment = "PA{$aPayoutInfo['inv_mt_login']}/{$aPayoutInfo['agent_mt_login']}/{$aPayoutInfo['pamm_mt_login']}/{$iPayoutId}";
            $bResult = $this->webactions->makeTransfer($aPayoutInfo['pamm_mt_login'], $aPayoutInfo['agent_mt_login'], $fBonusReceived, $sComment, $sComment);
            $iStatus = (int)$bResult;

            // и отметиться что всё ок
            $aFields = array(
                'status'    => $iStatus,
            );
            $this->db->update('pamm_agents_payments', $aFields, array('id' => $iPayoutId));
        }

        return $fBonusReceived;
    }

    /* Кроны */

    /**
     * Обновляет уровени просадки ПАММ-счетов
     *
     * @param boolean $bUpdate
     * @return boolean
     */
    function updateDrawdowns($bUpdate = true)
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_implicit_flush();

        // Собираем список кому надо получить detailed statement
        if ($bUpdate)
        {
            $this->db
                ->select('pamm_mt_login')
                ->from('pamm_offers')
                ->where('status != ', $this->gaPammOffersStatuses['CLOSED'])
                ->order_by('pamm_mt_login', 'ASC');
            $rQuery = $this->db->get();
            if ($rQuery->num_rows() > 0)
            {
                $aMtLogins = $rQuery->result_array();
                foreach ($aMtLogins as $aMtLogin)
                {
                    $this->prn($aMtLogin);
                    $aResult = $this->webactions->getDetailedStatement($aMtLogin['pamm_mt_login'], 0, time());
                    if (!empty($aResult))
                    {
                        $aFields = $aResult;
                        $aFields['pamm_mt_login']   = $aMtLogin['pamm_mt_login'];
                        $aFields['statement_date']  = "CURRENT_TIMESTAMP";
                        $this->db->insert('pamm_detailed_statements', $aFields, FALSE);
                    }
                }
            }
        }

        // Обновляем просадки в таблице рейтингов
        $this->db->query("CREATE TEMPORARY TABLE tt_drawdowns SELECT po.pamm_mt_login, MAX(pds.rel_drawdown_percent) AS rdrawdown, MAX(pds.max_drawdown_ft) AS mdrawdown FROM pamm_offers AS po INNER JOIN pamm_detailed_statements AS pds ON pds.pamm_mt_login = po.pamm_mt_login AND pds.rel_drawdown_percent <= 200 AND pds.max_drawdown_ft <= 100 WHERE po.status = 1 GROUP BY pds.pamm_mt_login");
        $this->db->query("UPDATE pamm_rating AS pr, tt_drawdowns AS ttd SET pr.rdrawdown = IF(pr.rdrawdown < ttd.rdrawdown OR pr.rdrawdown > 100, ttd.rdrawdown, pr.rdrawdown), pr.mdrawdown = IF(pr.mdrawdown < ttd.mdrawdown OR pr.mdrawdown > 100, ttd.mdrawdown, pr.mdrawdown) WHERE pr.mt_login = ttd.pamm_mt_login");

        return true;
    }

    /**
     * Обновляет рейтинг ПАММ-счетов
     *
     * @param boolean $bForce
     * @return boolean
     */
    function updatePammRating($bForce = false)
    {
        /*$rQuery = $this->db->select('value')->from('global_settings')->where('variable', 'is_rollover')->get();
        $bIsRollover = ($rQuery->num_rows() > 0)?$rQuery->row()->value:0;

        $rQuery = $this->db->select('value')->from('global_settings')->where('variable', 'update_pamm_rating')->get();
        $bUpdatePAMMRating = ($rQuery->num_rows() > 0)?$rQuery->row()->value:0;*/
        $bUpdatePAMMRating = 1;

        $rQuery = $this->db->select('pid')->from('pamm_idx_processes')->where('process_name', 'update_pamm_rating')->get();
        $iPid = ($rQuery->num_rows() > 0)?$rQuery->row()->pid:0;

        if ($bForce == true)
        {
            $bIsRollover = 0;
            $bUpdatePAMMRating = 1;
        }

        if (!empty($bIsRollover) || empty($bUpdatePAMMRating) || !empty($iPid))
        {
            $iPid = intval($iPid);
            if (posix_kill($iPid, 0))
            {
                var_dump("updatePammRating is already running");
                return false;
            }
        }
        if (!empty($bIsRollover) || empty($bUpdatePAMMRating))
        {
            var_dump("updatePammRating disabled");
            return false;
        }

        ignore_user_abort(true);
		set_time_limit(0);
        ob_implicit_flush();

        // Проставляемся в процессах
        $iPid = posix_getpid();
        $this->db->query("INSERT INTO pamm_idx_processes (process_name, pid) VALUES ('update_pamm_rating', {$iPid}) ON DUPLICATE KEY UPDATE pid = {$iPid}");

        // Выбираем кого надо обновить в обновлении №1
        $this->db->query("CREATE TEMPORARY TABLE tt_logins (mt_login INT(10) UNSIGNED NOT NULL, offer_id INT(10) UNSIGNED NOT NULL, user_id INT(10) UNSIGNED NOT NULL, investor_id INT(10) UNSIGNED NOT NULL, activated_at INT(10) UNSIGNED NOT NULL, partner_id INT(10) UNSIGNED NOT NULL, rdrawdown DOUBLE NOT NULL, mdrawdown DOUBLE NOT NULL, opened_tickets INT(10) UNSIGNED NOT NULL, current_capital DOUBLE NOT NULL, total_profit_percent DOUBLE NOT NULL, profit DOUBLE NOT NULL, profitness DOUBLE NOT NULL, PRIMARY KEY (mt_login)) SELECT po.pamm_mt_login as mt_login, po.id as offer_id, mai.user_id as user_id, mai.id as investor_id, mai.activated_at as activated_at, mai.partner_id as partner_id, IFNULL(pr.rdrawdown, 0) as rdrawdown, IFNULL(pr.mdrawdown, 0) as mdrawdown, IFNULL(pr.opened_tickets, 0) as opened_tickets, IFNULL(FLOOR(pmcc.capital*100)/100, 0) as current_capital, ROUND(IFNULL(SUM(IF(ptps.CLOSE_TIME > DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 MONTH), ptps.TOTAL_PROFIT/ptps.BALANCE, 0))*100, 0), 2) as total_profit_percent, ROUND(SUM(IFNULL(ptps.TOTAL_PROFIT, 0)), 2) as profit, ROUND(SUM(IFNULL(ptps.TOTAL_PROFIT/ptps.BALANCE, 0)) * 100, 2) as profitness FROM pamm_offers AS po INNER JOIN pamm_investors AS mai ON mai.pamm_mt_login = po.pamm_mt_login AND mai.type = 2 LEFT JOIN pamm_rating AS pr ON pr.mt_login = po.pamm_mt_login LEFT JOIN pamm_manager_capital_current AS pmcc ON pmcc.pamm_mt_login = po.pamm_mt_login LEFT JOIN pamm_tickets_profit_stat AS ptps ON ptps.LOGIN = po.pamm_mt_login WHERE po.status > 0 AND (pr.mt_login IS NULL OR pmcc.pamm_mt_login IS NOT NULL OR pr.offer_status != po.status) GROUP BY po.pamm_mt_login ORDER BY NULL ASC");
        $this->prn($this->db->last_query());

		// Запоминаем инвестиции и застрахованные суммы
        $this->db->query("CREATE TEMPORARY TABLE tt_invinsured (mt_login INT(10) UNSIGNED NOT NULL, investments DOUBLE NOT NULL, insured_sum DOUBLE NOT NULL, PRIMARY KEY (mt_login)) SELECT ai.pamm_mt_login as mt_login, ROUND(SUM(ai.current_sum), 2) as investments, ROUND(SUM(ai.insured_sum), 2) as insured_sum FROM pamm_investors AS ai WHERE ai.pamm_mt_login > 0 AND ai.status = {$this->gaPammInvestorAccountStatuses['ACTIVATED']} AND ai.type = {$this->gaPammInvestorTypes['INVESTOR']} GROUP BY ai.pamm_mt_login");
        $this->prn($this->db->last_query());

		// Вносим обновления №1
        $this->db->query("LOCK TABLES pamm_rating WRITE, pamm_rating AS pr WRITE, pamm_accounts AS pa READ, pamm_offers AS po READ, MT4_USERS AS mtu READ");
        $this->prn($this->db->last_query());
        $this->db->query("REPLACE INTO pamm_rating SELECT '' as nickname, '' as surname, '' as name, '' as lat_surname, '' as lat_name, ttmtl.user_id as user_id, ttmtl.mt_login as mt_login, 0 as account_id, ttmtl.investor_id as investor_id, ttmtl.current_capital as current_capital, ROUND(pa.capital, 2) as initial_capital, IFNULL(inv.investments, 0) as investments, IFNULL(inv.insured_sum, 0) as insured_sum, 1 as offer_exists, po.status as offer_status, po.trade_period*604800 as offer_period, po.trade_period as offer_trade_period, po.next_rollover as next_rollover, po.next_rollover_date as next_rollover_date, po.bonus/100 as manager_bonus, 1-po.bonus/100 as investor_bonus, ROUND(IFNULL(ttmtl.profit, 0), 2) as profit, ttmtl.profitness as profitness, ttmtl.total_profit_percent as profitness_last_month, ttmtl.activated_at as activated_at, ttmtl.rdrawdown as rdrawdown, ttmtl.mdrawdown as mdrawdown, ttmtl.opened_tickets as opened_tickets, ttmtl.partner_id as partner_id, po.min_balance as min_balance, NULL as permitted_investments, po.responsibility as responsibility, NULL FROM tt_logins AS ttmtl INNER JOIN pamm_accounts AS pa ON pa.pamm_mt_login = ttmtl.mt_login LEFT JOIN pamm_offers AS po ON po.pamm_mt_login = ttmtl.mt_login LEFT JOIN tt_invinsured AS inv ON inv.mt_login = ttmtl.mt_login WHERE ttmtl.mt_login > 0 GROUP BY ttmtl.mt_login ORDER BY NULL ASC");
        $this->prn($this->db->last_query());

        // Допил напильником
        $this->db->query("UPDATE pamm_rating AS pr, tt_invinsured AS inv SET pr.investments = inv.investments, pr.insured_sum = inv.insured_sum WHERE pr.mt_login = inv.mt_login");
        $this->prn($this->db->last_query());
        $this->db->query("UPDATE pamm_rating SET investments = insured_sum WHERE investments < insured_sum AND responsibility > 0");
        $this->prn($this->db->last_query());
        $this->db->query("UPDATE pamm_rating AS pr, MT4_USERS AS mtu SET pr.investments = mtu.BALANCE WHERE pr.mt_login = mtu.LOGIN AND pr.current_capital < 0 AND pr.investments > 0");
        $this->prn($this->db->last_query());
        $this->db->query("UPDATE pamm_rating AS pr, MT4_USERS AS mtu SET pr.investments = IF((mtu.BALANCE-pr.current_capital) < 0, 0, (mtu.BALANCE-pr.current_capital)) WHERE pr.mt_login = 7031 AND pr.mt_login = mtu.LOGIN AND mtu.BALANCE < (pr.current_capital+pr.investments)");
        $this->prn($this->db->last_query());
        $this->db->query("UNLOCK TABLES");
        $this->prn($this->db->last_query());

        // Подготавливаем обновление №2: кол-во открытых сделок и potential_investments
        $this->db
            ->select('pamm_mt_login')
            ->select('responsibility')
            ->from('pamm_offers')
            ->where('status >', 0)
            ->order_by('pamm_mt_login', 'ASC');
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() > 0)
        {
            $aResult = $rQuery->result_array();

            foreach ($aResult as $aRecord)
            {
                $this->prn($aRecord);

                $aFields = array();
                $aOpenedTradesCount = $this->webactions->getOpenedTradesCount($aRecord['pamm_mt_login']);
                $this->prn($aOpenedTradesCount);
                if (!empty($aOpenedTradesCount))
                {
                    $aFields['opened_tickets'] = $aOpenedTradesCount['opened'];
                }
                /*if (!empty($aRecord['responsibility']))
                {
                    $fPermittedInvestments = $this->getAllowedInvestmentsToPamm2_0($aRecord['pamm_mt_login']);
                    $aFields['permitted_investments'] = $fPermittedInvestments;
                }*/

                $this->db->where('mt_login', $aRecord['pamm_mt_login']);
                $this->db->update('pamm_rating', $aFields);
            }
        }

        // Уходим из процессов
        $this->db->where('process_name', 'update_pamm_rating');
        $this->db->delete('pamm_idx_processes');

        return true;
    }

    /**
     * Обновляет данные по последочной истории и истории доходностей ПАММ-счетов
     *
     * @param integer $iPammMTLogin
     * @return boolean
     */
    function updatePammMT4TradesData($iPammMTLogin=0)
    {
        $rQuery = $this->db->select('value')->from('global_settings')->where('variable', 'is_rollover')->get();
        $bIsRollover = ($rQuery->num_rows() > 0)?$rQuery->row()->value:0;

        $rQuery = $this->db->select('value')->from('global_settings')->where('variable', 'update_pamm_mt4_trades_data')->get();
        $bUpdatePMCSPTPS = ($rQuery->num_rows() > 0)?$rQuery->row()->value:0;

        $rQuery = $this->db->select('pid')->from('pamm_idx_processes')->where('process_name', 'update_pamm_mt4_trades_data')->get();
        $iPid = ($rQuery->num_rows() > 0)?$rQuery->row()->pid:0;

        if (!empty($bIsRollover) || empty($bUpdatePMCSPTPS) || !empty($iPid))
        {
            $iPid = intval($iPid);
            if (posix_kill($iPid, 0))
            {
                var_dump("UpdatePMCSAndPTPSData is already running");
                return false;
            }
        }

        ignore_user_abort(true);
        set_time_limit(0);
        ob_implicit_flush();

        // Проставляемся в процессах
        $iPid = posix_getpid();
        $this->db->query("INSERT INTO pamm_idx_processes (process_name, pid) VALUES ('update_pmcs_ptps', {$iPid}) ON DUPLICATE KEY UPDATE pid = {$iPid}");

        // Поиск пропавших сделок - выполнение resync, всё остальное за нас сделает runPammMTTradesCron
        if (!empty($iPammMTLogin))
        {
            $this->db->where('po.pamm_mt_login', $iPammMTLogin);
        }
        $this->db
            ->select('po.pamm_mt_login')
            ->from('pamm_offers AS po')
            ->where('po.status IN (1, 3)', NULL, FALSE);
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() > 0)
        {
            $aMtLogins = $rQuery->result_array();
            foreach ($aMtLogins as $aMtLogin)
            {
                $iTime = microtime(true);

                $aResult = $this->webactions->getClosedTrades($aMtLogin['pamm_mt_login'], time()-8000, time());
                if (!empty($aResult))
                {
                    foreach ($aResult as $aRow)
                    {
                        $sQuery = "INSERT IGNORE INTO MT4_TRADES (TICKET, LOGIN, SYMBOL, DIGITS, CMD, VOLUME, OPEN_TIME, OPEN_PRICE, SL, TP, CLOSE_TIME, EXPIRATION, REASON, CONV_RATE1, CONV_RATE2, COMMISSION, COMMISSION_AGENT, SWAPS, CLOSE_PRICE, PROFIT, TAXES, COMMENT, INTERNAL_ID, MARGIN_RATE, TIMESTAMP, MODIFY_TIME) VALUES ({$aRow['order']}, {$aRow['login']}, '{$aRow['symbol']}', {$aRow['digits']}, {$aRow['cmd']}, {$aRow['volume']}, FROM_UNIXTIME({$aRow['open_time']}), {$aRow['open_price']}, {$aRow['sl']}, {$aRow['tp']}, FROM_UNIXTIME({$aRow['close_time']}), FROM_UNIXTIME({$aRow['expiration']}), 0, {$aRow['conv_rate1']}, {$aRow['conv_rate2']}, {$aRow['commission']}, {$aRow['commission_agent']}, {$aRow['storage']}, {$aRow['close_price']}, {$aRow['profit']}, {$aRow['taxes']}, '{$aRow['taxes']}', 0, {$aRow['margin_rate']}, {$aRow['close_time']}, CONVERT_TZ(NOW(), '+00:00', @@global.time_zone))";
                        $this->db->query($sQuery);
                    }
                }

                //$iResult = $this->webactions->resyncClosedTrades($aMtLogin['pamm_mt_login'], time()-2400, time());
                $iTime -= microtime(true);
                $iTime = ceil($iTime);
                $iTime++;
                sleep((int)$iTime);
                $this->prn($aMtLogin);
            }
        }

        // Прогонка throw_idx_tick_func
        $this->db
            ->select('*')
            ->from('pamm_idx_symbols');
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() > 0)
        {
            $aSymbols = $rQuery->result_array();
            foreach ($aSymbols as $aSymbol)
            {
                var_dump($aSymbol);
                $this->db->query("SELECT throw_idx_tick_func('{$aSymbol['symbol']}')");
            }
        }

        // Уходим из процессов
        $this->db->where('process_name', 'update_pamm_mt4_trades_data');
        $this->db->delete('pamm_idx_processes');

        return true;
    }

    /**
     * Обновляет кеш инвесторов
     *
     * @param bool $bClearCache
     * @return bool
     */
    function updateInvestorsStatCache($bClearCache = false)
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_implicit_flush();

        //$this->email->to('tretyak@privatefx.com, developers@privatefx.com')->from('noreply@privatefx.com', 'api.PrivateFX Logger')->subject('UpdateInvestorsStatCache')->message('UpdateInvestorsStatCache started')->send();

        if (!empty($bClearCache))
        {
            $this->db->truncate('pamm_investors_stat');
        }

        // Получаем свежайшую инфу об инвесторах, тем самым обновляя кеш
        $this->prn("UPDATING INVESTORS STAT CACHE");
        $this->db
            ->select('pamm_mt_login')
            ->from('pamm_offers')
            ->where('status != ', $this->gaPammOffersStatuses['CLOSED'])
            ->where('(DATE_FORMAT(next_rollover_date, "%Y-%m-%d 00:00:00") < DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 3 DAY) OR status = 3)', NULL, FALSE)
            ->order_by('pamm_mt_login', 'ASC');
        $rQuery = $this->db->get();
        $aPAMMAccounts = array();
        if ($rQuery->num_rows() > 0)
        {
            $aPAMMAccounts = $rQuery->result_array();
            $rQuery->free_result();
        }

        foreach ($aPAMMAccounts as $aPAMMAccount)
        {
            $this->prn("UPDATING INVESTORS STAT CACHE FOR {$aPAMMAccount['pamm_mt_login']}");
            //$this->email->to('tretyak@privatefx.com')->from('noreply@privatefx.com', 'api.PrivateFX Logger')->subject('UpdateInvestorsStatCache')->message("Updating investors stat cache for {$aPAMMAccount['pamm_mt_login']}")->send();

            $this->db
                ->select('id AS investor_id')
                ->select('partner_id')
                ->select('user_id')
                ->from('pamm_investors')
                ->where('pamm_mt_login', $aPAMMAccount['pamm_mt_login'])
                ->where('status', $this->gaPammInvestorAccountStatuses['ACTIVATED'])
                ->order_by('id', 'ASC');
            $rQuery = $this->db->get();
            $aInvestors = array();
            if ($rQuery->num_rows() > 0)
            {
                $aInvestors = $rQuery->result_array();
                $rQuery->free_result();
            }
            foreach ($aInvestors as $aInvestor)
            {
                $this->prn("updating inv{$aInvestor['investor_id']}");
                $this->getInvestorDetails($aInvestor['user_id'], $aInvestor['investor_id'], $aInvestor['partner_id']);
            }
        }

        $this->prn('UPDATING INVESTORS STAT CACHE COMPLETE!');
        //$this->email->to('tretyak@privatefx.com, developers@privatefx.com')->from('noreply@privatefx.com', 'api.PrivateFX Logger')->subject('UpdateInvestorsStatCache')->message("Updating investors stat cache complete!")->send();

        return true;
    }

    /**
     * Обновляет данные котировкам ПАММ-индексов
     *
     * @param none
     * @return boolean
     */
    function updatePammIndexTickStat()
    {
        $this->db->query("INSERT INTO pamm_idx_tick_stat SELECT pis.symbol AS symbol, DATE_ADD(mtp.TIME, INTERVAL 2 HOUR) AS tick_time, mtp.BID AS bid FROM pamm_idx_symbols AS pis FORCE INDEX (first_tick) INNER JOIN MT4_PRICES AS mtp ON mtp.SYMBOL = pis.symbol AND mtp.TIME > pis.start_time WHERE pis.first_tick > 0");

        return true;
    }

    function updatePammLossLimitBalances()
    {
        $this->db->query("INSERT INTO pamm_loss_limit_balances SELECT po.pamm_mt_login, ROUND(SUM(mtt.PROFIT+mtt.COMMISSION+mtt.SWAPS+mtt.TAXES), 2) AS balance FROM pamm_offers AS po INNER JOIN MT4_TRADES AS mtt ON mtt.LOGIN = po.pamm_mt_login AND mtt.CLOSE_TIME > \"2000-01-01\" AND mtt.CLOSE_TIME < DATE_FORMAT(DATE_SUB(NOW(), INTERVAL DAYOFWEEK(NOW())-1 DAY), \"%Y-%m-%d 22:00:00\") WHERE po.loss_limit > 0 AND po.status = 1 GROUP BY po.pamm_mt_login ON DUPLICATE KEY UPDATE balance = VALUES(balance)");

        return true;
    }

    /**
     * Крон по выплате суммарных платежей по Индекс-деятельности по выходным
     *
     * @param   integer $iUnixtime
     * @return  bool
     */
    function runPammIndexWeekendPayments($iUnixtime = 0)
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_implicit_flush();

        if (empty($iUnixtime))
        {
            $iUnixtime = time();
        }

        // Получаем текущую метку времени, и запускаемся только есть больше 2250 (вс 22:50) и меньше 10000 (пн 00:00)
        $iCurrentTimeMark = intval(date("wHi", $iUnixtime));
        if ($iCurrentTimeMark > 10000 || $iCurrentTimeMark < 2250)
        {
            return false;
        }
        $sTimeFrom  = date("Y-m-d", $iUnixtime-86400);
        $sTimeTo    = date("Y-m-d", $iUnixtime);
        $sComment   = "Pi/{$sTimeFrom}/{$sTimeTo}";
        $this->prn($iCurrentTimeMark, $sTimeFrom, $sTimeTo, $sComment);

        $this->db
            ->select('ai.pamm_mt_login')
            ->select('ROUND(SUM(IF(pmo.operation = 0, 1, -1)*pmo.sum), 2) AS total_sum', FALSE)
            ->from('pamm_investors AS ai')
            ->join('pamm_money_orders AS pmo', "pmo.investor_id = ai.id AND pmo.status = 1 AND pmo.confirmed_at > UNIX_TIMESTAMP('{$sTimeFrom} 10:00') AND pmo.confirmed_at < UNIX_TIMESTAMP('{$sTimeTo} 22:50') AND pmo.money_withdrawn = 0")
            ->where("ai.for_index > 0 AND (ai.activated_at > UNIX_TIMESTAMP('{$sTimeFrom} 10:00') OR ai.closed_at > UNIX_TIMESTAMP('{$sTimeFrom} 10:00'))", NULL, FALSE)
            ->group_by('ai.pamm_mt_login')
            ->order_by('ai.pamm_mt_login', 'ASC');
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() > 0)
        {
            $aResult = $rQuery->result_array();
            foreach ($aResult as $aRow)
            {
                $bResult = $this->webactions->makePayment($aRow['pamm_mt_login'], $aRow['total_sum'], $sComment, true);
                $this->prn($aRow, $bResult);
                if ($bResult)
                {
                    $this->db->query("UPDATE pamm_investors AS ai, pamm_money_orders AS pmo SET pmo.money_withdrawn = 1 WHERE ai.pamm_mt_login = {$aRow['pamm_mt_login']} AND ai.for_index > 0 AND (ai.activated_at > UNIX_TIMESTAMP('{$sTimeFrom} 10:00') OR ai.closed_at > UNIX_TIMESTAMP('{$sTimeFrom} 10:00')) AND pmo.investor_id = ai.id AND pmo.status = 1 AND pmo.confirmed_at > UNIX_TIMESTAMP('{$sTimeFrom} 10:00') AND pmo.confirmed_at < UNIX_TIMESTAMP('{$sTimeTo} 22:50') AND pmo.money_withdrawn = 0");
                }
            }
        }

        return true;
    }

    /**
     * Крон по мониторингу ПАММ-скама
     * https://teamwox.fx-trend.com/tasks/view/1813
     *
     * @return bool
     */
    function runPammScamMonitoring()
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_implicit_flush();

        $aOutput = array();
        $rQuery = $this->db->query("SELECT GROUP_CONCAT(DISTINCT pamm_mt_login ORDER BY pamm_mt_login ASC SEPARATOR \", \") AS list FROM pamm_idx_shares");
        $sList = ($rQuery->num_rows() > 0)?$rQuery->row()->list:"0";
        $rQuery = $this->db->query("SELECT po.pamm_mt_login, po.id AS offer_id, po.bonus/100 AS offer_bonus, po.investor_id, ai.current_sum AS manager_sum, pr.opened_tickets FROM pamm_offers AS po INNER JOIN pamm_investors AS ai ON ai.pamm_mt_login = po.pamm_mt_login AND ai.type = {$this->gaPammInvestorTypes['MANAGER']} INNER JOIN pamm_rating AS pr ON pr.mt_login = po.pamm_mt_login AND pr.opened_tickets > 0 WHERE po.status = 1 AND po.pamm_mt_login NOT IN ({$sList})");
        $this->prn($this->db->last_query());
        if ($rQuery->num_rows() > 0)
        {
            $aResult = $rQuery->result_array();
            foreach ($aResult as $aRow)
            {
                $rQuery = $this->db->select('SUM(mtt.COMMISSION+mtt.SWAPS+mtt.PROFIT+mtt.TAXES) AS total_profit', FALSE)->from('MT4_TRADES AS mtt')->where('mtt.LOGIN', $aRow['pamm_mt_login'])->where('mtt.CMD < 2 AND mtt.CLOSE_TIME < "2000-01-01"')->get();
                $aRow['total_profit'] = $rQuery->row()->total_profit;
                if ($aRow['total_profit'] < 0)
                {
                    $this->prn($aRow);

                    $this->db
                        ->select('id AS investor_id')
                        ->select('partner_id')
                        ->select('user_id')
                        ->from('pamm_investors')
                        ->where('pamm_mt_login', $aRow['pamm_mt_login'])
                        ->where('status', $this->gaPammInvestorAccountStatuses['ACTIVATED'])
                        ->where('for_index', 0)
                        ->order_by('id', 'ASC');
                    $rQuery = $this->db->get();
                    $aInvestors = array();
                    if ($rQuery->num_rows() > 0)
                    {
                        $aInvestors = $rQuery->result_array();
                        $rQuery->free_result();
                    }
                    foreach ($aInvestors as $aInvestor)
                    {
                        //$this->prn("updating inv{$aInvestor['investor_id']}");
                        $this->getInvestorDetails($aInvestor['user_id'], $aInvestor['investor_id'], $aInvestor['partner_id']);
                    }

                    $rQuery = $this->db->query("SELECT ROUND(SUM(current_sum), 2) AS total_sum FROM pamm_investors WHERE pamm_mt_login = {$aRow['pamm_mt_login']}");
                    $fTotalSum = ($rQuery->num_rows() > 0)?$rQuery->row()->total_sum:0;

                    $fManagerShare  = ($fTotalSum>0)?$aRow['manager_sum']/$fTotalSum:0;
                    $fManagerProfit = $aRow['total_profit']*($aRow['offer_bonus'] + (1-$aRow['offer_bonus'])*$fManagerShare);

                    $rQuery = $this->db->query("SELECT new_capital AS capital FROM pamm_manager_capital_stat WHERE pamm_mt_login = {$aRow['pamm_mt_login']} ORDER BY close_time DESC, id DESC LIMIT 1");
                    $fCapital   = ($rQuery->num_rows() > 0)?$rQuery->row()->capital:0;
                    $fCapitalNew= bcadd($fCapital, $fManagerProfit, 2);
                    if ($fManagerProfit == 0 || ($fManagerProfit >= 0 && $fCapital >= 0) || ($fManagerProfit < 0 && $fCapital > 0 && $fManagerProfit+$fCapital >= 0))
                    {
                        $fCeiling = 1;
                    }
                    else
                    {
                        if ($fCapital < 0 && $fManagerProfit+$fCapital <= 0)
                        {
                            $fCeiling = 0;
                        }
                        else
                        {
                            $fCeiling = ($fCapital < 0 && $fManagerProfit+$fCapital > 0)?1-abs($fCapital/$fManagerProfit):abs($fCapital/$fManagerProfit);
                        }
                    }
                    $this->prn("fTotalSum = {$fTotalSum}", "fManagerShare = {$fManagerShare}", "fManagerProfit = {$fManagerProfit}", "fCapital = {$fCapital}", "fCapitalNew = {$fCapitalNew}", "fCeiling = {$fCeiling}");

                    $this->db
                        ->select('ai.id AS investor_id', FALSE)
                        ->select('ai.type')
                        ->select('ai.current_sum')
                        ->select("ai.current_sum/{$fTotalSum} AS investor_share", FALSE)
                        ->select('pis.period_profit')
                        ->select('pis.availsum')
                        ->select("IF(ai.type = 1, ({$aRow['total_profit']} - {$fManagerProfit} * {$fCeiling}) * (ai.current_sum/{$fTotalSum}/(1-{$fManagerShare})), {$fManagerProfit}) AS inv_profit_open", FALSE)
                        ->select("pis.period_profit + IF(ai.type = 1, ({$aRow['total_profit']} - {$fManagerProfit} * {$fCeiling}) * (ai.current_sum/{$fTotalSum}/(1-{$fManagerShare})), {$fManagerProfit}) AS new_period_profit", FALSE)
                        ->select("pis.availsum + IF(ai.type = 1, ({$aRow['total_profit']} - {$fManagerProfit} * {$fCeiling}) * (ai.current_sum/{$fTotalSum}/(1-{$fManagerShare})), {$fManagerProfit}) AS new_availsum", FALSE)
                        ->from('pamm_investors AS ai')
                        ->join('pamm_investors_stat AS pis', 'pis.investor_id = ai.id')
                        ->where('ai.pamm_mt_login', (int)$aRow['pamm_mt_login'])
                        ->where('ai.status', $this->gaPammInvestorAccountStatuses['ACTIVATED'])
                        ->where('ai.type', $this->gaPammInvestorTypes['INVESTOR'])
                        ->order_by('ai.id', 'ASC');
                    $rQuery = $this->db->get();
                    //$aResult = array();
                    $iCount = 0;
                    $fTotalSum = 0;
                    if ($rQuery->num_rows() > 0)
                    {
                        $aResult = $rQuery->result_array();
                        foreach ($aResult as $aResultRow)
                        {
                            if (bccomp($aResultRow['new_availsum'], 0, 2) == 1)
                            {
                                $fTotalSum = bcadd($fTotalSum, $aResultRow['new_availsum'], 2);
                            }
                            if (bccomp($aResultRow['new_availsum']/$aResultRow['current_sum'], 0.05, 2) == -1)
                            {
                                $iCount++;
                                $this->prn("GOTCHA!", $aResultRow);
                            }
                        }
                    }
                    if ($iCount > 0)
                    {
                        $aOutput[] = array(
                            'pamm_mt_login' => (int)$aRow['pamm_mt_login'],
                            'total_bad_inv' => $iCount,
                            'total_sum'     => $fTotalSum,
                        );
                    }
                    //$this->prn($this->db->last_query(), $aResult);
                    ob_flush();
                }
            }
        }

        return $aOutput;
    }

    /**
     * Крон по обновлению списка доступа для покупки индекса MyStat
     *
     * @return bool
     */
    function runPammIndexACLMyStat()
    {
        $this->db->query("INSERT INTO api.pamm_idx_acl
SELECT 'MyStat' AS symbol, a.foreign_account AS mt_login, pms.active AS status
FROM web.pamm_mystat_users AS pms
INNER JOIN web.users AS u ON u.nickname = pms.nickname
INNER JOIN processing.accounts AS a ON a.user_id = u.id AND a.account_type IN (13, 17)
ON DUPLICATE KEY UPDATE status = VALUES(status)");
        
        return true;
    }

    /**
     * Крон по автозакрытию сделок индекса MyStat, когда status = 0
     *
     * @return bool
     */
    function runPammIndexMyStatAutoClose()
    {
        $this->db
            ->select('pia.mt_login')
            ->select('mtt.ticket')
            ->from('pamm_idx_acl AS pia')
            ->join('MT4_TRADES AS mtt', "mtt.LOGIN = pia.mt_login AND mtt.SYMBOL = pia.symbol AND mtt.CLOSE_TIME < '2000-01-01'")
            ->where('pia.symbol', 'MyStat')
            ->where('pia.status', 0)
            ->order_by('mtt.ticket', 'ASC');
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() > 0)
        {
            $aResult = $rQuery->result_array();
            foreach ($aResult as $aRow)
            {
                $this->webactions->updateOrder($aRow['ticket'], 2);
            }
        }

        return true;
    }

    function runPreRolloverClosePammOpenedTickets()
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_implicit_flush();

        // Получаем свежайшую инфу о памм-счета, по которым нужно закрыть сделки
        $this->db
            ->select('pamm_mt_login')
            ->from('pamm_offers')
            ->where('status', $this->gaPammOffersStatuses['OPENED'])
            ->where('conditionally_periodic', 0)
            ->where('next_rollover_date < DATE_ADD(NOW(), INTERVAL 12 HOUR)', NULL, FALSE)
            ->order_by('pamm_mt_login', 'ASC');
        $rQuery = $this->db->get();
        $aPAMMAccounts = array();
        if ($rQuery->num_rows() > 0)
        {
            $aPAMMAccounts = $rQuery->result_array();
            $rQuery->free_result();
        }

        foreach ($aPAMMAccounts as $aPAMMAccount)
        {
            $aOpenedTickets = $this->webactions->getOpenedTradesCount($aPAMMAccount['pamm_mt_login']);
            if ($aOpenedTickets['opened'] > 0)
            {
                echo "Closing orders on {$aPAMMAccount['pamm_mt_login']}... ";
                $bResult = $this->webactions->closeOrders($aPAMMAccount['pamm_mt_login']);
                echo (($bResult)?"Done":"ERROR") . PHP_EOL;
            }
        }

        return true;
    }

    /* Демоны */

    /**
     * Крон по обработке торговых сделок для ПАММ-счетов и ПАММ-индексов
     *
     * @return bool
     */
    function runPammMTTradesCron()
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_implicit_flush();

        if (!$this->registerPammProcess('mt_trades_dispatcher'))
        {
            exit("ERROR: " . __FUNCTION__ . " is already running");
        }

        while (1)
        {
            $this->db
                ->select('pmttd.ticket')
                ->select('pmttd.login')
                ->select('pmttd.status')
                ->select("IF(IFNULL(po.pamm_mt_login, 0) != 0 AND (mtt.CMD < 2 OR (mtt.CMD = 6 AND (mtt.COMMENT LIKE 'PA%' OR mtt.COMMENT LIKE 'PMCC/%' OR mtt.COMMENT LIKE CONCAT('PI%/', po.investor_id, '/', po.pamm_mt_login, '/%')))), po.pamm_mt_login, 0) AS is_pamm", FALSE)
                ->select("IF(mtt.CMD = 6 AND mtt.COMMENT LIKE 'PA%', 0, 1) AS get_total_sum", FALSE)
                ->select("IFNULL(po.id, 0) AS offer_id", FALSE)
                ->select("IFNULL(po.bonus/100, 0) AS offer_bonus", FALSE)
                ->select("IFNULL(po.investor_id, 0) AS investor_id", FALSE)
                ->select("IF(mtt.CMD < 2, 1, 0) + IF(mtt.CMD = 6 AND (mtt.COMMENT LIKE CONCAT('PI%/', IFNULL(po.investor_id, 0), '/', pmttd.login, '/%') OR LOCATE('PMCC/', mtt.COMMENT) = 1), 2, 0) + IF(mtt.CMD = 6 AND (mtt.COMMENT LIKE 'PA%'), 3, 0) AS ticket_cmd", FALSE)
                ->select("mtt.VOLUME/100 AS lots", FALSE)
                ->select("mtt.SYMBOL AS symbol", FALSE)
                ->select("ROUND(mtt.COMMISSION+mtt.SWAPS+mtt.PROFIT++mtt.TAXES+IF(LOCATE('PMCC/', mtt.COMMENT) = 1, SUBSTRING(mtt.COMMENT, 6), 0), 2) AS ticket_profit", FALSE)
                //->select("CONVERT_TZ(mtt.CLOSE_TIME, '{$this->gMTTimeZone}', @@session.time_zone) AS ticket_close_time", FALSE)
                //->select("UNIX_TIMESTAMP(CONVERT_TZ(mtt.CLOSE_TIME, '{$this->gMTTimeZone}', @@session.time_zone)) AS ticket_unix_close_time", FALSE)
                ->select("DATE_ADD(mtt.CLOSE_TIME, INTERVAL 2 HOUR) AS ticket_close_time", FALSE)
                ->select("UNIX_TIMESTAMP(DATE_ADD(mtt.CLOSE_TIME, INTERVAL 2 HOUR)) AS ticket_unix_close_time", FALSE)
                ->from('pamm_mt_trades_dispatcher AS pmttd')
                ->join('pamm_offers AS po', 'po.pamm_mt_login = pmttd.login')
                ->join('MT4_TRADES AS mtt', 'mtt.TICKET = pmttd.TICKET AND mtt.CLOSE_TIME > "2000-01-01"', '', FALSE)
                ->where('pmttd.status', 0)
                ->or_where('pmttd.status', 4)
                ->order_by('pmttd.status', 'ASC')
                ->order_by('mtt.CLOSE_TIME', 'ASC')
                ->limit(2000);
            $rQuery = $this->db->get();
            //$this->prn($this->db->last_query());
            if ($rQuery->num_rows() > 0)
            {
                $aRows = $rQuery->result_array();
                //$this->prn($this->db->last_query(), $aRows);
                foreach ($aRows as $aRow)
                {
                    //$this->prn($aRow);
                    // Если сделка принадлежит ПАММ-счету
                    if (!empty($aRow['is_pamm']))
                    {
                        $bRefreshOther = false;
                        // Если это повторный перерасчет, нужный счет и прибыль сделки < 0, но надо сделать несколько экспериментальных вещей
                        if ($aRow['status'] == 4 && $aRow['ticket_cmd'] == 1 && $aRow['ticket_profit'] < 0)
                        {
                            //$this->prn("Our case", $aRow);

                            // Просчитываем остальные данные
                            $this->db
                                ->select('rollover_date')
                                ->from('pamm_rollovers')
                                ->where('pamm_mt_login', (int)$aRow['login'])
                                ->where('rollover_date <', (int)$aRow['ticket_unix_close_time'])
                                ->order_by('rollover_date', 'DESC')
                                ->limit(1);
                            $rQuery = $this->db->get();
                            if ($rQuery->num_rows() > 0)
                            {
                                $aResult = $rQuery->row_array();
                                $iCreatedAtFrom = $aResult['rollover_date']-7200;
                            }
                            else
                            {
                                $iCreatedAtFrom = 0;
                            }
                            $this->db->query("CREATE TEMPORARY TABLE aii SELECT ai.id AS investor_id, type FROM pamm_investors AS ai WHERE ai.pamm_mt_login = {$aRow['login']} AND ai.created_at <= {$aRow['ticket_unix_close_time']} ORDER BY ai.id ASC");
                            //$this->prn($this->db->last_query());
                            $this->db->query("CREATE TEMPORARY TABLE ai_set (investor_id INT(10) UNSIGNED NOT NULL, investor_sum DECIMAL(16,2) UNSIGNED NOT NULL, type INT(10) UNSIGNED NOT NULL, profit DECIMAL(16,2) NOT NULL, availsum DECIMAL (16,2) NOT NULL, PRIMARY KEY (investor_id)) SELECT investor_id, ROUND(investor_sum, 2) AS investor_sum, type, 0 AS profit, 0 AS availsum FROM (SELECT aii.investor_id, IFNULL(pp.investor_sum, 0) AS investor_sum, aii.type FROM aii LEFT JOIN pamm_payments AS pp ON pp.investor_id = aii.investor_id AND pp.created_at > {$iCreatedAtFrom} AND pp.created_at < {$aRow['ticket_unix_close_time']} AND pp.type != 9 ORDER BY aii.investor_id ASC, pp.created_at DESC) AS pp_investors_sum GROUP BY investor_id");
                            //$this->prn($this->db->last_query());
                            $this->db->query("DROP TEMPORARY TABLE aii");
                            //$this->prn($this->db->last_query());

                            // Запоминаем сколько было прибыли до закрытия обрабатываемой сделки
                            $this->db
                                ->select('investor_id')
                                ->select('investor_sum')
                                ->select('type')
                                ->from('ai_set');
                            $rQuery = $this->db->get();
                            //$this->prn($this->db->last_query());
                            if ($rQuery->num_rows() > 0)
                            {
                                $aResult = $rQuery->result_array();
                                foreach ($aResult as $aInvestor)
                                {
                                    if ($aInvestor['type'] == $this->gaPammInvestorTypes['MANAGER'])
                                    {
                                        $aRow['manager_sum'] = $aInvestor['investor_sum'];
                                    }
                                    $fPeriodProfit = $this->getInvestorsTotalProfitByPeriod($aInvestor['investor_id'], $iCreatedAtFrom, $aRow['ticket_unix_close_time']+1, $aRow['login']);
                                    $this->db->query("UPDATE ai_set SET profit = {$fPeriodProfit}, availsum = investor_sum + profit WHERE investor_id = {$aInvestor['investor_id']}");
                                    //$this->prn($this->db->last_query());
                                }
                            }

                            $rQuery = $this->db->query("SELECT ROUND(SUM(investor_sum), 2) AS total_sum FROM ai_set");
                            $fTotalSum = ($rQuery->num_rows() > 0)?$rQuery->row()->total_sum:0;
                            $fManagerShare  = ($fTotalSum>0)?$aRow['manager_sum']/$fTotalSum:0;
                            $fManagerProfit = $aRow['ticket_profit']*($aRow['offer_bonus'] + (1-$aRow['offer_bonus'])*$fManagerShare);

                            $rQuery = $this->db->query("SELECT new_capital AS capital FROM pamm_manager_capital_stat WHERE pamm_mt_login = {$aRow['login']} ORDER BY close_time DESC, id DESC LIMIT 1");
                            $fCapital   = ($rQuery->num_rows() > 0)?$rQuery->row()->capital:0;
                            $fCapitalNew= bcadd($fCapital, $fManagerProfit, 2);
                            if ($fManagerProfit == 0 || ($fManagerProfit >= 0 && $fCapital >= 0) || ($fManagerProfit < 0 && $fCapital > 0 && $fManagerProfit+$fCapital >= 0))
                            {
                                $fCeiling = 1;
                            }
                            else
                            {
                                if ($fCapital < 0 && $fManagerProfit+$fCapital <= 0)
                                {
                                    $fCeiling = 0;
                                }
                                else
                                {
                                    $fCeiling = ($fCapital < 0 && $fManagerProfit+$fCapital > 0)?1-abs($fCapital/$fManagerProfit):abs($fCapital/$fManagerProfit);
                                }
                            }
                            $rQuery->free_result();
                            //$this->prn("fTotalSum = {$fTotalSum}", "fManagerShare = {$fManagerShare}", "fManagerProfit = {$fManagerProfit}", "fCapital = {$fCapital}", "fCapitalNew = {$fCapitalNew}", "fCeiling = {$fCeiling}");

                            // Просчитываем новое состояние инв счетов
                            $this->db
                                ->select('ai_set.investor_id')
                                ->select('ai_set.investor_sum')
                                ->select('ai_set.type')
                                //->select("investor_sum/{$fTotalSum} AS investor_share", FALSE)
                                ->select('ai_set.profit AS period_profit', FALSE)
                                ->select('ai_set.availsum')
                                ->select("IF(ai_set.type = 1, ({$aRow['ticket_profit']} - {$fManagerProfit} * {$fCeiling}) * ((ai_set.investor_sum+IFNULL(pp.sum, 0))/{$fTotalSum}/(1-{$fManagerShare})), {$fManagerProfit}) AS inv_profit_open", FALSE)
                                ->select("ai_set.profit + IF(ai_set.type = 1, ({$aRow['ticket_profit']} - {$fManagerProfit} * {$fCeiling}) * ((ai_set.investor_sum+IFNULL(pp.sum, 0))/{$fTotalSum}/(1-{$fManagerShare})), {$fManagerProfit}) AS new_period_profit", FALSE)
                                ->select("ai_set.availsum + IF(ai_set.type = 1, ({$aRow['ticket_profit']} - {$fManagerProfit} * {$fCeiling}) * ((ai_set.investor_sum+IFNULL(pp.sum, 0))/{$fTotalSum}/(1-{$fManagerShare})), {$fManagerProfit}) AS new_availsum", FALSE)
                                ->from('ai_set')
                                ->join('pamm_payments AS pp', 'pp.investor_id = ai_set.investor_id AND pp.type = 9', 'left')
                                ->group_by('ai_set.investor_id')
                                ->order_by('ai_set.investor_id', 'ASC');
                            $rQuery = $this->db->get();
                            $iCount = 0;
                            $aResult = array();
                            if ($rQuery->num_rows() > 0)
                            {
                                $aResult = $rQuery->result_array();
                                //$this->prn($aResult);
                                foreach ($aResult as $aResultRow)
                                {
                                    if (bccomp($aResultRow['new_availsum'], 0, 2) == -1)
                                    {
                                        $iCount++;
                                    }
                                }
                            }
                            $rQuery->free_result();
                            if ($iCount > 0)
                            {
                                $this->prn("Our case", $aRow);
                                $this->prn("fTotalSum = {$fTotalSum}", "fManagerShare = {$fManagerShare}", "fManagerProfit = {$fManagerProfit}", "fCapital = {$fCapital}", "fCapitalNew = {$fCapitalNew}", "fCeiling = {$fCeiling}");
                                $this->prn("GOTCHA!", $iCount, $aResult);

                                // Запоминаем сколько было прибыли до времени закрытия обрабатываемой сделки
                                $this->db
                                    ->select('investor_id')
                                    ->from('ai_set');
                                $rQuery = $this->db->get();
                                //$this->prn($this->db->last_query());
                                if ($rQuery->num_rows() > 0)
                                {
                                    $aResult = $rQuery->result_array();
                                    foreach ($aResult as $aInvestor)
                                    {
                                        $fPeriodProfit = $this->getInvestorsTotalProfitByPeriod($aInvestor['investor_id'], $iCreatedAtFrom, $aRow['ticket_unix_close_time'], $aRow['login']);
                                        $this->db->query("UPDATE ai_set SET profit = {$fPeriodProfit}, availsum = investor_sum + profit WHERE investor_id = {$aInvestor['investor_id']}");
                                        //$this->prn($this->db->last_query());
                                    }
                                }

                                $this->db->select('*')->from('ai_set');
                                $rQuery = $this->db->get();
                                if ($rQuery->num_rows() > 0)
                                {
                                    $aResult = $rQuery->result_array();
                                    $this->prn($aResult);
                                }

                                // Вставляем новые срезы за секунду до закрытия сделки
                                $iUpdatedAt = $aRow['ticket_unix_close_time']-1;
                                $this->db->query("INSERT INTO pamm_payments (`investor_id`, `sum`, `created_at`, `type`, `investor_sum`) SELECT investor_id, profit AS `sum`, {$iUpdatedAt} AS created_at, 9 AS `type`, availsum AS investor_sum FROM ai_set");

                                // Ставим флаг обновления total_sum у сделок, которые уже были закрыты в это время - ticket_close_time
                                $bRefreshOther = true;
                            }

                            $this->db->query("DROP TEMPORARY TABLE ai_set");
                            //$this->prn($this->db->last_query());
                        }

                        // Проверка на необходимость подсчитать total_sum
                        if (!empty($aRow['get_total_sum']))
                        {
                            $this->db
                                ->select('rollover_date')
                                ->from('pamm_rollovers')
                                ->where('pamm_mt_login', (int)$aRow['login'])
                                ->where('rollover_date <', (int)$aRow['ticket_unix_close_time'])
                                ->order_by('rollover_date', 'DESC')
                                ->limit(1);
                            $rQuery = $this->db->get();
                            if ($rQuery->num_rows() > 0)
                            {
                                $aResult = $rQuery->row_array();
                                $iCreatedAtFrom = $aResult['rollover_date']-7200;
                            }
                            else
                            {
                                $iCreatedAtFrom = 0;
                            }

                            $this->db->query("CREATE TEMPORARY TABLE aii SELECT ai.id AS investor_id FROM pamm_investors AS ai WHERE ai.pamm_mt_login = {$aRow['login']} AND ai.created_at <= {$aRow['ticket_unix_close_time']} ORDER BY ai.id ASC");
                            $this->db->query("CREATE TEMPORARY TABLE ppi SELECT aii.investor_id, IFNULL(pp.investor_sum, 0) AS investor_sum FROM aii LEFT JOIN pamm_payments AS pp ON pp.investor_id = aii.investor_id AND pp.created_at > {$iCreatedAtFrom} AND pp.created_at < {$aRow['ticket_unix_close_time']} ORDER BY aii.investor_id ASC, pp.created_at DESC");
                            $rQuery = $this->db->query("SELECT IFNULL(ROUND(SUM(investor_sum), 2), 0) AS total_sum FROM (SELECT * FROM ppi GROUP BY investor_id) AS pp_investors_sum2");
                            $this->db->query("DROP TEMPORARY TABLE aii");
                            $this->db->query("DROP TEMPORARY TABLE ppi");
                        }
                        else
                        {
                            $rQuery = $this->db->query("SELECT total_sum FROM pamm_manager_capital_stat WHERE pamm_mt_login = {$aRow['login']} AND close_time <= '{$aRow['ticket_close_time']}' ORDER BY close_time DESC, id DESC LIMIT 1");
                        }
                        $fTotalSum = ($rQuery->num_rows() > 0)?$rQuery->row()->total_sum:0;
                        //$this->prn($this->db->last_query(), $fTotalSum);

                        $this->db
                            ->select("IFNULL(pp.investor_sum/{$fTotalSum}, 0) AS manager_share", FALSE)
                            ->select("pp.investor_sum AS manager_sum", FALSE)
                            ->select("IF({$aRow['ticket_cmd']} = 1, {$aRow['ticket_profit']}*({$aRow['offer_bonus']} + (1-{$aRow['offer_bonus']})*IFNULL(pp.investor_sum/{$fTotalSum}, 0)), {$aRow['ticket_profit']}) AS manager_profit", FALSE)
                            ->from('pamm_payments AS pp')
                            ->where('pp.investor_id', (int)$aRow['investor_id'])
                            ->where('pp.created_at <=', (int)$aRow['ticket_unix_close_time'])
                            ->order_by('pp.created_at', 'DESC')
                            ->limit(1);
                        $rQuery = $this->db->get();
                        if ($rQuery->num_rows() > 0)
                        {
                            $aManagerData = $rQuery->row_array();
                        }
                        else
                        {
                            $aManagerData['manager_share'] = $aManagerData['manager_sum'] = $aManagerData['manager_profit'] = 0;
                        }
                        //$this->prn($this->db->last_query(), $aManagerData);

                        // Если требуется обновить все сделки с этим временем - обновляем
                        if ($bRefreshOther)
                        {
                            $this->db->query("SET @capital = IFNULL((SELECT new_capital AS capital FROM pamm_manager_capital_stat WHERE pamm_mt_login = {$aRow['login']} AND close_time < '{$aRow['ticket_close_time']}' ORDER BY close_time DESC, id DESC LIMIT 1), 0)");
                            $this->prn($this->db->last_query());
                            $this->db->query("UPDATE pamm_manager_capital_stat SET manager_share = {$aManagerData['manager_share']}, manager_sum = {$aManagerData['manager_sum']}, manager_profit = IF(cmd = 1, profit*(offer_bonus + (1-offer_bonus)*IFNULL(manager_sum/{$fTotalSum}, 0)), profit), capital = @capital, new_capital = (@capital := ROUND(capital+manager_profit, 8)), ceiling = ROUND(IF(cmd = 2 OR cmd = 3 OR manager_profit = 0 OR (manager_profit >= 0 AND capital >= 0) OR (manager_profit < 0 AND capital > 0 AND manager_profit+capital >= 0), 1, IF(capital < 0 AND manager_profit+capital <= 0, 0, IF(capital < 0 AND manager_profit+capital > 0, 1-ABS(capital/manager_profit), ABS(capital/manager_profit)))), 8), total_sum = {$fTotalSum} WHERE pamm_mt_login = {$aRow['login']} AND close_time = '{$aRow['ticket_close_time']}' ORDER BY id ASC");
                            $this->prn($this->db->last_query());
                        }

                        $rQuery = $this->db->query("SELECT new_capital AS capital FROM pamm_manager_capital_stat WHERE pamm_mt_login = {$aRow['login']} AND close_time <= '{$aRow['ticket_close_time']}' ORDER BY close_time DESC, id DESC LIMIT 1");
                        $fCapital = ($rQuery->num_rows() > 0)?$rQuery->row()->capital:0;
                        //$this->prn($this->db->last_query(), $fCapital);
                        $fCapitalNew = bcadd($fCapital, $aManagerData['manager_profit'], 8);
                        if ($aRow['ticket_cmd'] == 2 && $fCapital < 0 && $aManagerData['manager_profit'] > 0 && $aRow['ticket_unix_close_time'] > 1470866400)
                        {
                            $rQuery = $this->db->query("SELECT id FROM pamm_money_orders WHERE investor_id = {$aRow['investor_id']} AND operation = 0 AND status = 1 AND sum = {$aManagerData['manager_profit']} AND confirmed_at > ({$aRow['ticket_unix_close_time']}-60) ORDER BY confirmed_at DESC LIMIT 1");
                            if ($rQuery->num_rows() > 0) {
                                $fCapitalNew = $aManagerData['manager_profit'];
                            }
                        }

                        $this->db->query("INSERT IGNORE INTO pamm_manager_capital_stat (ticket, pamm_mt_login, investor_id, offer_id, cmd, lots, symbol, profit, close_time, unix_close_time, offer_bonus, manager_share, manager_sum, manager_profit, capital, new_capital, ceiling, total_sum) VALUES ({$aRow['ticket']}, {$aRow['login']}, {$aRow['investor_id']}, {$aRow['offer_id']}, {$aRow['ticket_cmd']}, {$aRow['lots']}, '{$aRow['symbol']}', {$aRow['ticket_profit']}, '{$aRow['ticket_close_time']}', {$aRow['ticket_unix_close_time']}, {$aRow['offer_bonus']}, {$aManagerData['manager_share']}, {$aManagerData['manager_sum']}, ROUND({$aManagerData['manager_profit']}, 8), {$fCapital}, ROUND({$fCapitalNew}, 8), ROUND(IF({$aRow['ticket_cmd']} = 2 OR {$aRow['ticket_cmd']} = 3 OR {$aManagerData['manager_profit']} = 0 OR ({$aManagerData['manager_profit']} >= 0 AND {$fCapital} >= 0) OR ({$aManagerData['manager_profit']} < 0 AND {$fCapital} > 0 AND {$aManagerData['manager_profit']}+{$fCapital} >= 0), 1, IF({$fCapital} < 0 AND {$aManagerData['manager_profit']}+{$fCapital} <= 0, 0, IF({$fCapital} < 0 AND {$aManagerData['manager_profit']}+{$fCapital} > 0, 1-ABS({$fCapital}/{$aManagerData['manager_profit']}), ABS({$fCapital}/{$aManagerData['manager_profit']})))), 8), {$fTotalSum})");
                        //$this->prn($this->db->last_query());
                        $iInsertId = $this->db->insert_id();
                        //$this->prn($iInsertId);
                        if (!empty($iInsertId))
                        {
                            $sQuery = "UPDATE pamm_manager_capital_stat SET capital = ROUND(capital + ROUND({$aManagerData['manager_profit']}, 8), 8), new_capital = ROUND(new_capital + ROUND({$aManagerData['manager_profit']}, 8), 8), `ceiling` = ROUND(IF(cmd = 2 OR cmd = 3 OR manager_profit = 0 OR (manager_profit >= 0 AND capital >= 0) OR (manager_profit < 0 AND capital > 0 AND manager_profit+capital >= 0), 1, IF(capital < 0 AND manager_profit+capital <= 0, 0, IF(capital < 0 AND manager_profit+capital > 0, 1-ABS(capital/manager_profit), ABS(capital/manager_profit)))), 8) WHERE pamm_mt_login = {$aRow['login']} AND close_time > '{$aRow['ticket_close_time']}'";
                            $this->db->query($sQuery);
                            //$this->prn($sQuery, $this->db->affected_rows());

                            $rQuery = $this->db->query("SELECT new_capital, close_time FROM pamm_manager_capital_stat WHERE pamm_mt_login = {$aRow['login']} ORDER BY close_time DESC, id DESC LIMIT 1");
                            if ($rQuery->num_rows() > 0)
                            {
                                $aCapitalData = $rQuery->row_array();
                            }
                            else
                            {
                                $aCapitalData['new_capital'] = $aCapitalData['close_time'] = 0;
                            }
                            //$this->prn($this->db->last_query());

                            $sQuery = "REPLACE INTO pamm_manager_capital_current VALUES ({$aRow['login']}, '{$aCapitalData['close_time']}', {$aCapitalData['new_capital']})";
                            $this->db->query($sQuery);
                            //$this->prn($sQuery, $this->db->affected_rows());
                        }

                        // Обработано
                        $aFields = array(
                            'status'    => 1,
                        );
                        $this->db->where('ticket', (int)$aRow['ticket']);
                        //$this->db->where('status', 0);
                        $this->db->update('pamm_mt_trades_dispatcher', $aFields);
                    }
                    else
                    {
                        // Отклонено
                        $aFields = array(
                            'status'    => 2,
                        );
                        $this->db->where('ticket', (int)$aRow['ticket']);
                        //$this->db->where('status', 0);
                        $this->db->update('pamm_mt_trades_dispatcher', $aFields);
                    }

                    if (!$this->checkPammProcess('mt_trades_dispatcher')) exit;
                }
            }

            if (!$this->checkPammProcess('mt_trades_dispatcher')) exit;
            sleep(9);
        }

        return true;
    }

    /**
     * Крон по обработке торговых сделок для ПАММ-счетов и ПАММ-индексов
     *
     * @return bool
     */
    function runPammAITradesCron()
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_implicit_flush();

        if (!$this->registerPammProcess('ai_trades_dispatcher'))
        {
            exit("ERROR: " . __FUNCTION__ . " is already running");
        }

        while (1)
        {
            $this->db
                ->select('paitd.ticket')
                ->select('paitd.pamm_mt_login')
                ->select('smtt.LOGIN AS source_pamm_mt_login')
                ->select('smtt.SYMBOL')
                ->select('smtt.CMD')
                ->select('smtt.VOLUME')
                ->select('smtt.OPEN_PRICE')
                ->select('smtt.CLOSE_TIME')
                ->select('rmtt.CLOSE_TIME AS result_CLOSE_TIME', FALSE)
                ->select('paitd.copy_coefficient')
                ->select('paitd.result_ticket')
                ->select('po.copy_trades_commission')
                ->select('paitd.status')
                ->from('pamm_ai_trades_dispatcher AS paitd')
                ->join('MT4_TRADES AS smtt', 'smtt.TICKET = paitd.ticket')
                ->join('MT4_TRADES AS rmtt', 'rmtt.TICKET = paitd.result_ticket', 'left')
                ->join('pamm_offers AS po', 'po.pamm_mt_login = smtt.LOGIN')
                ->where('paitd.status', 1)
                ->or_where('paitd.status', 3)
                ->order_by('paitd.status', 'ASC')
                ->order_by('smtt.CLOSE_TIME', 'ASC')
                ->limit(1000);
            $rQuery = $this->db->get();
            $this->prn($this->db->last_query());
            if ($rQuery->num_rows() > 0)
            {
                $aRows = $rQuery->result_array();
                //$this->prn($aRows);
                foreach ($aRows as $aRow)
                {
                    $aFields = [];
                    if ($aRow['status'] == 1) {
                        $this->db
                            ->select('pmcs.ticket')
                            ->from('pamm_manager_capital_stat AS pmcs')
                            ->where('pmcs.pamm_mt_login', $aRow['pamm_mt_login'])
                            ->where('pmcs.close_time > DATE_SUB(NOW(), INTERVAL 1 WEEK)', NULL, FALSE)
                            ->where('pmcs.new_capital >= 10000', NULL, FALSE)
                            ->limit(1);
                        $rQuery = $this->db->get();
                        if ($rQuery->num_rows() == 0)
                        {
                            $aFields['status']  = 5;
                        }

                        if (count($aFields) == 0) {
                            $aSourcePammMargin      = $this->webactions->getTradesMarginInfo($aRow['source_pamm_mt_login']);
                            $aRecipientPammMargin   = $this->webactions->getTradesMarginInfo($aRow['pamm_mt_login']);
                            $fResultLots            = max(0.01, floatval(bcdiv(round($aRow['VOLUME'] * $aRecipientPammMargin['equity'] / $aSourcePammMargin['equity'] * $aRow['copy_coefficient']), 100, 2)));

                            $fResultCommossionPre   = min(1, floatval(bcdiv(round($aRow['VOLUME'] * $aRecipientPammMargin['equity'] / $aSourcePammMargin['equity'] * min(1, floatval($aRow['copy_coefficient']))), 100, 2)));
                            $fResultCommission      = round($fResultCommossionPre * $aRow['copy_trades_commission'], 2);

                            $sComment               = "{$aRow['source_pamm_mt_login']}/{$aRow['ticket']}/{$aRow['copy_coefficient']}";

                            $aFields = array(
                                'source_pamm_equity'    => $aSourcePammMargin['equity'],
                                'recipient_pamm_equity' => $aRecipientPammMargin['equity'],
                                'result_lots'           => $fResultLots,
                                'result_commission'     => $fResultCommission,
                            );

                            $iTicket = $this->webactions->addOrder($aRow['pamm_mt_login'], $aRow['CMD'], $fResultLots*100, $fResultCommission*-1, $aRow['SYMBOL'], $aRow['ticket'], $sComment);
                            $this->prn($aRow, $aSourcePammMargin, $aRecipientPammMargin, $fResultLots, $iTicket);

                            if ($iTicket) {
                                $aFields['result_ticket']   = $iTicket;
                                $aFields['status']          = 2;
                            }
                            if (!$iTicket && $aRow['CLOSE_TIME'] != "1970-01-01 00:00:00") {
                                $aFields['status']          = 4;
                            }
                        }
                    }
                    if ($aRow['status'] == 3) {
                        $bResult = $this->webactions->updateOrder($aRow['result_ticket'], 2);
                        if ($bResult) {
                            $aFields = array(
                                'status'     => 4,
                            );
                        }
                        if (!$bResult && $aRow['result_CLOSE_TIME'] != "1970-01-01 00:00:00") {
                            $aFields = array(
                                'status'     => 4,
                            );
                        }
                    }
                    if (count($aFields) > 0) {
                        $this->db->where('ticket', $aRow['ticket']);
                        $this->db->where('pamm_mt_login', $aRow['pamm_mt_login']);
                        $this->db->update('pamm_ai_trades_dispatcher', $aFields);
                    }
                }
            }

            if (!$this->checkPammProcess('ai_trades_dispatcher')) exit;
            sleep(3);
        }

        return true;
    }

    /**
     * Крон по обработке ПАММ-индекс сделок
     *
     * @return bool
     */
    function runPammIndexCron()
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_implicit_flush();

        if (!$this->registerPammProcess('pamm_index_cron'))
        {
            exit("ERROR: " . __FUNCTION__ . " is already running");
        }

        while (1)
        {
            $this->db
                ->select('pii.ticket')
                ->select('pii.status')
                ->select('IF(mtt.CLOSE_TIME < "2010-01-01", mtt.OPEN_TIME, mtt.CLOSE_TIME) AS action_time', FALSE)
                ->from('pamm_idx_investments AS pii')
                ->join('MT4_TRADES AS mtt', 'mtt.TICKET = pii.ticket')
                ->where('pii.status IN (1, 3)', NULL, FALSE)
                ->group_by('pii.ticket')
                ->order_by('action_time', 'ASC')
                ->limit(1000);
            $rQuery = $this->db->get();
            if ($rQuery->num_rows() > 0)
            {
                $aTickets = $rQuery->result_array();
                foreach ($aTickets as $aTicket)
                {
                    ob_start();
                    if ($aTicket['status'] == 1)
                    {
                        $this->doPammIndexDeposit($aTicket['ticket']);
                        //$this->email->to('tretyak@privatefx.com')->from('noreply@privatefx.com', 'api.PrivateFX Logger')->subject("Ticket #{$aTicket['ticket']} - Deposit")->message(ob_get_contents())->send();
                    }
                    else
                    {
                        $this->doPammIndexWithdrawal($aTicket['ticket']);
                        //$this->email->to('tretyak@privatefx.com')->from('noreply@privatefx.com', 'api.PrivateFX Logger')->subject("Ticket #{$aTicket['ticket']} - Withdrawal")->message(ob_get_contents())->send();
                    }
                    ob_end_flush();

                    if (!$this->checkPammProcess('pamm_index_cron')) exit;
                }
            }

            if (!$this->checkPammProcess('pamm_index_cron')) exit;
            sleep(5);
        }

        return true;
    }

    /**
     * Крон по обработке вброса тиков для ПАММ-индексов
     *
     * @return bool
     */
    function runPammIndexAddTick()
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_implicit_flush();

        if (!$this->registerPammProcess('addtick_dispatcher'))
        {
            exit("ERROR: " . __FUNCTION__ . " is already running");
        }

        while (1)
        {
            $this->db
                ->select('*')
                ->from('pamm_idx_addtick_dispatcher')
                ->where('status', 1)
                ->order_by('tick_date', 'ASC')
                ->order_by('id', 'ASC')
                ->limit(1000);
            $rQuery = $this->db->get();
            if ($rQuery->num_rows() > 0)
            {
                $aRows = $rQuery->result_array();
                foreach ($aRows as $aRow)
                {
                    $bResult = $this->webactions->addIndexTick($aRow['symbol'], $aRow['bid'], $aRow['ask']);
                    //$this->prn($aRow, $bResult);
                    if ($bResult)
                    {
                        $aFields = array(
                            'last_tick' => $aRow['bid'],
                        );
                        $this->db->where('symbol', $aRow['symbol']);
                        $this->db->update('pamm_idx_symbols', $aFields);

                        $aFields = array(
                            'status' => 2,
                        );
                        $this->db->where('id', $aRow['id']);
                        $this->db->update('pamm_idx_addtick_dispatcher', $aFields);
                    }

                    if (!$this->checkPammProcess('addtick_dispatcher')) exit;
                }
            }

            if (!$this->checkPammProcess('addtick_dispatcher')) exit;
            sleep(5);
        }

        return true;
    }

    /**
     * Крон по мониторингу ограничения убытков
     *
     * @return bool
     */
    function runPammLossLimitCron()
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_implicit_flush();

        if (!$this->registerPammProcess('loss_limit'))
        {
            exit("ERROR: " . __FUNCTION__ . " is already running");
        }

        while (intval(date("N")) < 6) // Processing only in business days
        {
            $this->db
                ->select('po.pamm_mt_login')
                ->select('po.loss_limit')
                ->select('mtu.BALANCE')
                ->select('IFNULL(pllb.balance, 0) AS loss_limit_balance', FALSE)
                ->select('ROUND(mtu.EQUITY, 2) AS EQUITY', FALSE)
                ->select('ROUND(GREATEST(mtu.BALANCE, IFNULL(pllb.balance, 0))*(100-po.loss_limit)/100, 2) AS LIMIT_LEVEL', FALSE)
                ->select('COUNT(mtt.TICKET) AS opened_trades', FALSE)
                ->select('IF(mtu.EQUITY < GREATEST(mtu.BALANCE, IFNULL(pllb.balance, 0))*(100-po.loss_limit)/100, 1, 0) AS LIMIT_LEVEL_REACHED', FALSE)
                ->from('pamm_offers AS po')
                ->join('pamm_loss_limit_balances AS pllb', 'pllb.pamm_mt_login = po.pamm_mt_login', 'left', FALSE)
                ->join('MT4_USERS AS mtu', 'mtu.LOGIN = po.pamm_mt_login AND mtu.ENABLE = 1 AND mtu.ENABLE_READONLY = 0 AND mtu.EQUITY < GREATEST(mtu.BALANCE, IFNULL(pllb.balance, 0))*(100-po.loss_limit/3)/100', '', FALSE)
                ->join('MT4_TRADES AS mtt', 'mtt.LOGIN = mtu.LOGIN AND mtt.CMD < 2 AND mtt.CLOSE_TIME < "2000-01-01"', 'left', FALSE)
                ->where('po.loss_limit > ', 0)
                ->group_by('po.pamm_mt_login')
                ->having('opened_trades > 0')
                ->order_by('LIMIT_LEVEL_REACHED', 'DESC');
            $rQuery = $this->db->get();
            if ($rQuery->num_rows() > 0)
            {
                $aRows = $rQuery->result_array();
                foreach ($aRows as $aRow)
                {
                    $bCloseTickets = false;
                    // Urgent intervention needed
                    if (!empty($aRow['LIMIT_LEVEL_REACHED']))
                    {
                        $bCloseTickets = true;
                    }
                    else
                    {
                        $aAccountInfo = $this->webactions->getUserInfo($aRow['pamm_mt_login'], true);
                        if (!empty($aAccountInfo) && is_array($aAccountInfo)
                         && bccomp($aAccountInfo['equity'], max(floatval($aAccountInfo['balance']), floatval($aRow['loss_limit_balance']))*(100-$aRow['loss_limit'])/100, 2) == -1)
                        {
                            $bCloseTickets = true;
                            $aRow['account_info'] = [
                                $aAccountInfo['balance'],
                                $aAccountInfo['margin'],
                                $aAccountInfo['free'],
                                $aAccountInfo['equity'],
                            ];
                        }
                    }

                    if ($bCloseTickets)
                    {
                        // Readonly enabling
                        $this->webactions->updateUser($aRow['pamm_mt_login'], 'ENABLE_READ_ONLY=1');
                        // Close trade tickets
                        $this->webactions->closeOrders($aRow['pamm_mt_login'], 1);

                        // Log to pamm_log
                        $aLog = [
                            'message'   => json_encode($aRow, JSON_PRETTY_PRINT),
                        ];
                        $this->db->insert('pamm_log', $aLog);

                        // Log to pamm_offers.admin_note
                        $this->db->where('pamm_mt_login', $aRow['pamm_mt_login']);
                        $this->db->set('admin_note', json_encode($aRow));
                        $this->db->update('pamm_offers');
                    }

                    if (!$this->checkPammProcess('loss_limit')) exit;
                }
            }

            if (!$this->checkPammProcess('loss_limit')) exit;
            sleep(5);
        }

        return true;
    }

    /**
     * Крон по рассылке уведомлений партнерам
     *
     * @return bool
     */
    function runPammNotificationsCron()
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_implicit_flush();

        if (!$this->registerPammProcess('notifications_dispatcher'))
        {
            exit("ERROR: " . __FUNCTION__ . " is already running");
        }

        while (1)
        {
            $this->db
                ->select('pn.id')
                ->select('pn.data')
                ->select('pp.status_url')
                ->from('pammapi_notifications AS pn')
                ->join('pammapi_partners AS pp', 'pp.id = pn.partner_id')
                ->where('pn.status', 0)
                ->limit(1000);
            $rQuery = $this->db->get();
            if ($rQuery->num_rows() > 0)
            {
                $aRows = $rQuery->result_array();
                foreach ($aRows as $aRow)
                {
                    $rCURL = curl_init();
                    curl_setopt($rCURL, CURLOPT_URL, $aRow['status_url']);
                    curl_setopt($rCURL, CURLOPT_POST, 1);
                    curl_setopt($rCURL, CURLOPT_POSTFIELDS, json_decode($aRow['data'], true));
                    curl_setopt($rCURL, CURLOPT_RETURNTRANSFER, 1);
                    //curl_setopt($rCURL, CURLOPT_HEADER, 1);
                    curl_setopt($rCURL, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($rCURL, CURLOPT_SSL_VERIFYPEER, 0);
                    $sResponse = curl_exec($rCURL);
                    $iCode = curl_getinfo($rCURL, CURLINFO_HTTP_CODE);
                    curl_close($rCURL);

                    $aFields = array(
                        'response'  => $sResponse,
                        'status'    => intval(($iCode == 200)),
                    );
                    $this->db->where('id', $aRow['id']);
                    $this->db->update('pammapi_notifications', $aFields);

                    if (!$this->checkPammProcess('notifications_dispatcher')) exit;
                }
            }

            if (!$this->checkPammProcess('notifications_dispatcher')) exit;
            sleep(5);
        }

        return true;
    }

    /* Разное */

    /**
     * Регистрирует ПАММ-процесс
     *
     * @param string $sName
     * @return bool
     */
    function registerPammProcess($sName = '')
    {
        if (empty($sName)) {
            return false;
        }

        $rQuery = $this->db->select('pid')->from('pamm_idx_processes')->where('process_name', $sName)->get();
        $iPid = ($rQuery->num_rows() > 0)?$rQuery->row()->pid:0;
        if (!empty($iPid))
        {
            $iPid = intval($iPid);
            if (posix_kill($iPid, 0))
            {
                return false;
            }
        }

        $iPid = posix_getpid();
        $this->db->query("INSERT INTO pamm_idx_processes (process_name, pid) VALUES ('{$sName}', {$iPid}) ON DUPLICATE KEY UPDATE pid = {$iPid}");

        return true;
    }

    /**
     * Проверяет жив ли ПАММ-процесс
     *
     * @param string $sName
     * @return bool
     */
    function checkPammProcess($sName = '')
    {
        if (empty($sName)) {
            return false;
        }

        $rQuery = $this->db->select('pid')->from('pamm_idx_processes')->where('process_name', $sName)->get();
        $iPid = ($rQuery->num_rows() > 0)?$rQuery->row()->pid:0;
        if ($iPid != posix_getpid())
        {
            // Уходим из процессов
            $this->db->where('process_name', $sName);
            $this->db->delete('pamm_idx_processes');

            return false;
        }

        return true;
    }

    /**
     * Получает время последнего ролловера по указанному ПАММ-счету
     *
     * @param integer $iPammMTLogin
     * @return integer
     */
    function getLastRolloverTime($iPammMTLogin)
    {
        $this->db
            ->select('pro.rollover_date')
            ->from('pamm_rollovers AS pro')
            ->join('pamm_offers AS po', 'po.pamm_mt_login = pro.pamm_mt_login')
            ->where('pro.pamm_mt_login', $iPammMTLogin)
            ->order_by('pro.rollover_date', 'DESC')
            ->limit(1);
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() > 0)
        {
            $iLastRolloverTime = $rQuery->row()->rollover_date;
        }
        else
        {
            $iLastRolloverTime = 0;
        }

        return $iLastRolloverTime;
    }

    /**
     * Получает доходности по инвесторскому счету за указанный период
     *
     * @param integer $iInvestorId
     * @param integer $iDateFrom
     * @param integer $iDateTo
     * @param integer $iPammMTLogin
     * @param bool $bForceDateFrom
     * @param bool $bKeepTT
     * @return float
     */
    function getInvestorsTotalProfitByPeriod($iInvestorId, $iDateFrom, $iDateTo, $iPammMTLogin, $bForceDateFrom = false, $bKeepTT = false)
    {
        // √ 0. Получаем данные по инвесторскому счету
        $this->db
            ->select('*')
            ->from('pamm_investors')
            ->where('id', $iInvestorId)
            ->limit(1);
        $rQuery = $this->db->get();
        if ($rQuery->num_rows() > 0)
        {
            $aInvestor = $rQuery->row_array();
            $rQuery->free_result();
        }
        else
        {
            return false;
        }

        // 1. Сформировать таблицу всех шар инв. счета
        $sQuery = "CREATE TEMPORARY TABLE pp{$iInvestorId} (investor_id INT(10) UNSIGNED NOT NULL, created_at INT(10) UNSIGNED NOT NULL, investor_sum DECIMAL(16, 2) NOT NULL, INDEX investor_id (investor_id)) SELECT investor_id, created_at, investor_sum FROM pamm_payments WHERE investor_id = {$iInvestorId} GROUP BY created_at ORDER BY created_at DESC";
        //$this->prn($sQuery);
        $this->db->query($sQuery);

        // 2. Получить всю прибыть за всю историю инв. счета
        $sForceDateFrom = empty($bForceDateFrom)?" AND pmcs.unix_close_time > {$aInvestor['activated_at']} ":"";
        if (empty($iDateFrom))
        {
            $iDateFrom = 0;
        }
        $sQuery = "CREATE TEMPORARY TABLE inv_ticket_profits (ticket INT(10) UNSIGNED NOT NULL, pamm_mt_login INT(10) UNSIGNED NOT NULL, investor_id INT(10) UNSIGNED NOT NULL, activated_at INT(10) UNSIGNED NOT NULL, close_time DATETIME NOT NULL, unix_close_time INT(10) UNSIGNED NOT NULL, profit DOUBLE NOT NULL, manager_profit DOUBLE NOT NULL, manager_share DOUBLE NOT NULL, ceiling DOUBLE NOT NULL, inv_profit DECIMAL(16, 4) NOT NULL, PRIMARY KEY (ticket), INDEX investor_id (investor_id)) SELECT pmcs.ticket, pmcs.pamm_mt_login /* {$iPammMTLogin} */, {$aInvestor['id']} AS investor_id, {$aInvestor['activated_at']} AS activated_at, pmcs.close_time, pmcs.unix_close_time, pmcs.profit, pmcs.manager_profit, pmcs.manager_share, pmcs.ceiling, IF({$aInvestor['type']} = 1, (pmcs.profit - pmcs.manager_profit * pmcs.ceiling) * IFNULL((SELECT (IF(pmcs.unix_close_time < 1311411600, ROUND(pp.investor_sum/pmcs.total_sum * 10000000) / 10000000 + 0.0000000, IF(pmcs.unix_close_time < 1337644800, ROUND(pp.investor_sum/pmcs.total_sum * 100000000) / 100000000 + 0.00000000, IF(pmcs.unix_close_time < 1348912800, FLOOR(pp.investor_sum/pmcs.total_sum * 100000000) / 100000000 + 0.00000000, IF(pmcs.unix_close_time < 1349604000, FLOOR(pp.investor_sum/pmcs.total_sum * 1000000000) / 1000000000 + 0.000000000, pp.investor_sum/pmcs.total_sum))))/(1-pmcs.manager_share)) FROM pp{$iInvestorId} AS pp WHERE pp.investor_id = {$iInvestorId} AND pp.created_at < pmcs.unix_close_time ORDER BY pp.created_at DESC LIMIT 1), 0), pmcs.manager_profit) AS inv_profit FROM pamm_manager_capital_stat AS pmcs WHERE pmcs.pamm_mt_login = {$iPammMTLogin} AND pmcs.cmd = 1 AND pmcs.unix_close_time >= {$iDateFrom} AND pmcs.unix_close_time < {$iDateTo} {$sForceDateFrom} GROUP BY pmcs.ticket ORDER BY pmcs.unix_close_time ASC, pmcs.ticket ASC";
        //$this->prn($sQuery);
        $this->db->query($sQuery);
        $this->db->query("DROP TEMPORARY TABLE pp{$iInvestorId}");

        $this->db
            ->select('IFNULL(FLOOR(IFNULL(SUM(inv_profit), 0)*100)/100, 0) AS inv_profit', FALSE)
            ->from('inv_ticket_profits')
            ->where('investor_id', (int)$iInvestorId)
            ->where('unix_close_time >= ', (int)$iDateFrom);
        $rQuery = $this->db->get();
        //$this->prn($this->db->last_query());
        if ($rQuery->num_rows() > 0)
        {
            $fTotalProfit = $rQuery->row()->inv_profit;
        }
        else
        {
            $fTotalProfit = 0;
        }
        $rQuery->free_result();

        // 3. Зачистить темплатовую таблицу, при необходимости
        if (empty($bKeepTT))
        {
            $this->db->query("DROP TEMPORARY TABLE inv_ticket_profits");
        }

        return $fTotalProfit;
    }

    /**
     * Отображение дебаг-информации
     *
     * @return bool
     */
    function prn()
    {
        if (is_cli() || ENVIRONMENT == "development")
        {
            $args = func_get_args();
            $last = array_slice(debug_backtrace(), 0, 1);
            $last = array_pop($last);
            $current_date = date("d.m.Y H:i:s");

            $html_data_tpl = '';

            foreach($args as $arg)
            {
                $html_data_tpl .= "--\n" . print_r($arg, true) . "\n";
            }

            if (is_cli())
            {
                $last['file'] = substr($last['file'], strlen(FCPATH));
                $html_main_tpl = "Called from {$last['file']}:{$last['line']} ({$current_date})\n{$html_data_tpl}\n\n";

                $aFields = array(
                    'message'   => $html_main_tpl,
                );
                $this->db->insert('rollover_log', $aFields);
            }
            else
            {
                $html_main_tpl  = "<div style='background-color: #EEE; border: 1px solid black; padding-left: 15px;'>\n";
                $html_main_tpl .= "<pre>\nCalled from <b>{$last['file']}</b> in line <b>{$last['line']}</b> <i>({$current_date})</i>\n{$html_data_tpl}</pre>\n</div>";
            }

            echo $html_main_tpl;

            unset($args);
            unset($last);
        }

        return true;
    }
}
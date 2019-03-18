<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class Pamm
 *
 * @property FT_Form_validation $form_validation
 * @property PammAPI $pammapi
 * @property WebActions $webactions
 */
class Pamm extends FT_Basic_Auth_Controller {

    public function __construct()
    {
        $this->sBasicRealm = "PAMM API Authentication";
        parent::__construct();

        $this->load->library('form_validation');
        $this->load->library('PammAPI');
        $this->load->library('WebActions');
        $this->pammapi->setPartnerId($this->getPartnerId());
        $this->pammapi->setPartnerMTLogin($this->getPartnerMTLogin());
        $this->pammapi->setPartnerPermissions($this->getPartnerPermissions());
    }

    private function sendToBrowser($mAnswer, $bStatus = true, $aErrors = array())
    {
        if (empty($aErrors) && $this->pammapi->hasErrors())
        {
            $aErrors = $this->pammapi->getErrors();
        }
        $aAnswer = array(
            'status'=> $bStatus,
            'data'  => $mAnswer,
            'errors'=> $aErrors,
        );
        switch ($this->getAcceptType())
        {
            case "json":
                $this->output->set_content_type('application/json');
                header('Content-Type: application/json');
                echo json_encode($aAnswer);
                break;
            /*case "xml":
                $this->output->set_content_type('application/xml');
                header('Content-Type: application/xml');
                $oXML = new SimpleXMLElement('<root/>');
                $this->to_xml($oXML, $aAnswer);
                echo $oXML->asXML();
                break;*/
            default:
                echo "<pre>";
                var_dump($aAnswer);
                echo "</pre>";
        }

        return true;
    }

    private function to_xml(SimpleXMLElement $object, array $data)
    {
        foreach ($data as $key => $value)
        {
            if (is_numeric($key))
            {
                $key = "key{$key}";
            }
            if (is_array($value))
            {
                $new_object = $object->addChild($key);
                $this->to_xml($new_object, $value);
            }
            else
            {
                $object->addChild($key, $value);
            }
        }
    }

    public function index()
	{
        return true;
    }

    /* ПАММ-счета */

    public function create_pamm()
    {
        $fPammMinCapital = PAMM_MIN_CAPITAL;
        $this->form_validation->set_rules('check_input', 'check_input', 'integer|greater_than_equal_to[0]|less_than_equal_to[1]');
        $this->form_validation->set_rules('ai_pamm', 'ai_pamm', 'required|integer|greater_than_equal_to[0]|less_than_equal_to[1]');
        $this->form_validation->set_rules('pamm_mt_login', 'pamm_mt_login', 'required|integer|callback_CheckMTPartnerRange');
        $this->form_validation->set_rules('user_id', 'user_id', 'required|integer');
        $this->form_validation->set_rules('account_id', 'account_id', 'required|integer');
        $this->form_validation->set_rules('amount', 'amount', "required|numeric|greater_than_equal_to[{$fPammMinCapital}]");                                // >= 500
        $this->form_validation->set_rules('inv_mt_login', 'inv_mt_login', 'required|numeric|callback_CheckMTLoginEquityGreaterOrEqualThan[amount]');        // >= amount
        $this->form_validation->set_rules('bonus', 'bonus', 'required|integer|greater_than_equal_to[1]|less_than_equal_to[99]');                            // >= 1  AND <= 99
        $this->form_validation->set_rules('commission', 'commission', 'required|integer|greater_than_equal_to[10]|less_than_equal_to[80]');                 // >= 10 AND <= 80
        $this->form_validation->set_rules('responsibility', 'responsibility', 'required|integer|callback_CheckResponsibilityRange');                        // >= 0  AND <= 100
        $this->form_validation->set_rules('trade_period', 'trade_period', 'required|integer|greater_than_equal_to[1]|less_than_equal_to[4]');               // >= 1  AND <= 4
        $this->form_validation->set_rules('cond_periodic', 'cond_periodic', 'required|integer|greater_than_equal_to[0]|less_than_equal_to[1]');             // >= 0  AND <= 1
        $this->form_validation->set_rules('min_balance', 'min_balance', 'required|numeric|greater_than_equal_to[10]|less_than_equal_to[amount]');           // >= 10 AND <= amount
        $this->form_validation->set_rules('mc_stopout', 'mc_stopout', 'required|integer|greater_than_equal_to[0]|less_than_equal_to[1]');                   // >= 0  AND <= 1
        $this->form_validation->set_rules('allow_copy_trades', 'allow_copy_trades', 'required|integer|greater_than_equal_to[0]|less_than_equal_to[1]');     // >= 0  AND <= 1
        $this->form_validation->set_rules('copy_trades_commission', 'copy_trades_commission', 'required|integer|greater_than_equal_to[0]|less_than_equal_to[500]'); // >= 0 AND <= 500
        $this->form_validation->set_rules('loss_limit', 'loss_limit', 'required|integer|greater_than_equal_to[0]|less_than_equal_to[90]');                  // >= 0 AND <= 90
        $this->form_validation->set_rules('agent_bonus', 'agent_bonus', 'required|integer|greater_than_equal_to[0]|less_than_equal_to[10]');                // >= 0  AND <= 10
        $this->form_validation->set_rules('agent_bonus_profit', 'agent_bonus_profit', 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]'); // >= 0  AND <= 100
        $this->form_validation->set_rules('agent_pay_delay', 'agent_pay_delay', 'required|integer|greater_than_equal_to[0]|less_than_equal_to[1]');         // >= 0  AND <= 1
        $this->form_validation->set_rules('report_level', 'report_level', 'required|integer|greater_than_equal_to[0]|less_than_equal_to[1]');               // >= 0  AND <= 1
        $this->form_validation->set_rules('contact_method', 'contact_method', 'required|min_length[1]|max_length[1000]');
        $this->form_validation->set_rules('ts_desc', 'ts_desc', 'required|min_length[1]|max_length[2000]');
        $bCheckInput = $this->input->post('check_input');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }
        elseif ($bCheckInput)
        {
            $this->sendToBrowser(false, true);
            return true;
        }

        $iAIPamm            = (int)$this->input->post('ai_pamm');
        $iPammMTLogin       = (int)$this->input->post('pamm_mt_login');
        $iUserId            = (int)$this->input->post('user_id');
        $iAccountId         = (int)$this->input->post('account_id');
        $fAmount            = (float)$this->input->post('amount');
        $iInvMTLogin        = (int)$this->input->post('inv_mt_login');
        $iBonus             = (int)$this->input->post('bonus');
        $iCommission        = (int)$this->input->post('commission');
        $iResponsibility    = (int)$this->input->post('responsibility');
        $iTradePeriod       = (int)$this->input->post('trade_period');
        $iCondPeriodic      = (int)$this->input->post('cond_periodic');
        $iMinBalance        = (int)$this->input->post('min_balance');
        $iMCStopout         = (int)$this->input->post('mc_stopout');
        $iAllowCopyTrades   = (int)$this->input->post('allow_copy_trades');
        $fCopyTradesCommission = (int)$this->input->post('copy_trades_commission');
        $iLossLimit         = (int)$this->input->post('loss_limit');
        $iAgentBonus        = (int)$this->input->post('agent_bonus');
        $iAgentBonusProfit  = (int)$this->input->post('agent_bonus_profit');
        $iAgentPayDelay     = (int)$this->input->post('agent_pay_delay');
        $iReportLevel       = (int)$this->input->post('report_level');
        $sContactMethod     = $this->input->post('contact_method');
        $sTSDesc            = $this->input->post('ts_desc');

        $mResult = $this->pammapi->createPamm($iAIPamm, $iPammMTLogin, $iInvMTLogin, $fAmount, $iUserId, $iAccountId, $iBonus, $iCommission, $iResponsibility, $iTradePeriod, $iCondPeriodic, $iMinBalance, $iMCStopout, $iAllowCopyTrades, $fCopyTradesCommission, $iLossLimit, $iAgentBonus, $iAgentBonusProfit, $iAgentPayDelay, $iReportLevel, $sContactMethod, $sTSDesc);
        if (!$mResult)
        {
            $this->sendToBrowser(false, false, $this->pammapi->Errors);
            return false;
        }

        $this->sendToBrowser($mResult);
        return true;
    }

    public function close_pamm($iPammMTLogin, $iUserId, $bAllowCloseTrades = false, $bAllow = false)
    {
        $aResult = $this->pammapi->closePamm($iPammMTLogin, $iUserId, $bAllowCloseTrades, $bAllow);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function pamm_details()
    {
        $this->form_validation->set_rules('pamm_mt_login', 'pamm_mt_login', 'required|integer');
        if ($this->form_validation->run() == FALSE)
        {
            return false;
        }

        $iPammMTLogin   = (int)$this->input->get_post('pamm_mt_login');

        $aResult = $this->pammapi->getPammDetails($iPammMTLogin);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function change_pamm_details()
    {
        $this->form_validation->set_rules('pamm_mt_login', 'pamm_mt_login', 'required|integer');
        $this->form_validation->set_rules('user_id', 'user_id', 'required|integer');
        $this->form_validation->set_rules('allow_copy_trades', 'allow_copy_trades', 'required|integer|greater_than_equal_to[0]|less_than_equal_to[1]');     // >= 0  AND <= 1
        $this->form_validation->set_rules('copy_trades_commission', 'copy_trades_commission', 'required|integer|greater_than_equal_to[0]|less_than_equal_to[500]');     // >= 0 AND <= 500
        $this->form_validation->set_rules('loss_limit', 'loss_limit', 'required|integer|greater_than_equal_to[0]|less_than_equal_to[90]');                  // >= 0 AND <= 90
        $this->form_validation->set_rules('agent_bonus', 'agent_bonus', 'required|integer|greater_than_equal_to[0]|less_than_equal_to[10]');                // >= 0  AND <= 10
        $this->form_validation->set_rules('agent_bonus_profit', 'agent_bonus_profit', 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]'); // >= 0  AND <= 100
        $this->form_validation->set_rules('agent_pay_delay', 'agent_pay_delay', 'required|integer|greater_than_equal_to[0]|less_than_equal_to[1]');         // >= 0  AND <= 1
        $this->form_validation->set_rules('contact_method', 'contact_method', 'required|min_length[1]|max_length[1000]');
        $this->form_validation->set_rules('ts_desc', 'ts_desc', 'required|min_length[1]|max_length[2000]');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $iPammMTLogin       = (int)$this->input->get_post('pamm_mt_login');
        $iUserId            = (int)$this->input->post('user_id');
        $iAllowCopyTrades   = (int)$this->input->post('allow_copy_trades');
        $fCopyTradesCommission = (int)$this->input->post('copy_trades_commission');
        $iLossLimit         = (int)$this->input->post('loss_limit');
        $iAgentBonus        = (int)$this->input->post('agent_bonus');
        $iAgentBonusProfit  = (int)$this->input->post('agent_bonus_profit');
        $iAgentPayDelay     = (int)$this->input->post('agent_pay_delay');
        $sContactMethod     = $this->input->post('contact_method');
        $sTSDesc            = $this->input->post('ts_desc');

        $aResult = $this->pammapi->сhangePammDetails($iPammMTLogin, $iUserId, $iAllowCopyTrades, $fCopyTradesCommission, $iLossLimit, $iAgentBonus, $iAgentBonusProfit, $iAgentPayDelay, $sContactMethod, $sTSDesc);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function set_trades_copy()
    {
        $this->form_validation->set_rules('pamm_mt_login', 'pamm_mt_login', 'required|integer');
        $this->form_validation->set_rules('user_id', 'user_id', 'required|integer');
        $this->form_validation->set_rules('source_pamm_mt_login', 'source_pamm_mt_login', 'required|integer');
        $this->form_validation->set_rules('copy_coefficient', 'copy_coefficient', 'required|numeric|greater_than_equal_to[0]|less_than_equal_to[10]');// >= 0 AND <= 10
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $iPammMTLogin       = (int)$this->input->get_post('pamm_mt_login');
        $iUserId            = (int)$this->input->post('user_id');
        $iSourcePammMTLogin = (int)$this->input->post('source_pamm_mt_login');
        $fCopyCoefficient   = (float)$this->input->post('copy_coefficient');

        $aResult = $this->pammapi->setTradesCopy($iPammMTLogin, $iUserId, $iSourcePammMTLogin, $fCopyCoefficient);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function get_pamm_list()
    {
        $this->form_validation->set_rules('offer_status', 'offer_status', 'required|integer|greater_than_equal_to[0]|less_than_equal_to[3]');   // >= 1  AND <= 3
        $this->form_validation->set_rules('close_unixtime', 'close_unixtime', 'integer');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $iOfferStatus = (int)$this->input->get_post('offer_status');
        $iCloseTime   = (int)$this->input->get_post('close_unixtime');

        $aResult = $this->pammapi->getPammList($iOfferStatus, $iCloseTime);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function get_profit_statement()
    {
        $this->form_validation->set_rules('pamm_mt_login', 'pamm_mt_login', 'required|integer');
        $this->form_validation->set_rules('period', 'period', 'required|integer|greater_than_equal_to[1]|less_than_equal_to[3]');   // >= 1  AND <= 3
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $iPammMTLogin   = (int)$this->input->get_post('pamm_mt_login');
        $iPeriod        = (int)$this->input->get_post('period');
        $iTimeFrom      = (int)$this->input->get_post('from_time');
        $iTimeTo        = (int)$this->input->get_post('to_time');

        $aResult = $this->pammapi->getProfitStatement($iPammMTLogin, $iPeriod, $iTimeFrom, $iTimeTo);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function get_pamm_users()
    {
        $this->form_validation->set_rules('pamm_mt_login', 'pamm_mt_login', 'required|integer');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $iPammMTLogin   = (int)$this->input->get_post('pamm_mt_login');

        $aResult = $this->pammapi->getPammUsers($iPammMTLogin);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function get_pamm_maximum_drawdowns()
    {
        $this->form_validation->set_rules('pamm_mt_login', 'pamm_mt_login', 'required|integer');
        $this->form_validation->set_rules('from_unix_time', 'from_unix_time', 'required|integer');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $iPammMTLogin   = (int)$this->input->get_post('pamm_mt_login');
        $iFromUnixTime  = (int)$this->input->get_post('from_unix_time');

        $aResult = $this->pammapi->getPammMaximumDrawdowns($iPammMTLogin, $iFromUnixTime);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function get_pamm_opened_tickets()
    {
        $this->form_validation->set_rules('pamm_mt_login', 'pamm_mt_login', 'required|integer');
        $this->form_validation->set_rules('user_id', 'user_id', 'required|integer');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $iPammMTLogin   = (int)$this->input->get_post('pamm_mt_login');
        $iUserId        = (int)$this->input->get_post('user_id');

        $aResult = $this->pammapi->getPammOpenedTickets($iPammMTLogin, $iUserId);
        $this->sendToBrowser($aResult);

        return true;
    }

    /* Инвесторские счета */

    public function create_investor()
    {
        $this->form_validation->set_rules('user_id', 'user_id', 'required|integer');
        $this->form_validation->set_rules('account_id', 'account_id', 'required|integer');
        $this->form_validation->set_rules('pamm_mt_login', 'pamm_mt_login', 'required|integer');
        $this->form_validation->set_rules('amount', 'amount', 'required|numeric');
        $this->form_validation->set_rules('inv_mt_login', 'inv_mt_login', 'required|integer');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $iUserId        = (int)$this->input->get_post('user_id');
        $iAccountId     = (int)$this->input->get_post('account_id');
        $iPammMTLogin   = (int)$this->input->get_post('pamm_mt_login');
        $fAmount        = (float)$this->input->get_post('amount');
        $iInvMTLogin    = (int)$this->input->get_post('inv_mt_login');
        $iForIndex      = (int)$this->input->get_post('for_index');
        $iForIC         = (int)$this->input->get_post('for_ic');
        $iAgentPayout   = (int)$this->input->get_post('agent_payouts');

        $aResult = $this->pammapi->createInvestor($iUserId, $iAccountId, $iPammMTLogin, $fAmount, $iInvMTLogin, $iForIndex, $iForIC, $iAgentPayout);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function get_investor_details()
    {
        $this->form_validation->set_rules('user_id', 'user_id', 'required|integer');
        $this->form_validation->set_rules('investor_id', 'investor_id', 'required|integer');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $iUserId    = (int)$this->input->get_post('user_id');
        $iInvestorId= (int)$this->input->get_post('investor_id');

        $aResult = $this->pammapi->getInvestorDetails($iUserId, $iInvestorId);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function get_user_investors_info()
    {
        $this->form_validation->set_rules('user_id', 'user_id', 'required|integer');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $iUserId    = (int)$this->input->get_post('user_id');

        $aResult = $this->pammapi->getUserInvestorsInfo($iUserId);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function get_investor_tickets_profit_list()
    {
        $this->form_validation->set_rules('investor_id', 'investor_id', 'required|integer');
        $this->form_validation->set_rules('pamm_mt_login', 'pamm_mt_login', 'required|integer');
        $this->form_validation->set_rules('time_from', 'time_from', 'required|integer');
        $this->form_validation->set_rules('time_to', 'time_to', 'required|integer');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $iInvestorId    = (int)$this->input->get_post('investor_id');
        $iPammMTLogin   = (int)$this->input->get_post('pamm_mt_login');
        $iTimeFrom      = (int)$this->input->get_post('time_from');
        $iTimeTo        = (int)$this->input->get_post('time_to');
        $bForceTimeFrom = (bool)$this->input->get_post('force_time_from');

        $aResult = $this->pammapi->getInvestorTicketsProfitList($iInvestorId, $iPammMTLogin, $iTimeFrom, $iTimeTo, $bForceTimeFrom);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function change_investor_details()
    {
        $this->form_validation->set_rules('investor_id', 'investor_id', 'required|integer');
        $this->form_validation->set_rules('user_id', 'user_id', 'required|integer');
        $this->form_validation->set_rules('show_mode', 'show_mode', 'required|integer');
        $this->form_validation->set_rules('auto_withdrawal', 'auto_withdrawal', 'required|integer');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $iInvestorId    = (int)$this->input->get_post('investor_id');
        $iUserId        = (int)$this->input->get_post('user_id');
        $iShowMode      = (int)$this->input->get_post('show_mode');
        $iAutoWithdrawal= (int)$this->input->get_post('auto_withdrawal');

        $aResult = $this->pammapi->changeInvestorDetails($iInvestorId, $iUserId, $iShowMode, $iAutoWithdrawal);
        $this->sendToBrowser($aResult);

        return true;
    }

    /* Распоряжения */

    public function create_money_order()
    {
        $this->form_validation->set_rules('investor_id', 'investor_id', 'required|integer');
        $this->form_validation->set_rules('user_id', 'user_id', 'required|integer');
        $this->form_validation->set_rules('operation', 'operation', 'required|integer');
        $this->form_validation->set_rules('amount', 'amount', 'required|numeric');
        $this->form_validation->set_rules('ignore_offer', 'ignore_offer', 'integer');
        $this->form_validation->set_rules('force', 'force', 'integer');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $iInvestorId    = (int)$this->input->get_post('investor_id');
        $iUserId        = (int)$this->input->get_post('user_id');
        $iOperation     = (int)$this->input->get_post('operation');
        $fAmount        = abs((float)$this->input->get_post('amount'));
        $bIgnoreOffer   = (int)$this->input->get_post('ignore_offer');
        $bForce         = (int)$this->input->get_post('force');

        $aResult = $this->pammapi->createMoneyOrder($iInvestorId, $iUserId, $iOperation, $fAmount, false, $bIgnoreOffer, $bForce);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function process_money_order()
    {
        $this->form_validation->set_rules('order_id', 'order_id', 'required|integer');
        $this->form_validation->set_rules('ignore_offer', 'ignore_offer', 'integer');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $iOrderId       = (int)$this->input->get_post('order_id');
        $bIgnoreOffer   = (int)$this->input->get_post('ignore_offer');

        $aResult = $this->pammapi->processMoneyOrder($iOrderId, $bIgnoreOffer);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function cancel_money_order()
    {
        $this->form_validation->set_rules('order_id', 'order_id', 'required|integer');
        $this->form_validation->set_rules('user_id', 'user_id', 'required|integer');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $iOrderId   = (int)$this->input->get_post('order_id');
        $iUserId    = (int)$this->input->get_post('user_id');

        $aResult = $this->pammapi->cancelMoneyOrder($iOrderId, $iUserId);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function get_money_orders_list()
    {
        $iOrderId       = 0;
        $iInvestorId    = 0;
        $iUserId        = 0;
        $iPammMTLogin   = 0;
        $iOrderType     = -1;
        $iOrderStatus   = -1;

        if (!is_null($this->input->post('order_id')))
        {
            $iOrderId    = (int)$this->input->post('order_id');
        }
        if (!is_null($this->input->post('investor_id')))
        {
            $iInvestorId    = (int)$this->input->post('investor_id');
        }
        if (!is_null($this->input->post('user_id')))
        {
            $iUserId        = (int)$this->input->post('user_id');
        }
        if (!is_null($this->input->post('pamm_mt_login')))
        {
            $iPammMTLogin   = (int)$this->input->post('pamm_mt_login');
        }
        if (!is_null($this->input->post('order_type')))
        {
            $iOrderType     = (int)$this->input->post('order_type');
        }
        if (!is_null($this->input->post('order_status')))
        {
            $iOrderStatus   = (int)$this->input->post('order_status');
        }

        $aResult = $this->pammapi->getMoneyOrdersList($iOrderId, $iInvestorId, $iUserId, $iPammMTLogin, $iOrderType, $iOrderStatus);
        $this->sendToBrowser($aResult);

        return true;
    }

    /* Ролловер */

    public function run_pre_rollover()
    {
        $aResult = $this->pammapi->runPreRollover();
        $this->sendToBrowser($aResult);

        return true;
    }

    public function run_periodic_rollover($bAllow = false)
    {
        $aResult = $this->pammapi->runPeriodicRollover($bAllow);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function run_rollover($iPammMTLogin, $bAllow = false)
    {
        $aResult = $this->pammapi->runRollover($iPammMTLogin, $bAllow);
        $this->sendToBrowser($aResult);

        return true;
    }

    /* Индексы */

    public function get_pamm_index_list()
    {
        $aResult = $this->pammapi->getPammIndexList();
        $this->sendToBrowser($aResult);

        return true;
    }

    public function get_pamm_index_details()
    {
        $this->form_validation->set_rules('symbol', 'symbol', 'required|alpha_dash');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $sSymbol    = $this->input->get_post('symbol');

        $aResult = $this->pammapi->getPammIndexDetails($sSymbol);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function get_pamm_index_tick_stat()
    {
        $this->form_validation->set_rules('symbol', 'symbol', 'required|alpha_dash');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $sSymbol    = $this->input->get_post('symbol');

        $aResult = $this->pammapi->getPammIndexTickStat($sSymbol);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function get_pamm_index_changes_for_week()
    {
        $this->form_validation->set_rules('week_date', 'week_date', 'required|alpha_dash');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $sWeekDate    = $this->input->get_post('week_date');

        $aResult = $this->pammapi->getPammIndexChangesForWeek($sWeekDate);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function change_pamm_index_pamm()
    {
        $this->form_validation->set_rules('symbol', 'symbol', 'required|string|max_length[12]');
        $this->form_validation->set_rules('old_pamm_mt_login', 'old_pamm_mt_login', 'required|integer');
        $this->form_validation->set_rules('new_pamm_mt_login', 'new_pamm_mt_login', 'required|integer');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $sSymbol        = $this->input->get_post('symbol');
        $iOldPammMtLogin= (int)$this->input->get_post('old_pamm_mt_login');
        $iNewPammMtLogin= (int)$this->input->get_post('new_pamm_mt_login');

        $aResult = $this->pammapi->changePammIndexPamm($sSymbol, $iOldPammMtLogin, $iNewPammMtLogin);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function change_pamm_index_structure()
    {
        $this->form_validation->set_rules('symbol', 'symbol', 'required|string|max_length[12]');
        $this->form_validation->set_rules('pamm[]', 'pamm', 'required|integer');
        $this->form_validation->set_rules('share[]', 'share', 'required|numeric');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $sSymbol    = $this->input->get_post('symbol');
        $aPamms     = $this->input->get_post('pamm');
        $aShares    = $this->input->get_post('share');
        $aConfig    = array_combine($aPamms, $aShares);
        $iTotalShare = 0;
        foreach ($aConfig as $aPamm => $aShare) {
            $iTotalShare += $aShare;
        }
        if (bccomp($iTotalShare, 1, 2) != 0) {
            $this->sendToBrowser(false, false, ['Wrong total share']);
            return false;
        }

        $aResult = $this->pammapi->changePammIndexStructure($sSymbol, $aConfig);
        $this->sendToBrowser($aResult);

        return true;
    }

    /* Агентские выплаты */

    public function get_agent_payments_list()
    {
        $this->form_validation->set_rules('time_from', 'time_from', 'required|integer');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $iTimeFrom      = (int)$this->input->get_post('time_from');

        $aResult = $this->pammapi->getAgentPaymentsList($iTimeFrom);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function change_agent_payments_status()
    {
        $this->form_validation->set_rules('user_ids', 'user_ids', 'required|min_length[1]');
        $this->form_validation->set_rules('status', 'status', 'required|integer|greater_than_equal_to[0]|less_than_equal_to[1]');             // >= 0  AND <= 1
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $sUserIds   = $this->input->get_post('user_ids');
        $iStatus    = $this->input->get_post('status');

        $aResult = $this->pammapi->changeAgentPaymentsStatus($sUserIds, $iStatus);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function add_user_alias()
    {
        $this->form_validation->set_rules('main_user_id', 'main_user_id', 'required|integer');
        $this->form_validation->set_rules('alias_user_id', 'alias_user_id', 'required|integer');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $iUserId        = $this->input->get_post('main_user_id');
        $iAliasUserId   = $this->input->get_post('alias_user_id');

        $aResult = $this->pammapi->addUserAlias($iUserId, $iAliasUserId);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function update_agents_links()
    {
        $aResult = $this->pammapi->updateAgentsLinks();
        $this->sendToBrowser($aResult);

        return true;
    }

    /* Cron-jobs */

    public function update_drawdowns($iUpdate = 0)
    {
        $aResult = $this->pammapi->updateDrawdowns($iUpdate);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function update_pamm_rating($iForce = 0)
    {
        $aResult = $this->pammapi->updatePammRating($iForce);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function update_pamm_mt4_trades_data()
    {
        $aResult = $this->pammapi->updatePammMT4TradesData();
        $this->sendToBrowser($aResult);

        return true;
    }

    public function update_investors_stat_cache($clear_cache = 0)
    {
        if (is_cli())
        {
            $bClearCache = (bool)$clear_cache;
        }
        else
        {
            $bClearCache = (bool)$this->input->get_post('clear_cache');
        }

        $aResult = $this->pammapi->updateInvestorsStatCache($bClearCache);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function update_pamm_index_tick_stat()
    {
        $aResult = $this->pammapi->updatePammIndexTickStat();
        $this->sendToBrowser($aResult);

        return true;
    }

    public function update_pamm_loss_limit_balances()
    {
        $aResult = $this->pammapi->updatePammLossLimitBalances();
        $this->sendToBrowser($aResult);

        return true;
    }

    public function run_pamm_index_weekend_payments($unixtime = 0)
    {
        $aResult = $this->pammapi->runPammIndexWeekendPayments($unixtime);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function run_pamm_scam_monitoring()
    {
        $aResult = $this->pammapi->runPammScamMonitoring();
        $this->sendToBrowser($aResult);

        return true;
    }

    public function update_pamm_index_acl_mystat()
    {
        $aResult = $this->pammapi->runPammIndexACLMyStat();
        $this->sendToBrowser($aResult);

        return true;
    }

    public function run_pamm_index_mystat_autoclose()
    {
        $aResult = $this->pammapi->runPammIndexMyStatAutoClose();
        $this->sendToBrowser($aResult);

        return true;
    }

    public function run_prerollover_close_pamm_opened_tickets()
    {
        $aResult = $this->pammapi->runPreRolloverClosePammOpenedTickets();
        $this->sendToBrowser($aResult);

        return true;
    }

    /* Daemons */

    public function run_pamm_mt_trades_cron()
    {
        $aResult = $this->pammapi->runPammMTTradesCron();
        $this->sendToBrowser($aResult);

        return true;
    }

    public function run_pamm_ai_trades_cron()
    {
        $aResult = $this->pammapi->runPammAITradesCron();
        $this->sendToBrowser($aResult);

        return true;
    }

    public function run_pamm_index_cron()
    {
        $aResult = $this->pammapi->runPammIndexCron();
        $this->sendToBrowser($aResult);

        return true;
    }

    public function run_pamm_index_add_tick()
    {
        $aResult = $this->pammapi->runPammIndexAddTick();
        $this->sendToBrowser($aResult);

        return true;
    }

    public function run_pamm_loss_limit_cron()
    {
        $aResult = $this->pammapi->runPammLossLimitCron();
        $this->sendToBrowser($aResult);

        return true;
    }

    public function run_pamm_notifications_cron()
    {
        $aResult = $this->pammapi->runPammNotificationsCron();
        $this->sendToBrowser($aResult);

        return true;
    }

    /* Callbacks */

    /**
     * Проверка на существование inv-mt-счета и доступность суммы на счете
     *
     * @param integer $iMTLogin
     * @param double $fAvailableEquity:
     * @return bool
     */
    function CheckMTLoginEquityGreaterOrEqualThan($iMTLogin, $fAvailableEquity)
    {
        $aInvAccountInfo = $this->webactions->getUserInfo($iMTLogin);
        if (empty($aInvAccountInfo) || bccomp($aInvAccountInfo['equity'], $fAvailableEquity, 2) == -1) // $aInvAccountInfo['equity'] < $fAvailableEquity
        {
            return false;
        }

        return true;
    }

    /**
     * Проверка на вхожление значения номера МТ-счета в диапазон партнера
     *
     * @param integer $iMTLogin
     * @return bool
     */
    function CheckMTPartnerRange($iMTLogin)
    {
        return ($iMTLogin >= $this->iMTRangeStart && $iMTLogin <= $this->iMTRangeEnd)?true:false;
    }

    /**
     * Проверка на вхожление значения уровня ответственности
     *
     * @param integer $iResponsibility
     * @return bool
     */
    function CheckResponsibilityRange($iResponsibility)
    {
        return in_array($iResponsibility, array(0, 20, 30, 40, 50, 60, 70, 80, 100));
    }

}

/* End of file pamm.php */
/* Location: ./application/controllers/pamm.php */
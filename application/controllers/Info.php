<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class Pamm
 *
 * @property FT_Form_validation $form_validation
 * @property PammAPI $pammapi
 * @property WebActions $webactions
 * @property CI_URI $uri
 * @property CI_Input $input
 * @property CI_DB_query_builder $db
 * @property FT_Output $output
 */
class Info extends CI_Controller {

    private $sBasicRealm = "PrivateFX InfoAPI Authentication";
    private $sAcceptType = "";
    private $iUserId     = 0;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('form_validation');
        $this->load->library('PammAPI');
        $this->load->library('WebActions');
        $this->pammapi->setPartnerId(1);
        $this->setAcceptType();
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
            case "xml":
                $this->output->set_content_type('application/xml');
                header('Content-Type: application/xml');
                $oXML = new SimpleXMLElement('<root/>');
                $this->to_xml($oXML, $aAnswer);
                echo $oXML->asXML();
                break;
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

    private function setAcceptType()
    {
        $aMimes = $this->output->get_mimes();

        // Detecting Accept type
        $mAccept = $this->input->server('HTTP_ACCEPT');
        if (strpos($mAccept, ';') !== false)
        {
            $mAccept = substr($mAccept, 0, strpos($mAccept, ';'));
        }
        $mAccept = explode(',', $mAccept);
        if (is_array($mAccept))
        {
            foreach ($mAccept as $sAccept)
            {
                foreach ($aMimes as $sType => $mAccept)
                {
                    if ((!is_array($mAccept) && $mAccept == $sAccept) || (is_array($mAccept) && in_array($sAccept, $mAccept)))
                    {
                        $this->sAcceptType = $sType;
                    }
                }
            }
        }

        /*switch ($this->sAcceptType)
        {
            case "json":
                $this->output->set_content_type('application/json');
                header('Content-Type: application/json');
                break;
            case "xml":
                $this->output->set_content_type('application/xml');
                header('Content-Type: application/xml');
                break;
            default:
                break;
        }*/

        return true;
    }

    private function getAcceptType()
    {
        return $this->sAcceptType;
    }

    private function checkBasicAuth()
    {
        $sUser = $this->input->server('PHP_AUTH_USER');
        $sPass = $this->input->server('PHP_AUTH_PW');

        $this->setAcceptType();

        if (!isset($sUser))
        {
            header("WWW-Authenticate: Basic realm=\"{$this->sBasicRealm}\"");
        }
        else if (isset($sUser) && !empty($sPass))
        {
            $this->load->database();

            $sSQL = "SELECT * FROM web.users AS u INNER JOIN web.users_settings AS us ON us.id = u.id AND us.info_api_status = 1 WHERE u.nickname = ? AND us.info_api_key = ?";
            $rQuery = $this->db->query($sSQL, array($sUser, $sPass));
            if ($rQuery->num_rows() > 0)
            {
                foreach ($rQuery->result_array() as $aRow)
                {
                    $this->iUserId = (int)$aRow['id'];
                }

                $aPost = $this->input->post(NULL, TRUE);
                $this->db->set('user_id', $this->iUserId);
                $this->db->set('module', $this->uri->segment(1, ''));
                $this->db->set('version', 2);
                $this->db->set('ip', $_SERVER["REMOTE_ADDR"]);
                $this->db->set('action_name', $this->uri->segment(2, 'index'));
                $this->db->set('action_params', json_encode($aPost));
                $this->db->insert('infoapi_access_log');

                return true;
            }

            header("WWW-Authenticate: Basic realm=\"{$this->sBasicRealm}\"");
        }
        $this->output->set_status_header(401, 'Unauthorized');
        exit('401 Unauthorized');
    }

    public function index()
	{
        return true;
    }

    /* ПАММ-счета */

    public function get_pamm_list()
    {
        $aResult = $this->pammapi->getPammList(1);
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

    /* Инвесторские счета */

    public function get_investor_details()
    {
        $this->checkBasicAuth();

        $this->form_validation->set_rules('investor_id', 'investor_id', 'required|integer');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $iInvestorId= (int)$this->input->get_post('investor_id');

        $aResult = $this->pammapi->getInvestorDetails($this->iUserId, $iInvestorId);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function get_user_investors_info()
    {
        $this->checkBasicAuth();

        $aResult = $this->pammapi->getUserInvestorsInfo($this->iUserId);
        $this->sendToBrowser($aResult);

        return true;
    }

    public function get_investor_tickets_profit_list()
    {
        $this->checkBasicAuth();

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

        $sSQL = "SELECT * FROM pamm_investors WHERE id = ? AND user_id = ? AND partner_id = ?";
        $rQuery = $this->db->query($sSQL, array($iInvestorId, $this->iUserId, 0));
        if ($rQuery->num_rows() > 0)
        {
            $aResult = $this->pammapi->getInvestorTicketsProfitList($iInvestorId, $iPammMTLogin, $iTimeFrom, $iTimeTo, $bForceTimeFrom);
            /*foreach ($aResult as &$aRow)
            {
                unset($aRow['ticket']);
                unset($aRow['ceiling']);
                unset($aRow['pamm_mt_login']);
                unset($aRow['unix_close_time']);
                unset($aRow['total_sum']);
            }*/
            $this->sendToBrowser($aResult);

            return true;
        }

        $this->sendToBrowser(false, false);
        return false;
    }

    /* Распоряжения */

    public function get_money_orders_list()
    {
        $this->checkBasicAuth();

        $this->form_validation->set_rules('investor_id', 'investor_id', 'required|integer');
        if ($this->form_validation->run() == FALSE)
        {
            $this->sendToBrowser(false, false, $this->form_validation->getErrorsArray());
            return false;
        }

        $iInvestorId= (int)$this->input->get_post('investor_id');

        $aResult = $this->pammapi->getMoneyOrdersList(0, $iInvestorId, $this->iUserId);
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

        $sSymbol = $this->input->get_post('symbol');

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

        $sSymbol = $this->input->get_post('symbol');

        $aResult = $this->pammapi->getPammIndexTickStat($sSymbol);
        $this->sendToBrowser($aResult);

        return true;
    }

}

/* End of file pamm.php */
/* Location: ./application/controllers/pamm.php */
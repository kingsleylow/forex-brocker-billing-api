<?php

/**
 * Class FT_Basic_Auth_Controller
 *
 * @property CI_URI $uri
 * @property CI_Input $input
 * @property CI_DB_query_builder $db
 * @property FT_Output $output
 *
 */

class FT_Basic_Auth_Controller extends CI_Controller {

    public $sBasicRealm = "";
    private $sAcceptType = "";
    private $iPartnerId = 0;
    private $iPartnerName = "";
    private $iPartnerMTLogin = 0;
    private $iPartnerAgentMTLogin = 0;
    private $iPartnerIPs = "";
    private $iPartnerStatusURL = "";
    private $aPermissions = array();
    public $iMTRangeStart = 0;
    public $iMTRangeEnd = 0;

    public function __construct()
    {
        parent::__construct();

        $this->load->helper('url');

        if (!is_cli())
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

                $sSQL = "SELECT * FROM pammapi_partners WHERE id = ? AND passkey = ? AND ? REGEXP ips";
                $rQuery = $this->db->query($sSQL, array($sUser, $sPass, $_SERVER["REMOTE_ADDR"]));
                if ($rQuery->num_rows() > 0)
                {
                    foreach ($rQuery->result_array() as $aRow)
                    {
                        $this->iPartnerId = (int)$aRow['id'];
                        $this->iPartnerName = $aRow['name'];
                        $this->iPartnerMTLogin = (int)$aRow['trans_mt_login'];
                        $this->iPartnerAgentMTLogin = (int)$aRow['agent_mt_login'];
                        $this->iPartnerIPs = $aRow['ips'];
                        $this->iPartnerStatusURL = $aRow['status_url'];
                        $this->aPermissions = explode(',', $aRow['pamm_api']);
                        $this->iMTRangeStart = (int)$aRow['mt_range_start'];
                        $this->iMTRangeEnd = (int)$aRow['mt_range_end'];
                    }

                    $aPost = $this->input->post(NULL, TRUE);
                    $this->db->set('partner_id', $this->iPartnerId);
                    $this->db->set('module', $this->uri->segment(1, ''));
                    $this->db->set('version', 2);
                    $this->db->set('ip', $_SERVER["REMOTE_ADDR"]);
                    $this->db->set('action_name', $this->uri->segment(2, 'index'));
                    $this->db->set('action_params', json_encode($aPost));
                    $this->db->insert('pammapi_access_log');

                    return true;
                }

                header('WWW-Authenticate: Basic realm="PAMM API Authentication"');
            }
            $this->output->set_status_header(401, 'Unauthorized');
            exit('401 Unauthorized');
        }

        return true;
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

    public function getAcceptType()
    {
        return $this->sAcceptType;
    }

    public function getPartnerId()
    {
        return $this->iPartnerId;
    }

    public function getPartnerName()
    {
        return $this->iPartnerName;
    }

    public function getPartnerMTLogin()
    {
        return $this->iPartnerMTLogin;
    }

    public function getPartnerAgentMTLogin()
    {
        return $this->iPartnerAgentMTLogin;
    }

    public function getPartnerIPs()
    {
        return $this->iPartnerIPs;
    }

    public function getPartnerStatusURL()
    {
        return $this->iPartnerStatusURL;
    }

    public function getPartnerPermissions()
    {
        return $this->aPermissions;
    }
}

/* End of file FT_Basic_Auth_Controller.php */
/* Location: ./application/core/FT_Basic_Auth_Controller.php */
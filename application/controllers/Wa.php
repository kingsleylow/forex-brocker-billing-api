<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class Wa
 *
 * @property FT_Form_validation $form_validation
 * @property PammAPI $pammapi
 * @property WebActions $webactions
 */
class Wa extends FT_Basic_Auth_Controller {

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

    /* Cron-jobs */

    public function run_webactions_queries_cron()
    {
        $aResult = $this->webactions->runQueriesCron();
        $this->sendToBrowser($aResult);

        return true;
    }

}

/* End of file pamm.php */
/* Location: ./application/controllers/pamm.php */
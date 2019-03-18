<?php
/**
 * Created by PhpStorm.
 * User: Stern87
 * Date: 07.02.14
 * Time: 18:21
 */

class FT_Output extends CI_Output {

    public function __construct()
    {
        parent::__construct();
    }

    public function get_mimes()
    {
        return $this->mimes;
    }

    public function _display($output = '')
    {
        parent::_display($output);

        global $BM;

        $elapsed = $BM->elapsed_time('total_execution_time_start', 'total_execution_time_end');

        if ($elapsed > 20)
        {
            $CI =& get_instance();
            $uri = $CI->uri->uri_string;
            $spost = json_encode($CI->input->post(NULL, TRUE));
            $sUser = $CI->input->server('PHP_AUTH_USER');

            error_log("Total execution time: {$elapsed}; User: {$sUser}; URI: {$uri}; POST: {$spost}");
        }
    }

}
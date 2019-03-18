<?php

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
 * @property CI_Form_validation $form_validation  Form Validation Class
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
 */

class WebActions {

    private $rFAPI = [];   // Массив подключений
    private $MT_host, $MT_port, $MT_master;
    private $db;

    function __construct()
    {
        $CI =& get_instance();
        $CI->load->database();
        $this->db = $CI->db;
        $CI->config->load('webactions');

        $this->MT_host = $CI->config->item('mt4_host');
        $this->MT_port = $CI->config->item('mt4_port');
        $this->MT_master = $CI->config->item('mt4_plugin_master');
    }

    /**
     * Выполнить соединение/реконнект
     *
     * @param   string  $sServerIP
     * @return  boolean
     */
    private function Connect($sServerIP = '')
    {
        if (empty($sServerIP))
        {
            $sServerIP = $this->MT_host;
        }
        if (empty($this->rFAPI[$sServerIP]) || feof($this->rFAPI[$sServerIP]))
        {
            $this->rFAPI[$sServerIP] = @fsockopen($sServerIP, $this->MT_port, $iError, $sError, 10);

            if (!feof($this->rFAPI[$sServerIP]))
            {
                fputs($this->rFAPI[$sServerIP], "W");
            }
        }

        return (!feof($this->rFAPI[$sServerIP]));
    }

    /**
     * Performs request to the server and gets the answer
     *
     * @param   mixed   $mQuery
     * @param   string  $sServerIP
     * @return  mixed
     */
    private function makeRequest($mQuery, $sServerIP = '')
    {
        if (empty($sServerIP))
        {
            $sServerIP = $this->MT_host;
        }

        $aFields = array(
            'query'		=> (is_array($mQuery))?serialize($mQuery):mb_convert_encoding($mQuery, "UTF-8", "Windows-1251"),
            'status'	=> 0,
            'response'	=> '',
        );

        $sReturn = "ERROR\r\nИзвините, функция временно недоступна в связи с техническими работами";
        $aReturn = array();
        $aResponse = array();

        $this->Connect();

        if ($this->rFAPI[$sServerIP])
        {
            if (is_array($mQuery))
            {
                foreach ($mQuery as $sQuery)
                {
                    if (fputs($this->rFAPI[$sServerIP], "$sQuery\n") != false)
                    {
                        $sReturn = "";
                        while (!feof($this->rFAPI[$sServerIP]))
                        {
                            $sLine = fgets($this->rFAPI[$sServerIP], 1024);
                            $sLine = str_replace("\0", "", $sLine);
                            if ($sLine=="end\r\n") break;
                            $sReturn .= $sLine;
                        }
                        $aReturn[] = $sReturn;
                        $aResponse[] = mb_convert_encoding($sReturn, "UTF-8", "Windows-1251");
                    }
                }
            }
            else
            {
                if (fputs($this->rFAPI[$sServerIP], "$mQuery\n") != false)
                {
                    $sReturn = "";
                    while (!feof($this->rFAPI[$sServerIP]))
                    {
                        $sLine = fgets($this->rFAPI[$sServerIP], 1024);
                        $sLine = str_replace("\0", "", $sLine);
                        if ($sLine=="end\r\n") break;
                        $sReturn .= $sLine;
                    }
                    $aResponse[] = mb_convert_encoding($sReturn, "UTF-8", "Windows-1251");
                }
            }
            $aFields['status']		= 1;
            $aFields['response']	= serialize($aResponse);
        }

        //if (strpos($mQuery, "BALANCE-") > 0)
        //{
            $this->db->insert('fapi_queries_log', $aFields);
        //}

        return (is_array($mQuery))?$aReturn:$sReturn;
    }

    /**
     * Провести платеж
     *
     * @param int $iLogin
     * @param float $fValue
     * @param string $sComment
     * @param int $iForce
     * @param int $iUpdateUser

     * @return boolean
     */
    function makePayment($iLogin, $fValue, $sComment, $iForce = 0, $iUpdateUser = 0)
    {
        if (empty($iLogin) || !is_numeric($iLogin) || (empty($fValue) && empty($iForce)))
        {
            trigger_error("WebActions::makePayment incorrect input parameters", E_USER_WARNING);
            return false;
        }

        $sQuery = "CLIENTSCHANGEBALANCE-MASTER={$this->MT_master}|LOGIN={$iLogin}|VALUE={$fValue}|COMMENT={$sComment}|FORCE={$iForce}|UPDATEUSER={$iUpdateUser}";
        $sResult = $this->makeRequest($sQuery);

        $iPos = strpos($sResult, "OK\r\n");

        return ($iPos === false || $iPos != 0)?false:true;
    }

    /**
     * Провести трансфер
     *
     * @param int $iFrom
     * @param int $iTo
     * @param float $fValue
     * @param string $sCommentFrom
     * @param string $sCommentTo
     * @param int $iForce
     * @param int $iUserUpdate
     *
     * @return boolean
     */
    function makeTransfer($iFrom, $iTo, $fValue, $sCommentFrom, $sCommentTo, $iForce = 0, $iUserUpdate = 0)
    {
        if (empty($iFrom) || empty($iTo) || empty($fValue) || !is_numeric($iFrom) || !is_numeric($iTo) || empty($sCommentFrom) || empty($sCommentTo))
        {
            trigger_error("WebActions::makeTransfer incorrect input parameters", E_USER_WARNING);
            return false;
        }

        $sQuery = "CLIENTSTRANSFERBALANCE-MASTER={$this->MT_master}|FROM={$iFrom}|TO={$iTo}|VALUE={$fValue}|COMMENTFROM={$sCommentFrom}|COMMENTTO={$sCommentTo}|FORCE={$iForce}|UPDATEUSER={$iUserUpdate}";
        $sResult = $this->makeRequest($sQuery);

        $iPos = strpos($sResult, "OK\r\n");

        return ($iPos === false || $iPos != 0)?false:true;
    }

    /**
     * Создание нового счета
     *
     * @param string $sGroup
     * @param string $sName
     * @param string $sPassword
     * @param string $sInvestor
     * @param string $sEmail
     * @param string $sCountry
     * @param string $sCity
     * @param string $sAddress
     * @param string $sComment
     * @param string $sPhone
     * @param string $sPhonePassword
     * @param int $iZipcode
     * @param int $iID
     * @param int $iLeverage
     * @param string $sAgent
     * @param int $iEnabled
     * @param int $iIsDemo
     *
     * @return boolean
     */
    function createAccount($sGroup, $sName, $sPassword, $sInvestor, $sEmail, $sCountry, $sCity, $sAddress, $sComment, $sPhone, $sPhonePassword, $iZipcode, $iID, $iLeverage, $sAgent='', $iEnabled=0, $iIsDemo=0)
    {
        if (empty($sGroup) || empty($sName) || empty($sPassword) || empty($sInvestor) || empty($sEmail) || empty($sCountry) || empty($sCity) || empty($sAddress) || empty($sComment) || empty($sPhone)
         || empty($sPhonePassword) || empty($iZipcode) || empty($iID) || empty($iLeverage))
        {
            trigger_error("WebActions::createAccount incorrect input parameters", E_USER_WARNING);
            return false;
        }

        $sIP = $this->input->ip_address();
        $sName = mb_convert_encoding($sName, "windows-1251", "UTF-8");
        $sCountry = mb_convert_encoding($sCountry, "windows-1251", "UTF-8");
        $sCity = mb_convert_encoding($sCity, "windows-1251", "UTF-8");
        $sAddress = mb_convert_encoding($sAddress, "windows-1251", "UTF-8");
        $sComment = mb_convert_encoding($sComment, "windows-1251", "UTF-8");
        $sPhonePassword = mb_convert_encoding($sPhonePassword, "windows-1251", "UTF-8");
        $fDeposit = (!empty($iIsDemo) ? 5000 : 0);

        $sQuery = "CLIENTSADDUSER-MASTER={$this->MT_master}|IP={$sIP}|GROUP={$sGroup}|NAME={$sName}|PASSWORD={$sPassword}|INVESTOR={$sInvestor}|EMAIL={$sEmail}|COUNTRY={$sCountry}|STATE=|CITY={$sCity}|ADDRESS={$sAddress}|"
                . "COMMENT={$sComment}|PHONE={$sPhone}|PHONE_PASSWORD={$sPhonePassword}|STATUS=RE|ZIPCODE={$iZipcode}|ID={$iID}|LEVERAGE={$iLeverage}|AGENT={$sAgent}|SEND_REPORTS=1|DEPOSIT={$fDeposit}|ENABLE={$iEnabled}";

        $sResult = $this->makeRequest($sQuery);
        $aResult = explode("\r\n", $sResult);

        if ($aResult[0]=="OK")
        {
            $aMtLogin = explode("=", $sResult);
            // На всякий случай обновляем и MT4_USERS.
            // Делаем это с помощью INSERT, который отвалится в случае если такой LOGIN в таблице уже есть - т.е. репликация нас опередила.
            $aFields = array(
                'LOGIN'				=> $aMtLogin[1],
                'GROUP'				=> $sGroup,
                'ENABLE'			=> $iEnabled,
                'ENABLE_CHANGE_PASS'=> 1,
                'ENABLE_READONLY'	=> 0,
                'PASSWORD_PHONE'	=> $sPhonePassword,
                'NAME'				=> $sName,
                'COUNTRY'			=> $sCountry,
                'CITY'				=> $sCity,
                'STATE'				=> "",
                'ZIPCODE'			=> $iZipcode,
                'ADDRESS'			=> $sAddress,
                'PHONE'				=> $sPhone,
                'EMAIL'				=> $sEmail,
                'COMMENT'			=> $sComment,
                'ID'				=> $iID,
                'STATUS'			=> "RE",
                'REGDATE'			=> date("Y-m-d H:i:s"),
                'LASTDATE'			=> date("Y-m-d H:i:s"),
                'LEVERAGE'			=> $iLeverage,
                'AGENT_ACCOUNT'		=> $sAgent,
                'TIMESTAMP'			=> time(),
                'BALANCE'			=> $fDeposit,
                'PREVMONTHBALANCE'	=> 0,
                'PREVBALANCE'		=> 0,
                'CREDIT'			=> 0,
                'INTERESTRATE'		=> 0,
                'TAXES'				=> 0,
                'SEND_REPORTS'		=> 1,
                'USER_COLOR'		=> -16777216,
                'EQUITY'			=> 0,
                'MARGIN'			=> 0,
                'MARGIN_LEVEL'		=> 0,
                'MARGIN_FREE'		=> 0,
                'MODIFY_TIME'		=> date("Y-m-d H:i:s", time()-600),	// На всякий случай
            );
            $this->db->insert('MT4_USERS', $aFields);

            return true;
        }

        return false;
    }

    /**
     * Получает развернутую информацию о forex счете без ордеров
     *
     * @param int $iLogin
     * @param int $iForce
     *
     * @return boolean|array
     */
    function getUserInfo($iLogin, $iForce = 0)
    {
        if (empty($iLogin) || !is_numeric($iLogin))
        {
            trigger_error("WebActions::getUserInfo incorrect input parameters", E_USER_WARNING);
            return false;
        }

        if (empty($iForce)) {
            $this->db
                ->select('LOGIN AS login, `GROUP` AS group, ENABLE AS enable, ENABLE_CHANGE_PASS AS enable_change_password, ENABLE_READONLY AS enable_readonly, NAME AS name, COUNTRY AS country, CITY AS city, STATE AS state, ZIPCODE AS zipcode, ADDRESS AS address, PHONE AS phone, EMAIL AS email, COMMENT AS comment, ID AS id, STATUS AS status, REGDATE AS regdate, LASTDATE AS lastdate, LEVERAGE AS leverage, AGENT_ACCOUNT AS agent_account, BALANCE AS balance, MARGIN AS margin, MARGIN_FREE AS free, EQUITY AS equity, PREVMONTHBALANCE AS prevmonthbalance, PREVBALANCE AS prevbalance, CREDIT AS credit, INTERESTRATE AS interestrate, TAXES AS taxes, 0 AS prevmonthequity, 0 AS prevequity, SEND_REPORTS AS send_reports')
                ->from('MT4_USERS')
                ->where('login', $iLogin)
                ->where('MODIFY_TIME > DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 121 MINUTE)');
            $rQuery = $this->db->get();
            if ($rQuery->num_rows() > 0)
            {
                return $rQuery->row_array();
            }
        }

        $sQuery = "CLIENTSUSERINFO-MASTER={$this->MT_master}|LOGIN={$iLogin}";
        $sResult = $this->makeRequest($sQuery);
        $aResult = explode("\r\n", $sResult);
        if ($aResult[0]=="OK")
        {
            $aAccountFields = array(
                'login', 'group', 'enable', 'enable_change_password', 'enable_readonly', 'name', 'country', 'city', 'state', 'zipcode', 'address',
                'phone', 'email', 'comment', 'id', 'status', 'regdate', 'lastdate', 'leverage', 'agent_account', 'balance', 'margin', 'free', 'equity',
                'prevmonthbalance',	'prevbalance', 'credit', 'interestrate', 'taxes', 'prevmonthequity', 'prevequity', 'send_reports'
            );
            array_shift($aResult);
            array_pop($aResult);
            $aAccountInfo = array_combine($aAccountFields, $aResult);
            $aAccountInfo['name']	= iconv('windows-1251', 'UTF-8', $aAccountInfo['name']);
            $aAccountInfo['country']= iconv('windows-1251', 'UTF-8', $aAccountInfo['country']);
            $aAccountInfo['city']	= iconv('windows-1251', 'UTF-8', $aAccountInfo['city']);
            $aAccountInfo['state']	= iconv('windows-1251', 'UTF-8', $aAccountInfo['state']);
            $aAccountInfo['address']= iconv('windows-1251', 'UTF-8', $aAccountInfo['address']);
            $aAccountInfo['comment']= iconv('windows-1251', 'UTF-8', $aAccountInfo['comment']);

            $aFields = array(
                'LOGIN'				=> $aAccountInfo['login'],
                'GROUP'				=> $aAccountInfo['group'],
                'ENABLE'			=> $aAccountInfo['enable'],
                'ENABLE_CHANGE_PASS'=> $aAccountInfo['enable_change_password'],
                'ENABLE_READONLY'	=> $aAccountInfo['enable_readonly'],
                'PASSWORD_PHONE'	=> "",
                'NAME'				=> $aAccountInfo['name'],
                'COUNTRY'			=> $aAccountInfo['country'],
                'CITY'				=> $aAccountInfo['city'],
                'STATE'				=> $aAccountInfo['state'],
                'ZIPCODE'			=> $aAccountInfo['zipcode'],
                'ADDRESS'			=> $aAccountInfo['address'],
                'PHONE'				=> $aAccountInfo['phone'],
                'EMAIL'				=> $aAccountInfo['email'],
                'COMMENT'			=> $aAccountInfo['comment'],
                'ID'				=> $aAccountInfo['id'],
                'STATUS'			=> $aAccountInfo['status'],
                'REGDATE'			=> $aAccountInfo['regdate'],
                'LASTDATE'			=> $aAccountInfo['lastdate'],
                'LEVERAGE'			=> $aAccountInfo['leverage'],
                'AGENT_ACCOUNT'		=> $aAccountInfo['agent_account'],
                'TIMESTAMP'			=> time(),
                'BALANCE'			=> $aAccountInfo['balance'],
                'PREVMONTHBALANCE'	=> $aAccountInfo['prevmonthbalance'],
                'PREVBALANCE'		=> $aAccountInfo['prevbalance'],
                'CREDIT'			=> $aAccountInfo['credit'],
                'INTERESTRATE'		=> $aAccountInfo['interestrate'],
                'TAXES'				=> $aAccountInfo['taxes'],
                'SEND_REPORTS'		=> $aAccountInfo['send_reports'],
                'USER_COLOR'		=> -16777216,
                'EQUITY'			=> $aAccountInfo['equity'],
                'MARGIN'			=> $aAccountInfo['margin'],
                'MARGIN_LEVEL'		=> ($aAccountInfo['margin'] != '0.00' ? ($aAccountInfo['equity'] / $aAccountInfo['margin'] * 100) : 0),
                'MARGIN_FREE'		=> $aAccountInfo['free'],
                'MODIFY_TIME'		=> date("Y-m-d H:i:s", time()),	// На всякий случай
            );
            $this->db->replace('MT4_USERS', $aFields);

            return $aAccountInfo;
        }

        return false;
    }

    /**
     * Проверка пароля
     *
     * @param int $iLogin
     * @param string $sPassword
     * @param int $iInvestor
     *
     * @return boolean
     */
    function сheckPass($iLogin, $sPassword, $iInvestor = 0)
    {
        if (empty($iLogin) || !is_numeric($iLogin) || empty($sPassword))
        {
            trigger_error("WebActions::checkPass incorrect input parameters", E_USER_WARNING);
            return false;
        }

        $sQuery = "CLIENTSCHECKPASS-MASTER={$this->MT_master}|LOGIN={$iLogin}|PASSWORD={$sPassword}|INVESTOR={$iInvestor}";
        $sResult = $this->makeRequest($sQuery);
        $aResult = explode("\r\n", $sResult);

        return ($aResult[0]=="OK");
    }

    /**
     * Замена пароля
     *
     * @param int $iLogin
     * @param string $sPassword
     * @param int $iInvestor
     *
     * @return boolean
     */
    function сhangePass($iLogin, $sPassword, $iInvestor = 0)
    {
        if (empty($iLogin) || !is_numeric($iLogin) || empty($sPassword))
        {
            trigger_error("WebActions::changePass incorrect input parameters", E_USER_WARNING);
            return false;
        }

        $sQuery = "CLIENTSCHANGEPASS-MASTER={$this->MT_master}|LOGIN={$iLogin}|PASSWORD={$sPassword}|INVESTOR={$iInvestor}|DROPKEY=0";
        $sResult = $this->makeRequest($sQuery);
        $aResult = explode("\r\n", $sResult);

        return ($aResult[0]=="OK");
    }

    /**
     * Обновление данных клиента
     *
     * @param int $iLogin
     * @param string $sUpdates
     *
     * @return bool
     */
    function updateUser($iLogin, $sUpdates)
    {
        if (!isset($iLogin) || !is_numeric($iLogin) || !isset($sUpdates) || strlen($sUpdates)==0)
        {
            trigger_error("WebActions::updateUser incorrect input parameters", E_USER_WARNING);
            return false;
        }

        $sQuery = "CLIENTSUSERUPDATE-MASTER={$this->MT_master}|LOGIN={$iLogin}|{$sUpdates}";
        $sResult = $this->makeRequest($sQuery);
        $aResult = explode("\r\n", $sResult);

        return ($aResult[0]=="OK");
    }

    /**
     * Получение маржевых данных счета
     *
     * @param int $iLogin
     *
     * @return array:
     * 		float margin маржа
     * 		float free_margin (средства-маржа)
     * 		float equity средства
     */
    function getTradesMarginInfo($iLogin)
    {
        if (empty($iLogin) || !is_numeric($iLogin))
        {
            trigger_error("WebActions::getTradesMarginInfo incorrect input parameters", E_USER_WARNING);
            return false;
        }

        $sQuery = "TRADESMARGININFO-MASTER={$this->MT_master}|LOGIN={$iLogin}";
        $sResult = $this->makeRequest($sQuery);
        $aResult = explode("\r\n", $sResult);
        if ($aResult[0]=="OK")
        {
            $aTradesMarginInfo = array(
                'margin'    => $aResult[1],
                'free'      => $aResult[2],
                'equity'    => $aResult[3],
            );

            // Обновляем данные в базе данных
            $aFields = array(
                'EQUITY'        => $aTradesMarginInfo['equity'],
                'MARGIN'        => $aTradesMarginInfo['margin'],
                'MARGIN_FREE'   => $aTradesMarginInfo['free'],
            );
            $this->db->update('MT4_USERS', $aFields, array('LOGIN' => $iLogin));

            return $aTradesMarginInfo;
        }

        return false;
    }

    function addOrder($iLogin, $iCmd, $iVolume, $iTaxes, $sSymbol, $iOriginalTicket, $sComment = "")
    {
        if (empty($iLogin) || !is_numeric($iLogin) || !is_numeric($iCmd) || empty($iVolume) || !is_numeric($iVolume) ||
            empty($iTaxes) || !is_numeric($iTaxes) || empty($iOriginalTicket) || !is_numeric($iOriginalTicket) || empty($sSymbol))
        {
            trigger_error("WebActions::addOrder incorrect input parameters", E_USER_WARNING);
            return false;
        }
        $sQuery = "CLIENTSADDORDER-MASTER={$this->MT_master}|LOGIN={$iLogin}|CMD={$iCmd}|VOLUME={$iVolume}|TAXES={$iTaxes}|SYMBOL={$sSymbol}|ORIGINALTICKET={$iOriginalTicket}|COMMENT={$sComment}";
        $sResult = $this->makeRequest($sQuery);
        $aResult = explode("\r\n", $sResult);
        if ($aResult[0] == "OK")
        {
            return $aResult[1];
        }

        return false;
    }

    function updateOrder($iTicket, $iUpdateMode = 0, $iTo = 0, $iRecycle = 0, $iFixBalance = 0)
    {
        if (empty($iTicket) || !is_numeric($iTicket) || !is_numeric($iTo) || !is_numeric($iRecycle) ||
            !is_numeric($iFixBalance) || !is_numeric($iUpdateMode))
        {
            trigger_error("WebActions::addOrder incorrect input parameters", E_USER_WARNING);
            return false;
        }
        $sQuery = "CLIENTSUPDATEORDER-MASTER={$this->MT_master}|TICKET={$iTicket}|TO={$iTo}|RECYCLE={$iRecycle}|FIXBALANCE={$iFixBalance}|UPDATEMODE={$iUpdateMode}";
        $sResult = $this->makeRequest($sQuery);
        $aResult = explode("\r\n", $sResult);

        return ($aResult[0] == "OK");
    }

    /**
     * Получение списка открытых сделок клиента
     *
     * @param int $iLogin
     *
     * @return array
     */
    function getOpenedTrades($iLogin)
    {
        if (empty($iLogin) || !is_numeric($iLogin))
        {
            trigger_error("WebActions::getOpenedTrades incorrect input parameters", E_USER_WARNING);
            return false;
        }

        $sQuery = "GETOPENEDORDERS-MASTER={$this->MT_master}|LOGIN={$iLogin}";
        $sResult = $this->makeRequest($sQuery);
        $aResult = explode("\r\n", $sResult);
        if ($aResult[0] == "OK")
        {
            $aTrades = array();
            // Выкидываем из массива ненужное
            array_shift($aResult);
            array_pop($aResult);

            // Составляем фоторобот
            $aTradeFields = array(
                'order', 'login', 'symbol', 'digits', 'cmd', 'volume', 'open_time', 'open_price', 'sl', 'tp', 'close_time', 'expiration', 'conv_rates0',
                'conv_rates1', 'commission', 'commission_agent', 'storage', 'close_price', 'profit', 'taxes', 'comment', 'margin_rate', 'reserved0', 'reserved1', 'reserved2', 'reserved3',
            );

            // Проходимся по всем строкам
            foreach ($aResult as $sTrade)
            {
                $aTrade = explode("\r\n", str_replace("|", "\r\n", $sTrade));
                $aTrade = array_combine($aTradeFields, $aTrade);
                $aTrade['comment'] = mb_convert_encoding($aTrade['comment'], 'UTF-8', 'windows-1251');
                $aTrades[] = $aTrade;
            }

            return $aTrades;
        }

        return false;
    }

    /**
     * Получение количества открытых сделок клиента
     *
     * @param int $iLogin
     *
     * @return array
     */
    function getOpenedTradesCount($iLogin)
    {
        if (empty($iLogin) || !is_numeric($iLogin))
        {
            trigger_error("WebActions::getOpenedTradesCount incorrect input parameters", E_USER_WARNING);
            return false;
        }

        $sQuery = "GETOPENEDORDERSCOUNT-MASTER={$this->MT_master}|LOGIN={$iLogin}";
        $sResult = $this->makeRequest($sQuery);
        $aResult = explode("\r\n", $sResult);
        if ($aResult[0] == "OK")
        {
            $aOpenedTradesCount = array(
                'total'     => $aResult[1],
                'opened'    => $aResult[2],
                'pending'   => $aResult[3],
            );
            return $aOpenedTradesCount;
        }

        return false;
    }

    /**
     * Получение списка закрытых сделок клиента за указанный период времени
     *
     * @param int $iLogin
     * @param int $iTimeFrom
     * @param int $iTimeTo
     *
     * @return array
     */
    function getClosedTrades($iLogin, $iTimeFrom, $iTimeTo)
    {
        if (empty($iLogin) || !is_numeric($iLogin) || !isset($iTimeFrom) || !is_numeric($iTimeFrom) || empty($iTimeTo) || !is_numeric($iTimeTo))
        {
            trigger_error("WebActions::getClosedTrades incorrect input parameters", E_USER_WARNING);
            return false;
        }

        $sQuery = "GETCLOSEDORDERSBYTIME-MASTER={$this->MT_master}|LOGIN={$iLogin}|TIMEFROM={$iTimeFrom}|TIMETO={$iTimeTo}";
        $sResult = $this->makeRequest($sQuery);
        $aResult = explode("\r\n", $sResult);
        if ($aResult[0] == "OK")
        {
            $aTrades = array();
            // Выкидываем из массива ненужное
            array_shift($aResult);
            array_pop($aResult);

            // Составляем фоторобот
            $aTradeFields = array(
                'order', 'login', 'symbol', 'digits', 'cmd', 'volume', 'open_time', 'open_price', 'sl', 'tp', 'close_time', 'expiration', 'conv_rate1',
                'conv_rate2', 'commission', 'commission_agent', 'storage', 'close_price', 'profit', 'taxes', 'comment', 'margin_rate',
            );
            // Проходимся по всем строкам
            foreach ($aResult as $sTrade)
            {
                $aTrade = explode("\r\n", str_replace("|", "\r\n", $sTrade));
                $aTrade = array_combine($aTradeFields, $aTrade);
                $aTrade['comment'] = mb_convert_encoding($aTrade['comment'], 'UTF-8', 'windows-1251');
                $aTrades[] = $aTrade;
            }

            return $aTrades;
        }

        return false;
    }

    /**
     * Обновление списка закрытых сделок клиента в репликации за указанный период времени
     *
     * @param int $iLogin
     * @param int $iTimeFrom
     * @param int $iTimeTo
     *
     * @return int|bool
     */
    function resyncClosedTrades($iLogin, $iTimeFrom, $iTimeTo)
    {
        if (empty($iLogin) || !is_numeric($iLogin) || !isset($iTimeFrom) || !is_numeric($iTimeFrom) || empty($iTimeTo) || !is_numeric($iTimeTo))
        {
            trigger_error("WebActions::getClosedTrades incorrect input parameters", E_USER_WARNING);
            return false;
        }

        $sQuery = "RESYNCCLIENTSCLOSEDORDERS-MASTER={$this->MT_master}|LOGIN={$iLogin}|TIMEFROM={$iTimeFrom}|TIMETO={$iTimeTo}";
        $sResult = $this->makeRequest($sQuery);
        $aResult = explode("\r\n", $sResult);
        if ($aResult[0] == "OK")
        {
            return $aResult[1];
        }

        return false;
    }

    /**
     * Закрывает сделки клиента
     *
     * @param int $iLogin
     * @param int $iDeletePending
     *
     * @return boolean
     */
    function closeOrders($iLogin, $iDeletePending=0)
    {
        if (empty($iLogin)	|| !is_numeric($iLogin))
        {
            trigger_error("WebActions::closeOrders incorrect input parameters", E_USER_WARNING);
            return false;
        }

        $sQuery = "CLOSECLIENTSORDERS-MASTER={$this->MT_master}|LOGIN={$iLogin}|DELETE_PENDING={$iDeletePending}";
        $sResult = $this->makeRequest($sQuery);
        $aResult = explode("\r\n", $sResult);

        return ($aResult[0] == "OK");
    }

    /**
     * Получение списка закрытых сделок клиента за указанный период времени
     *
     * @param int $iLogin
     * @param int $iTimeFrom
     * @param int $iTimeTo
     *
     * @return array
     */
    function getDetailedStatement($iLogin, $iTimeFrom, $iTimeTo)
    {
        if (empty($iLogin) || !is_numeric($iLogin) || !isset($iTimeFrom) || !is_numeric($iTimeFrom) || empty($iTimeTo) || !is_numeric($iTimeTo))
        {
            trigger_error("WebActions::getDetailedStatement incorrect input parameters", E_USER_WARNING);
            return false;
        }

        $sQuery = "GETCLIENTSSTATEMENT-MASTER={$this->MT_master}|LOGIN={$iLogin}|TIMEFROM={$iTimeFrom}|TIMETO={$iTimeTo}";
        $sResult = $this->makeRequest($sQuery);
        $aResult = explode("\r\n", $sResult);
        if ($aResult[0] == "OK")
        {
            // Выкидываем из массива ненужное
            array_shift($aResult);
            array_pop($aResult);

            // Составляем фоторобот
            $aStatementKeys = array(
                'initial_deposit',
                'summary_profit',
                'gross_profit',
                'gross_loss',
                'profit_factor',
                'expected_payoff',
                'absolute_drawdown',
                'max_drawdown',
                'max_drawdown_percent',
                'rel_drawdown_percent',
                'rel_drawdown',
                'summary_trades',
                'short_trades',
                'short_trades_won',
                'long_trades',
                'long_trades_won',
                'profit_trades',
                'profit_trades_total',
                'loss_trades',
                'loss_trades_total',
                'max_profit',
                'min_profit',
                'avg_profit_trades',
                'avg_loss_trades',
                'con_profit_trades1',
                'con_profit1',
                'con_loss_trades1',
                'con_loss1',
                'con_profit2',
                'con_profit_trades2',
                'con_loss2',
                'con_loss_trades2',
                'avg_con_winners',
                'avg_con_losers',
                'sum_deposit',
                'sum_withdrawal',
                'max_drawdown_ft',
            );

            return array_combine($aStatementKeys, $aResult);
        }

        return false;
    }

    /**
     * Отправляет почту по внутренней почтовой системе MT
     *
     * @param int $iSender
     * @param int $iReceiveTime
     * @param string $sSubject
     * @param int $iReaded
     * @param string $iLogin
     * @param string $sGroup
     * @param string $sBody
     * @param string $sSender
     *
     * @return boolean
     */
    function sendMail($iSender, $iReceiveTime, $sSubject, $iReaded = 0, $iLogin = '', $sGroup = '', $sBody = '', $sSender = '')
    {
        if (empty($iSender) || !is_numeric($iSender) || (empty($iLogin) && empty($sGroup)) || empty($sSubject) || empty($sBody))
        {
            trigger_error("WebActions::sendMail incorrect input parameters", E_USER_WARNING);
            return false;
        }
        $iReceiveTime = !empty($iReceiveTime) ? $iReceiveTime : time() + 10;

        $sQuery = "MAILSEND-MASTER={$this->MT_master}|RECEIVE_TIME={$iReceiveTime}|SENDER={$iSender}|SENDER_DESCRIPTION={$sSender}|LOGIN={$iLogin}|SUBJECT={$sSubject}|READED={$iReaded}|GROUP={$sGroup}|BODY={$sBody}";
        $sResult = $this->makeRequest($sQuery);
        $aResult = explode("\r\n", $sResult);

        return ($aResult[0] == "OK");
    }

    function getAllGroups()
    {
        $sQuery = "GROUPSALL-MASTER={$this->MT_master}";
        $sResult = $this->MakeRequest(array("query" => $sQuery));
        $aGroups = explode("\r\n", trim($sResult));
        array_shift($aGroups);

        return $aGroups;
    }

    function addIndexTick($sSymbol, $fBid, $fAsk)
    {
        if (empty($sSymbol) || empty($fBid) || empty($fAsk) || !is_numeric($fBid) || !is_numeric($fAsk))
        {
            trigger_error("WebActions::addIndexTick incorrect input parameters", E_USER_WARNING);
            return false;
        }

        $sQuery = "ADDTICK-MASTER=asdasd22|SYMBOL={$sSymbol}|BID={$fBid}|ASK={$fAsk}";
        $sResult = $this->makeRequest($sQuery);

        $iPos = strpos($sResult, "OK\r\n");

        return ($iPos === false || $iPos != 0)?false:true;
    }

    function updateIndexPluginSettings($sName, $sValue='')
    {
        if (empty($sName) || strlen($sValue) == 0)
        {
            trigger_error("WebActions::updateIndexPluginSettings incorrect input parameters", E_USER_WARNING);
            return false;
        }

        $sQuery = "PAMMINDEXSETTINGSCHANGE-MASTER=asdasd22|NAME={$sName}|VALUE={$sValue}";
        $sResult = $this->makeRequest($sQuery);

        $iPos = strpos($sResult, "OK\r\n");

        return ($iPos === false || $iPos != 0)?false:true;
    }

    /**
     * Крон по обработке отложенных запросов
     *
     * @return bool
     */
    function runQueriesCron()
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_implicit_flush();

        $rQuery = $this->db->select('pid')->from('pamm_idx_processes')->where('process_name', 'wa_queries_dispatcher')->get();
        $iPid = ($rQuery->num_rows() > 0)?$rQuery->row()->pid:0;
        if (!empty($iPid))
        {
            $iPid = intval($iPid);
            if (posix_kill($iPid, 0))
            {
                exit("WebActionsQueriesCron: WebActionsQueriesCron is already running");
            }
        }

        $iPid = posix_getpid();
        $this->db->query("INSERT INTO pamm_idx_processes (process_name, pid) VALUES ('wa_queries_dispatcher', {$iPid}) ON DUPLICATE KEY UPDATE pid = {$iPid}");

        while (1)
        {
            $this->db
                ->select('*')
                ->from('fapi_queries_dispatcher')
                ->where('status', 0)
                ->order_by('id', 'ASC')
                ->limit(2000);
            $rQuery = $this->db->get();
            //$this->prn($this->db->last_query());
            if ($rQuery->num_rows() > 0)
            {
                $aRows = $rQuery->result_array();
                //$this->prn($aRows);
                foreach ($aRows as $aRow)
                {
                    $sResult = $this->makeRequest($aRow['query'], $aRow['server_ip']);
                    $iPos = strpos($sResult, "OK\r\n");

                    if ($iPos === 0)
                    {
                        $this->db->query("UPDATE fapi_queries_dispatcher SET status = 1, process_date = NOW() WHERE id = {$aRow['id']}");
                        //$this->prn("UPDATED");
                    }

                    $rQuery = $this->db->select('pid')->from('pamm_idx_processes')->where('process_name', 'wa_queries_dispatcher')->get();
                    $iPid = ($rQuery->num_rows() > 0)?$rQuery->row()->pid:0;
                    if ($iPid != posix_getpid())
                    {
                        // Уходим из процессов
                        $this->db->where('process_name', 'wa_queries_dispatcher');
                        $this->db->delete('pamm_idx_processes');
                        exit;
                    }
                }
            }

            $rQuery = $this->db->select('pid')->from('pamm_idx_processes')->where('process_name', 'wa_queries_dispatcher')->get();
            $iPid = ($rQuery->num_rows() > 0)?$rQuery->row()->pid:0;
            if ($iPid != posix_getpid())
            {
                // Уходим из процессов
                $this->db->where('process_name', 'wa_queries_dispatcher');
                $this->db->delete('pamm_idx_processes');
                exit;
            }
            sleep(7);
        }

        return true;
    }

    /* Разное */

    /**
     * Отображение дебаг-информации
     *
     * @return bool
     */
    function prn()
    {
        $args = func_get_args();
        $last = array_slice(debug_backtrace(), 0, 1);
        $last = array_pop($last);
        $current_date = date("d.m.Y H:i:s");

        $html_data_tpl = '';

        foreach($args as $arg)
        {
            $html_data_tpl .= "--\n" . trim(var_export($arg, true)) . "\n";
        }

        if (is_cli())
        {
            $html_main_tpl = "Called from {$last['file']} in line {$last['line']} ({$current_date})\n{$html_data_tpl}\n\n";

            $aFields = array(
                'message'   => $html_main_tpl,
            );
            $this->db->insert('rollover_log', $aFields);
        }
        else
        {
            $html_main_tpl = '<div style="background-color: #EEE; border: 1px solid black; padding-left: 15px;">'."\n";
            $html_main_tpl .= "<pre>\nCalled from <b>{$last['file']}</b> in line <b>{$last['line']}</b> <i>({$current_date})</i>\n{$html_data_tpl}</pre>\n</div>";
        }

        echo $html_main_tpl;

        unset($args);
        unset($last);

        return true;
    }
}
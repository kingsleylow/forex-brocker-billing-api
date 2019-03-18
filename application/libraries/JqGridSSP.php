<?php

/**
 * Class jqGridSSP
 *
 * @property CI_DB_query_builder $db
 *
 */

class jqGridSSP {

    private $db;
    private $bIsAjax;
    private $sName;
    private $sQuery;
    private $iCountMethod;
    private $aProperties = array(
        'mtype'         => "POST",
        'datatype'      => "json",
        'rowNum'        => 20,
        'rowList'       => array(10, 20, 30),
        'viewrecords'   => true,
        'sortorder'     => 'asc',
    );
    private $aColNames  = array();
    private $aColModels = array();
    private $aFormatters= array();
    private $sWhere     = "";
    private $sHaving    = "";

    function __construct()
    {
        $CI =& get_instance();
        $CI->load->database();
        $this->db = $CI->db;

        $this->bIsAjax = $CI->input->is_ajax_request();

        $CI->load->helper('url');
        $this->aProperties['url'] = current_url();

        return true;
    }

    public function setName($sName)
    {
        $this->sName = $sName;
        $this->aProperties['pager'] = "#{$sName}_pager";
        return true;
    }

    public function setQuery($sQuery)
    {
        //$this->sQuery = "SELECT SQL_CALC_FOUND_ROWS ".substr($sQuery, 7);
        $this->sQuery = $sQuery;
        return true;
    }

    public function setCountMethod($sName)
    {
        switch ($sName)
        {
            case "COUNT(*)":
                $this->iCountMethod = 2;
                break;
            case "SQL_CALC_FOUND_ROWS":
            default:
                $this->iCountMethod = 1;
                break;
        }
    }

    public function setProperty($sName, $sValue)
    {
        $this->aProperties[$sName] = $sValue;
        return true;
    }

    public function addColumn($sName, $sTitle, $sType='text', $iWidth=120, $aSelectOpts=null, $sFormatter='')
    {
        $sField = $sName;
        $iPos = strpos($sName, '.');
        if ($iPos > 0)
        {
            $sName = substr($sName, $iPos+1);
        }
        $aColumn = array(
            'name'      => $sName,
            'field'     => $sField,
            'id'        => $sName,
            'width'     => $iWidth,
            'edittype'  => $sType,
            'sorttype'  => $sType,
        );
        if (!empty($sFormatter))
        {
            $this->aFormatters[] = $sFormatter;
            $aColumn['formatter'] = "%formatter".(count($this->aFormatters)-1)."%";
        }
        switch ($sType)
        {
            case 'text':
                $aColumn['searchoptions'] = array('sopt' => array("cn"));
                break;
            case 'integer':
                $aColumn['align'] = "right";
                $aColumn['searchoptions'] = array('sopt' => array('eq','ne','le','lt','gt','ge'));
                break;
            case 'number':
                $aColumn['align'] = "right";
                $aColumn['formatter'] = "number";
                $aColumn['formatoptions'] = array('decimalSeparator' => '.', 'thousandsSeparator' => " ");
                $aColumn['searchoptions'] = array('sopt' => array('eq','ne','le','lt','gt','ge'));
                break;
            case 'datetime':
                $aColumn['align'] = "right";
                $aColumn['searchoptions'] = array('sopt' => array('cn','nc','le','lt','gt','ge'), 'dataInit' => '%datetime_searchoptions_dataInit%');
                break;
            case 'select':
                $sSelectOpts = ":Все";
                foreach ($aSelectOpts as $sKey => $sOption)
                {
                    $sSelectOpts .= ";{$sKey}:{$sOption}";
                }
                $aColumn['align'] = "right";
                $aColumn['stype'] = "select";
                $aColumn['formatter'] = "select";
                $aColumn['searchoptions'] = array('sopt' => array('eq','ne'), 'value' => $sSelectOpts);
                $aColumn['editoptions'] = array('value' => $sSelectOpts);
                break;
            default:
                $aColumn['searchoptions'] = array('sopt' => array("cn"));
                break;
        }
        $this->aColNames[] = $sTitle;
        $this->aColModels[] = $aColumn;
        return true;
    }

    public function addWhere($sWhere)
    {
        $this->sWhere = $sWhere;
        return true;
    }

    public function addHaving($sHaving)
    {
        $this->sHaving = $sHaving;
        return true;
    }

    public function getOutput()
    {
        $aRequest = ($_SERVER['REQUEST_METHOD']=="POST")?$_POST:$_GET;

        if (!empty($this->bIsAjax) || !empty($aRequest['oper']))
        {
            $iPage      = intval($aRequest['page']); // get the requested page
            $iLimit     = intval($aRequest['rows']); // get how many rows we want to have into the grid
            $iStart     = $iLimit*$iPage - $iLimit;
            $sIndex     = $this->aProperties['sortname']; // get index row - i.e. user click to sort
            foreach ($this->aColModels as $aModel)
            {
                if (strcmp($aModel['name'], $aRequest['sidx'])==0)
                {
                    $sIndex = $aModel['name'];
                }
            }
            $sOrder     = (strcasecmp($aRequest['sord'], "asc")==0 || strcasecmp($aRequest['sord'], "desc")==0)?strtoupper($aRequest['sord']):"ASC"; // get the direction
            $aFilters   = json_decode(@$aRequest['filters'], true);
            $sGroupOp   = (strcasecmp($aFilters['groupOp'], "and")==0 || strcasecmp($aFilters['groupOp'], "or")==0)?" {$aFilters['groupOp']} ":" AND ";
            $aWhere     = array();
            $sWhere     = "";
            $aHaving    = array();
            $sHaving    = "";
            if (!empty($aFilters))
            {
                foreach ($aFilters['rules'] as $aRule)
                {
                    $bFound = false;
                    $sType  = "";
                    $sField = false;
                    $bSimple= false;
                    foreach ($this->aColModels as $aModel)
                    {
                        if (strcmp($aModel['name'], $aRule['field'])==0)
                        {
                            $bFound = true;
                            $sType  = $aModel['edittype'];
                            $sField = $aModel['field'];
                            $bSimple= !(strcmp($aModel['name'], $aModel['field'])==0);
                        }
                    }
                    if ($bFound)
                    {
                        $sCondition = "{$sField} ";
                        switch ($aRule['op'])
                        {
                            case 'eq':  // equal
                                $sCondition .= '= %data%';
                                break;
                            case 'ne':  // not equal
                                $sCondition .= '!= %data%';
                                break;
                            case 'lt':  // less
                                $sCondition .= '< %data%';
                                break;
                            case 'le':  // less or equal
                                $sCondition .= '<= %data%';
                                break;
                            case 'gt':  // greater
                                $sCondition .= '> %data%';
                                break;
                            case 'ge':  // greater or equal
                                $sCondition .= '>= %data%';
                                break;
                            case 'bw':  // begins with
                                $sCondition .= 'LIKE "%data%%"';
                                break;
                            case 'bn':  // does not begin with
                                $sCondition .= 'NOT LIKE "%data%%"';
                                break;
                            case 'in':  // is in
                                $sCondition .= 'IN (%data%)';
                                break;
                            case 'ni':  // is not in
                                $sCondition .= 'NOT IN (%data%)';
                                break;
                            case 'ew':  // ends with
                                $sCondition .= 'LIKE "%%data%"';
                                break;
                            case 'en':  // does not end with
                                $sCondition .= 'NOT LIKE "%%data%"';
                                break;
                            case 'cn':  // contains
                                $sCondition .= 'LIKE "%data%"';
                                break;
                            case 'nc':  // does not contain
                                $sCondition .= 'NOT LIKE "%data%"';
                                break;
                            default:
                                $sCondition .= 'LIKE "%data%"';
                                break;
                        }
                        switch ($sType)
                        {
                            case 'text':
                                $sData = $this->db->escape_like_str($aRule['data']);
                                break;
                            case 'integer':
                                $sData = intval($aRule['data']);
                                break;
                            case 'number':
                                $sData = floatval($aRule['data']);
                                break;
                            case 'datetime':
                                $sData = '"'.$this->db->escape_like_str($aRule['data']).'"';
                                break;
                            case 'select':
                                $sData = intval($aRule['data']);
                                break;
                            default:
                                $sData = $this->db->escape_like_str($aRule['data']);
                                break;
                        }
                        $sCondition = str_replace('%data%', $sData, $sCondition);

                        if (!$bSimple)
                        {
                            $aHaving[]  = $sCondition;
                        }
                        else
                        {
                            $aWhere[]   = $sCondition;
                        }
                    }
                }
                if (!empty($aHaving))
                {
                    $sHaving = implode($sGroupOp, $aHaving);
                }
                if (!empty($aWhere))
                {
                    $sWhere = implode($sGroupOp, $aWhere);
                }
            }

            // Creating HAVING
            if (!empty($this->sHaving))
            {
                $sHaving = (!empty($sHaving))?"({$this->sHaving}) AND ({$sHaving})":$this->sHaving;
            }
            if (!empty($sHaving))
            {
                $sHaving = "HAVING {$sHaving}";
            }

            // Creating WHERE
            if (!empty($this->sWhere))
            {
                $sWhere = (!empty($sWhere))?"({$this->sWhere}) AND ({$sWhere})":$this->sWhere;
            }
            if (!empty($sWhere))
            {
                $sWhere = "WHERE {$sWhere}";
            }

            // Creating ORDER BY
            $sOrderBy = "ORDER BY {$sIndex} {$sOrder}";

            // Creating LIMIT
            $sLimit = "LIMIT {$iStart}, {$iLimit}";

            if (!empty($aRequest['oper']))
            {
                $sLimit = "";
            }

            if ($this->iCountMethod == 1)
            {
                $sQuery = "SELECT SQL_CALC_FOUND_ROWS ".substr($this->sQuery, 7);
            }
            else
            {
                $sQuery = $this->sQuery;
            }
            $rQuery = $this->db->query("{$sQuery} {$sWhere} {$sHaving} {$sOrderBy} {$sLimit}");
            //var_dump($this->db->last_query());
            $aData = $rQuery->result_array();

            if (!empty($aRequest['oper']))
            {
                switch ($aRequest['oper'])
                {
                    case 'csv':
                    default:
                        header('Content-Encoding: UTF-8');
                        header("Content-type: text/csv; charset=UTF-8");
                        header("Content-Disposition: attachment; filename=export.csv");
                        header("Pragma: no-cache");
                        header("Expires: 0");
                        echo "\xEF\xBB\xBF";
                        echo implode(";", $this->aColNames)."\r\n";
                        foreach ($aData as $aRow)
                        {
                            echo str_replace(array("\r\n", "\n"), " ", implode(";", $aRow))."\r\n";
                        }
                        exit();
                }
            }

            // Data set length after filtering
            if ($this->iCountMethod == 1)
            {
                $rQuery = $this->db->query("SELECT FOUND_ROWS() AS total");
            }
            else
            {
                $iPos = strpos($sQuery, $this->aColModels[count($this->aColModels)-1]['name']."`\nFROM ");
                if ($iPos == false)
                {
                    $iPos = strpos($sQuery, $this->aColModels[count($this->aColModels)-1]['name']."\nFROM ");
                }
                $sQuery = "SELECT COUNT(*) AS total ".substr($sQuery, $iPos+strlen($this->aColModels[count($this->aColModels)-1]['name'])+1)." ";
                $rQuery = $this->db->query("{$sQuery} {$sWhere} {$sHaving}");
            }
            $recordsFiltered = (int)$rQuery->row()->total;
            $recordsTotal = $recordsFiltered;

            $aResponse = array(
                'page'      => $iPage,
                'total'     => ceil($recordsTotal/$iLimit),
                'records'   => $recordsTotal,
                'rows'      => array(),
            );
            $i = 0;
            foreach ($aData as $aRow)
            {
                $aResponse['rows'][$i]['id']    = (isset($aRow['id'])?$aRow['id']:$aRow[$this->aColModels[0]['name']]);
                $aResponse['rows'][$i]['cell']  = $aRow;
                $i++;
            }

            exit(json_encode($aResponse));
        }

        $aParams = $this->aProperties;
        $aParams['colNames'] = $this->aColNames;
        $aParams['colModel'] = $this->aColModels;
        $sParams = json_encode($aParams, JSON_NUMERIC_CHECK + JSON_PRETTY_PRINT);
        $sOutput = <<<OUTPUT
<table id="{$this->sName}"></table>
<div id="{$this->sName}_pager"></div>

<script>
    $(document).ready(function() {
        jQuery("#{$this->sName}").jqGrid({$sParams});
        jQuery("#{$this->sName}").jqGrid('navGrid', '#{$this->sName}_pager', {edit:false,add:false,del:false},{},{},{},{closeOnEscape:true, multipleSearch:true});
        jQuery("#{$this->sName}").jqGrid('filterToolbar', {searchOperators: true, search:true, defaultSearch:"cn", stringResult: true});
        jQuery("#{$this->sName}").jqGrid('navSeparatorAdd', "#{$this->sName}_pager");
        jQuery("#{$this->sName}").jqGrid('navButtonAdd', "#{$this->sName}_pager", {caption:"Экспорт", buttonicon:"ui-icon-extlink", onClickButton:function(e){ jQuery("#{$this->sName}").jqGrid('excelExport', {tag:'csv', url:window.location.toString()}); }, position: "last", title:"Экспорт в CSV"});
    });
</script>
OUTPUT;

        $sOutput = str_replace('"%datetime_searchoptions_dataInit%"', 'function(elem){$(elem).datepicker({changeMonth: true, changeYear: true, dateFormat: "yy-mm-dd"});}', $sOutput);

        foreach ($this->aFormatters as $iKey => $sFormatter)
        {
            $sOutput = str_replace('"%formatter'.$iKey.'%"', $sFormatter, $sOutput);
        }

        return $sOutput;
    }
}
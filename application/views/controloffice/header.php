<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="../../favicon.ico">

    <title><?php echo $title ?></title>

    <!-- Bootstrap core CSS -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="/css/dashboard.css" rel="stylesheet">

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <script src="/js/bootstrap.min.js"></script>

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <!-- Custom styles and js for jqGrid -->
    <link rel="stylesheet" type="text/css" media="screen" href="/css/jquery-ui.min.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="/css/ui.jqgrid.css" />

    <script src="/js/jquery-ui.min.js" type="text/javascript"></script>
    <script src="/js/i18n/grid.locale-ru.js" type="text/javascript"></script>
    <script src="/js/jquery.jqGrid.min.js" type="text/javascript"></script>
</head>

<body>

<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="<?php echo base_url('controloffice'); ?>">api.privatefx.com - Control Office</a>
        </div>
        <div class="navbar-collapse collapse">
            <ul class="nav navbar-nav navbar-right">
                <li class="<?php if ($segment == "settings") {?>active<?php } ?>"><a href="<?php echo base_url('controloffice/settings'); ?>">Настройки</a></li>
            </ul>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-sm-3 col-md-2 sidebar">
            <ul class="nav nav-sidebar">
                <li<?php if ($segment == "") {?> class="active"<?php } ?>><a href="<?php echo base_url('controloffice'); ?>">Обзор</a></li>
            </ul>
            <ul class="nav nav-sidebar">
                <li class="<?php if ($segment == "get_pamm_list") {?>active<?php } ?>"><a href="<?php echo base_url('controloffice/get_pamm_list'); ?>">Список ПАММ-счетов</a></li>
                <li class="<?php if ($segment == "get_investors_list") {?>active<?php } ?>"><a href="<?php echo base_url('controloffice/get_investors_list'); ?>">Список инвесторов</a></li>
                <li class="<?php if ($segment == "get_money_orders_list") {?>active<?php } ?>"><a href="<?php echo base_url('controloffice/get_money_orders_list'); ?>">Список распоряжений</a></li>
                <li class="<?php if ($segment == "get_agent_payments_list") {?>active<?php } ?>"><a href="<?php echo base_url('controloffice/get_agent_payments_list'); ?>">Список агентских выплат</a></li>
                <li class="<?php if ($segment == "get_agents_status_list") {?>active<?php } ?>"><a href="<?php echo base_url('controloffice/get_agents_status_list'); ?>">Список агентских разрешений</a></li>
            </ul>
            <ul class="nav nav-sidebar">
                <li class="<?php if ($segment == "get_webactions_list") {?>active<?php } ?> disabled"><a href="<?php echo base_url('controloffice/get_webactions_list'); ?>">Список WebActions-запросов</a></li>
            </ul>
            <ul class="nav nav-sidebar">
                <li class="<?php if ($segment == "get_tps_list") {?>active<?php } ?> disabled"><a href="<?php echo base_url('controloffice/get_tps_list'); ?>">Список TPS-транзакций</a></li>
            </ul>
            <ul class="nav nav-sidebar">
                <li class="<?php if ($segment == "get_notifications_list") {?>active<?php } ?>"><a href="<?php echo base_url('controloffice/get_notifications_list'); ?>">Список уведомлений</a></li>
            </ul>
        </div>
        <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">

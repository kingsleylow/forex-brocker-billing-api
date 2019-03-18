<script type="text/javascript" src="/fusioncharts/js/fusioncharts.js"></script>
<script type="text/javascript" src="/fusioncharts/js/themes/fusioncharts.theme.fint.js"></script>

<h1 class="page-header">Обзор</h1>

<div class="row placeholders">
    <div class="col-xs-6 col-sm-4 placeholder">
        <h4>PAMM-запросы</h4>
        <span class="text-muted">за последние сутки</span>
        <div id="chartContainerPAMM">FusionCharts XT will load here!</div>
    </div>
    <div class="col-xs-6 col-sm-4 placeholder">
        <h4>WA-запросы</h4>
        <span class="text-muted">за последние сутки</span>
        <div id="chartContainerWA">FusionCharts XT will load here!</div>
    </div>
    <div class="col-xs-6 col-sm-4 placeholder">
        <h4>TPS-запросы</h4>
        <span class="text-muted">за последние сутки</span>
        <div id="chartContainerTPS">FusionCharts XT will load here!</div>
    </div>
</div>

<script type="text/javascript">
    FusionCharts.ready(function(){
        var PieChartPAMM = new FusionCharts({
            "type": "pie2d",
            "renderAt": "chartContainerPAMM",
            "width": "490",
            "height": "450",
            "dataFormat": "json",
            "dataSource": {
                "chart": {
                    //"paletteColors": "#0075c2,#1aaf5d,#f2c500,#f45b00,#8e0000",
                    "bgColor": "#ffffff",
                    "showBorder": "0",
                    "use3DLighting": "0",
                    "showShadow": "0",
                    "enableSmartLabels": "0",
                    "startingAngle": "0",
                    "showPercentValues": "1",
                    "showPercentInTooltip": "0",
                    "decimals": "1",
                    "captionFontSize": "14",
                    "subcaptionFontSize": "14",
                    "subcaptionFontBold": "0",
                    "toolTipColor": "#ffffff",
                    "toolTipBorderThickness": "0",
                    "toolTipBgColor": "#000000",
                    "toolTipBgAlpha": "80",
                    "toolTipBorderRadius": "2",
                    "toolTipPadding": "5",
                    "showHoverEffect": "1",
                    "showLegend": "1",
                    "legendBgColor": "#ffffff",
                    "legendBorderAlpha": "0",
                    "legendShadow": "0",
                    "legendItemFontSize": "10",
                    "legendItemFontColor": "#666666"
                },
                "data": <?php echo $chart_data_pamm; ?>
            }
        });
        PieChartPAMM.render();

        var PieChartWA = new FusionCharts({
            "type": "pie2d",
            "renderAt": "chartContainerWA",
            "width": "490",
            "height": "450",
            "dataFormat": "json",
            "dataSource": {
                "chart": {
                    "paletteColors": "#0075c2,#1aaf5d,#f2c500,#f45b00,#8e0000",
                    "bgColor": "#ffffff",
                    "showBorder": "0",
                    "use3DLighting": "0",
                    "showShadow": "0",
                    "enableSmartLabels": "0",
                    "startingAngle": "0",
                    "showPercentValues": "1",
                    "showPercentInTooltip": "0",
                    "decimals": "1",
                    "captionFontSize": "14",
                    "subcaptionFontSize": "14",
                    "subcaptionFontBold": "0",
                    "toolTipColor": "#ffffff",
                    "toolTipBorderThickness": "0",
                    "toolTipBgColor": "#000000",
                    "toolTipBgAlpha": "80",
                    "toolTipBorderRadius": "2",
                    "toolTipPadding": "5",
                    "showHoverEffect": "1",
                    "showLegend": "1",
                    "legendBgColor": "#ffffff",
                    "legendBorderAlpha": "0",
                    "legendShadow": "0",
                    "legendItemFontSize": "10",
                    "legendItemFontColor": "#666666"
                },
                "data": <?php echo $chart_data_wa; ?>
            }
        });
        PieChartWA.render();

        var PieChartTPS = new FusionCharts({
            "type": "pie2d",
            "renderAt": "chartContainerTPS",
            "width": "490",
            "height": "450",
            "dataFormat": "json",
            "dataSource": {
                "chart": {
                    "paletteColors": "#0075c2,#1aaf5d,#f2c500,#f45b00,#8e0000",
                    "bgColor": "#ffffff",
                    "showBorder": "0",
                    "use3DLighting": "0",
                    "showShadow": "0",
                    "enableSmartLabels": "0",
                    "startingAngle": "0",
                    "showPercentValues": "1",
                    "showPercentInTooltip": "0",
                    "decimals": "1",
                    "captionFontSize": "14",
                    "subcaptionFontSize": "14",
                    "subcaptionFontBold": "0",
                    "toolTipColor": "#ffffff",
                    "toolTipBorderThickness": "0",
                    "toolTipBgColor": "#000000",
                    "toolTipBgAlpha": "80",
                    "toolTipBorderRadius": "2",
                    "toolTipPadding": "5",
                    "showHoverEffect": "1",
                    "showLegend": "1",
                    "legendBgColor": "#ffffff",
                    "legendBorderAlpha": "0",
                    "legendShadow": "0",
                    "legendItemFontSize": "10",
                    "legendItemFontColor": "#666666"
                },
                "data": <?php echo $chart_data_tps; ?>
            }
        });
        PieChartTPS.render();
    });
</script>
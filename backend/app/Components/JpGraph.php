<?php

namespace App\Components;

use JpGraph\JpGraph as BaseJpGraph;
use TijsVerkoyen\CssToInlineStyles\Exception;
use App\Components\Helper\LogHelper;

/**
 * JpGraph 定制化类
 *
 * @author funson
 * @since 1.0
 */
class JpGraph extends BaseJpGraph
{
    /**
     * 邮件报表代理商消耗表
     * @param array $data
     * @param array $label
     * @param string $file
     * @param integer $w
     * @param integer $h
     * @return string the formatted result.
     */
    public static function reportBarBroker($data, $label, $file, $w = 500, $h = 280)
    {
        JpGraph::load();
        JpGraph::module('bar');

        // Create the graph. These two calls are always required
        $graph = new \Graph($w, $h);
        $graph->SetScale("textlin");
        $graph->graph_theme = null;
        $graph->SetFrame(false);

        $graph->SetShadow();
        $graph->img->SetMargin(60, 20, 35, 85);
        $graph->xaxis->SetTickLabels($label);
        $graph->xaxis->SetLabelAngle(45);

        // Setup the X and Y grid
        $graph->ygrid->SetFill(false, '#DDDDDD@0.5', '#BBBBBB@0.5');

        // Create the bar plots
        $b1plot = new \BarPlot($data);

        $b1plot->SetFillColor([[237, 124, 48], [114, 173, 76], [249, 192, 47], [70, 115, 194], [93, 156, 211]]);
        $b1plot->SetWeight(0);

        $b1plot->value->Show();
        $b1plot->SetValuePos('top');
        // Must use TTF fonts if we want text at an arbitrary angle
        $b1plot->value->SetFont(FF_VERDANA, FS_NORMAL);
        $b1plot->value->SetAngle(0);
        $b1plot->value->SetFormat('%d');
        // Black color for positive values and darkred for negative values
        $b1plot->value->SetColor("black", "darkred");

        $graph->Add($b1plot);

        // generate the graph
        try {
            if (file_exists($file)) {
                unlink($file);
            }
            $graph->Stroke($file);
        } catch (Exception $e) {
            LogHelper::error($e->getMessage());
        }
        // $graph->Stroke();
        return true;
    }

    /**
     * 邮件报表代理商消耗表
     * @param array $data
     * @param array $label
     * @param string $file
     * @param integer $w
     * @param integer $h
     * @return string the formatted result.
     */
    public static function reportBarDownload($data, $label, $file, $w = 500, $h = 220)
    {
        JpGraph::load();
        JpGraph::module('bar');

        // Create the graph. These two calls are always required
        $graph = new \Graph($w, $h);
        $graph->SetScale("textlin");
        $graph->graph_theme = null;
        $graph->SetFrame(false);

        $graph->SetShadow();
        $graph->img->SetMargin(60, 0, 35, 50);
        $graph->xaxis->SetTickLabels($label);
        $graph->xaxis->SetLabelAngle(15);

        // Setup the X and Y grid
        $graph->ygrid->SetFill(false, '#DDDDDD@0.5', '#BBBBBB@0.5');

        // Create the bar plots
        $b1plot = new \BarPlot($data[0]);
        $b1plot->SetFillColor('lightblue');
        $b1plot->SetWeight(0);
        $b2plot = new \BarPlot($data[1]);
        $b2plot->SetFillColor("orange");
        $b2plot->SetWeight(0);

        // Create the grouped bar plot
        $gbplot = new \GroupBarPlot([$b1plot, $b2plot]);

        // ...and add it to the graPH
        $graph->Add($gbplot);

        $graph->xaxis->title->Set("蓝色-下载量  橙色-消耗");
        $graph->xaxis->SetTitlemargin(20);

        // generate the graph
        try {
            if (file_exists($file)) {
                unlink($file);
            }
            $graph->Stroke($file);
        } catch (Exception $e) {
            LogHelper::error($e->getMessage());
        }
        // $graph->Stroke();
        return true;
    }

    /**
     * 饼图 - 前十名支出媒体
     * @param array $data
     * @param array $label
     * @param string $file
     * @param integer $w
     * @param integer $h
     * @return string the formatted result.
     */
    public static function reportPiePayment($data, $label, $file, $w = 500, $h = 300)
    {
        JpGraph::load();
        JpGraph::module('pie');
        
        // Create the graph. These two calls are always required
        $graph = new \PieGraph($w, $h);
        //$graph->title->SetFont(FF_CHINESE,FS_NORMAL,12);
        $graph->title->SetColor("#56595d");
        $graph->legend->Pos(0.1, 0.2);
        $graph->graph_theme = null;
        $graph->SetFrame(false);

        // Create pie plot
        $plot = new \PiePlot($data);
        $plot->SetCenter(0.5, 0.60);
        $plot->SetSize(0.3);
        $plot->SetStartAngle(90);

        // Enable and set policy for guide-lines. Make labels line up vertically
        $plot->SetGuideLines(true, false);
        $plot->SetGuideLinesAdjust(1.8, 2.5);
        $arr = array('#5d9cd3', '#eb7a3a', '#a5a5a5', '#fec02c', '#4673c2', '#72ad4c', '#285f8e',
            '#9d4718', '#626263', '#94721c', '#284476');
        $count = count($data);
        $color = array_slice($arr, 0, $count);
        $plot->SetSliceColors(array_reverse($color));


        // Setup the labels to be displayed
        $plot->SetLabels($label);
        // This method adjust the position of the labels. This is given as fractions
        // of the radius of the Pie. A value < 1 will put the center of the label
        // inside the Pie and a value >= 1 will pout the center of the label outside the
        // Pie. By default the label is positioned at 0.5, in the middle of each slice.
        $plot->SetLabelPos(1);
        // Setup the labels
        //$plot->SetLabelType(PIE_VALUE_PER);
        $plot->value->Show();
        //$plot->value->SetFont(FF_ARIAL,FS_NORMAL,9);
        $plot->value->SetFormat('%2.1f%%');

        $graph->Add($plot);

        // generate the graph
        try {
            if (file_exists($file)) {
                unlink($file);
            }
            $graph->Stroke($file);
        } catch (Exception $e) {
            LogHelper::error($e->getMessage());
        }
        // $graph->Stroke();
        return true;
    }

    /**
     * 生成趋势折线图
     * @param $data
     * @param $file
     * @param int $w
     * @param int $h
     * @return bool
     * @throws \JpGraph\ModuleNotFoundException
     */
    public static function reportLineConsumption($data, $file, $w = 500, $h = 300)
    {
        JpGraph::load();
        JpGraph::module('line');

        $graph = new \Graph(500, 300);                                                    //创建新的Graph对象
        $graph->SetScale("textlin");                                                    //设置刻度样式
        $graph->img->SetMargin(30, 30, 80, 30);                    //设置图表边界
        //$graph->title->Set("CDN Traffic Total");        //设置图表标题
        $graph->title->SetColor("blue");
        $graph->title->SetMargin(20);
        $graph->xaxis->HideLabels(); // Hide xaxis
        $graph->yaxis->HideLabels();

        // Create the linear plot
        $lineplot=new \LinePlot($data);              // 创建新的LinePlot对象
       // $lineplot->SetLegend("Line(Mbits)");   //设置图例文字
        $lineplot->SetColor("blue");                 // 设置曲线的颜色

        // Add the plot to the graph
        $graph->Add($lineplot);                     //在统计图上绘制曲线

        // Output line
        try {
            if (file_exists($file)) {

                unlink($file);
            }
            $graph->Stroke($file);
        } catch (Exception $e) {
            LogHelper::error($e->getMessage());
        }
        return true;
    }
}

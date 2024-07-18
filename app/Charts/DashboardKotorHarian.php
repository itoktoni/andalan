<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\LarapexChart;

class DashboardKotorHarian
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(): \ArielMejiaDev\LarapexCharts\PieChart
    {
        return $this->chart->pieChart()
            ->setTitle('Sebaran Linen di Rumah Sakit.')
            ->setSubtitle('Tahun 2024.')
            ->addData([10294, 6567, 8035])
            ->setLabels(['Siloam Kebon Jeruk', 'Carolus Serpong', 'Premier Bintaro']);
    }
}

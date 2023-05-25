<?php

namespace App\IndicatorTemplates\Performance;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Uneca\Chimera\Http\Livewire\Chart;
use Uneca\Chimera\Interfaces\BarChart;
use Uneca\Chimera\Interfaces\LineChart;
use Uneca\Chimera\Services\AreaTree;
use Uneca\Chimera\Traits\FilterBasedAxisTitle;
use Uneca\Chimera\Services\Helpers;
use DateInterval;
use DatePeriod;

class HouseholdsEnumeratedByDayCumulative  extends Chart implements LineChart
{
    use FilterBasedAxisTitle;
    private bool $isSampleData = false;
    public function getData(array $filter = []): Collection
    {
        $this->isSampleData = true;
        return \collect([
            (object)[
                'enumeration_date' => Carbon::now()->subDays(4)->format('Y-m-d'),
                'nice_date' => Carbon::now()->subDay(4)->format('M d, Y'), //'Jan 1, 2021',
                'total' => '1500',
                'cumulative_sum' => '1500',
                'target' => '1000',
            ],
            (object)[
                'enumeration_date' => Carbon::now()->subDays(3)->format('Y-m-d'),
                'nice_date' => Carbon::now()->subDay(3)->format('M d, Y'), //'Jan 1, 2021',
                'total' => '500',
                'cumulative_sum' => '2000',
                'target' => '2000',
            ],
            (object)[
                'enumeration_date' => Carbon::now()->subDays(2)->format('Y-m-d'),
                'nice_date' => Carbon::now()->subDay(2)->format('M d, Y'), //'Jan 1, 2021',
                'total' => '1100',
                'cumulative_sum' => '3000',
                'target' => '3000',
            ],
            (object)[
                'enumeration_date' => Carbon::now()->subDays(1)->format('Y-m-d'),
                'nice_date' => Carbon::now()->subDay(1)->format('M d, Y'), //'Jan 1, 2021',
                'total' => '900',
                'cumulative_sum' => '4000',
                'target' => '4000',
            ],
            (object)[
                'enumeration_date' => Carbon::now()->format('Y-m-d'),
                'nice_date' => Carbon::now()->format('M d, Y'), //'Jan 1, 2021',
                'total' => '1000',
                'cumulative_sum' => '5000',
                'target' => '5000',
            ],


        ]);
    }

    protected function getTraces(Collection $data, string $filterPath): array
    {

        $now = Carbon::now();
        $questionnaire = $this->indicator->getQuestionnaire();
        $start_date = $questionnaire->start_date;
        $end_date = $questionnaire->end_date;

        $totalDays = $end_date->diffInDays($start_date) + 1;
        $areas = (new AreaTree())->areas($filterPath, nameOfReferenceValueToInclude: 'number_of_hh');
        if($this->isSampleData){
            $areas = $data->map(function ($item) use ($data) {
                return (object) [
                    'value' => \round($data->sum('total')),
                ];
            });
        }
        $householdEV = Helpers::safeDivide($areas->sum('value'), $totalDays);

        $data = $data->map(function ($row)  use ($householdEV, $totalDays, $start_date, $end_date) {
            if(Carbon::parse($row->enumeration_date)->lt($start_date)){
                $row->target = 0;
            } else {
                $performedDays = Carbon::parse($row->enumeration_date)->diffInDays($start_date) + 1;
                $performedDays = $performedDays > $totalDays ? $totalDays : $performedDays;
                $row->target = $householdEV * $performedDays;
            }
            return $row;
        });

        $traceCumulative = array_merge(
            $this::LineTraceTemplate,
            [
                'x' => $data->pluck('nice_date')->all(),
                'y' => $data->pluck('cumulative_sum')->all(),
                'texttemplate' => "%{y:.2f}",
                'hovertemplate' => "%{x}<br> %{y:.0f}",
                'name' => __('Actual'),
            ]
        );
        $traceTarget = array_merge(
            $this::LineTraceTemplate,
            [
                'x' => $data->pluck('nice_date')->all(),
                'y' => $data->pluck('target')->all(),
                'texttemplate' => "%{y:.2f}",
                'hovertemplate' => "%{x}<br> %{y:.0f}",
                'name' => __('Target'),
            ]
        );

        return [$traceCumulative, $traceTarget];
    }

    protected function getLayout(string $filterPath): array
    {
        $layout = parent::getLayout($filterPath);
        $layout['xaxis']['title']['text'] = __("Enumeration dates");
        $layout['yaxis']['title']['text'] = __("# of households (cumulative)");
        $layout['colorway'] = ['#1e3b87', '#c99c25', '#6f066f', '#7a37aa', '#a30538', '#ff0506', '#dba61f', '#ff6f06', '#fea405', '#ffff05', '#a3d804', '#056e05', '#3939d9', '#0579cc'];
        if ($this->isSampleData) {
            $layout['annotations'] = [[
                'text' => __('SAMPLE'),
                'textangle' => -30,
                'opacity' => 0.12,
                'xref' => 'paper',
                'yref' => 'paper',
                'font' => ['color' => 'black', 'size' => 120]
            ]];
        }
        return $layout;
    }
}

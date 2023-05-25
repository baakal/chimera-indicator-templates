<?php

namespace App\IndicatorTemplates\Performance;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Uneca\Chimera\Http\Livewire\Chart;
use Uneca\Chimera\Interfaces\BarChart;
use Uneca\Chimera\Services\AreaTree;
use Uneca\Chimera\Traits\FilterBasedAxisTitle;
use Uneca\Chimera\Services\Helpers;

class HouseholdsEnumeratedAgainstTargetByDay  extends Chart implements BarChart
{
    use FilterBasedAxisTitle;
    private bool $isSampleData = false;
    public function getData(array $filter = []): Collection
    {
        $this->isSampleData = true;
        return collect([
            (object) [
                'enumeration_date' =>  Carbon::now()->subDays(4)->format('Y-m-d'),
                'total' => '1000',
            ],
            (object) [
                'enumeration_date' =>  Carbon::now()->subDays(3)->format('Y-m-d'),
                'total' => '1500',
            ],
            (object) [
                'enumeration_date' =>  Carbon::now()->subDays(2)->format('Y-m-d'),
                'total' => '1100',
            ],
            (object) [
                'enumeration_date' =>  Carbon::now()->subDays(1)->format('Y-m-d'),
                'total' => '900',
            ],
            (object) [
                'enumeration_date' =>  Carbon::now()->format('Y-m-d'),
                'total' => '800',
            ],

        ]);

    }

    protected function getTraces(Collection $data, string $filterPath): array
    {

        $areas = (new AreaTree())->areas($filterPath, nameOfReferenceValueToInclude: 'number_of_hh');
        if($this->isSampleData){
            $areas = $data->map(function ($item) use ($data) {
                return (object) [
                    'value' => \round($data->sum('total')),
                ];
            });
        }

        $totalTarget = $areas->sum('value');

        $questionnaire = $this->indicator->getQuestionnaire();
        $start_date = $questionnaire->start_date;
        $end_date = $questionnaire->end_date;

        $total_enum_days=$start_date->diffInDays($end_date,false);

        $data = $data->map(function ($row)  use ($totalTarget,$total_enum_days) {
            $row->bar_width = 43200000;
            $row->target_bar_width = 56300000;
            $row->dailytarget =Helpers::safeDivide($totalTarget, $total_enum_days, true);
            $row->dailyPerformance =$row->total;

            return $row;
        });

        $traceDailyTarget = array_merge(
            $this::ValueTraceTemplate,
            [
                'x' => $data->pluck('enumeration_date')->all(),
                'y' => $data->pluck('dailytarget')->all(),
                'texttemplate' => "%{value:.0f}",
                'hovertemplate' => "%{label}<br> %{value:.0f}",
                'width' => $data->pluck('target_bar_width')->all(),
                'name' => __("Today's Target"),
                'marker' => ['color' => '#c5c5c5','opacity'=>0.4],
            ]
        );

        $traceDailyPerformance = array_merge(
            $this::ValueTraceTemplate,
            [
                'x' => $data->pluck('enumeration_date')->all(),
                'y' => $data->pluck('dailyPerformance')->all(),
                'texttemplate' => "%{value:.0f}",
                'hovertemplate' => "%{label}<br> %{value:.0f}",
                'width' => $data->pluck('bar_width')->all(),
                'name' => __('Actual'),
                'marker' => ['color' => '#1e3b87'],
            ]
        );

        return [$traceDailyTarget,$traceDailyPerformance];
    }

    protected function getLayout(string $filterPath): array
    {
        $layout = parent::getLayout($filterPath);

        $layout['xaxis']['title']['text'] = __("Enumeration dates");
        $layout['yaxis']['title']['text'] = __("# of households");

        $questionnaire = $this->indicator->getQuestionnaire();
        $start_date = $questionnaire->start_date->subDays(3)->format('Y-m-d');
        $end_date = $questionnaire->end_date->addDays(3)->format('Y-m-d');


        $layout['xaxis']['type'] = 'date';
        $layout['xaxis']['range'] = [$start_date, $end_date];
        $layout['xaxis']['rangeselector']['x'] = 0.9;
        $layout['barmode'] = 'overlay';
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

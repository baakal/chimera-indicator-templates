<?php

namespace App\IndicatorTemplates\Performance;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Uneca\Chimera\Http\Livewire\Chart;
use Uneca\Chimera\Interfaces\BarChart;
use Uneca\Chimera\Services\AreaTree;
use Uneca\Chimera\Traits\FilterBasedAxisTitle;
use Uneca\Chimera\Services\Helpers;

class PercentagePopulationEnumeratedAgainstTarget extends Chart implements BarChart
{
    use FilterBasedAxisTitle;
    private bool $isSampleData = false;
    public function getData(array $filter = []): Collection
    {
        $this->isSampleData = true;
        return collect([
            (object)[
                'area_name' => 'Area 1',
                'area_code' => '1',
                'total' => '10000',

            ],
            (object) [
                'area_name' => 'Area 2',
                'area_code' => '2',
                'total' => '15000',

            ],
            (object) [
                'area_name' => 'Area 3',
                'area_code' => '3',
                'total' => '11000',

            ],
            (object) [
                'area_name' => 'Area 4',
                'area_code' => '4',
                'total' => '9000',

            ],
            (object) [
                'area_name' => 'Area 5',
                'area_code' => '5',
                'total' => '8000',

            ],
        ]);
    }

    protected function getTraces(Collection $data, string $filterPath): array
    {

        $areas = (new AreaTree())->areas($filterPath, nameOfReferenceValueToInclude: 'population');
        if($this->isSampleData){
            $areas = $data->map(function ($item) {
                return (object) [
                    'code' => $item->area_code,
                    'name' => $item->area_name,
                    'value' => \round($item->total * (1 +rand(1, 100) / 100)),
                ];
            });
        }

        $questionnaire = $this->indicator->getQuestionnaire();
        $now = Carbon::now();
        $startDate = $questionnaire->start_date->subDays(1);
        $endDate = $questionnaire->end_date;

        $total_Enum_days=$startDate->diffInDays($endDate,false);
        $days_Since_enum_start=$startDate->diffInDays($now,false);
        $day_count_yesterday=$startDate->diffInDays($now,false) - 1;

        if($days_Since_enum_start > $total_Enum_days) {
            $days_Since_enum_start = $total_Enum_days;
        }
        if($day_count_yesterday > $total_Enum_days) {
            $day_count_yesterday = $total_Enum_days;
        }
        $dataKeyByAreaCode = $data->keyBy('area_code');

        $dataKeyByAreaCode = $data->keyBy('area_code');
        $data = $areas->map(function ($area) use ($dataKeyByAreaCode, $days_Since_enum_start, $total_Enum_days) {
            $area->bar_width = 0.7;
            $area->total = $dataKeyByAreaCode[$area->code]->total ?? 0;
            $area->proportion = Helpers::safeDivide($area->total, $area->value) * 100;
            $area->expected = Helpers::safeDivide($days_Since_enum_start, $total_Enum_days) * 100;
            $area->expected_value = $area->value;
            $area->tooltip =  "<b>{$area->value}</b><br><br><b>Households:</b> %{value:.0f} (number) <br> <b>Performance:</b>%{text} %<extra></extra>";
            return $area;
        });

        $tracePercentageTodayTarget = array_merge(
            $this::PercentageBarTraceTemplate,
            [
                'x' => $data->pluck('name')->all(),
                'y' => $data->pluck('expected')->all(),
                'text' => $data->pluck('total')->all(),
                'texttemplate' => "%{value:.0f}",
                'name' => __("Today's target"),
                'visible' => 'true',
                'marker' => ['color' => '#c5c5c5','opacity'=>0.4],
                'hovertemplate' => "<b>%{x}</b><br><br><b>Households:</b> %{text:.0f} (number) <br> <b>Performance: </b>%{value:.0f}<extra></extra>",

            ]
        );

        $tracePercentageActual = array_merge(
            $this::PercentageBarTraceTemplate,
            [
                'x' => $data->pluck('name')->all(),
                'y' => $data->pluck('proportion')->all(),
                'text' => $data->pluck('total')->all(),
                'texttemplate' => "%{value:.0f}",
                'width' => $data->pluck('bar_width')->all(),
                'marker' => ['color' => '#1e3b87'],
                'visible' => 'true',
                'name' => __('Mapped population'),
                'hovertemplate' => "<b>%{x}</b><br><br><b>Households:</b> %{text:.0f} (number) <br> <b>Performance: </b>%{value:.0f}%<extra></extra>",

            ]
        );

        $traceTodayTarget = array_merge(
            $this::PercentageBarTraceTemplate,
            [
                'x' => $data->pluck('name')->all(),
                'y' => $data->pluck('expected_value')->all(),
                'text' => $data->pluck('expected')->all(),
                'texttemplate' => "%{value:.0f}",
                'hovertemplate' => "%{label}<br> %{value:.0f}",
                'name' => __("Today's target"),
                'visible' => false,
                'marker' => ['color' => '#c5c5c5','opacity'=>0.4],
                'hovertemplate' => "<b>%{x}</b><br><br><b>Households:</b> %{value:.0f} (number) <br> <b>Performance:</b>%{text} %<extra></extra>",


            ]
        );

        $traceActual = array_merge(
            $this::PercentageBarTraceTemplate,
            [
                'x' => $data->pluck('name')->all(),
                'y' => $data->pluck('total')->all(),
                'texttemplate' => "%{value:.0f}",
                'hovertemplate' => "%{label}<br> %{value:.0f}",
                'width' => $data->pluck('bar_width')->all(),
                'marker' => ['color' => '#1e3b87'],
                'visible' => false,
                'name' => __('Mapped'),
                'hovertemplate' => "<b>%{x}</b><br><br><b>Households:</b> %{value:.0f} (number) <br> <b>Performance:</b>%{y} %<extra></extra>",

            ]
        );
        return [$traceTodayTarget, $traceActual, $tracePercentageTodayTarget, $tracePercentageActual];

    }

    protected function getLayout(string $filterPath): array
    {
        $layout = parent::getLayout($filterPath);
        $layout['updatemenus'] = [[
                'buttons' => [
                    [
                        'args' => [['visible' => [\false, \false, true, true]],[ 'yaxis' => [
                            'title' => ['text' => __('% of persons')]
                        ]]],
                        'label' => '%',
                        'method' => 'update',
                        'active' => \true,

                    ],
                    [
                        'args' => [['visible' => [ true, true,false,false]], ['yaxis' => [
                            'title' => ['text' => __('# of persons')]
                        ]
                        ]],
                        'label' => '#',
                        'method' => 'update',
                        'active' => \false
                    ]
                ],
                'direction' => 'left',
                'pad' => ['r' => 20],
                'showactive' => true,
                'type' => 'buttons',
                'x' => 1,
                'xanchor' => 'right',
                'align' => 'left',
                'y' => 1.1,
                'yanchor' => 'top',
                'bordercolor' => '#c5c5c5',
            ]];
        $layout['xaxis']['title']['text'] = $this->getAreaBasedAxisTitle($filterPath);
        $layout['yaxis']['title']['text'] = __('% of persons');
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

<?php

namespace App\IndicatorTemplates\Meta;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Uneca\Chimera\Http\Livewire\Chart;
use Uneca\Chimera\Interfaces\BarChart;
use Uneca\Chimera\Services\AreaTree;
use Uneca\Chimera\Traits\FilterBasedAxisTitle;

class EstimatedNumberOfDaysLeft extends Chart  implements BarChart
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
                'total' => '100',
                'value' => '100',
            ],
            (object) [
                'area_name' => 'Area 2',
                'area_code' => '2',
                'total' => '200',
                'value' => '200',
            ],
            (object) [
                'area_name' => 'Area 3',
                'area_code' => '3',
                'total' => '300',
                'value' => '300',
            ],
            (object) [
                'area_name' => 'Area 4',
                'area_code' => '4',
                'total' => '400',
                'value' => '400',
            ],
            (object) [
                'area_name' => 'Area 5',
                'area_code' => '5',
                'total' => '500',
                'value' => '500',
            ],

        ]);

    }

    protected function getTraces(Collection $data, string $filterPath): array
    {
        $result = $data;
        $questionnaire=$this->indicator->getQuestionnaire();

        $areas = (new AreaTree())->areas(parentPath:$filterPath,nameOfReferenceValueToInclude:'number_of_hh');
        if($this->isSampleData) {
            $areas = $data->map(function ($area) {
                return (object)['code' => $area->area_code, 'name' => $area->area_name];
            });

        }

        $totalTarget=$areas->sum('value');


        $totalActual=$result->sum('total');

        $areas[]= (object) ['total' => $totalActual,'value' => $totalTarget,'code' => '','name' => 'All '.$this->getAreaBasedAxisTitle($filterPath)];

        $now = Carbon::now();
        $start_date = $questionnaire->start_date->subDays(1);
        $end_date = $questionnaire->end_date;

        $total_enum_days=$start_date->diffInDays($end_date,false);
        $days_since_enum_start=$start_date->diffInDays($now,false);
        $day_count_yesterday=$start_date->diffInDays($now,false) - 1;

        if($days_since_enum_start > $total_enum_days) {
            $days_since_enum_start = $total_enum_days;
        }
        if($day_count_yesterday > $total_enum_days) {
            $day_count_yesterday = $total_enum_days;
        }

        $areas = $areas->map(function ($row) use ($days_since_enum_start, $total_enum_days,$result) {

            $resultRow=$result->first(function($item) use($row){
                return $item->area_code==$row->code;
            });

            if(isset($resultRow))
            {
                $row->total=$resultRow->total;
            }

            $row->bar_width = 0.7;
            $row->target_bar_width = 1;

            if (!isset($row->total)) {
                $row->total = 0;
            }
            if($row->total == 0) {
                $row->num_days_left = $days_since_enum_start > 0 ? 0 : $total_enum_days;
            } else{
                $averageDaily = $days_since_enum_start > 0 ? $row->total/$days_since_enum_start : 0;
                $row->num_days_left = $averageDaily > 0 ? number_format(($row->value??0 - $row->total)/ $averageDaily, 0, '','') : 0;
                $row->num_days_left = $row->num_days_left > 0 ? $row->num_days_left : 0;
                if($row->num_days_left > 100){
                    $row->num_days_left = 100;
                }
            }

            $row->target = $total_enum_days - $days_since_enum_start;

            return $row;
        });


        $traceTodayTarget = array_merge(
            $this::ValueTraceTemplate,
            [
                'x' => $areas->pluck('name')->all(),
                'y' => $areas->pluck('target')->all(),
                'text' => $areas->pluck('target')->all(),
                'name' => __("Target number of days"),
                'marker' => ['color' => '#c5c5c5','opacity'=>0.4],
            ]
        );

        $traceActual = array_merge(
            $this::ValueTraceTemplate,
            [
                'x' => $areas->pluck('name')->all(),
                'y' => $areas->pluck('num_days_left')->all(),
                'text' => $areas->pluck('num_days_left')->all(),
                'width' => $areas->pluck('bar_width')->all(),
                'name' => __('Estimated number of days'),
                'marker' => ['color' => '#1e3b87'],
            ]
        );

        return [$traceTodayTarget, $traceActual];
    }

    protected function getLayout(string $filterPath): array
    {
        $layout=parent::getLayout($filterPath);

        $layout['xaxis']['title']['text'] = $this->getAreaBasedAxisTitle($filterPath);
        $layout['yaxis']['title']['text'] = "# of days";

        $layout['barmode'] = 'overlay';

        return $layout;
    }
}

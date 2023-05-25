<?php

namespace App\IndicatorTemplates\Meta;

use Illuminate\Support\Collection;
use Uneca\Chimera\Http\Livewire\Chart;
use Uneca\Chimera\Interfaces\BarChart;
use Uneca\Chimera\Models\Area;
use Uneca\Chimera\Services\AreaTree;
use Uneca\Chimera\Traits\FilterBasedAxisTitle;
use Uneca\Chimera\Services\Helpers;

class AverageInterviewTime extends Chart implements BarChart
{
    use FilterBasedAxisTitle;
    private bool $isSampleData = false;
    private int $ConversionRateToMinute = 60;

    public function getData(array $filter): Collection
    {
        $this->isSampleData = true;
        return collect([
            (object)[
                'area_name' => 'Area 1',
                'area_code' => 'area1',
                'total_household' => '100',
                'total_interview_time' => '900',
            ],
            (object) [
                'area_name' => 'Area 2',
                'area_code' => '2',
                'total_household' => '200',
                'total_interview_time' => '2000',
            ],
            (object) [
                'area_name' => 'Area 3',
                'area_code' => '3',
                'total_household' => '300',
                'total_interview_time' => '3500',
            ],
            (object) [
                'area_name' => 'Area 4',
                'area_code' => '4',
                'total_household' => '400',
                'total_interview_time' => '3500',
            ],
            (object) [
                'area_name' => 'Area 5',
                'area_code' => '5',
                'total_household' => '500',
                'total_interview_time' => '5500',
            ],
        ]);

    }

    protected function getTraces(Collection $data, string $filterPath): array
    {
        $areas = (new AreaTree())->areas($filterPath);
        $dataKeyByAreaCode = $data->keyBy('area_code');

        if($this->isSampleData) {
            $areas = $data->map(function ($area) {
                return (object)['code' => $area->area_code, 'name' => $area->area_name];
            });

        }

        $data = $areas->map(function ($area) use ($dataKeyByAreaCode) {
            try {
                $area->average_interview_time = 0;
                $area->total_interview_time = 0;
                $area->total_household = 0;
                if (isset($dataKeyByAreaCode[$area->code])) {
                    $area->average_interview_time =Helpers::safeDivide(Helpers::safeDivide($dataKeyByAreaCode[$area->code]?->total_interview_time, $dataKeyByAreaCode[$area->code]?->total_household, true), $this->ConversionRateToMinute, true);
                    $area->total_interview_time = $dataKeyByAreaCode[$area->code]?->total_interview_time;
                    $area->total_household = $dataKeyByAreaCode[$area->code]?->total_household;
                }
            } catch (\Exception $exception) {
                //
            }
            return $area;
        });
        $totalInterviewTime = $data->sum('total_interview_time');
        $totalHouseholds = $data->sum('total_household');
        $averageInterviewTimeInMinute = Helpers::safeDivide(Helpers::safeDivide($totalInterviewTime, $totalHouseholds),$this->ConversionRateToMinute,true);

        $data[] = (object)['average_interview_time' => $averageInterviewTimeInMinute,'total_household' => $totalInterviewTime,'total_interview_time' => $totalHouseholds,
                    'area_code'=> '','name'=>'All '.$this->getAreaBasedAxisTitle($filterPath)];

        $traceActual = array_merge(
            $this::BarTraceTemplate,
            [
                'x' => $data->pluck('name')->all(),
                'y' => $data->pluck('average_interview_time')->all(),
                'texttemplate' => "%{value:.0f}",
                'hovertemplate' => "%{label}<br> %{value:.0f}",
                'name' => __('Average interview time'),
            ]
        );
        return  [$traceActual];
    }

    protected function getLayout(string $filterPath): array
    {
        $layout = parent::getLayout($filterPath);
        $layout['xaxis']['title']['text'] = $this->getAreaBasedAxisTitle($filterPath);
        $layout['yaxis']['title']['text'] = __("Average time (minutes)");
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

<?php

namespace App\IndicatorTemplates\Demographic;

use Illuminate\Support\Collection;
use Uneca\Chimera\Http\Livewire\Chart;
use Uneca\Chimera\Interfaces\BarChart;
use Uneca\Chimera\Services\AreaTree;
use Uneca\Chimera\Services\BreakoutQueryBuilder;
use Uneca\Chimera\Services\QueryFragmentFactory;
use Uneca\Chimera\Traits\FilterBasedAxisTitle;
use Uneca\Chimera\Services\Helpers;

class MaleToFemaleRatio extends Chart implements BarChart
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
                'males' => '100',
                'females' => '110'
            ],
            (object)[
                'area_name' => 'Area 2',
                'area_code' =>'2',
                'males' => '100',
                'females' => '110'
            ],
            (object)[
                'area_name' => 'Area 3',
                'area_code' =>'3',
                'males' => '100',
                'females' => '110'
            ],
            (object)[
                'area_name' => 'Area 4',
                'area_code' =>'4',
                'males' => '100',
                'females' => '110'
            ],
            (object)[
                'area_name' => 'Area 5',
                'area_code' =>'5',
                'males' => '100',
                'females' => '110'
            ],
            ]
        );
    }


    protected function getTraces(Collection $data, string $filterPath): array
    {

        $areas = (new AreaTree())->areas($filterPath);
        $dataKeyByAreaCode = $data->keyBy('area_code');
        if($this->isSampleData){
            $areas = $data->map(function ($item) {
                return (object) [
                    'code' => $item->area_code,
                    'name' => $item->area_name,
                ];
            });
        }
        $data = $areas->map(function ($area) use ($dataKeyByAreaCode) {
            $area->males = $dataKeyByAreaCode[$area->code]->males ?? 0;
            $area->females = $dataKeyByAreaCode[$area->code]->females ?? 0;
            $area->m_ratio = Helpers::safeDivide($area->males, $area->females) * 100;
            return $area;
        });

        $totalMales = $data->sum('males');
        $totalFemales = $data->sum('females');
        $totalRate = Helpers::safeDivide($totalMales, $totalFemales) * 100;
        $data[]= (object) ['males' => $totalMales,'females' => $totalFemales, 'm_ratio' => $totalRate, 'code' => '','name' => 'All '.$this->getAreaBasedAxisTitle($filterPath)];

        $traceRate = array_merge(
            $this::ValueTraceTemplate,
            [
                'x' => $data->pluck('name')->all(),
                'y' => $data->pluck('m_ratio')->all(),
                'texttemplate' => "%{value:.2f}",
                'hovertemplate' => "%{label}<br> %{value:.2f}",
                'marker' => ['color' => '#1e3b87'],
                'name' => __('Males per 100 females'),
            ]
        );

        return [$traceRate];
    }

    protected function getLayout(string $filterPath): array
    {
        $layout=parent::getLayout($filterPath);

        $layout['xaxis']['title']['text'] = $this->getAreaBasedAxisTitle($filterPath);
        $layout['yaxis']['title']['text'] = __("# of males per 100 females");
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

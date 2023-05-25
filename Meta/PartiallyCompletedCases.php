<?php

namespace App\IndicatorTemplates\Meta;

use Illuminate\Support\Collection;
use Uneca\Chimera\Http\Livewire\Chart;
use Uneca\Chimera\Interfaces\BarChart;
use Uneca\Chimera\Services\AreaTree;
use Uneca\Chimera\Services\BreakoutQueryBuilder;
use Uneca\Chimera\Services\QueryFragmentFactory;
use Uneca\Chimera\Traits\FilterBasedAxisTitle;

class PartiallyCompletedCases extends Chart implements BarChart
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
                'partial' => '10',
            ],
            (object) [
                'area_name' => 'Area 2',
                'area_code' => '2',
                'partial' => '20',
            ],
            (object) [
                'area_name' => 'Area 3',
                'area_code' => '3',
                'partial' => '30',
            ],
            (object) [
                'area_name' => 'Area 4',
                'area_code' => '4',
                'partial' => '10',
            ],
            (object) [
                'area_name' => 'Area 5',
                'area_code' => '5',
                'partial' => '5',
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
            $area->partial = $dataKeyByAreaCode[$area->code]->partial ?? 0;
            return $area;
        });

        $tracePartial = array_merge(
            $this::ValueTraceTemplate,
            [
                'x' => $data->pluck('name')->all(),
                'y' => $data->pluck('partial')->all(),
                'text' => $data->pluck('partial')->all(),
                'marker' => ['color' => '#1e3b87'],
                'name' => __('Partially saved cases'),
            ]
        );

        return [$tracePartial];
    }

    protected function getLayout(string $filterPath): array
    {
        $layout=parent::getLayout($filterPath);

        $layout['xaxis']['title']['text'] = $this->getAreaBasedAxisTitle($filterPath);
        $layout['yaxis']['title']['text'] = __("# of partially saved cases");
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

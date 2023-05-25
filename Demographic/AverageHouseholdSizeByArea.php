<?php

namespace App\IndicatorTemplates\Demographic;

use Illuminate\Support\Collection;
use Uneca\Chimera\Http\Livewire\Chart;
use Uneca\Chimera\Interfaces\BarChart;
use Uneca\Chimera\Services\AreaTree;
use Uneca\Chimera\Traits\FilterBasedAxisTitle;
use Uneca\Chimera\Services\Helpers;

class AverageHouseholdSizeByArea extends Chart implements BarChart
{
    use FilterBasedAxisTitle;
    private bool $isSampleData = false;

    /*
    * Uncomment this function to use the actual data from the database
    * public function getData(array $filter): Collection
    * {
    *     list($selectColumns, $whereConditions) = QueryFragmentFactory::make($this->indicator->questionnaire)->getSqlFragments($filter);
    *     return (new BreakoutQueryBuilder($this->indicator->questionnaire))
    *         ->select(array_merge(["SUM(%population_variable%) as population,Count(*) as households"], $selectColumns))
    *         ->from(['%household_table%'])
    *         ->where(array_merge(['%household_case_filter%'],$whereConditions))
    *         ->groupBy(['area_code'])
    *         ->orderBy(['area_name'])
    *         ->get();
    * }
    */
    public function getData(array $filter): Collection
    {
        $this->isSampleData = true;
        return collect([
            (object)[
                'area_name' => 'Area 1',
                'area_code' => '1',
                'population' => '100',
                'households' => '15',
            ],
            (object) [
                'area_name' => 'Area 2',
                'area_code' => '2',
                'population' => '200',
                'households' => '25',
            ],
            (object) [
                'area_name' => 'Area 3',
                'area_code' => '3',
                'population' => '300',
                'households' => '35',
            ],
            (object) [
                'area_name' => 'Area 4',
                'area_code' => '4',
                'population' => '400',
                'households' => '45',
            ],
            (object) [
                'area_name' => 'Area 5',
                'area_code' => '5',
                'population' => '500',
                'households' => '55',
            ],
        ]);

    }

    protected function getTraces(Collection $data, string $filterPath): array
    {
        $areas = (new AreaTree())->areas($filterPath);
        $dataKeyByAreaCode = $data->keyBy('area_code');
        $totalPopulation = $data->sum('population');
        $totalHouseholds = $data->sum('households');
        $averageHouseholdSize = Helpers::safeDivide($totalPopulation, $totalHouseholds);

        if($this->isSampleData) {
            $areas = $data->map(function ($area) {
                return (object)['code' => $area->area_code, 'name' => $area->area_name];
            });

        }

        $data = $areas->map(function ($area) use ($dataKeyByAreaCode) {
            $area->population = $dataKeyByAreaCode[$area->code]->population ?? 0;
            $area->households = $dataKeyByAreaCode[$area->code]->households ?? 0;
            $area->average_household_size = Helpers::safeDivide($area->population, $area->households) ;
            return $area;
        });

        $data[] = (object)[ 'population' => $totalPopulation,
                            'households' => $totalHouseholds,
                            'average_household_size' => $averageHouseholdSize,
                            'area_code'=> '',
                            'name'=>'All '.$this->getAreaBasedAxisTitle($filterPath)];

        $trace = array_merge(
            $this::ValueTraceTemplate,
            [
                'x' => $data->pluck('name')->all(),
                'y' => $data->pluck('average_household_size')->all(),
                'texttemplate' => "%{value:.2f}",
                // 'hovertemplate' => "%{label}<br> %{value:.2f}",
                'hovertemplate' => "<b>%{label}</b><br><br>".__('Avg. household size').": <b>%{value:.2f}</b><br>
                                    <extra></extra>",

                'name' => __('Avg. household size'),
            ]
        );

        return [$trace];

    }

    protected function getLayout(string $filterPath): array
    {
        $layout = parent::getLayout($filterPath);
        $layout['xaxis']['title']['text'] = $this->getAreaBasedAxisTitle($filterPath);
        $layout['yaxis']['title']['text'] = __("# of households");
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

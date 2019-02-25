<?php

namespace App\Nova;

use App\Fields\CustomBelongsToMany;
use App\Fields\SampleIds;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Trix;
use Treestoneit\BelongsToField\BelongsToField;

class Experiment extends Resource
{
    use RelationSortable;

    public static $model = 'App\Models\Experiment';

    public static $search = ['id'];
    public static $globallySearchable = false;

    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            SampleIds::make('Samples')
                ->help('A new line for each sample id')
                ->rules('required'),
            BelongsToField::make('Assay'),
            DateTime::make('Requested at')
                ->rules('required', 'date')
                ->hideWhenCreating()
                ->sortable(),
            Number::make('Number of Samples', 'samples_count')
                ->onlyOnIndex()
                ->sortable(),
            Trix::make('Comment')
                ->hideFromIndex(),

            CustomBelongsToMany::make('Samples'),

            File::make('Result File')
                ->hideWhenCreating()
                ->disk('local')
                ->path('experiments')
                ->prunable()
                ->storeOriginalName('original_filename')
                ->deletable(false),

            HasMany::make('Data', 'resultData', ResultData::class)
        ];
    }

    public function cards(Request $request)
    {
        return [];
    }
}

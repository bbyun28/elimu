<?php

namespace App\Nova;

use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Panel;

class Audit extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \OwenIt\Auditing\Models\Audit::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        $values = [];
        if ($this->event != 'created') {
            $values[] = Code::make('Old Values')->json();
        }
        if ($this->event != 'deleted') {
            $values[] =  Code::make('New Values')->json();
        }

        $values[] = new Panel('User Information', $this->userInformation());

        return array_merge([
                ID::make()->sortable()->onlyOnForms(),
                Text::make('Description', function () {
                    return $this->event . " " .
                        strtolower(Nova::resourceForModel($this->auditable_type)::singularLabel());
                }),
                MorphTo::make('Auditable')->hideFromIndex(),
                DateTime::make('Date', 'created_at'),
            ], $values);
    }

    public function userInformation()
    {
        return [
            BelongsTo::make('User'),
            Text::make('IP Address')->hideFromIndex(),
            Text::make('User Agent')->hideFromIndex(),
        ];
    }
}
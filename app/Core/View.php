<?php

namespace App\Core;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\View as ViewFacade;

class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        if (isset($data['layout'])) {
            $layout = $data['layout'];
            unset($data['layout']);
        }

        $data['config'] = config('church');
        $viewName = str_replace('/', '.', $view);

        if (!ViewFacade::exists($viewName)) {
            abort(500, "View not found: {$view}");
        }

        $html = ViewFacade::make($viewName, $data)->render();

        if ($layout) {
            $layoutName = str_replace('/', '.', $layout);
            $layoutData = array_merge($data, ['content' => $html]);
            throw new HttpResponseException(response(ViewFacade::make($layoutName, $layoutData)));
        }

        throw new HttpResponseException(response($html));
    }

    public static function partial(string $view, array $data = []): void
    {
        $viewName = str_replace('/', '.', $view);
        echo ViewFacade::make($viewName, array_merge($data, ['config' => config('church')]))->render();
    }

    public static function json(array $data, int $status = 200): void
    {
        throw new HttpResponseException(response()->json($data, $status));
    }
}

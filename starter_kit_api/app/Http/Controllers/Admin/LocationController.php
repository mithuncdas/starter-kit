<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Location\TreeLocationRequest;
use App\Http\Resources\Admin\AdminAreaResource;
use App\Http\Resources\Admin\AdminAreaTreeResource;
use App\Http\Resources\Admin\CountryResource;
use App\Http\Responses\ApiResponder;
use App\Models\AdminArea;
use App\Models\Country;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    use ApiResponder;

    // Safety caps on listing endpoints. Tuned for typical admin-area shapes
    // (countries ≈ 250, top-level divisions ≤ 50, children ≤ a few hundred).
    private const LIST_LIMIT = 500;

    // Recursive tree endpoint default depth. The maximum cap (6) is enforced
    // by TreeLocationRequest so misuse on deep hierarchies can't return the full tree.
    private const TREE_DEFAULT_DEPTH = 3;

    public function countries(): JsonResponse
    {
        $countries = Country::query()
            ->active()
            ->orderBy('name')
            ->limit(self::LIST_LIMIT)
            ->get();

        return $this->success(
            data: CountryResource::collection($countries)->resolve(),
            message: 'Countries fetched.',
        );
    }

    public function structure(Country $country): JsonResponse
    {
        $country->load('structure.level');

        return $this->success(
            data: CountryResource::make($country)->resolve(),
            message: 'Country structure fetched.',
        );
    }

    public function topLevel(Country $country): JsonResponse
    {
        $areas = $country->topLevelAreas()
            ->with('level')
            ->orderBy('id')
            ->limit(self::LIST_LIMIT)
            ->get();

        return $this->success(
            data: AdminAreaResource::collection($areas)->resolve(),
            message: 'Top-level areas fetched.',
        );
    }

    public function children(AdminArea $area): JsonResponse
    {
        $children = $area->children()
            ->with('level')
            ->orderBy('id')
            ->limit(self::LIST_LIMIT)
            ->get();

        return $this->success(
            data: AdminAreaResource::collection($children)->resolve(),
            message: 'Child areas fetched.',
        );
    }

    public function tree(TreeLocationRequest $request, Country $country): JsonResponse
    {
        $maxDepth = (int) ($request->validated()['max_depth'] ?? self::TREE_DEFAULT_DEPTH);

        $tree = AdminArea::query()
            ->tree($maxDepth)
            ->where('country_id', $country->id)
            ->with('level')
            ->get()
            ->toTree();

        return $this->success(
            data: AdminAreaTreeResource::collection($tree)->resolve(),
            message: 'Country tree fetched.',
        );
    }
}

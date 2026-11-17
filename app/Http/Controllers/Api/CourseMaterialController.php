<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\QueriesResearchData;
use App\Http\Controllers\Controller;
use App\Http\Resources\MaterialResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CourseMaterialController extends Controller
{
    use QueriesResearchData;

    public function index(Request $request, int $id): AnonymousResourceCollection
    {
        $this->ensureCourseExists($id);
        $this->ensureEnrolled($id, $request->user()->getAuthIdentifier());

        $materials = DB::table('materials')->where('course_id', $id)->orderBy('id')->get();

        return MaterialResource::collection($materials);
    }
}

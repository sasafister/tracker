<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The signed-in user's own projects. Another user's project is answered
 * with 404, as if it did not exist.
 */
class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $projects = $request->user()
            ->projects()
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project) => $this->present($project));

        return response()->json($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $project = $request->user()->projects()->create([
            'client_id' => $data['client_id'] ?? null,
            'name' => $data['name'],
            'color' => $data['color'] ?? '#475569',
            'hourly_rate' => $data['hourly_rate'] ?? null,
        ]);

        return response()->json($this->present($project), 201);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $this->ensureOwnedBy($request, $project);

        $data = $this->validated($request, $project);

        $project->name = $data['name'];

        if (isset($data['color'])) {
            $project->color = $data['color'];
        }

        if (array_key_exists('client_id', $data)) {
            $project->client_id = $data['client_id'];
        }

        // An empty rate means "use my default".
        $project->hourly_rate = $data['hourly_rate'] ?? null;
        $project->save();

        return response()->json($this->present($project));
    }

    /**
     * Entries on the project keep their time and the rate they were logged
     * at; they just lose the project.
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->ensureOwnedBy($request, $project);

        $project->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, ?Project $project = null): array
    {
        $userId = $request->user()->id;

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('projects', 'name')
                    ->where('user_id', $userId)
                    ->ignore($project),
            ],
            'color' => ['sometimes', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'client_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where('user_id', $userId),
            ],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ]);
    }

    private function ensureOwnedBy(Request $request, Project $project): void
    {
        abort_unless($project->user_id === $request->user()->id, 404);
    }

    private function present(Project $project): array
    {
        return [
            'id' => $project->id,
            'client_id' => $project->client_id,
            'name' => $project->name,
            'color' => $project->color,
            'hourly_rate' => $project->hourly_rate,
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Rules\TaxId;
use App\Support\TaxId as TaxIdNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The signed-in user's own clients. Another user's client is answered with
 * 404, as if it did not exist.
 */
class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clients = $request->user()
            ->clients()
            ->orderBy('name')
            ->get()
            ->map(fn (Client $client) => $this->present($client));

        return response()->json($clients);
    }

    public function store(Request $request): JsonResponse
    {
        $client = $request->user()->clients()->create($this->validated($request));

        return response()->json($this->present($client), 201);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $this->ensureOwnedBy($request, $client);

        $client->update($this->validated($request, $client));

        return response()->json($this->present($client));
    }

    /**
     * Its projects stay, just without a client.
     */
    public function destroy(Request $request, Client $client): JsonResponse
    {
        $this->ensureOwnedBy($request, $client);

        $client->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, ?Client $client = null): array
    {
        $request->merge([
            'tax_id' => TaxIdNumber::normalize($request->input('tax_id')),
        ]);

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('clients', 'name')
                    ->where('user_id', $request->user()->id)
                    ->ignore($client),
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'tax_id' => ['nullable', new TaxId],
        ]);
    }

    private function ensureOwnedBy(Request $request, Client $client): void
    {
        abort_unless($client->user_id === $request->user()->id, 404);
    }

    private function present(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'address' => $client->address,
            'tax_id' => $client->tax_id,
        ];
    }
}

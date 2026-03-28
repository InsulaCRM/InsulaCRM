<?php

namespace App\Http\Controllers;

use App\Models\SavedView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavedViewController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'entity_type' => 'required|string|in:' . implode(',', SavedView::ENTITY_TYPES),
        ]);

        $userId = auth()->id();

        $views = SavedView::forEntity($request->entity_type)
            ->accessibleBy(auth()->user())
            ->orderBy('name')
            ->get();

        // Look up this user's default for this entity_type
        $defaultViewId = DB::table('saved_view_defaults')
            ->where('user_id', $userId)
            ->where('entity_type', $request->entity_type)
            ->value('saved_view_id');

        // Annotate each view with per-user default flag
        $views->each(function ($view) use ($defaultViewId) {
            $view->is_user_default = $view->id === $defaultViewId;
        });

        return response()->json($views);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'entity_type' => 'required|string|in:' . implode(',', SavedView::ENTITY_TYPES),
            'name' => 'required|string|max:100',
            'filters' => 'required|array',
            'is_shared' => 'boolean',
        ]);

        // Only admin can create shared views
        if (!empty($data['is_shared']) && !auth()->user()->isAdmin()) {
            $data['is_shared'] = false;
        }

        $view = SavedView::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'entity_type' => $data['entity_type'],
            'name' => $data['name'],
            'filters' => $data['filters'],
            'is_shared' => $data['is_shared'] ?? false,
        ]);

        return response()->json($view, 201);
    }

    public function update(Request $request, SavedView $savedView)
    {
        // Own or admin
        if ($savedView->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'filters' => 'sometimes|array',
            'is_shared' => 'sometimes|boolean',
        ]);

        if (isset($data['is_shared']) && !auth()->user()->isAdmin()) {
            unset($data['is_shared']);
        }

        $savedView->update($data);

        return response()->json($savedView);
    }

    public function destroy(SavedView $savedView)
    {
        if ($savedView->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $savedView->delete();

        return response()->json(['success' => true]);
    }

    public function setDefault(SavedView $savedView)
    {
        // Any user can set a default on views accessible to them (own + shared in their tenant)
        $accessible = SavedView::forEntity($savedView->entity_type)
            ->accessibleBy(auth()->user())
            ->where('id', $savedView->id)
            ->exists();

        if (!$accessible) {
            abort(403);
        }

        // Upsert per-user default (one default per user per entity_type)
        DB::table('saved_view_defaults')->updateOrInsert(
            ['user_id' => auth()->id(), 'entity_type' => $savedView->entity_type],
            ['saved_view_id' => $savedView->id, 'updated_at' => now(), 'created_at' => now()],
        );

        return response()->json(['success' => true]);
    }
}

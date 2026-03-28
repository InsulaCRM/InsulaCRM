<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::withCount(['leads', 'deals'])->orderBy('name')->get();

        if (request()->expectsJson()) {
            return response()->json(['tags' => $tags]);
        }

        return view('tags.index', compact('tags'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'required|string|in:blue,green,red,orange,purple,cyan,yellow,pink,teal',
        ]);

        $tag = Tag::firstOrCreate(
            ['tenant_id' => auth()->user()->tenant_id, 'name' => $request->name],
            ['color' => $request->color]
        );

        if ($request->expectsJson()) {
            return response()->json(['tag' => $tag]);
        }

        return redirect()->route('tags.index')->with('success', __('Tag created.'));
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('tags.index')->with('success', __('Tag deleted.'));
    }

    /**
     * Attach a tag to a taggable model (lead or deal) via AJAX.
     */
    public function attach(Request $request)
    {
        $request->validate([
            'tag_id' => ['required', Rule::exists('tags', 'id')->where('tenant_id', auth()->user()->tenant_id)],
            'taggable_type' => 'required|in:lead,deal',
            'taggable_id' => 'required|integer',
        ]);

        $model = $request->taggable_type === 'lead'
            ? \App\Models\Lead::findOrFail($request->taggable_id)
            : \App\Models\Deal::findOrFail($request->taggable_id);

        $model->tags()->syncWithoutDetaching([$request->tag_id]);

        return response()->json(['success' => true]);
    }

    /**
     * Detach a tag from a taggable model via AJAX.
     */
    public function detach(Request $request)
    {
        $request->validate([
            'tag_id' => ['required', Rule::exists('tags', 'id')->where('tenant_id', auth()->user()->tenant_id)],
            'taggable_type' => 'required|in:lead,deal',
            'taggable_id' => 'required|integer',
        ]);

        $model = $request->taggable_type === 'lead'
            ? \App\Models\Lead::findOrFail($request->taggable_id)
            : \App\Models\Deal::findOrFail($request->taggable_id);

        $model->tags()->detach($request->tag_id);

        return response()->json(['success' => true]);
    }
}

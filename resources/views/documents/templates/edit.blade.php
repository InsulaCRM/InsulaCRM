@extends('layouts.app')

@section('title', __('Edit Document Template'))
@section('page-title', __('Edit Document Template'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('pipeline') }}">{{ __('Pipeline') }}</a></li>
<li class="breadcrumb-item"><a href="{{ route('document-templates.index') }}">{{ __('Document Templates') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ $template->name }}</li>
@endsection

@section('content')
<form method="POST" action="{{ route('document-templates.update', $template) }}" id="template-form">
    @csrf
    @method('PUT')
    <div class="row">
        {{-- Left: Editor --}}
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Edit Template') }}</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label required">{{ __('Template Name') }}</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $template->name) }}" placeholder="{{ __('e.g., Standard LOI') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">{{ __('Type') }}</label>
                            <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="">{{ __('Select type...') }}</option>
                                @foreach($types as $key => $label)
                                    <option value="{{ $key }}" {{ old('type', $template->type) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="hidden" name="is_default" value="0">
                            <input type="checkbox" name="is_default" value="1" class="form-check-input" id="is-default" {{ old('is_default', $template->is_default) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is-default">{{ __('Set as default template for this type') }}</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label required mb-0">{{ __('Template Content (HTML)') }}</label>
                            <div>
                                <div class="dropdown d-inline-block">
                                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/><polyline points="7 9 12 4 17 9"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
                                        {{ __('Load Starter Template') }}
                                    </button>
                                    <div class="dropdown-menu">
                                        @foreach($starterTemplates as $key => $starter)
                                            <button type="button" class="dropdown-item starter-template-btn" data-template-key="{{ $key }}">{{ $starter['name'] }}</button>
                                        @endforeach
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-1" id="preview-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/></svg>
                                    {{ __('Preview') }}
                                </button>
                                @if(auth()->user()->tenant->ai_enabled)
                                <button type="button" class="btn btn-sm btn-outline-purple ms-1" id="ai-draft-doc-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles icon-sm" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                                    {{ __('AI Draft') }}
                                </button>
                                @endif
                            </div>
                        </div>
                        @if(auth()->user()->tenant->ai_enabled)
                        <div id="ai-draft-panel" class="card card-sm border-purple mb-2" style="display: none; border-left: 3px solid #ae3ec9;">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles text-purple me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                                    <strong class="text-purple" style="font-size: 13px;">{{ __('AI Document Draft') }}</strong>
                                    <button type="button" class="btn-close ms-auto" id="ai-draft-close" style="font-size: 10px;" aria-label="{{ __('Close') }}"></button>
                                </div>
                                <div class="mb-2">
                                    <input type="text" id="ai-draft-prompt" class="form-control form-control-sm" placeholder="{{ __('Describe what you want the document to contain...') }}">
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-purple" id="ai-draft-generate-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                                        {{ __('Generate') }}
                                    </button>
                                    <label class="form-check form-check-inline mb-0" style="font-size: 12px;">
                                        <input type="checkbox" class="form-check-input" id="ai-draft-append" checked>
                                        <span class="form-check-label">{{ __('Append to existing content') }}</span>
                                    </label>
                                    <div id="ai-draft-loading" class="spinner-border spinner-border-sm text-purple" role="status" style="display: none;">
                                        <span class="visually-hidden">{{ __('Loading...') }}</span>
                                    </div>
                                </div>
                                <div id="ai-draft-error" class="text-danger mt-1" style="display: none; font-size: 12px;"></div>
                            </div>
                        </div>
                        @endif
                        @php
                            $contentPlaceholder = __('Enter HTML template content with {{merge.fields}} placeholders...');
                            $contentHint = __('Use HTML for formatting. Insert merge fields by clicking them in the sidebar or typing {{field.name}} directly.');
                        @endphp
                        <textarea name="content" id="template-content" class="form-control @error('content') is-invalid @enderror" rows="25" style="font-family: 'SFMono-Regular', Menlo, Monaco, Consolas, 'Liberation Mono', monospace; font-size: 13px; line-height: 1.6; tab-size: 4; resize: vertical;" placeholder="{{ $contentPlaceholder }}" required>{{ old('content', $template->content) }}</textarea>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-hint">{{ $contentHint }}</small>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('document-templates.index') }}" class="btn btn-outline-secondary me-2">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2"/><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M14 4l0 4l-6 0l0 -4"/></svg>
                        {{ __('Update Template') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Right: Merge Fields Reference --}}
        <div class="col-lg-4">
            <div class="card mb-3" style="position: sticky; top: 1rem;">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Available Merge Fields') }}</h3>
                </div>
                <div class="card-body p-0" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                    <div class="accordion" id="merge-fields-accordion">
                        @foreach($mergeFields as $category => $fields)
                        @php $catId = \Illuminate\Support\Str::slug($category); @endphp
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button {{ !$loop->first ? 'collapsed' : '' }} py-2" type="button" data-bs-toggle="collapse" data-bs-target="#mf-{{ $catId }}">
                                    <strong>{{ $category }}</strong>
                                    <span class="badge bg-secondary-lt ms-2">{{ count($fields) }}</span>
                                </button>
                            </h2>
                            <div id="mf-{{ $catId }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#merge-fields-accordion">
                                <div class="accordion-body p-0">
                                    <div class="list-group list-group-flush">
                                        @foreach($fields as $field => $label)
                                        <button type="button"
                                                class="list-group-item list-group-item-action py-1 px-3 merge-field-btn"
                                                data-field="{{ $field }}"
                                                title="{{ __('Click to insert') }}">
                                            <code class="text-primary" style="font-size: 12px;">&#123;&#123;{{ $field }}&#125;&#125;</code>
                                            <small class="text-secondary d-block" style="font-size: 11px;">{{ $label }}</small>
                                        </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Preview Modal --}}
<div class="modal modal-blur fade" id="preview-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Template Preview (Sample Data)') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body p-0">
                <div id="preview-loading" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-secondary mt-2">{{ __('Generating preview...') }}</p>
                </div>
                <div id="preview-content" class="p-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var textarea = document.getElementById('template-content');
    var previewModal = new bootstrap.Modal(document.getElementById('preview-modal'));

    // Starter template data
    var starterTemplates = @json($starterTemplates);

    // Insert merge field at cursor position
    document.querySelectorAll('.merge-field-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var field = '{' + '{' + this.getAttribute('data-field') + '}' + '}';
            insertAtCursor(textarea, field);
            textarea.focus();
        });
    });

    function insertAtCursor(el, text) {
        var start = el.selectionStart;
        var end = el.selectionEnd;
        var before = el.value.substring(0, start);
        var after = el.value.substring(end);
        el.value = before + text + after;
        el.selectionStart = el.selectionEnd = start + text.length;
        el.dispatchEvent(new Event('input'));
    }

    // Load starter template
    document.querySelectorAll('.starter-template-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var key = this.getAttribute('data-template-key');
            if (starterTemplates[key]) {
                if (textarea.value.trim() && !confirm('{{ __('This will replace the current content. Continue?') }}')) {
                    return;
                }
                textarea.value = starterTemplates[key].content;
            }
        });
    });

    // Preview button — uses existing template ID for preview
    document.getElementById('preview-btn').addEventListener('click', function() {
        var content = textarea.value.trim();
        if (!content) {
            alert('{{ __('Please enter template content first.') }}');
            return;
        }

        document.getElementById('preview-loading').style.display = 'block';
        document.getElementById('preview-content').innerHTML = '';
        previewModal.show();

        fetch('{{ url("/document-templates/" . $template->id . "/preview") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ content: content })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.getElementById('preview-loading').style.display = 'none';
            document.getElementById('preview-content').innerHTML = data.html || '';
        })
        .catch(function() {
            document.getElementById('preview-loading').style.display = 'none';
            document.getElementById('preview-content').innerHTML = '<div class="alert alert-danger">{{ __('Preview failed. Please try again.') }}</div>';
        });
    });

    // Keyboard shortcuts
    textarea.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'P') {
            e.preventDefault();
            document.getElementById('preview-btn').click();
        }
        if (e.key === 'Tab') {
            e.preventDefault();
            insertAtCursor(this, '    ');
        }
    });

    // AI Draft Document
    var aiDraftBtn = document.getElementById('ai-draft-doc-btn');
    if (aiDraftBtn) {
        var aiPanel = document.getElementById('ai-draft-panel');
        var aiPromptInput = document.getElementById('ai-draft-prompt');
        var aiGenerateBtn = document.getElementById('ai-draft-generate-btn');
        var aiCloseBtn = document.getElementById('ai-draft-close');
        var aiLoading = document.getElementById('ai-draft-loading');
        var aiError = document.getElementById('ai-draft-error');
        var aiAppendCheck = document.getElementById('ai-draft-append');

        aiDraftBtn.addEventListener('click', function() {
            aiPanel.style.display = aiPanel.style.display === 'none' ? 'block' : 'none';
            if (aiPanel.style.display === 'block') {
                aiPromptInput.focus();
            }
        });

        aiCloseBtn.addEventListener('click', function() {
            aiPanel.style.display = 'none';
        });

        aiPromptInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                aiGenerateBtn.click();
            }
        });

        aiGenerateBtn.addEventListener('click', function() {
            var prompt = aiPromptInput.value.trim();
            if (!prompt) {
                aiError.textContent = '{{ __('Please describe what you want the document to contain.') }}';
                aiError.style.display = 'block';
                aiPromptInput.focus();
                return;
            }

            var typeSelect = document.querySelector('select[name="type"]');
            var templateType = typeSelect ? typeSelect.value : '';

            aiError.style.display = 'none';
            aiLoading.style.display = 'inline-block';
            aiGenerateBtn.disabled = true;
            aiDraftBtn.disabled = true;

            fetch('{{ url("/ai/draft-document") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    template_type: templateType,
                    prompt: prompt
                })
            })
            .then(function(r) {
                if (!r.ok) throw new Error(r.statusText);
                return r.json();
            })
            .then(function(res) {
                aiLoading.style.display = 'none';
                aiGenerateBtn.disabled = false;
                aiDraftBtn.disabled = false;

                if (res.content) {
                    if (aiAppendCheck.checked && textarea.value.trim()) {
                        textarea.value = textarea.value + '\n\n' + res.content;
                    } else {
                        textarea.value = res.content;
                    }
                    textarea.dispatchEvent(new Event('input'));
                    aiPromptInput.value = '';
                    aiPanel.style.display = 'none';
                    textarea.focus();
                } else {
                    aiError.textContent = res.error || '{{ __('AI did not return content. Please try again.') }}';
                    aiError.style.display = 'block';
                }
            })
            .catch(function(err) {
                aiLoading.style.display = 'none';
                aiGenerateBtn.disabled = false;
                aiDraftBtn.disabled = false;
                aiError.textContent = '{{ __('AI draft failed. Please check your AI settings and try again.') }}';
                aiError.style.display = 'block';
            });
        });
    }
});
</script>
@endpush

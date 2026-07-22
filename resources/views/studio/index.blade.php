@extends('blueprint-studio::layout')

@section('body')
<div
    x-data="blueprintStudio(@js($project))"
    x-init="init()"
    x-cloak
    class="relative min-h-screen"
>
    {{-- Toast --}}
    <div
        class="pointer-events-none fixed inset-x-0 top-4 z-[60] flex justify-center px-4"
        x-show="toast.visible"
        x-transition
    >
        <div
            class="pointer-events-auto flex max-w-lg items-start gap-3 rounded-xl border px-4 py-3 shadow-lg backdrop-blur-md"
            :class="toast.type === 'error' ? 'border-rose-400/40 bg-rose-50 text-rose-800' : 'bps-toast-ok'"
        >
            <p class="text-sm leading-relaxed" x-text="toast.message"></p>
        </div>
    </div>

    <header class="bps-header relative border-b backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-5 sm:px-6 lg:px-8">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-accent-soft">Laravel Package</p>
                <h1 class="bps-title mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">
                    {{ $brand['name'] ?? 'Laravel Blueprint Studio' }}
                </h1>
                <p class="bps-muted mt-1 text-sm">Multi-model builder · pick what to generate · one click</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="bps-panel hidden rounded-lg border px-3 py-2 text-right sm:block">
                    <p class="bps-muted text-[10px] uppercase tracking-wider">History</p>
                    <p class="bps-title font-mono text-xs" x-text="stats.total ?? 0"></p>
                </div>
                <div class="bps-panel flex items-center gap-1 rounded-xl border p-1">
                    <button type="button" @click="setTheme('light')"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                            :class="theme === 'light' ? 'bg-accent text-ink-950' : 'bps-muted bps-hover'">Light</button>
                    <button type="button" @click="setTheme('dark')"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                            :class="theme === 'dark' ? 'bg-accent text-ink-950' : 'bps-muted bps-hover'">Dark</button>
                </div>
            </div>
        </div>

        <nav class="mx-auto flex max-w-7xl gap-1 px-4 pb-3 sm:px-6 lg:px-8">
            <button type="button" @click="activeTab = 'builder'"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition"
                    :class="activeTab === 'builder' ? 'bg-accent/15 text-accent-soft' : 'bps-muted bps-hover'">Builder</button>
            <button type="button" @click="activeTab = 'history'; loadHistory()"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition"
                    :class="activeTab === 'history' ? 'bg-accent/15 text-accent-soft' : 'bps-muted bps-hover'">History</button>
        </nav>
    </header>

    <main class="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- SINGLE BUILDER PAGE --}}
        <section x-show="activeTab === 'builder'" class="animate-slide-up space-y-6">
            <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
                {{-- LEFT: models + generate actions only --}}
                <div class="space-y-4 lg:col-span-4 lg:sticky lg:top-4">
                    <div class="bps-panel rounded-2xl border p-5 space-y-4">
                        <div>
                            <h2 class="bps-title text-lg font-semibold">Models</h2>
                            <p class="bps-muted mt-1 text-sm">Add models here, edit fields on the right.</p>
                        </div>

                        <div class="flex gap-2">
                            <input type="text" x-model="newModelName" list="models-list"
                                   @keydown.enter.prevent="addModel()"
                                   @input="newModelName = newModelName.replace(/[^A-Za-z0-9_]/g, '')"
                                   placeholder="e.g. Product"
                                   class="bps-input min-w-0 flex-1 rounded-xl border px-3 py-2.5 text-sm focus:border-accent/50 focus:ring-2 focus:ring-accent/20">
                            <button type="button" @click="addModel()" :disabled="!newModelName.trim()"
                                    class="shrink-0 rounded-xl bg-accent px-3 py-2.5 text-xs font-semibold text-ink-950 disabled:opacity-40">
                                Add
                            </button>
                        </div>
                        <datalist id="models-list">
                            <template x-for="m in project.models || []" :key="m">
                                <option :value="m"></option>
                            </template>
                        </datalist>

                        <ul class="max-h-56 space-y-1.5 overflow-y-auto scroll-thin" x-show="models.length">
                            <template x-for="m in models" :key="m.id">
                                <li class="flex items-center gap-1">
                                    <button type="button" @click="selectModel(m.id)"
                                            class="min-w-0 flex-1 rounded-xl border px-3 py-2 text-left transition"
                                            :class="activeModelId === m.id ? 'border-accent bg-accent/10' : 'bps-border bps-hover'">
                                        <p class="bps-title truncate text-sm font-semibold" x-text="m.name || 'Untitled'"></p>
                                        <p class="bps-muted font-mono text-[10px]">
                                            <span x-text="tableNameFor(m.name)"></span>
                                            · <span x-text="(m.fields || []).length"></span> fields
                                        </p>
                                    </button>
                                    <button type="button" @click="removeModel(m.id)"
                                            class="rounded-lg px-2 py-2 text-xs text-rose-500 hover:bg-rose-500/10"
                                            title="Remove model">&times;</button>
                                </li>
                            </template>
                        </ul>

                        <p class="bps-muted text-[11px]" x-show="activeModel">
                            Selected: <span class="font-semibold text-accent-soft" x-text="activeModel?.name"></span>
                            <span x-show="modelExists" class="text-amber-600"> · update existing</span>
                        </p>
                        <p class="bps-muted text-[11px]" x-show="!activeModel">Add a model, then select it.</p>

                        <template x-if="activeModel">
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border bps-border px-3 py-2.5">
                                <input type="checkbox" x-model="activeModel.soft_deletes" class="h-4 w-4 rounded text-accent">
                                <span class="bps-soft text-sm">Soft deletes</span>
                            </label>
                        </template>

                        <div class="flex flex-col gap-2 border-t bps-border pt-4">
                            <button type="button" @click="generateActive()" :disabled="busy || !activeModel?.name || !hasAnyComponent"
                                    class="w-full rounded-xl bg-accent px-4 py-3 text-sm font-semibold text-ink-950 transition hover:bg-accent-soft disabled:opacity-40">
                                <span x-show="!busy || busyAction !== 'one'" x-text="modelExists ? 'Update & generate this' : 'Generate this model'"></span>
                                <span x-show="busy && busyAction === 'one'">Generating…</span>
                            </button>
                            <button type="button" @click="generateAllModels()" :disabled="busy || !models.length || !hasAnyComponent"
                                    class="w-full rounded-xl border bps-border px-4 py-2.5 text-sm font-semibold bps-soft transition bps-hover disabled:opacity-40">
                                <span x-show="!busy || busyAction !== 'all'" x-text="'Generate all (' + models.length + ')'"></span>
                                <span x-show="busy && busyAction === 'all'">Generating all…</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: options + fields + output --}}
                <div class="space-y-5 lg:col-span-8">
                    {{-- Options bar --}}
                    <div class="bps-panel rounded-2xl border p-5 space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 class="bps-title text-sm font-semibold">Generate options</h3>
                                <p class="bps-muted mt-0.5 text-xs">Base, components, and import tools</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="openPasteModal()" :disabled="!activeModel"
                                        class="rounded-lg border bps-border px-3 py-1.5 text-xs font-semibold bps-soft transition bps-hover disabled:opacity-40">
                                    Paste rows
                                </button>
                                <button type="button" @click="$refs.blueprintFile.click()"
                                        class="rounded-lg border bps-border px-3 py-1.5 text-xs font-semibold bps-soft transition bps-hover">
                                    Import Blueprint
                                </button>
                                <input type="file" x-ref="blueprintFile" class="hidden"
                                       accept=".yaml,.yml,.txt,.draft"
                                       @change="onBlueprintFile($event)">
                            </div>
                        </div>

                        <div>
                            <p class="bps-muted mb-2 text-[10px] font-medium uppercase tracking-wider">Controller base</p>
                            <div class="grid grid-cols-3 gap-2">
                                <template x-for="opt in baseOptions" :key="opt.id">
                                    <button type="button" @click="sharedBase = opt.id"
                                            class="rounded-xl border px-3 py-2.5 text-center transition"
                                            :class="sharedBase === opt.id ? 'border-accent bg-accent/10' : 'bps-border'">
                                        <p class="bps-title text-sm font-semibold" x-text="opt.label"></p>
                                        <p class="bps-muted mt-0.5 text-[10px]" x-text="opt.hint"></p>
                                    </button>
                                </template>
                            </div>
                            <div class="mt-3 rounded-xl border bps-border bg-accent/5 px-3 py-2.5 font-mono text-[11px] bps-soft space-y-1" x-show="activeModel?.name">
                                <p><span class="bps-muted">Controller:</span> <span class="text-accent-soft" x-text="basePreview.controller"></span></p>
                                <p><span class="bps-muted">Views:</span> <span class="text-accent-soft" x-text="basePreview.views"></span></p>
                                <p><span class="bps-muted">Requests:</span> <span class="text-accent-soft" x-text="basePreview.requests"></span></p>
                                <p><span class="bps-muted">Route:</span> <span class="text-accent-soft" x-text="basePreview.route"></span></p>
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <p class="bps-muted text-[10px] font-medium uppercase tracking-wider">Components</p>
                                <div class="flex gap-3">
                                    <button type="button" @click="selectAllComponents(true)" class="text-[10px] text-accent-soft hover:underline">All on</button>
                                    <button type="button" @click="selectAllComponents(false)" class="text-[10px] bps-muted hover:underline">All off</button>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
                                <template x-for="opt in componentOptions" :key="opt.key">
                                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border bps-border px-3 py-2.5 transition"
                                           :class="components[opt.key] ? 'border-accent/40 bg-accent/5' : 'opacity-55'">
                                        <input type="checkbox" x-model="components[opt.key]" class="h-3.5 w-3.5 shrink-0 rounded text-accent">
                                        <span class="min-w-0">
                                            <span class="bps-title block text-xs font-semibold" x-text="opt.label"></span>
                                            <span class="bps-muted hidden text-[10px] sm:block" x-text="opt.hint"></span>
                                        </span>
                                    </label>
                                </template>
                            </div>
                            <p class="bps-muted mt-2 text-[11px]">Uncheck to skip that part when generating.</p>
                        </div>
                    </div>

                    {{-- Shown only after importing a blueprint file --}}
                    <div class="bps-panel rounded-2xl border p-5" x-show="showBlueprintPanel" x-transition>
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 class="bps-title text-sm font-semibold">Blueprint file</h3>
                                <p class="bps-muted mt-1 text-xs">
                                    <span x-text="blueprintFileName || 'Imported draft'"></span>
                                    · edit if needed, then import all models
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="closeBlueprintPanel()"
                                        class="rounded-lg border bps-border px-3 py-1.5 text-xs bps-soft">Hide</button>
                                <button type="button" @click="loadDraftIntoForm()" :disabled="!draftText.trim() || busy"
                                        class="rounded-lg bg-accent/15 px-3 py-1.5 text-xs font-semibold text-accent-soft disabled:opacity-40">Load to builder</button>
                                <button type="button" @click="importDraft()" :disabled="!draftText.trim() || busy"
                                        class="rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-ink-950 disabled:opacity-40">Import all models</button>
                            </div>
                        </div>
                        <textarea x-model="draftText" rows="10"
                                  class="bps-input mt-3 w-full rounded-xl border px-3 py-2 font-mono text-[12px] leading-relaxed focus:border-accent/50 focus:ring-2 focus:ring-accent/20"></textarea>
                        <div class="mt-3 rounded-lg border bps-border px-3 py-2" x-show="draftPreview.length">
                            <p class="bps-muted text-[10px] uppercase tracking-wider">Will generate</p>
                            <ul class="mt-1 space-y-1 font-mono text-[11px] bps-soft">
                                <template x-for="m in draftPreview" :key="m.name">
                                    <li>
                                        <span class="text-accent-soft" x-text="m.name"></span>
                                        — <span x-text="(m.fields || []).length"></span> fields
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    {{-- Fields table --}}
                    <div class="bps-panel overflow-hidden rounded-2xl border">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b bps-border px-5 py-4">
                            <div>
                                <h3 class="bps-title text-sm font-semibold">
                                    Migration fields
                                    <span class="bps-muted font-normal" x-show="activeModel" x-text="'· ' + (activeModel?.name || '')"></span>
                                </h3>
                                <p class="bps-muted text-xs">id & timestamps locked · select a model on the left</p>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" @click="openPasteModal()" :disabled="!activeModel"
                                        class="rounded-lg border bps-border px-3 py-1.5 text-xs font-medium bps-soft disabled:opacity-40">
                                    Paste rows
                                </button>
                                <button type="button" @click="addField()" :disabled="!activeModel"
                                        class="rounded-lg bg-accent/15 px-3 py-1.5 text-xs font-medium text-accent-soft hover:bg-accent/25 disabled:opacity-40">
                                    + Add field
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto scroll-thin">
                            <table class="min-w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b bps-border text-[11px] uppercase tracking-wider bps-muted">
                                        <th class="px-4 py-3 font-medium">Column</th>
                                        <th class="px-4 py-3 font-medium">Type</th>
                                        <th class="px-4 py-3 font-medium">Nullable</th>
                                        <th class="px-4 py-3 font-medium">Unique</th>
                                        <th class="px-4 py-3 font-medium">Default / Meta</th>
                                        <th class="px-4 py-3 font-medium"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(field, index) in visualFields" :key="index">
                                        <tr class="border-b bps-border transition bps-hover" :class="field.locked && 'field-locked'">
                                            <td class="px-4 py-2.5">
                                                <input type="text" x-model="field.name"
                                                       :disabled="field.locked" :readonly="field.locked"
                                                       class="bps-input w-32 rounded-lg border px-2.5 py-1.5 font-mono text-xs disabled:cursor-not-allowed">
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <template x-if="field.locked">
                                                    <span class="font-mono text-xs bps-muted" x-text="field.type"></span>
                                                </template>
                                                <template x-if="!field.locked">
                                                    <select x-model="field.type" class="bps-input rounded-lg border px-2.5 py-1.5 text-xs">
                                                        <template x-for="t in fieldTypes" :key="t.value">
                                                            <option :value="t.value" x-text="t.label"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <input type="checkbox" x-model="field.nullable" :disabled="field.locked" class="h-3.5 w-3.5 rounded text-accent">
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <input type="checkbox" x-model="field.unique" :disabled="field.locked" class="h-3.5 w-3.5 rounded text-accent">
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <template x-if="field.locked">
                                                    <span class="text-xs bps-muted">auto</span>
                                                </template>
                                                <template x-if="!field.locked && field.type === 'enum'">
                                                    <input type="text" x-model="field.enum_values" placeholder="active, inactive"
                                                           class="bps-input w-40 rounded-lg border px-2.5 py-1.5 font-mono text-xs">
                                                </template>
                                                <template x-if="!field.locked && field.type === 'foreignId'">
                                                    <input type="text" x-model="field.foreign_table" placeholder="users"
                                                           class="bps-input w-28 rounded-lg border px-2.5 py-1.5 font-mono text-xs">
                                                </template>
                                                <template x-if="!field.locked && field.type !== 'enum' && field.type !== 'foreignId'">
                                                    <input type="text" x-model="field.default" placeholder="—"
                                                           class="bps-input w-24 rounded-lg border px-2.5 py-1.5 font-mono text-xs">
                                                </template>
                                            </td>
                                            <td class="px-4 py-2.5 text-right">
                                                <button type="button" x-show="!field.locked" @click="removeField(index)"
                                                        class="text-xs text-rose-500 hover:text-rose-600">Remove</button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Output on the right --}}
                    <div class="bps-panel rounded-2xl border p-5" x-show="lastResult" x-transition>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="bps-muted text-[10px] uppercase tracking-wider">Created / updated files</p>
                            <p class="font-mono text-xs text-accent-soft" x-show="routeUri" x-text="'Open: ' + routeUri"></p>
                        </div>
                        <ul class="mt-3 grid gap-1.5 sm:grid-cols-2 font-mono text-[11px] bps-soft">
                            <template x-for="file in (lastResult?.files || [])" :key="file">
                                <li class="truncate rounded-lg border bps-border px-2.5 py-1.5" x-text="file"></li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Paste multiple rows modal --}}
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 x-show="pasteModalOpen" x-cloak
                 @keydown.escape.window="pasteModalOpen && closePasteModal()">
                <div class="absolute inset-0 bg-ink-950/50 backdrop-blur-sm" @click="closePasteModal()"></div>
                <div class="bps-panel relative z-10 w-full max-w-lg rounded-2xl border p-6 shadow-xl" @click.stop>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="bps-title text-lg font-semibold">Paste multiple rows</h3>
                            <p class="bps-muted mt-1 text-sm">One field name per line (or comma-separated). Optional <span class="font-mono text-xs">name:type</span>.</p>
                        </div>
                        <button type="button" @click="closePasteModal()" class="bps-muted text-lg leading-none">&times;</button>
                    </div>
                    <textarea x-model="pasteText" rows="8" x-ref="pasteArea"
                              placeholder="title&#10;body&#10;price&#10;is_active&#10;published_at&#10;user_id:foreignId"
                              class="bps-input mt-4 w-full rounded-xl border px-3 py-2 font-mono text-sm focus:border-accent/50 focus:ring-2 focus:ring-accent/20"></textarea>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" @click="closePasteModal()"
                                class="rounded-xl border bps-border px-4 py-2 text-sm bps-soft">Cancel</button>
                        <button type="button" @click="applyPaste(); closePasteModal()" :disabled="!pasteText.trim()"
                                class="rounded-xl bg-accent px-4 py-2 text-sm font-semibold text-ink-950 disabled:opacity-40">
                            Add fields
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- HISTORY --}}
        <section x-show="activeTab === 'history'" class="space-y-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="bps-title text-lg font-semibold">History</h2>
                    <p class="bps-muted mt-1 text-sm">Click <span class="font-medium">Edit</span> to reload a model into the builder, remove fields you do not need, then generate again to update files.</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="loadHistory()" class="bps-panel rounded-lg border px-3 py-2 text-xs font-medium bps-soft">Refresh</button>
                    <button type="button" @click="clearHistory()" class="rounded-lg border border-rose-400/40 px-3 py-2 text-xs font-medium text-rose-500">Clear</button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <template x-for="stat in [{label:'Total',key:'total'},{label:'Models',key:'models'},{label:'CRUD',key:'full_crud'},{label:'Failed',key:'failures'}]" :key="stat.key">
                    <div class="bps-panel rounded-xl border px-4 py-3">
                        <p class="bps-muted text-[10px] uppercase tracking-wider" x-text="stat.label"></p>
                        <p class="bps-title mt-1 text-2xl font-semibold" x-text="stats[stat.key] ?? 0"></p>
                    </div>
                </template>
            </div>

            <div class="bps-panel overflow-hidden rounded-2xl border">
                <div class="max-h-[28rem] divide-y overflow-y-auto scroll-thin" style="border-color: var(--bps-border)">
                    <template x-if="!history.length">
                        <p class="bps-muted px-5 py-12 text-center text-sm">No history yet.</p>
                    </template>
                    <template x-for="item in history" :key="item.id">
                        <article class="bps-hover px-5 py-4 transition">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-md px-2 py-0.5 font-mono text-[10px] uppercase tracking-wide bg-accent/15 text-accent-soft" x-text="item.action"></span>
                                        <span class="bps-title text-sm font-medium" x-text="item.resource"></span>
                                        <span class="bps-muted text-[11px]" x-show="historyFieldNames(item).length"
                                              x-text="'· ' + historyFieldNames(item).join(', ')"></span>
                                    </div>
                                    <p class="bps-muted mt-1 text-xs" x-text="item.message"></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                            x-show="canEditHistory(item)"
                                            @click="editFromHistory(item)"
                                            class="rounded-lg bg-accent/15 px-3 py-1.5 text-xs font-semibold text-accent-soft hover:bg-accent/25">
                                        Edit
                                    </button>
                                    <time class="bps-muted font-mono text-[10px]" x-text="formatDate(item.created_at)"></time>
                                </div>
                            </div>
                            <template x-if="item.files?.length">
                                <ul class="mt-3 flex flex-wrap gap-1.5">
                                    <template x-for="f in item.files" :key="f">
                                        <li class="rounded-md border bps-border px-2 py-1 font-mono text-[10px] bps-soft" x-text="f"></li>
                                    </template>
                                </ul>
                            </template>
                        </article>
                    </template>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t bps-border py-8 text-center text-xs bps-muted">
        <p class="bps-soft">
            Laravel Blueprint Studio · Developed by
            <a href="https://imrandev.bd/" target="_blank" rel="noopener noreferrer"
               class="font-medium text-accent-soft underline-offset-2 hover:underline">Imran Dev BD</a>
        </p>
        <p class="mt-2">
            Any issue? Contact me please:
            <a href="https://imrandev.bd/contact" target="_blank" rel="noopener noreferrer"
               class="font-medium text-accent-soft underline-offset-2 hover:underline">imrandev.bd/contact</a>
        </p>
    </footer>
</div>
@endsection

@push('scripts')
<script>
function blueprintStudio(initialProject) {
    const knownTypes = ['string','text','longText','integer','bigInteger','boolean','decimal','float','date','dateTime','time','json','uuid','email','password','foreignId','enum'];
    let uid = 1;

    return {
        project: initialProject || {},
        fieldTypes: initialProject?.field_types || [],
        defaults: initialProject?.default_columns || [
            { name: 'id', type: 'id', locked: true },
            { name: 'timestamps', type: 'timestamps', locked: true },
        ],
        stats: initialProject?.stats || {},
        history: [],
        theme: (typeof localStorage !== 'undefined' && localStorage.getItem('bps-theme')) || 'dark',
        activeTab: 'builder',
        baseOptions: [
            { id: 'user', label: 'User', hint: 'User/ folder' },
            { id: 'admin', label: 'Admin', hint: 'Admin/ folder' },
            { id: 'guest', label: 'Guest', hint: 'Guest/ folder' },
        ],
        componentOptions: [
            { key: 'model', label: 'Model', hint: 'Eloquent class + fillable' },
            { key: 'migration', label: 'Migration', hint: 'database table columns' },
            { key: 'controller', label: 'Controller', hint: 'CRUD + form requests' },
            { key: 'route', label: 'Route', hint: 'auto add to routes/web.php' },
            { key: 'view', label: 'View', hint: 'index, create, edit, show' },
        ],
        components: {
            model: true,
            migration: true,
            controller: true,
            route: true,
            view: true,
        },
        models: [],
        activeModelId: null,
        newModelName: '',
        sharedBase: 'user',
        pasteText: '',
        pasteModalOpen: false,
        draftText: '',
        draftExample: initialProject?.draft_example || '',
        draftPreview: [],
        showBlueprintPanel: false,
        blueprintFileName: '',
        busy: false,
        busyAction: null,
        lastResult: null,
        routeHint: '',
        routeUri: '',
        toast: { visible: false, message: '', type: 'success' },

        get activeModel() {
            return this.models.find(m => m.id === this.activeModelId) || null;
        },

        get visualFields() {
            const fields = this.activeModel?.fields || [];
            return [...this.defaults.map(d => ({ ...d, locked: true })), ...fields];
        },

        get modelExists() {
            const name = (this.activeModel?.name || '').trim();
            if (!name) return false;
            return (this.project.models || []).some(m => m.toLowerCase() === name.toLowerCase());
        },

        get hasAnyComponent() {
            return Object.values(this.components).some(Boolean);
        },

        get basePreview() {
            const name = this.activeModel?.name || 'Product';
            const folder = this.tableNameFor(name);
            const base = this.sharedBase || 'user';
            const Base = base.charAt(0).toUpperCase() + base.slice(1);
            return {
                controller: `app/Http/Controllers/${Base}/${name}Controller.php`,
                views: `resources/views/${base}/${folder}/`,
                requests: `app/Http/Requests/${Base}/`,
                route: `/${base}/${folder}  ·  ${base}.${folder}.*`,
            };
        },

        setTheme(theme) {
            this.theme = theme;
            document.documentElement.setAttribute('data-theme', theme);
            try { localStorage.setItem('bps-theme', theme); } catch (e) {}
        },

        async init() {
            this.setTheme(this.theme);
            if (initialProject?.default_components) {
                this.components = { ...this.components, ...initialProject.default_components };
            }
            this.addModel('Product', false);
            try {
                const data = await window.BlueprintStudio.request(window.BlueprintStudio.routes.bootstrap);
                this.project = data.data || this.project;
                this.fieldTypes = data.data?.field_types || this.fieldTypes;
                this.defaults = data.data?.default_columns || this.defaults;
                this.draftExample = data.data?.draft_example || this.draftExample;
                if (data.data?.default_components) {
                    this.components = { ...this.components, ...data.data.default_components };
                }
                this.stats = data.data?.stats || {};
                this.history = data.history || [];
            } catch (e) {
                this.notify(e.message, 'error');
            }
        },

        selectAllComponents(on = true) {
            Object.keys(this.components).forEach(k => this.components[k] = !!on);
        },

        makeModel(name = '') {
            return {
                id: 'm' + (uid++),
                name: name,
                soft_deletes: false,
                fields: name ? [{
                    name: 'name',
                    type: 'string',
                    nullable: false,
                    unique: false,
                    default: '',
                    enum_values: 'active, inactive',
                    foreign_table: '',
                    locked: false,
                }] : [],
            };
        },

        addModel(name = null, select = true) {
            const raw = (name ?? this.newModelName ?? '').toString().trim().replace(/[^A-Za-z0-9_]/g, '');
            if (!raw) {
                this.notify('Enter a model name', 'error');
                return;
            }
            const studly = raw.charAt(0).toUpperCase() + raw.slice(1);
            if (this.models.some(m => m.name.toLowerCase() === studly.toLowerCase())) {
                const existing = this.models.find(m => m.name.toLowerCase() === studly.toLowerCase());
                this.selectModel(existing.id);
                this.notify(`${studly} already in list — selected`);
                this.newModelName = '';
                return;
            }
            const model = this.makeModel(studly);
            this.models.push(model);
            this.newModelName = '';
            if (select) this.selectModel(model.id);
        },

        selectModel(id) {
            this.activeModelId = id;
        },

        removeModel(id) {
            const idx = this.models.findIndex(m => m.id === id);
            if (idx < 0) return;
            this.models.splice(idx, 1);
            if (this.activeModelId === id) {
                this.activeModelId = this.models[idx]?.id || this.models[idx - 1]?.id || null;
            }
        },

        tableNameFor(name) {
            if (!name) return '';
            const snake = name.replace(/([a-z])([A-Z])/g, '$1_$2').toLowerCase();
            if (snake.endsWith('s')) return snake;
            if (snake.endsWith('y')) return snake.slice(0, -1) + 'ies';
            return snake + 's';
        },

        openPasteModal() {
            if (!this.activeModel) {
                this.notify('Select a model first', 'error');
                return;
            }
            this.pasteModalOpen = true;
            this.$nextTick(() => {
                try { this.$refs.pasteArea?.focus(); } catch (e) {}
            });
        },

        closePasteModal() {
            this.pasteModalOpen = false;
        },

        closeBlueprintPanel() {
            this.showBlueprintPanel = false;
        },

        async onBlueprintFile(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            try {
                const text = await file.text();
                this.draftText = text;
                this.blueprintFileName = file.name;
                this.showBlueprintPanel = true;
                this.draftPreview = [];
                const data = await window.BlueprintStudio.request(window.BlueprintStudio.routes.draftParse, {
                    method: 'POST',
                    body: JSON.stringify({ draft: text }),
                });
                this.draftPreview = data.data?.models || [];
                this.notify(`Loaded ${file.name} · ${this.draftPreview.length} model(s)`);
            } catch (e) {
                this.showBlueprintPanel = true;
                this.notify(e.message || 'Could not read Blueprint file', 'error');
            }
            event.target.value = '';
        },

        mapDraftField(f) {
            return {
                name: f.name || '',
                type: knownTypes.includes(f.type) ? f.type : 'string',
                nullable: !!f.nullable,
                unique: !!f.unique,
                default: f.default || '',
                enum_values: f.enum_values || 'active, inactive',
                foreign_table: f.foreign_table || '',
                locked: false,
            };
        },

        async loadDraftIntoForm() {
            if (!this.draftText.trim()) return;
            this.busy = true;
            try {
                const data = await window.BlueprintStudio.request(window.BlueprintStudio.routes.draftParse, {
                    method: 'POST',
                    body: JSON.stringify({ draft: this.draftText }),
                });
                const models = data.data?.models || [];
                this.draftPreview = models;
                if (!models.length) {
                    this.notify('No models found in draft', 'error');
                    return;
                }
                this.models = [];
                models.forEach((m, i) => {
                    const model = this.makeModel(m.name);
                    model.soft_deletes = !!m.soft_deletes;
                    model.fields = (m.fields || []).map(f => this.mapDraftField(f));
                    this.models.push(model);
                    if (i === 0) this.activeModelId = model.id;
                });
                this.notify(`Loaded ${models.length} model(s) into builder`);
            } catch (e) {
                this.notify(e.message || 'Parse failed', 'error');
            } finally {
                this.busy = false;
            }
        },

        async importDraft() {
            if (!this.draftText.trim()) return;
            if (!this.hasAnyComponent) {
                this.notify('Select at least one generate option', 'error');
                return;
            }
            this.busy = true;
            this.busyAction = 'all';
            try {
                const result = await window.BlueprintStudio.request(window.BlueprintStudio.routes.draftImport, {
                    method: 'POST',
                    body: JSON.stringify({
                        draft: this.draftText,
                        base: this.sharedBase,
                        components: { ...this.components },
                    }),
                });
                this.draftPreview = result.parsed?.models || [];
                const files = [];
                (result.results || []).forEach(r => files.push(...this.resultFiles(r)));
                this.lastResult = { files: [...new Set(files)] };
                const first = result.results?.[0];
                if (first) {
                    this.routeUri = first.route?.uri || '';
                    this.routeHint = first.route_hint || '';
                }
                this.notify(result.message || 'Draft imported');
                await this.refreshMeta();
                this.activeTab = 'history';
            } catch (e) {
                this.notify(e.message || 'Import failed', 'error');
            } finally {
                this.busy = false;
                this.busyAction = null;
            }
        },

        addField(name = '', type = 'string') {
            if (!this.activeModel) {
                this.notify('Select a model first', 'error');
                return;
            }
            this.activeModel.fields.push({
                name,
                type: knownTypes.includes(type) ? type : 'string',
                nullable: false,
                unique: false,
                default: '',
                enum_values: 'active, inactive',
                foreign_table: '',
                locked: false,
            });
        },

        removeField(visualIndex) {
            if (!this.activeModel) return;
            const idx = visualIndex - this.defaults.length;
            if (idx >= 0) this.activeModel.fields.splice(idx, 1);
        },

        applyPaste() {
            if (!this.activeModel) return;
            const raw = this.pasteText.trim();
            if (!raw) return;

            const tokens = raw.split(/[\n,;]+/).map(s => s.trim()).filter(Boolean);
            const existing = new Set(this.activeModel.fields.map(f => f.name.toLowerCase()));
            let added = 0;

            tokens.forEach(token => {
                let name = token;
                let type = 'string';
                if (token.includes(':')) {
                    const parts = token.split(':');
                    name = parts[0].trim();
                    type = (parts[1] || 'string').trim();
                }
                name = name.replace(/[^A-Za-z0-9_]/g, '');
                if (!name || ['id', 'timestamps', 'created_at', 'updated_at'].includes(name.toLowerCase())) return;
                if (existing.has(name.toLowerCase())) return;

                if (!token.includes(':')) {
                    if (/_id$/i.test(name)) type = 'foreignId';
                    else if (/email/i.test(name)) type = 'email';
                    else if (/password/i.test(name)) type = 'password';
                    else if (/^(is_|has_|can_)/i.test(name) || /^(active|published|enabled)$/i.test(name)) type = 'boolean';
                    else if (/(_at|date)$/i.test(name)) type = 'dateTime';
                    else if (/(price|amount|total|cost)/i.test(name)) type = 'decimal';
                    else if (/(count|qty|quantity|stock|age)/i.test(name)) type = 'integer';
                    else if (/(body|content|description|bio)/i.test(name)) type = 'text';
                }

                this.addField(name, type);
                existing.add(name.toLowerCase());
                added++;
            });

            this.pasteText = '';
            this.notify(added ? `Added ${added} field${added === 1 ? '' : 's'}` : 'No new fields to add');
        },

        fieldPayload(model) {
            return (model.fields || []).map(f => ({
                name: f.name,
                type: f.type,
                nullable: !!f.nullable,
                unique: !!f.unique,
                default: f.default || null,
                enum_values: f.enum_values || null,
                foreign_table: f.foreign_table || null,
            })).filter(f => f.name);
        },

        resultFiles(result) {
            if (!result) return [];
            const files = [];
            if (result.model?.relative) files.push(result.model.relative);
            if (result.migration?.relative) files.push(result.migration.relative);
            if (result.controller?.relative) files.push(result.controller.relative);
            if (result.layout?.created && result.layout?.relative) files.push(result.layout.relative);
            if (result.route?.updated && result.route?.relative) files.push(result.route.relative);
            (result.requests || []).forEach(r => r.relative && files.push(r.relative));
            (result.views || []).forEach(v => v.relative && files.push(v.relative));
            return files;
        },

        async generateActive() {
            if (!this.activeModel?.name) return;
            if (!this.hasAnyComponent) {
                this.notify('Select at least one generate option', 'error');
                return;
            }
            this.busy = true;
            this.busyAction = 'one';
            try {
                const result = await window.BlueprintStudio.request(window.BlueprintStudio.routes.crud, {
                    method: 'POST',
                    body: JSON.stringify({
                        name: this.activeModel.name,
                        soft_deletes: !!this.activeModel.soft_deletes,
                        base: this.sharedBase,
                        fields: this.fieldPayload(this.activeModel),
                        components: { ...this.components },
                    }),
                });
                this.lastResult = { ...result, files: this.resultFiles(result) };
                this.routeHint = result.route_hint || '';
                this.routeUri = result.route?.uri || '';
                this.notify(result.message || 'Generated');
                await this.refreshMeta();
            } catch (e) {
                this.notify(e.message || 'Generate failed', 'error');
            } finally {
                this.busy = false;
                this.busyAction = null;
            }
        },

        async generateAllModels() {
            const ready = this.models.filter(m => (m.name || '').trim());
            if (!ready.length) {
                this.notify('Add at least one model', 'error');
                return;
            }
            if (!this.hasAnyComponent) {
                this.notify('Select at least one generate option', 'error');
                return;
            }
            this.busy = true;
            this.busyAction = 'all';
            try {
                const result = await window.BlueprintStudio.request(window.BlueprintStudio.routes.crudBatch, {
                    method: 'POST',
                    body: JSON.stringify({
                        base: this.sharedBase,
                        components: { ...this.components },
                        models: ready.map(m => ({
                            name: m.name,
                            soft_deletes: !!m.soft_deletes,
                            fields: this.fieldPayload(m),
                        })),
                    }),
                });
                const files = [];
                (result.results || []).forEach(r => files.push(...this.resultFiles(r)));
                this.lastResult = { files: [...new Set(files)] };
                const first = result.results?.[0];
                if (first) {
                    this.routeUri = first.route?.uri || '';
                    this.routeHint = first.route_hint || '';
                }
                this.notify(result.message || 'All models generated');
                await this.refreshMeta();
                this.activeTab = 'history';
            } catch (e) {
                this.notify(e.message || 'Batch generate failed', 'error');
            } finally {
                this.busy = false;
                this.busyAction = null;
            }
        },

        async loadHistory() {
            const data = await window.BlueprintStudio.request(window.BlueprintStudio.routes.history);
            this.history = data.data || [];
            this.stats = data.stats || this.stats;
        },

        canEditHistory(item) {
            if (!item || item.resource === 'draft' || item.resource === 'batch') return false;
            const fields = item.payload?.fields;
            return Array.isArray(fields) && item.resource;
        },

        historyFieldNames(item) {
            const fields = item?.payload?.fields || [];
            return fields.map(f => f.name).filter(n => n && !['id', 'timestamps'].includes(n));
        },

        editFromHistory(item) {
            if (!this.canEditHistory(item)) {
                this.notify('This history item has no editable fields', 'error');
                return;
            }
            const payload = item.payload || {};
            const fields = (payload.fields || []).filter(f => {
                const name = (f.name || '').toLowerCase();
                const type = (f.type || '').toLowerCase();
                return name && !['id', 'timestamps'].includes(name) && !['id', 'timestamps'].includes(type) && !f.locked;
            });

            let model = this.models.find(m => m.name.toLowerCase() === item.resource.toLowerCase());
            if (!model) {
                model = this.makeModel(item.resource);
                this.models.push(model);
            }
            model.soft_deletes = !!(payload.options?.soft_deletes || payload.soft_deletes);
            model.fields = fields.map(f => this.mapDraftField(f));
            if (payload.components) {
                this.components = { ...this.components, ...payload.components };
            }
            this.sharedBase = payload.base || this.sharedBase;
            this.selectModel(model.id);
            this.activeTab = 'builder';
            this.notify(`Loaded ${item.resource} — adjust fields/components, then generate`);
        },

        async clearHistory() {
            if (!confirm('Clear all history?')) return;
            await window.BlueprintStudio.request(window.BlueprintStudio.routes.historyClear, { method: 'DELETE' });
            this.history = [];
            this.stats = { total: 0, models: 0, controllers: 0, full_crud: 0, failures: 0 };
            this.notify('History cleared');
        },

        async refreshMeta() {
            try {
                const data = await window.BlueprintStudio.request(window.BlueprintStudio.routes.bootstrap);
                this.project = data.data || this.project;
                this.stats = data.data?.stats || this.stats;
                this.history = data.history || this.history;
            } catch (_) {}
        },

        notify(message, type = 'success') {
            this.toast = { visible: true, message, type };
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => this.toast.visible = false, 3800);
        },

        formatDate(value) {
            if (!value) return '';
            try { return new Date(value).toLocaleString(); } catch { return value; }
        },
    };
}
</script>
@endpush

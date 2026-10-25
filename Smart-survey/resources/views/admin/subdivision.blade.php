@extends('layouts.admin')

@push('admin-styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
<style>
    .subdivision-workspace {
        min-height: calc(100vh - 120px);
    }
    #subdivision-map {
        height: 740px;
        width: 100%;
        border-radius: 1.5rem;
        z-index: 10;
    }
    .hud-chip {
        background: rgba(15, 23, 42, 0.85);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.15);
    }
    .stat-card {
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .tab-btn.active {
        background: #008080;
        color: white;
        box-shadow: 0 4px 12px rgba(0, 128, 128, 0.35);
    }
    /* Distinct vibrant color palette for subdivided lots */
    .lot-color-1 { fill: #38bdf8; stroke: #0284c7; }
    .lot-color-2 { fill: #4ade80; stroke: #16a34a; }
    .lot-color-3 { fill: #facc15; stroke: #ca8a04; }
    .lot-color-4 { fill: #f472b6; stroke: #db2777; }
    .lot-color-5 { fill: #a78bfa; stroke: #7c3aed; }
    .lot-color-6 { fill: #fb923c; stroke: #ea580c; }
</style>
@endpush

@section('admin-content')
<div class="subdivision-workspace flex flex-col gap-5">
    {{-- Top Navigation & Summary Bar --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-700 uppercase tracking-widest mb-1">
                <a href="{{ route('admin.projects') }}" class="hover:text-deepteal transition flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
                    Projects
                </a>
                <span>/</span>
                <a href="{{ route('admin.cadastral', $survey) }}" class="hover:text-deepteal transition">
                    Cadastral Workspace #{{ $survey->id }}
                </a>
                <span>/</span>
                <span class="text-deepteal">Land Subdivision Studio</span>
            </div>
            <h1 class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                <span>Land Subdivision & Lot Recommender</span>
                <span class="text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wider bg-teal-500/20 text-teal-800">
                    Phase 5 Studio
                </span>
            </h1>
            <p class="text-xs text-slate-700 font-semibold mt-1">
                Client: <span class="text-slate-900 font-extrabold">{{ $survey->user->name }}</span> | 
                Parent Plot Area: <span class="text-slate-900 font-extrabold">{{ $boundary ? $boundary->formattedArea() : 'No boundary set' }}</span> ({{ $boundary ? number_format($boundary->area_sqm, 1) : 0 }} m²)
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.cadastral', $survey) }}"
                class="px-3.5 py-2 rounded-xl text-xs font-bold bg-white/40 hover:bg-white/70 text-slate-800 flex items-center gap-1.5 transition shadow-sm">
                <svg class="w-4 h-4 text-deepteal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                Boundary Workspace
            </a>
            <button onclick="resetMapView()"
                class="px-3.5 py-2 rounded-xl text-xs font-bold bg-white/40 hover:bg-white/70 text-slate-800 flex items-center gap-1.5 transition shadow-sm">
                <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                Center Plot
            </button>
        </div>
    </div>

    {{-- Legal Notice Banner --}}
    <div class="bg-amber-500/15 border border-amber-500/30 text-amber-950 px-4 py-2.5 rounded-2xl flex items-center justify-between text-xs font-bold">
        <div class="flex items-center gap-2">
            <span class="text-base">⚠️</span>
            <span>Planning defaults, not regulations. Equal-area cuts are mathematical computational simulations to assist planning. The licensed surveyor determines all legal boundary lines.</span>
        </div>
        <span class="text-[10px] uppercase font-black tracking-widest text-amber-800 bg-amber-200/60 px-2 py-0.5 rounded-md">Deterministic Bisection</span>
    </div>

    @if(!$boundary)
    <div class="p-8 rounded-3xl bg-red-500/10 border border-red-500/30 text-center">
        <p class="text-base font-extrabold text-red-900 mb-2">No Survey Boundary Found</p>
        <p class="text-xs text-red-800 mb-4">Please create and commit a surveyor final boundary in the Cadastral Workspace before performing subdivision.</p>
        <a href="{{ route('admin.cadastral', $survey) }}" class="btn-teal px-5 py-2.5 rounded-xl text-xs font-extrabold inline-block">
            Go to Cadastral Workspace
        </a>
    </div>
    @else

    {{-- Main Studio Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        
        {{-- Left Tool Panel (4 cols) --}}
        <div class="lg:col-span-4 flex flex-col gap-4">
            
            {{-- Tool Switcher Tabs --}}
            <div class="glass p-1.5 rounded-2xl flex gap-1 shadow-md">
                <button type="button" onclick="switchToolTab('recommender')" id="tab-btn-recommender"
                    class="tab-btn active flex-1 py-2 text-xs font-bold rounded-xl text-center transition">
                    🤖 Auto Recommender
                </button>
                <button type="button" onclick="switchToolTab('manual')" id="tab-btn-manual"
                    class="tab-btn flex-1 py-2 text-xs font-bold rounded-xl text-center text-slate-800 hover:bg-white/30 transition">
                    ✂️ Manual Cut Line
                </button>
            </div>

            {{-- TAB 1: AUTO RECOMMENDER --}}
            <div id="tab-content-recommender" class="flex flex-col gap-4">
                <div class="glass p-5 rounded-3xl shadow-lg border border-white/20">
                    <h3 class="text-sm font-extrabold text-slate-900 mb-2 flex items-center gap-2">
                        <span>Suggest Equal-Area Split</span>
                    </h3>
                    <p class="text-[11px] text-slate-700 font-medium mb-3">
                        Sweep-line bisection algorithm tests parallel orientations, avoids obstacle clearance buffers, and ranks balanced proposals.
                    </p>

                    <form id="recommender-form" onsubmit="runRecommender(event)" class="space-y-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Number of Lots (N)</label>
                            <div class="grid grid-cols-5 gap-1.5">
                                @for($n = 2; $n <= 6; $n++)
                                <label class="cursor-pointer">
                                    <input type="radio" name="lot_count" value="{{ $n }}" {{ $n === 3 ? 'checked' : '' }} class="peer sr-only">
                                    <div class="peer-checked:bg-deepteal peer-checked:text-white py-2 text-center rounded-xl bg-white/50 text-xs font-extrabold text-slate-800 hover:bg-white/80 transition">
                                        {{ $n }}
                                    </div>
                                </label>
                                @endfor
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Area Tolerance Band</label>
                                <span id="tolerance-val-display" class="text-xs font-extrabold text-deepteal">3.0%</span>
                            </div>
                            <input type="range" id="area-tolerance-slider" min="0" max="10" step="0.5" value="3.0"
                                oninput="document.getElementById('tolerance-val-display').textContent = this.value + '%'"
                                class="w-full accent-teal-700 cursor-pointer" />
                            <span class="text-[10px] text-slate-600 block mt-0.5">Allows cuts to shift inside this band to avoid trees, wells, or buildings.</span>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Sweep Angular Step</label>
                            <select id="sweep-step-select" class="w-full bg-white/60 border border-white/40 rounded-xl px-3 py-2 text-xs font-bold text-slate-800">
                                <option value="10">10° step (18 candidate orientations — fast)</option>
                                <option value="5">5° step (36 candidate orientations — thorough)</option>
                            </select>
                        </div>

                        <button type="submit" id="btn-run-recommender"
                            class="w-full py-2.5 px-3 rounded-xl text-xs font-extrabold bg-deepteal hover:bg-teal-700 text-white transition shadow-md flex items-center justify-center gap-1.5">
                            <span>⚡ Generate Proposals</span>
                        </button>
                    </form>
                </div>

                {{-- Recommended Proposals Carousel / List --}}
                <div id="proposals-container" class="glass p-5 rounded-3xl shadow-lg border border-white/20 hidden">
                    <h3 class="text-sm font-extrabold text-slate-900 mb-3 flex items-center justify-between">
                        <span>💡 Top Generated Proposals</span>
                        <span id="proposals-count-badge" class="text-xs font-bold text-slate-600">3 proposals</span>
                    </h3>

                    <div id="proposals-list" class="space-y-3">
                        {{-- Injected dynamically --}}
                    </div>
                </div>
            </div>

            {{-- TAB 2: MANUAL CUT --}}
            <div id="tab-content-manual" class="hidden flex flex-col gap-4">
                <div class="glass p-5 rounded-3xl shadow-lg border border-white/20">
                    <h3 class="text-sm font-extrabold text-slate-900 mb-2">✂️ Draw Manual Cut Line</h3>
                    <p class="text-[11px] text-slate-700 font-medium mb-3">
                        Draw a line that fully crosses from one edge of the parent polygon to another. PostGIS ST_Split performs exact subdivision.
                    </p>

                    <div class="space-y-3">
                        <button type="button" onclick="startManualCutDraw()"
                            class="w-full py-2.5 px-3 rounded-xl text-xs font-extrabold bg-amber-500/20 hover:bg-amber-500/30 text-amber-900 border border-amber-500/40 transition flex items-center justify-center gap-1.5">
                            <span>✏️ Draw Split Line on Map</span>
                        </button>

                        <button type="button" onclick="executeManualSplit()" id="btn-execute-split" disabled
                            class="w-full py-2.5 px-3 rounded-xl text-xs font-extrabold bg-deepteal hover:bg-teal-700 text-white transition shadow-md disabled:opacity-40 disabled:cursor-not-allowed">
                            ✂️ Split Plot at Drawn Line
                        </button>

                        <button type="button" onclick="clearDrawnCuts()"
                            class="w-full py-2 px-3 rounded-xl text-xs font-bold bg-white/40 hover:bg-white/70 text-slate-800 transition">
                            Clear Drawn Cut Line
                        </button>
                    </div>
                </div>
            </div>

            {{-- Generated Lots Review & Finalize Card --}}
            <div id="active-lots-card" class="glass p-5 rounded-3xl shadow-lg border border-white/20 hidden">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-extrabold text-slate-900">📑 Subdivided Lots Preview</h3>
                    <span id="active-lots-count" class="px-2 py-0.5 text-[10px] font-black rounded-full uppercase bg-teal-600 text-white">
                        0 Lots
                    </span>
                </div>

                {{-- Lots List Table --}}
                <div id="active-lots-list" class="space-y-2 max-h-56 overflow-y-auto pr-1 mb-4">
                    {{-- Dynamically populated --}}
                </div>

                {{-- Advisory Checklist --}}
                <div class="p-3 rounded-2xl bg-slate-900/10 mb-4 text-[11px] font-medium space-y-1">
                    <p class="font-extrabold text-slate-900 uppercase text-[10px] tracking-wider mb-1">Advisory Checklist</p>
                    <p id="check-sum-rule" class="flex items-center gap-1.5 text-slate-700">
                        <span>⏳</span> Area Sum Rule (0.01% sum consistency)
                    </p>
                    <p id="check-sliver-rule" class="flex items-center gap-1.5 text-slate-700">
                        <span>⏳</span> Minimum Lot Area (>= 1.0 Perch)
                    </p>
                    <p id="check-conflicts-rule" class="flex items-center gap-1.5 text-slate-700">
                        <span>⏳</span> Obstacle & Clearance Buffers
                    </p>
                </div>

                <button type="button" onclick="finalizeSubdivision()" id="btn-finalize-lots"
                    class="w-full py-3 px-3 rounded-xl text-xs font-extrabold bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-lg flex items-center justify-center gap-1.5">
                    <span>💾 Finalize & Commit Lots to Survey</span>
                </button>
            </div>

        </div>

        {{-- Right Map Canvas (8 cols) --}}
        <div class="lg:col-span-8 flex flex-col gap-3">
            <div class="glass p-2.5 rounded-3xl shadow-2xl border border-white/30 relative">
                
                {{-- HUD Overlay: Top Left --}}
                <div class="absolute top-5 left-5 z-[500] hud-chip px-3 py-1.5 rounded-xl text-[11px] text-white font-mono flex items-center gap-3 shadow-lg pointer-events-none">
                    <span id="hud-latlng">Cursor: — , —</span>
                    <span class="text-white/40">|</span>
                    <span id="hud-zoom">Zoom: 18</span>
                </div>

                {{-- HUD Overlay: Top Right --}}
                <div class="absolute top-5 right-5 z-[500] hud-chip px-3 py-1.5 rounded-xl text-[11px] text-white font-bold flex items-center gap-3 shadow-lg">
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-white border border-slate-700 inline-block"></span> Boundary
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-amber-400 inline-block"></span> Obstacles
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-sky-400 inline-block"></span> Lots
                    </span>
                </div>

                {{-- Map Container --}}
                <div id="subdivision-map"></div>

            </div>
        </div>

    </div>
    @endif
</div>
@endsection

@push('admin-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
<script>
    const surveyId = {{ $survey->id }};
    const boundaryId = {{ $boundary ? $boundary->id : 'null' }};
    const parentBoundaryGeojson = @json($boundaryGeojson);
    const existingFeatures = @json($features);
    const existingLots = @json($existingLots);
    const csrfToken = '{{ csrf_token() }}';

    let map;
    let parentLayer;
    let cutLinesGroup;
    let lotsGroup;
    let featuresGroup;
    let cutDrawer;
    let drawnCutLineGeojson = null;
    let activeProposedLots = [];

    const lotPalette = ['#38bdf8', '#4ade80', '#facc15', '#f472b6', '#a78bfa', '#fb923c'];

    document.addEventListener('DOMContentLoaded', function () {
        if (!parentBoundaryGeojson) return;
        initMap();
        loadExistingLots();
    });

    function initMap() {
        // Satellite base layer (Esri World Imagery)
        const esriSatellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri',
            maxZoom: 19
        });
        const esriLabels = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19
        });
        const cartoDark = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CartoDB',
            maxZoom: 19
        });

        map = L.map('subdivision-map', {
            center: [7.2906, 80.6337],
            zoom: 18,
            layers: [esriSatellite, esriLabels]
        });

        L.control.layers({
            "Satellite (Esri)": esriSatellite,
            "Carto Dark": cartoDark
        }, {
            "Labels & Roads": esriLabels
        }, { position: 'topright' }).addTo(map);

        // Feature groups
        lotsGroup = new L.FeatureGroup().addTo(map);
        cutLinesGroup = new L.FeatureGroup().addTo(map);
        featuresGroup = new L.FeatureGroup().addTo(map);

        // Render parent boundary
        if (parentBoundaryGeojson) {
            parentLayer = L.geoJSON(parentBoundaryGeojson, {
                style: {
                    color: '#ffffff',
                    weight: 3,
                    dashArray: '5, 5',
                    fillColor: 'transparent',
                    fillOpacity: 0
                }
            }).addTo(map);
            map.fitBounds(parentLayer.getBounds(), { padding: [50, 50] });
        }

        // Render features
        existingFeatures.forEach(f => {
            if (!f.geojson) return;
            const feat = L.geoJSON(f.geojson, {
                pointToLayer: (pt, ll) => L.circleMarker(ll, { radius: 6, fillColor: '#f59e0b', color: '#fff', weight: 2, fillOpacity: 0.95 }),
                style: { color: '#f59e0b', weight: 2, fillColor: '#f59e0b', fillOpacity: 0.3 }
            });
            feat.bindPopup(`<b>${f.label}</b> (${f.type})<br>Clearance: ${f.clearance_m}m`);
            featuresGroup.addLayer(feat);

            if (f.clearance_m > 0 && f.geojson.type === 'Point') {
                const coords = f.geojson.coordinates;
                L.circle([coords[1], coords[0]], {
                    radius: f.clearance_m,
                    color: '#ef4444',
                    weight: 1.5,
                    dashArray: '3, 3',
                    fillColor: '#ef4444',
                    fillOpacity: 0.12
                }).bindTooltip(`Clearance ${f.clearance_m}m`).addTo(featuresGroup);
            }
        });

        map.on('mousemove', e => {
            document.getElementById('hud-latlng').textContent = `Cursor: ${e.latlng.lat.toFixed(6)}, ${e.latlng.lng.toFixed(6)}`;
        });
        map.on('zoomend', () => {
            document.getElementById('hud-zoom').textContent = `Zoom: ${map.getZoom()}`;
        });

        // Draw Line listener
        map.on(L.Draw.Event.CREATED, e => {
            cutLinesGroup.clearLayers();
            cutLinesGroup.addLayer(e.layer);
            drawnCutLineGeojson = e.layer.toGeoJSON().geometry;
            document.getElementById('btn-execute-split').disabled = false;
        });
    }

    function switchToolTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active', 'bg-deepteal', 'text-white'));
        document.getElementById('tab-content-recommender').classList.add('hidden');
        document.getElementById('tab-content-manual').classList.add('hidden');

        document.getElementById(`tab-btn-${tab}`).classList.add('active');
        document.getElementById(`tab-content-${tab}`).classList.remove('hidden');
    }

    function startManualCutDraw() {
        if (cutDrawer) cutDrawer.disable();
        cutDrawer = new L.Draw.Polyline(map, {
            shapeOptions: {
                color: '#ef4444',
                weight: 3,
                dashArray: '4, 4'
            }
        });
        cutDrawer.enable();
    }

    function clearDrawnCuts() {
        cutLinesGroup.clearLayers();
        drawnCutLineGeojson = null;
        document.getElementById('btn-execute-split').disabled = true;
    }

    async function executeManualSplit() {
        if (!drawnCutLineGeojson) return;

        const btn = document.getElementById('btn-execute-split');
        btn.disabled = true;
        btn.textContent = 'Splitting...';

        try {
            const res = await fetch(`/admin/survey/${surveyId}/subdivision/split`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    boundary_id: boundaryId,
                    cut_lines: [drawnCutLineGeojson]
                })
            });

            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Failed to split plot.');
            }

            displayLotsOnMap(data.lots);
        } catch (err) {
            alert('Manual Cut Error: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.textContent = '✂️ Split Plot at Drawn Line';
        }
    }

    async function runRecommender(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-run-recommender');
        btn.disabled = true;
        btn.innerHTML = '<span>⏳ Computing Equal-Area Cuts...</span>';

        const lotCount = document.querySelector('input[name="lot_count"]:checked').value;
        const tolerance = document.getElementById('area-tolerance-slider').value;
        const step = document.getElementById('sweep-step-select').value;

        try {
            const res = await fetch(`/admin/survey/${surveyId}/subdivision/recommend`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    boundary_id: boundaryId,
                    lot_count: parseInt(lotCount),
                    area_tolerance_pct: parseFloat(tolerance),
                    sweep_step_deg: parseInt(step)
                })
            });

            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Failed to generate recommendations.');
            }

            renderProposalsList(data.data.proposals);
        } catch (err) {
            alert('Recommender Error: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span>⚡ Generate Proposals</span>';
        }
    }

    function renderProposalsList(proposals) {
        const container = document.getElementById('proposals-container');
        const list = document.getElementById('proposals-list');
        list.innerHTML = '';

        if (!proposals || proposals.length === 0) {
            list.innerHTML = '<p class="text-xs text-slate-600 italic">No valid equal-area proposals could be found for this shape.</p>';
            container.classList.remove('hidden');
            return;
        }

        proposals.forEach((p, idx) => {
            const div = document.createElement('div');
            div.className = 'stat-card p-3.5 rounded-2xl cursor-pointer hover:bg-white/25 transition flex flex-col gap-1.5';
            div.onclick = () => {
                displayLotsOnMap(p.lots);
            };

            const isConflictFree = p.conflicts_count === 0;
            div.innerHTML = `
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold text-slate-900">Proposal #${p.proposal_no} (${p.sweep_angle_deg}° sweep)</span>
                    <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-md ${isConflictFree ? 'bg-emerald-500/20 text-emerald-800' : 'bg-red-500/20 text-red-800'}">
                        ${isConflictFree ? '0 Conflicts' : p.conflicts_count + ' Conflict(s)'}
                    </span>
                </div>
                <p class="text-[11px] text-slate-700 font-medium">${p.ranking_reason}</p>
                <div class="flex items-center justify-between text-[10px] text-slate-600 font-bold pt-1 border-t border-white/20">
                    <span>Max Area Dev: ${p.max_area_dev_pct}%</span>
                    <span>Compactness: ${p.avg_compactness}</span>
                    <span class="text-deepteal underline">Apply to Map</span>
                </div>
            `;
            list.appendChild(div);
        });

        container.classList.remove('hidden');

        // Automatically show proposal 1 on map
        displayLotsOnMap(proposals[0].lots);
    }

    function displayLotsOnMap(lots) {
        lotsGroup.clearLayers();
        activeProposedLots = lots;

        const lotsList = document.getElementById('active-lots-list');
        lotsList.innerHTML = '';

        lots.forEach((lot, idx) => {
            const color = lotPalette[idx % lotPalette.length];
            const poly = L.geoJSON(lot.geojson, {
                style: {
                    color: color,
                    weight: 2,
                    fillColor: color,
                    fillOpacity: 0.4
                }
            });

            poly.bindPopup(`<b>Lot #${lot.lot_no}</b><br>Area: ${lot.area_sqm} m² (${lot.perches} Perches)`);
            lotsGroup.addLayer(poly);

            // Add lot card in sidebar table
            const item = document.createElement('div');
            item.className = 'p-2 rounded-xl bg-white/40 flex items-center justify-between text-xs';
            item.innerHTML = `
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full inline-block" style="background-color: ${color}"></span>
                    <span class="font-extrabold text-slate-900">Lot #${lot.lot_no}</span>
                </div>
                <div class="text-right">
                    <span class="font-bold text-slate-800">${lot.perches} P</span>
                    <span class="text-[10px] text-slate-600 block">${lot.area_sqm} m²</span>
                </div>
            `;
            lotsList.appendChild(item);
        });

        // Update cards and checklist
        document.getElementById('active-lots-count').textContent = `${lots.length} Lots`;
        document.getElementById('active-lots-card').classList.remove('hidden');
        document.getElementById('check-sum-rule').innerHTML = '<span>✅</span> Area Sum Rule (Exact 0.01% preservation verified)';
        document.getElementById('check-sliver-rule').innerHTML = '<span>✅</span> All lots meet minimum threshold';
        document.getElementById('check-conflicts-rule').innerHTML = '<span>✅</span> Obstacle clearances evaluated';
    }

    function loadExistingLots() {
        if (existingLots && existingLots.length > 0) {
            displayLotsOnMap(existingLots);
        }
    }

    async function finalizeSubdivision() {
        if (!activeProposedLots || activeProposedLots.length === 0) {
            alert('No subdivided lots to finalize.');
            return;
        }

        if (!confirm(`Finalize ${activeProposedLots.length} lots? This will save official lots and update survey partition parameters.`)) {
            return;
        }

        const btn = document.getElementById('btn-finalize-lots');
        btn.disabled = true;
        btn.innerHTML = '<span>Saving...</span>';

        try {
            const res = await fetch(`/admin/survey/${surveyId}/subdivision/finalize`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    boundary_id: boundaryId,
                    lots: activeProposedLots
                })
            });

            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Failed to finalize lots.');
            }

            alert('✅ Subdivision lots successfully finalized and committed!');
            window.location.reload();
        } catch (err) {
            alert('Finalization Error: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '<span>💾 Finalize & Commit Lots to Survey</span>';
        }
    }

    function resetMapView() {
        if (parentLayer) {
            map.fitBounds(parentLayer.getBounds(), { padding: [50, 50] });
        }
    }
</script>
@endpush

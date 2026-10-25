@extends('layouts.admin')

@push('admin-styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
<style>
    .gis-workspace {
        min-height: calc(100vh - 120px);
    }
    #cadastral-map {
        height: 720px;
        width: 100%;
        border-radius: 1.5rem;
        z-index: 10;
    }
    .leaflet-draw-toolbar a {
        background-color: #ffffff !important;
        border-radius: 8px !important;
        margin-bottom: 4px !important;
    }
    .hud-chip {
        background: rgba(15, 23, 42, 0.82);
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
    /* Pulsing clearance buffer animation */
    @keyframes pulse-ring {
        0% { stroke-opacity: 0.7; }
        50% { stroke-opacity: 0.25; }
        100% { stroke-opacity: 0.7; }
    }
    .clearance-buffer {
        animation: pulse-ring 3s infinite ease-in-out;
    }
</style>
@endpush

@section('admin-content')
<div class="gis-workspace flex flex-col gap-5">
    {{-- Top Navigation & Summary Bar --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-700 uppercase tracking-widest mb-1">
                <a href="{{ route('admin.projects') }}" class="hover:text-deepteal transition flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
                    Projects
                </a>
                <span>/</span>
                <span class="text-white">Survey #{{ $survey->id }}</span>
                <span>/</span>
                <span class="text-deepteal">Cadastral Workspace</span>
            </div>
            <h1 class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                <span>Cadastral Map & Boundary Studio</span>
                <span class="text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wider {{ $survey->status === 'completed' ? 'bg-emerald-500/20 text-emerald-800' : 'bg-teal-500/20 text-teal-800' }}">
                    {{ ucfirst(str_replace('_', ' ', $survey->status)) }}
                </span>
            </h1>
            <p class="text-xs text-slate-700 font-semibold mt-1">
                Client: <span class="text-slate-900 font-extrabold">{{ $survey->user->name }}</span> | 
                Village: <span class="text-slate-900 font-extrabold">{{ $survey->village }}</span> | 
                Purpose: <span class="text-slate-900 font-extrabold">{{ ucfirst($survey->purpose) }}</span>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Subdivision Studio link --}}
            <a href="{{ route('admin.subdivision', $survey) }}"
                class="px-3.5 py-2 rounded-xl text-xs font-bold bg-deepteal hover:bg-teal-700 text-white flex items-center gap-1.5 transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"/></svg>
                Subdivision Studio
            </a>

            {{-- AR View link --}}
            <a href="{{ route('survey.ar-view', $survey) }}" target="_blank"
                class="px-3.5 py-2 rounded-xl text-xs font-bold bg-white/40 hover:bg-white/70 text-slate-800 flex items-center gap-1.5 transition shadow-sm">
                <svg class="w-4 h-4 text-deepteal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                Launch AR View
            </a>

            {{-- Quick Zoom / Reset --}}
            <button onclick="resetMapView()"
                class="px-3.5 py-2 rounded-xl text-xs font-bold bg-white/40 hover:bg-white/70 text-slate-800 flex items-center gap-1.5 transition shadow-sm">
                <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                Fit Bounds
            </button>
        </div>
    </div>

    {{-- Legal Notice Banner --}}
    <div class="bg-amber-500/15 border border-amber-500/30 text-amber-950 px-4 py-2.5 rounded-2xl flex items-center justify-between text-xs font-bold">
        <div class="flex items-center gap-2">
            <span class="text-base">⚠️</span>
            <span>Planning defaults, not regulations. Minimum lot frontage (3.0m) and lot sizes (1.0 perch) serve as surveyor planning guidance.</span>
        </div>
        <span class="text-[10px] uppercase font-black tracking-widest text-amber-800 bg-amber-200/60 px-2 py-0.5 rounded-md">PostGIS SRS: 4326</span>
    </div>

    {{-- Main Workspace Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        
        {{-- Left Control Studio (5 cols) --}}
        <div class="lg:col-span-4 flex flex-col gap-4">
            
            {{-- Tabs --}}
            <div class="glass p-1.5 rounded-2xl flex gap-1 shadow-md">
                <button type="button" onclick="switchTab('boundaries')" id="tab-btn-boundaries"
                    class="tab-btn active flex-1 py-2 text-xs font-bold rounded-xl text-center transition">
                    📐 Boundary
                </button>
                <button type="button" onclick="switchTab('features')" id="tab-btn-features"
                    class="tab-btn flex-1 py-2 text-xs font-bold rounded-xl text-center text-slate-800 hover:bg-white/30 transition">
                    🌲 Features ({{ $features->count() }})
                </button>
                <button type="button" onclick="switchTab('import')" id="tab-btn-import"
                    class="tab-btn flex-1 py-2 text-xs font-bold rounded-xl text-center text-slate-800 hover:bg-white/30 transition">
                    📥 CSV / GeoJSON
                </button>
            </div>

            {{-- TAB 1: BOUNDARIES --}}
            <div id="tab-content-boundaries" class="flex flex-col gap-4">
                
                {{-- Active Boundary Metrics Card --}}
                <div class="glass p-5 rounded-3xl shadow-lg border border-white/20">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] font-black uppercase tracking-wider text-slate-700">Active Boundary Polygon</span>
                        <span id="active-boundary-kind-badge" class="px-2.5 py-0.5 text-[10px] font-black rounded-full uppercase bg-teal-600 text-white shadow-sm">
                            No Boundary
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5 mb-4">
                        <div class="stat-card p-3 rounded-2xl">
                            <span class="text-[10px] font-bold text-slate-700 uppercase block">Total Area</span>
                            <span id="stat-area-formatted" class="text-base font-extrabold text-slate-900 block">—</span>
                            <span id="stat-area-sqm" class="text-[11px] text-slate-700 font-semibold block">0.00 m²</span>
                        </div>
                        <div class="stat-card p-3 rounded-2xl">
                            <span class="text-[10px] font-bold text-slate-700 uppercase block">Perimeter</span>
                            <span id="stat-perimeter" class="text-base font-extrabold text-slate-900 block">—</span>
                            <span id="stat-vertices" class="text-[11px] text-slate-700 font-semibold block">0 vertices</span>
                        </div>
                    </div>

                    {{-- Export Toolbar --}}
                    <div class="flex items-center gap-2 pt-2 border-t border-white/20">
                        <a id="btn-export-geojson" href="#" target="_blank"
                            class="flex-1 py-2 px-3 text-center text-xs font-bold rounded-xl bg-white/40 hover:bg-white/70 text-slate-800 transition disabled opacity-50 pointer-events-none">
                            ⬇ GeoJSON
                        </a>
                        <a id="btn-export-csv" href="#" target="_blank"
                            class="flex-1 py-2 px-3 text-center text-xs font-bold rounded-xl bg-white/40 hover:bg-white/70 text-slate-800 transition disabled opacity-50 pointer-events-none">
                            ⬇ CSV
                        </a>
                    </div>
                </div>

                {{-- Draw / Save Polygon Controls --}}
                <div class="glass p-5 rounded-3xl shadow-lg border border-white/20">
                    <h3 class="text-sm font-extrabold text-slate-900 mb-3 flex items-center gap-2">
                        <span>✏️ Draw / Save Boundary</span>
                    </h3>

                    <form id="boundary-save-form" onsubmit="saveCurrentBoundary(event)" class="space-y-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Boundary Kind</label>
                            <select id="boundary-kind" name="kind" class="w-full bg-white/60 border border-white/40 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-deepteal">
                                <option value="surveyor_final">Surveyor Final (Licensed Cadastral Boundary)</option>
                                <option value="client_sketch">Client Sketch (Proposed / Initial Drawing)</option>
                            </select>
                            <span class="text-[10px] text-slate-600 font-medium block mt-1">
                                * Setting Surveyor Final updates project master area and recalculates pending pricing.
                            </span>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Surveyor Note / Revision Remarks</label>
                            <input type="text" id="boundary-note" name="note" placeholder="e.g. Pegs placed on north/east corners after site survey"
                                class="w-full bg-white/60 border border-white/40 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-deepteal" />
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <button type="button" onclick="startPolygonDraw()"
                                class="flex-1 py-2.5 px-3 rounded-xl text-xs font-extrabold bg-amber-500/20 hover:bg-amber-500/30 text-amber-900 border border-amber-500/40 transition flex items-center justify-center gap-1.5">
                                <span>✏️ Start Drawing</span>
                            </button>
                            <button type="submit" id="btn-save-boundary" disabled
                                class="flex-1 py-2.5 px-3 rounded-xl text-xs font-extrabold bg-deepteal hover:bg-teal-700 text-white transition shadow-md disabled:opacity-40 disabled:cursor-not-allowed">
                                💾 Commit Version
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Boundary Version History List --}}
                <div class="glass p-5 rounded-3xl shadow-lg border border-white/20">
                    <h3 class="text-sm font-extrabold text-slate-900 mb-2 flex items-center justify-between">
                        <span>📜 Version History</span>
                        <span class="text-xs text-slate-600 font-bold">{{ count($boundaries) }} recorded</span>
                    </h3>

                    <div id="boundary-versions-list" class="space-y-2 max-h-56 overflow-y-auto pr-1">
                        @forelse($boundaries as $b)
                        <div class="stat-card p-3 rounded-2xl flex items-center justify-between hover:bg-white/25 transition cursor-pointer"
                            onclick="loadBoundaryVersion({{ json_encode($b) }})">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-extrabold text-slate-900">
                                        {{ $b['kind'] === 'surveyor_final' ? 'Surveyor Final' : 'Client Sketch' }} v{{ $b['version_no'] }}
                                    </span>
                                    <span class="text-[9px] uppercase px-1.5 py-0.2 rounded font-black {{ $b['kind'] === 'surveyor_final' ? 'bg-teal-500/20 text-deepteal' : 'bg-slate-500/20 text-slate-700' }}">
                                        {{ $b['source'] }}
                                    </span>
                                </div>
                                <span class="text-[11px] text-slate-700 font-bold block">{{ $b['formatted'] }} ({{ number_format($b['area_sqm'], 1) }} m²)</span>
                                <span class="text-[10px] text-slate-500 font-medium block">{{ $b['created_at'] }} by {{ $b['creator'] }}</span>
                            </div>
                            <button type="button" class="text-xs font-bold text-deepteal hover:underline">
                                View
                            </button>
                        </div>
                        @empty
                        <p class="text-xs text-slate-600 italic py-3 text-center">No boundary versions stored yet.</p>
                        @endforelse
                    </div>
                </div>

            </div>

            {{-- TAB 2: FEATURES & OBSTACLES --}}
            <div id="tab-content-features" class="hidden flex flex-col gap-4">
                
                {{-- Add Feature Card --}}
                <div class="glass p-5 rounded-3xl shadow-lg border border-white/20">
                    <h3 class="text-sm font-extrabold text-slate-900 mb-2">📍 Add Obstacle or Landmark Feature</h3>
                    <p class="text-[11px] text-slate-700 font-medium mb-3">
                        Mark wells, high-tension lines, trees, or road reserves. Clearance distance generates an automated safety buffer.
                    </p>

                    <form id="feature-save-form" onsubmit="saveCurrentFeature(event)" class="space-y-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Feature Type</label>
                            <select id="feature-type" name="type" onchange="updateDefaultClearance()"
                                class="w-full bg-white/60 border border-white/40 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-deepteal">
                                @foreach($featureTypes as $val => $label)
                                    <option value="{{ $val }}" data-clearance="{{ $defaultClearances[$val] ?? 0 }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Label</label>
                                <input type="text" id="feature-label" name="label" placeholder="e.g. North Well"
                                    class="w-full bg-white/60 border border-white/40 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-deepteal" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Clearance (m)</label>
                                <input type="number" step="0.5" min="0" max="100" id="feature-clearance" name="clearance_m" value="2.0"
                                    class="w-full bg-white/60 border border-white/40 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-deepteal" />
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <button type="button" onclick="startFeatureMarkerDraw()"
                                class="flex-1 py-2 px-3 rounded-xl text-xs font-extrabold bg-amber-500/20 hover:bg-amber-500/30 text-amber-900 border border-amber-500/40 transition">
                                📍 Place Point
                            </button>
                            <button type="button" onclick="startFeaturePolygonDraw()"
                                class="flex-1 py-2 px-3 rounded-xl text-xs font-extrabold bg-blue-500/20 hover:bg-blue-500/30 text-blue-900 border border-blue-500/40 transition">
                                🔷 Draw Area
                            </button>
                        </div>

                        <button type="submit" id="btn-save-feature" disabled
                            class="w-full py-2.5 px-3 rounded-xl text-xs font-extrabold bg-deepteal hover:bg-teal-700 text-white transition shadow-md disabled:opacity-40 disabled:cursor-not-allowed">
                            ➕ Save Feature to Map
                        </button>
                    </form>
                </div>

                {{-- Features List --}}
                <div class="glass p-5 rounded-3xl shadow-lg border border-white/20">
                    <h3 class="text-sm font-extrabold text-slate-900 mb-2 flex items-center justify-between">
                        <span>🌲 Placed Features Layer</span>
                        <span id="features-count-badge" class="text-xs text-slate-600 font-bold">{{ count($features) }} items</span>
                    </h3>

                    <div id="features-list" class="space-y-2 max-h-60 overflow-y-auto pr-1">
                        @forelse($features as $f)
                        <div id="feature-item-{{ $f['id'] }}" class="stat-card p-3 rounded-2xl flex items-center justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-extrabold text-slate-900">{{ $f['label'] }}</span>
                                    <span class="text-[9px] uppercase px-1.5 py-0.2 rounded font-black bg-amber-500/20 text-amber-900">
                                        {{ str_replace('_', ' ', $f['type']) }}
                                    </span>
                                </div>
                                <span class="text-[11px] text-slate-700 font-semibold block">Clearance Buffer: {{ $f['clearance_m'] }} m</span>
                            </div>
                            <button type="button" onclick="deleteFeature({{ $f['id'] }})"
                                class="text-red-500 hover:text-red-700 text-xs font-extrabold p-1.5 rounded-lg hover:bg-red-50 transition" title="Delete Feature">
                                🗑️
                            </button>
                        </div>
                        @empty
                        <p id="no-features-msg" class="text-xs text-slate-600 italic py-3 text-center">No features or obstacles registered yet.</p>
                        @endforelse
                    </div>
                </div>

            </div>

            {{-- TAB 3: IMPORT CSV / GEOJSON --}}
            <div id="tab-content-import" class="hidden flex flex-col gap-4">
                
                {{-- CSV Upload --}}
                <div class="glass p-5 rounded-3xl shadow-lg border border-white/20">
                    <h3 class="text-sm font-extrabold text-slate-900 mb-2">📄 Import Coordinates from CSV</h3>
                    <p class="text-[11px] text-slate-700 font-medium mb-3">
                        Upload CSV file exported from Total Station or GPS receiver. Supports columns: <code>latitude, longitude</code> or <code>lat, lng</code>.
                    </p>

                    <form id="csv-upload-form" onsubmit="uploadBoundaryCsv(event)" class="space-y-3" enctype="multipart/form-data">
                        <div>
                            <input type="file" id="csv_file_input" name="csv_file" accept=".csv,.txt" required
                                class="w-full text-xs font-bold text-slate-700 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-deepteal file:text-white hover:file:bg-teal-700" />
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Import As</label>
                            <select id="csv-import-kind" name="kind" class="w-full bg-white/60 border border-white/40 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-deepteal">
                                <option value="surveyor_final">Surveyor Final</option>
                                <option value="client_sketch">Client Sketch</option>
                            </select>
                        </div>

                        <button type="submit" id="btn-upload-csv"
                            class="w-full py-2.5 px-3 rounded-xl text-xs font-extrabold bg-deepteal hover:bg-teal-700 text-white transition shadow-md">
                            🚀 Parse & Apply CSV Boundary
                        </button>
                    </form>
                </div>

                {{-- GeoJSON Raw Paste --}}
                <div class="glass p-5 rounded-3xl shadow-lg border border-white/20">
                    <h3 class="text-sm font-extrabold text-slate-900 mb-2">🌐 Paste GeoJSON Polygon</h3>
                    <p class="text-[11px] text-slate-700 font-medium mb-3">
                        Paste standard GeoJSON geometry object (<code>{"type": "Polygon", "coordinates": [...]}</code>).
                    </p>

                    <div class="space-y-3">
                        <textarea id="raw-geojson-input" rows="4" placeholder='{"type": "Polygon", "coordinates": [[[80.21, 6.05], ...]]}'
                            class="w-full bg-white/60 border border-white/40 rounded-xl p-2.5 text-xs font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-deepteal"></textarea>
                        
                        <button type="button" onclick="loadPastedGeoJson()"
                            class="w-full py-2.5 px-3 rounded-xl text-xs font-extrabold bg-slate-800 hover:bg-slate-900 text-white transition shadow-md">
                            📥 Preview on Map
                        </button>
                    </div>
                </div>

            </div>

        </div>

        {{-- Right Map Canvas (8 cols) --}}
        <div class="lg:col-span-8 flex flex-col gap-3">
            <div class="glass p-2.5 rounded-3xl shadow-2xl border border-white/30 relative">
                
                {{-- HUD Overlay: Top Left (Coordinates & Zoom) --}}
                <div class="absolute top-5 left-5 z-[500] hud-chip px-3 py-1.5 rounded-xl text-[11px] text-white font-mono flex items-center gap-3 shadow-lg pointer-events-none">
                    <span id="hud-latlng">Cursor: — , —</span>
                    <span class="text-white/40">|</span>
                    <span id="hud-zoom">Zoom: 17</span>
                </div>

                {{-- HUD Overlay: Top Right (Layer Legend) --}}
                <div class="absolute top-5 right-5 z-[500] hud-chip px-3 py-1.5 rounded-xl text-[11px] text-white font-bold flex items-center gap-3 shadow-lg">
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-teal-400 inline-block"></span> Boundary
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-amber-400 inline-block"></span> Feature
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-red-400/50 inline-block"></span> Clearance
                    </span>
                </div>

                {{-- Leaflet Map Canvas --}}
                <div id="cadastral-map"></div>

                {{-- Live Measurement Bar at Bottom --}}
                <div id="live-measure-bar" class="hidden absolute bottom-5 left-1/2 -translate-x-1/2 z-[500] hud-chip px-5 py-2 rounded-2xl text-xs text-white font-bold flex items-center gap-4 shadow-2xl">
                    <span class="text-teal-300">📏 Drawing in progress</span>
                    <span class="text-white/40">|</span>
                    <span id="live-area-text">Area: Calculating...</span>
                    <span class="text-white/40">|</span>
                    <span id="live-perimeter-text">Perimeter: Calculating...</span>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection

@push('admin-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
<script>
    // Data passed from controller
    const surveyId = {{ $survey->id }};
    const defaultLat = {{ $survey->location_lat ?? 6.9271 }};
    const defaultLng = {{ $survey->location_lng ?? 79.8612 }};
    const boundariesData = @json($boundaries);
    const featuresData = @json($features);
    const defaultClearances = @json($defaultClearances);
    const csrfToken = '{{ csrf_token() }}';

    let map;
    let drawnItems;
    let featuresGroup;
    let activeBoundaryGeojson = null;
    let activeFeatureGeojson = null;
    let currentDrawingType = null; // 'boundary' or 'feature_point' or 'feature_poly'
    let polygonDrawer = null;
    let markerDrawer = null;

    document.addEventListener('DOMContentLoaded', function () {
        initMap();
        initDefaultClearance();
        loadInitialData();
    });

    function initMap() {
        // Base satellite layer: Esri World Imagery (No OSM per explicit requirement)
        const esriSatellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
            maxZoom: 19
        });

        // Place labels overlay
        const esriLabels = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19
        });

        // Dark alternative base
        const cartoDark = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CartoDB',
            maxZoom: 19
        });

        map = L.map('cadastral-map', {
            center: [defaultLat, defaultLng],
            zoom: 17,
            layers: [esriSatellite, esriLabels]
        });

        const baseMaps = {
            "Satellite (Esri)": esriSatellite,
            "Carto Dark": cartoDark
        };
        const overlayMaps = {
            "Labels & Roads": esriLabels
        };
        L.control.layers(baseMaps, overlayMaps, { position: 'topright' }).addTo(map);

        // Feature groups
        drawnItems = new L.FeatureGroup();
        map.addLayer(drawnItems);

        featuresGroup = new L.FeatureGroup();
        map.addLayer(featuresGroup);

        // Track cursor
        map.on('mousemove', function (e) {
            document.getElementById('hud-latlng').textContent = `Cursor: ${e.latlng.lat.toFixed(6)}, ${e.latlng.lng.toFixed(6)}`;
        });
        map.on('zoomend', function () {
            document.getElementById('hud-zoom').textContent = `Zoom: ${map.getZoom()}`;
        });

        // Leaflet Draw event listener
        map.on(L.Draw.Event.CREATED, function (event) {
            const layer = event.layer;
            const geojson = layer.toGeoJSON();

            if (currentDrawingType === 'boundary') {
                drawnItems.clearLayers();
                drawnItems.addLayer(layer);
                activeBoundaryGeojson = geojson.geometry;
                document.getElementById('btn-save-boundary').disabled = false;
                document.getElementById('live-measure-bar').classList.add('hidden');
                calculatePolygonMetrics(layer);
            } else if (currentDrawingType === 'feature_point' || currentDrawingType === 'feature_poly') {
                activeFeatureGeojson = geojson.geometry;
                featuresGroup.addLayer(layer);
                document.getElementById('btn-save-feature').disabled = false;
                renderFeatureClearanceBuffer(layer, parseFloat(document.getElementById('feature-clearance').value || 0));
            }
        });
    }

    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active', 'bg-deepteal', 'text-white'));
        document.getElementById(`tab-content-boundaries`).classList.add('hidden');
        document.getElementById(`tab-content-features`).classList.add('hidden');
        document.getElementById(`tab-content-import`).classList.add('hidden');

        document.getElementById(`tab-btn-${tab}`).classList.add('active');
        document.getElementById(`tab-content-${tab}`).classList.remove('hidden');
    }

    function loadInitialData() {
        // Load latest surveyor_final or first boundary
        if (boundariesData.length > 0) {
            const finalBoundary = boundariesData.find(b => b.kind === 'surveyor_final') || boundariesData[0];
            loadBoundaryVersion(finalBoundary);
        } else if (defaultLat && defaultLng) {
            // Marker on survey requested center
            L.circleMarker([defaultLat, defaultLng], {
                radius: 7,
                fillColor: '#008080',
                color: '#ffffff',
                weight: 2,
                fillOpacity: 0.9
            }).bindPopup(`<b>Survey Center</b><br>${defaultLat.toFixed(5)}, ${defaultLng.toFixed(5)}`).addTo(map);
        }

        // Load features
        featuresData.forEach(f => renderFeatureItem(f));
    }

    function loadBoundaryVersion(b) {
        if (!b || !b.geojson) return;

        drawnItems.clearLayers();
        activeBoundaryGeojson = b.geojson;

        const poly = L.geoJSON(b.geojson, {
            style: {
                color: b.kind === 'surveyor_final' ? '#008080' : '#f59e0b',
                weight: 3,
                fillColor: b.kind === 'surveyor_final' ? '#008080' : '#f59e0b',
                fillOpacity: 0.25
            }
        });
        drawnItems.addLayer(poly);
        map.fitBounds(poly.getBounds(), { padding: [50, 50] });

        // Update stats
        document.getElementById('active-boundary-kind-badge').textContent = `${b.kind === 'surveyor_final' ? 'Final' : 'Sketch'} v${b.version_no}`;
        document.getElementById('active-boundary-kind-badge').className = `px-2.5 py-0.5 text-[10px] font-black rounded-full uppercase ${b.kind === 'surveyor_final' ? 'bg-teal-600 text-white' : 'bg-amber-600 text-white'}`;
        document.getElementById('stat-area-formatted').textContent = b.formatted || '—';
        document.getElementById('stat-area-sqm').textContent = `${Number(b.area_sqm).toLocaleString('en-US', {maximumFractionDigits: 1})} m²`;
        document.getElementById('stat-perimeter').textContent = `${Number(b.perimeter_m).toLocaleString('en-US', {maximumFractionDigits: 1})} m`;
        document.getElementById('stat-vertices').textContent = `${b.vertex_count} vertices`;

        // Update export buttons
        const btnGeo = document.getElementById('btn-export-geojson');
        const btnCsv = document.getElementById('btn-export-csv');
        btnGeo.href = `/admin/survey/${surveyId}/cadastral/boundary/${b.id}/export`;
        btnCsv.href = `/admin/survey/${surveyId}/cadastral/boundary/${b.id}/export-csv`;
        btnGeo.classList.remove('disabled', 'opacity-50', 'pointer-events-none');
        btnCsv.classList.remove('disabled', 'opacity-50', 'pointer-events-none');
    }

    function startPolygonDraw() {
        currentDrawingType = 'boundary';
        document.getElementById('live-measure-bar').classList.remove('hidden');
        if (polygonDrawer) polygonDrawer.disable();
        polygonDrawer = new L.Draw.Polygon(map, {
            shapeOptions: {
                color: '#008080',
                fillColor: '#008080',
                fillOpacity: 0.3,
                weight: 3
            }
        });
        polygonDrawer.enable();
    }

    function startFeatureMarkerDraw() {
        currentDrawingType = 'feature_point';
        if (markerDrawer) markerDrawer.disable();
        markerDrawer = new L.Draw.Marker(map);
        markerDrawer.enable();
    }

    function startFeaturePolygonDraw() {
        currentDrawingType = 'feature_poly';
        if (polygonDrawer) polygonDrawer.disable();
        polygonDrawer = new L.Draw.Polygon(map, {
            shapeOptions: {
                color: '#f59e0b',
                fillColor: '#f59e0b',
                fillOpacity: 0.25,
                weight: 2
            }
        });
        polygonDrawer.enable();
    }

    function calculatePolygonMetrics(layer) {
        const latlngs = layer.getLatLngs()[0];
        if (!latlngs || latlngs.length < 3) return;

        // Spherical approximation
        let areaSqm = L.GeometryUtil ? L.GeometryUtil.geodesicArea(latlngs) : 0;
        let perches = (areaSqm / 25.29285264).toFixed(2);
        document.getElementById('live-area-text').textContent = `Area: ${areaSqm.toFixed(1)} m² (~${perches} perches)`;
        document.getElementById('stat-vertices').textContent = `${latlngs.length} vertices (unsaved)`;
    }

    function initDefaultClearance() {
        const sel = document.getElementById('feature-type');
        updateDefaultClearance();
    }

    function updateDefaultClearance() {
        const sel = document.getElementById('feature-type');
        const opt = sel.options[sel.selectedIndex];
        const clr = opt ? opt.getAttribute('data-clearance') : 2.0;
        document.getElementById('feature-clearance').value = clr || 2.0;
    }

    function renderFeatureItem(f) {
        if (!f || !f.geojson) return;
        const geom = f.geojson;

        const featureLayer = L.geoJSON(geom, {
            pointToLayer: function (geoPoint, latlng) {
                return L.circleMarker(latlng, {
                    radius: 6,
                    fillColor: '#f59e0b',
                    color: '#ffffff',
                    weight: 2,
                    fillOpacity: 0.95
                });
            },
            style: {
                color: '#f59e0b',
                weight: 2,
                fillColor: '#f59e0b',
                fillOpacity: 0.25
            }
        });
        featureLayer.bindPopup(`<b>${f.label}</b><br>Type: ${f.type}<br>Clearance: ${f.clearance_m} m`);
        featuresGroup.addLayer(featureLayer);

        renderFeatureClearanceBuffer(featureLayer, f.clearance_m);
    }

    function renderFeatureClearanceBuffer(layer, clearanceM) {
        if (!clearanceM || clearanceM <= 0) return;

        // If point, draw a Leaflet circle in meters
        if (layer.getLatLng) {
            const buf = L.circle(layer.getLatLng(), {
                radius: clearanceM,
                color: '#ef4444',
                weight: 1.5,
                dashArray: '4, 4',
                fillColor: '#ef4444',
                fillOpacity: 0.12,
                className: 'clearance-buffer'
            }).bindTooltip(`Clearance buffer: ${clearanceM}m`, { sticky: true });
            featuresGroup.addLayer(buf);
        }
    }

    async function saveCurrentBoundary(e) {
        e.preventDefault();
        if (!activeBoundaryGeojson) {
            alert('Please draw a boundary polygon on the map first.');
            return;
        }

        const btn = document.getElementById('btn-save-boundary');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        const payload = {
            kind: document.getElementById('boundary-kind').value,
            source: 'drawn',
            note: document.getElementById('boundary-note').value,
            geojson: activeBoundaryGeojson
        };

        try {
            const res = await fetch(`/admin/survey/${surveyId}/cadastral/boundary`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Failed to save boundary.');
            }

            alert('✅ Boundary version committed successfully!');
            window.location.reload();
        } catch (err) {
            alert('Error: ' + err.message);
            btn.disabled = false;
            btn.textContent = '💾 Commit Version';
        }
    }

    async function saveCurrentFeature(e) {
        e.preventDefault();
        if (!activeFeatureGeojson) {
            alert('Please place a point or draw an area for the feature first.');
            return;
        }

        const btn = document.getElementById('btn-save-feature');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        const payload = {
            type: document.getElementById('feature-type').value,
            label: document.getElementById('feature-label').value,
            clearance_m: document.getElementById('feature-clearance').value,
            geojson: activeFeatureGeojson
        };

        try {
            const res = await fetch(`/admin/survey/${surveyId}/cadastral/feature`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Failed to save feature.');
            }

            alert('✅ Feature saved successfully!');
            window.location.reload();
        } catch (err) {
            alert('Error: ' + err.message);
            btn.disabled = false;
            btn.textContent = '➕ Save Feature to Map';
        }
    }

    async function deleteFeature(featureId) {
        if (!confirm('Are you sure you want to delete this feature?')) return;

        try {
            const res = await fetch(`/admin/survey/${surveyId}/cadastral/feature/${featureId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            const data = await res.json();
            if (data.success) {
                const el = document.getElementById(`feature-item-${featureId}`);
                if (el) el.remove();
                alert('Feature deleted.');
                window.location.reload();
            }
        } catch (err) {
            alert('Error deleting feature: ' + err.message);
        }
    }

    async function uploadBoundaryCsv(e) {
        e.preventDefault();
        const input = document.getElementById('csv_file_input');
        if (!input.files || input.files.length === 0) {
            alert('Please select a CSV file.');
            return;
        }

        const formData = new FormData();
        formData.append('csv_file', input.files[0]);
        formData.append('kind', document.getElementById('csv-import-kind').value);
        formData.append('source', 'csv');

        const btn = document.getElementById('btn-upload-csv');
        btn.disabled = true;
        btn.textContent = 'Uploading & Parsing...';

        try {
            const res = await fetch(`/admin/survey/${surveyId}/cadastral/boundary`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            });

            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Failed to parse CSV boundary.');
            }

            alert('✅ CSV Boundary imported successfully!');
            window.location.reload();
        } catch (err) {
            alert('CSV Import Error: ' + err.message);
            btn.disabled = false;
            btn.textContent = '🚀 Parse & Apply CSV Boundary';
        }
    }

    function loadPastedGeoJson() {
        const raw = document.getElementById('raw-geojson-input').value.trim();
        if (!raw) return;
        try {
            const parsed = JSON.parse(raw);
            const geom = parsed.geometry ? parsed.geometry : parsed;
            if (geom.type !== 'Polygon') {
                alert('GeoJSON must be of type "Polygon".');
                return;
            }

            drawnItems.clearLayers();
            const poly = L.geoJSON(geom, {
                style: { color: '#008080', weight: 3, fillColor: '#008080', fillOpacity: 0.3 }
            });
            drawnItems.addLayer(poly);
            map.fitBounds(poly.getBounds());
            activeBoundaryGeojson = geom;
            document.getElementById('btn-save-boundary').disabled = false;
            switchTab('boundaries');
            alert('Preview loaded on map. Click "Commit Version" in the Boundary tab to save.');
        } catch (e) {
            alert('Invalid JSON format: ' + e.message);
        }
    }

    function resetMapView() {
        if (drawnItems && drawnItems.getLayers().length > 0) {
            map.fitBounds(drawnItems.getBounds(), { padding: [50, 50] });
        } else {
            map.setView([defaultLat, defaultLng], 17);
        }
    }
</script>
@endpush

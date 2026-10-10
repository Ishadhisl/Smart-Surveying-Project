@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-12 text-center lg:text-left">
        <h2 class="text-4xl font-extrabold text-slate-900 tracking-tighter">{{ __('messages.new_survey') }}</h2>
        <p class="text-white font-bold opacity-70 tracking-widest uppercase text-[10px] mt-2">Submit official parameters for professional assessment</p>
    </div>

    <form action="{{ route('survey.store') }}" method="POST" enctype="multipart/form-data" id="survey-form">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <!-- Form Side -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Main Details Card -->
                <div class="glass p-10 rounded-[3rem] relative overflow-hidden border-white/40 shadow-2xl bg-white/10">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="form-group">
                            <label class="block text-[10px] font-black text-slate-900/50 uppercase tracking-widest mb-3">{{ __('messages.village') }}</label>
                            <input type="text" name="village" class="w-full bg-white/40 border border-white/20 rounded-2xl px-6 py-4 text-slate-900 font-bold placeholder-slate-900/40 focus:bg-white transition-all outline-none shadow-inner" placeholder="e.g. Colombo 07" required value="{{ old('village') }}">
                        </div>

                        <div class="form-group">
                            <label class="block text-[10px] font-black text-slate-900/50 uppercase tracking-widest mb-3">{{ __('messages.purpose') }}</label>
                            <select name="purpose" id="purpose" class="w-full bg-white/40 border border-white/20 rounded-2xl px-6 py-4 text-slate-900 font-bold focus:bg-white transition-all outline-none shadow-inner" required onchange="updateEstimate()">
                                <option value="none" selected disabled>Select Purpose</option>
                                <option value="partitioning">Partitioning (කැබලිවලට වෙන් කිරීම)</option>
                                <option value="loan">Bank Loan (බැංකු ණය)</option>
                                <option value="construction">New Construction (අලුත් ඉදිකිරීමක් සඳහා)</option>
                                <option value="land_sale">Land Sale (ඉඩම් විකිණීම)</option>
                                <option value="other">Other (වෙනත්)</option>
                            </select>
                        </div>
                    </div>

                    <div id="partitioning-fields" class="mt-8 hidden">
                        <div class="form-group">
                            <label class="block text-[10px] font-black text-slate-900/50 uppercase tracking-widest mb-3">Number of Lots</label>
                            <input type="number" name="lot_count" id="lot_count" class="w-full bg-white/40 border border-white/20 rounded-2xl px-6 py-4 text-slate-900 font-bold outline-none" min="1" value="1" oninput="updateEstimate()">
                        </div>
                    </div>

                    <div id="other-purpose-fields" class="mt-8 hidden">
                        <div class="form-group">
                            <label class="block text-[10px] font-black text-slate-900/50 uppercase tracking-widest mb-3">Please Specify the Purpose</label>
                            <input type="text" name="other_purpose" id="other_purpose" class="w-full bg-white/40 border border-white/20 rounded-2xl px-6 py-4 text-slate-900 font-bold placeholder-slate-900/40 outline-none shadow-inner" placeholder="Enter your specific requirement">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8">
                        <div>
                            <label class="block text-[10px] font-black text-slate-900/50 uppercase tracking-widest mb-3">Land Size</label>
                            <div class="flex gap-4">
                                <div class="w-1/2">
                                    <input type="number" name="size_acres" id="size_acres" class="w-full bg-white/40 border border-white/20 rounded-2xl px-4 py-4 text-slate-900 font-bold outline-none shadow-inner" min="0" value="0" placeholder="Acres" oninput="updateEstimate()">
                                    <span class="block text-[9px] text-slate-500 font-bold uppercase mt-1">Acres</span>
                                </div>
                                <div class="w-1/2">
                                    <input type="number" name="size_perches" id="size_perches" class="w-full bg-white/40 border border-white/20 rounded-2xl px-4 py-4 text-slate-900 font-bold outline-none shadow-inner" min="0" value="15" placeholder="Perches" required oninput="updateEstimate()">
                                    <span class="block text-[9px] text-slate-500 font-bold uppercase mt-1">Perches</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="block text-[10px] font-black text-slate-900/50 uppercase tracking-widest mb-3">Boundary Points</label>
                            <input type="number" name="boundary_points" id="boundary_points" class="w-full bg-white/40 border border-white/20 rounded-2xl px-6 py-4 text-slate-900 font-bold outline-none shadow-inner" min="4" value="4" oninput="updateEstimate()">
                        </div>
                    </div>
                </div>

                <!-- Map Card -->
                <div class="glass p-10 rounded-[3rem] shadow-2xl border-white/40 bg-white/10">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-end mb-6 gap-4">
                        <label class="block text-[10px] font-black text-slate-900/50 uppercase tracking-widest">{{ __('messages.location') }} (Click on map to mark precise location)</label>
                        <button type="button" id="btn-gps" class="text-[9px] bg-slate-900 text-white px-4 py-2 rounded-full uppercase font-black tracking-widest shadow-lg hover:bg-slate-800 transition-all flex items-center justify-center gap-2 w-full md:w-auto">
                            <span>📍</span> Use My Location
                        </button>
                    </div>

                    <div class="flex gap-3 mb-6">
                        <input type="text" id="map-search-input" placeholder="Search City / Town (e.g. Kandy, Sri Lanka)" class="w-full bg-white/60 border border-white/40 rounded-2xl px-6 py-3 text-sm text-slate-900 font-bold placeholder-slate-900/50 focus:bg-white transition-all outline-none shadow-inner">
                        <button type="button" id="btn-search" class="bg-deepteal text-white px-8 py-3 rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-lg hover:bg-teal-700 transition shrink-0">Search</button>
                    </div>

                    <div id="map" class="h-80 md:h-96 w-full rounded-3xl border-8 border-white shadow-inner overflow-hidden relative z-0"></div>
                    <input type="hidden" name="location_lat" id="lat">
                    <input type="hidden" name="location_lng" id="lng">
                </div>
            </div>

            <!-- Sticky Pricing / Submit Side -->
            <div class="lg:col-span-1 space-y-8">
                <!-- Deed Upload Card -->
                <div class="glass p-8 rounded-[2.5rem] border-white/40 shadow-xl bg-white/10">
                    <label class="block text-[10px] font-black text-slate-900/50 uppercase tracking-widest mb-4">{{ __('messages.upload_deed') }} (PDF) <span class="italic lowercase opacity-60 ml-1">- Optional</span></label>
                    <div class="relative group cursor-pointer border-4 border-dashed border-white/20 p-8 rounded-[2rem] hover:border-white transition-all text-center bg-white/10">
                        <input type="file" name="deed" id="deed-upload" class="absolute inset-0 opacity-0 cursor-pointer" accept="application/pdf" onchange="updateFileName(this)">
                        <div id="upload-placeholder">
                            <div class="text-4xl mb-3 opacity-30">📄</div>
                            <div class="text-[9px] text-slate-900 font-black uppercase tracking-widest">Select Legal Document</div>
                        </div>
                        <div id="file-name" class="hidden text-xs font-black text-deepteal break-all"></div>
                    </div>
                    @error('deed') <p class="text-red-900 text-[10px] mt-3 font-black uppercase tracking-tighter">{{ $message }}</p> @enderror
                </div>

                <!-- Price Calculator Result -->
                <div class="bg-slate-900 p-10 rounded-[3rem] text-center shadow-[0_20px_50px_rgba(0,0,0,0.3)] border border-white/10 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-deepteal/20 to-transparent"></div>
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-white/40 mb-6 relative z-10">Professional Fee Estimate</h3>
                    <div class="text-5xl font-black text-white relative z-10 mb-2">
                        <span class="text-lg opacity-40 font-bold">Rs.</span>
                        <span id="estimate-display">15,000</span>
                    </div>
                    <p class="text-[9px] text-white/30 mt-6 leading-relaxed font-bold relative z-10 uppercase tracking-widest">
                        * Final pricing confirmed after field inspection
                    </p>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full btn-teal py-6 rounded-[2rem] text-white font-black text-lg shadow-[0_15px_30px_rgba(0,128,128,0.4)] flex items-center justify-center gap-3 uppercase tracking-widest">
                    Submit Request
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // Setup Satellite and Standard Layers
    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    });
    
    // Esri World Imagery (Free Satellite View)
    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { 
        maxZoom: 19,
        attribution: 'Tiles © Esri'
    });

    const map = L.map('map', {
        layers: [osmLayer] // default layer
    }).setView([7.8731, 80.7718], 8);

    const baseMaps = {
        "🗺️ Standard Map": osmLayer,
        "🌍 Satellite View": satelliteLayer
    };

    L.control.layers(baseMaps, null, {position: 'topright'}).addTo(map);

    let marker;
    
    function setLocationAndMarker(lat, lng, zoomLevel) {
        map.flyTo([lat, lng], zoomLevel);
        if (marker) map.removeLayer(marker);
        marker = L.marker([lat, lng]).addTo(map);
        document.getElementById('lat').value = lat;
        document.getElementById('lng').value = lng;
    }

    map.on('click', function(e) {
        setLocationAndMarker(e.latlng.lat, e.latlng.lng, map.getZoom());
    });

    // 1. GPS Button Logic
    document.getElementById('btn-gps').addEventListener('click', function() {
        const btn = this;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span>⏳</span> Locating...';
        
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(function(position) {
                setLocationAndMarker(position.coords.latitude, position.coords.longitude, 18);
                btn.innerHTML = '<span>✅</span> Location Found';
                setTimeout(() => btn.innerHTML = originalText, 3000);
            }, function(error) {
                alert("GPS Error: Could not get your location. Please ensure location permissions are enabled.");
                btn.innerHTML = originalText;
            }, { enableHighAccuracy: true });
        } else {
            alert("Geolocation is not supported by your browser.");
            btn.innerHTML = originalText;
        }
    });

    // 2. City Search Logic (Nominatim API)
    document.getElementById('btn-search').addEventListener('click', function() {
        const query = document.getElementById('map-search-input').value;
        if (!query) return;

        const btn = this;
        const originalText = btn.innerText;
        btn.innerText = 'Searching...';

        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + ', Sri Lanka')}&limit=1`)
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);
                    map.flyTo([lat, lon], 14); // Zoom to town level, wait for them to pinpoint precisely
                } else {
                    alert("Location not found. Try a different city or town name.");
                }
            })
            .catch(err => {
                console.error(err);
                alert("Error searching location. Please check your internet connection.");
            })
            .finally(() => {
                btn.innerText = originalText;
            });
    });

    // Allow pressing "Enter" in the search box
    document.getElementById('map-search-input').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('btn-search').click();
        }
    });

    function updateEstimate() {
        const purpose = document.getElementById('purpose').value;
        const acres = parseFloat(document.getElementById('size_acres').value) || 0;
        const perches = parseFloat(document.getElementById('size_perches').value) || 0;
        const totalPerches = (acres * 160) + perches;

        const lots = parseInt(document.getElementById('lot_count').value) || 1;
        const points = parseInt(document.getElementById('boundary_points').value) || 4;

        // UI toggles
        const pFields = document.getElementById('partitioning-fields');
        pFields.classList.toggle('hidden', purpose !== 'partitioning');

        const opFields = document.getElementById('other-purpose-fields');
        opFields.classList.toggle('hidden', purpose !== 'other');

        // Pricing Logic (Refined Tiered SL Strategy)
        let price = 15000; // Mobilization

        if (totalPerches <= 40) {
            price += totalPerches * 850;
        } else if (totalPerches < 160) {
            price += 34000 + ((totalPerches - 40) * 400);
        } else {
            const totalAcres = Math.floor(totalPerches / 160);
            const remainingPerches = totalPerches - (totalAcres * 160);
            
            price += 65000; // First acre
            if (totalAcres > 1) {
                price += (totalAcres - 1) * 35000;
            }
            if (remainingPerches > 0) {
                price += remainingPerches * 200;
            }
        }
        
        if (points > 4) {
            price += (points - 4) * 1500;
        }

        if (purpose === 'partitioning') {
            price += (lots * 6500);
        } else if (purpose === 'loan' || purpose === 'court_partition' || purpose === 'banking') {
            price += 5000; // Documentation
        }

        // Round to nearest 100
        const roundedPrice = Math.round(price / 100) * 100;
        document.getElementById('estimate-display').innerText = roundedPrice.toLocaleString();
    }

    function updateFileName(input) {
        const placeholder = document.getElementById('upload-placeholder');
        const fileNameDiv = document.getElementById('file-name');
        if (input.files && input.files[0]) {
            placeholder.classList.add('hidden');
            fileNameDiv.classList.remove('hidden');
            fileNameDiv.innerText = 'Selected: ' + input.files[0].name;
        }
    }

    updateEstimate();
</script>
@endpush

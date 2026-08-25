@php
    $currentBusiness = $currentBusiness ?? ($business ?? \App\Models\Business::findBySlug(session('active_business_slug')));
    $enforceGeofence = (int)($currentBusiness->enforce_geofence ?? 0);
@endphp

<form id="locationsForm">

    @if (isset($location))
        <input type="hidden" name="location_slug" id="location_slug"
               value="{{ isset($location) && !empty($location) ? $location->slug : '' }}">
    @endif

    <div class="form-group mb-3">
        <label for="name">Location Name</label>
        <input type="text" name="name" id="name" class="form-control" placeholder="Location Name"
               value="{{ isset($location) && !empty($location) ? $location->name : '' }}" required>
    </div>

    <div class="form-group mb-3">
        <label for="company_size">Company size</label>
        <select id="company_size" name="company_size" required class="form-select">
            <option value="">Select Company Size</option>
            <option value="1-10"    {{ isset($location) && !empty($location) && $location->company_size === '1-10' ? 'selected' : '' }}>1-10 employees</option>
            <option value="11-50"   {{ isset($location) && !empty($location) && $location->company_size === '11-50' ? 'selected' : '' }}>11-50 employees</option>
            <option value="51-200"  {{ isset($location) && !empty($location) && $location->company_size === '51-200' ? 'selected' : '' }}>51-200 employees</option>
            <option value="201-500" {{ isset($location) && !empty($location) && $location->company_size === '201-500' ? 'selected' : '' }}>201-500 employees</option>
            <option value="500+"    {{ isset($location) && !empty($location) && $location->company_size === '500+' ? 'selected' : '' }}>500+ employees</option>
        </select>
    </div>

    <div class="form-group mb-3">
        <label for="address">Physical Address</label>
        <input type="text" name="address" id="address" class="form-control" placeholder="Physical Address"
               value="{{ isset($location) && !empty($location) ? $location->physical_address : '' }}" required>
    </div>

    <div class="form-group mb-3">
        <label for="country">Country</label>
        <select name="country" id="country" class="form-select"
                data-selected="{{ isset($location) && !empty($location) ? $location->country : '' }}"
                data-countries-url="{{ route('business.holidays.countries', $currentBusiness->slug, false) }}">
            <option value="">Loading countries...</option>
        </select>
    </div>

    @php
        $latVal = old('latitude', $location->latitude ?? '');
        $lngVal = old('longitude', $location->longitude ?? '');
        $radVal = old('radius_m', $location->radius_m ?? 150);
@endphp

    @if($enforceGeofence)
        <div class="border rounded p-3 mb-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <label class="mb-0 fw-semibold">Geofence Coordinates</label>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnUseMyLocation">
                        Use my current location
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnSyncMapFromFields">
                        Update map from fields
                    </button>
                </div>
            </div>

            <div class="row g-2 mt-2">
                <div class="col-md-8">
                    <label class="form-label">Search building/address</label>
                    <input type="text" id="placeSearch" class="form-control" placeholder="e.g. KICC, Nairobi" autocomplete="off">
                    <div id="placeResults" class="list-group mt-1" style="max-height: 260px; overflow:auto; display:none;"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Paste coordinates / Google Maps URL</label>
                    <input type="text" id="pasteCoords" class="form-control" placeholder="-1.286389, 36.817223 OR a Maps link">
                </div>
            </div>

            <div class="row g-2 mt-2">
                <div class="col-md-4">
                    <label class="form-label">Latitude</label>
                    <input type="number" name="latitude" id="latitude" class="form-control"
                           step="0.0000001" value="{{ $latVal }}" placeholder="-1.286389">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Longitude</label>
                    <input type="number" name="longitude" id="longitude" class="form-control"
                           step="0.0000001" value="{{ $lngVal }}" placeholder="36.817223">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Radius (meters)</label>
                    <input type="number" name="radius_m" id="radius_m" class="form-control"
                           min="1" value="{{ $radVal }}">
                </div>
            </div>

            <div class="mt-3">
                <div id="locationPickerMap" style="height: 300px; border-radius: .5rem;"></div>
                <small class="text-muted d-block mt-2">
                    Search, paste, click the map, drag marker, or use your location. Radius updates automatically.
                </small>
            </div>
        </div>
    @endif

    <div class="form-group">
        <button type="button" class="btn btn-primary w-100" id="submitButton" onclick="saveLocation(this)">
            <i class="bi bi-check-circle"></i> Save Location
        </button>
    </div>
</form>

@if($enforceGeofence)
    @push('styles')
        <link rel="stylesheet"
              href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
              integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
              crossorigin=""/>
        <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    @endpush

    @push('scripts')
        <script
            src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-XQoYMqMTK8LvdlxU/3mxGwJ4dDqLw4v0uQmPzLE0mXQ="
            crossorigin="">
        </script>
        <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
        <script>
(function () {
  const mapId = 'locationPickerMap';
  const mapEl = document.getElementById(mapId);
  const latEl = document.getElementById('latitude');
  const lngEl = document.getElementById('longitude');
  const radEl = document.getElementById('radius_m');
  const searchInput = document.getElementById('placeSearch');
  const resultsBox = document.getElementById('placeResults');
  const pasteEl = document.getElementById('pasteCoords');

  // --- Search (proxy via controller to avoid CORS) ---
  let searchTimer, aborter;
  async function geocode(term) {
    if (aborter) aborter.abort();
    aborter = new AbortController();
    const url = "{{ route('attendance.geocode') }}" + "?q=" + encodeURIComponent(term) + "&countrycodes=ke&limit=8";
    const res = await fetch(url, { signal: aborter.signal });
    if (!res.ok) throw new Error('geocode failed');
    return res.json();
  }
  function renderResults(items) {
    resultsBox.innerHTML = '';
    if (!items.length) { resultsBox.style.display = 'none'; return; }
    items.forEach(it => {
      const a = document.createElement('a');
      a.href = 'javascript:void(0)';
      a.className = 'list-group-item list-group-item-action';
      a.innerHTML = `<div class="fw-semibold">${it.display_name}</div>
                     <small class="text-muted">${(+it.lat).toFixed(6)}, ${(+it.lon).toFixed(6)}</small>`;
      a.onclick = () => {
        latEl.value = (+it.lat).toFixed(7);
        lngEl.value = (+it.lon).toFixed(7);
        try { syncMarkerFromFields(); } catch(_) {}
        searchInput.value = it.display_name;
        resultsBox.style.display = 'none';
      };
      resultsBox.appendChild(a);
    });
    resultsBox.style.display = 'block';
  }
  searchInput?.addEventListener('input', () => {
    const term = searchInput.value.trim();
    clearTimeout(searchTimer);
    if (!term) { resultsBox.style.display = 'none'; return; }
    searchTimer = setTimeout(async () => {
      try { renderResults(await geocode(term)); } catch { resultsBox.style.display = 'none'; }
    }, 250);
  });

  // --- Paste box: coords or Google Maps URL ---
  function tryParseCoords(raw) {
    // 1) Lat,Lng
    let m = raw.match(/(-?\d+(\.\d+)?)[,\s]+(-?\d+(\.\d+)?)/);
    if (m) return { lat: +m[1], lng: +m[3] };
    // 2) URL @lat,lng
    try {
      const u = new URL(raw);
      m = u.href.match(/@(-?\d+(\.\d+)?),\s*(-?\d+(\.\d+)?)/);
      if (m) return { lat: +m[1], lng: +m[3] };
      // 3) ?q=lat,lng
      const q = u.searchParams.get('q');
      if (q) {
        const mm = q.match(/(-?\d+(\.\d+)?)[,\s]+(-?\d+(\.\d+)?)/);
        if (mm) return { lat: +mm[1], lng: +mm[3] };
      }
    } catch (_) {}
    return null;
  }
  pasteEl?.addEventListener('blur', () => {
    const raw = (pasteEl.value || '').trim();
    if (!raw) return;
    const p = tryParseCoords(raw);
    if (!p) {
      return window.Swal?.fire?.('Oops', 'Couldn’t extract coordinates. Try a plain "-1.286389, 36.817223".', 'info');
    }
    latEl.value = p.lat.toFixed(7);
    lngEl.value = p.lng.toFixed(7);
    try { syncMarkerFromFields(); } catch(_) {}
  });

  // --- Map (optional) ---
  let map, marker, circle;
  function syncFields() {
    if (!marker || !circle) return;
    const p = marker.getLatLng();
    if (latEl) latEl.value = p.lat.toFixed(7);
    if (lngEl) lngEl.value = p.lng.toFixed(7);
    if (radEl) radEl.value = Math.round(circle.getRadius());
  }
  function syncMarkerFromFields() {
    if (!marker || !circle || !map) return;
    const newLat = parseFloat(latEl?.value || '-1.286389');
    const newLng = parseFloat(lngEl?.value || '36.817223');
    const newR   = Math.max(1, parseInt(radEl?.value || '150', 10));
    marker.setLatLng([newLat, newLng]);
    circle.setLatLng([newLat, newLng]);
    circle.setRadius(newR);
    map.setView([newLat, newLng], map.getZoom());
  }

  document.getElementById('btnUseMyLocation')?.addEventListener('click', () => {
    if (!navigator.geolocation) return window.Swal?.fire?.('Error','Geolocation not supported.','error');
    navigator.geolocation.getCurrentPosition(
      pos => {
        latEl.value = pos.coords.latitude.toFixed(7);
        lngEl.value = pos.coords.longitude.toFixed(7);
        try {
          syncMarkerFromFields();
          map?.setView([+latEl.value, +lngEl.value], 16);
        } catch(_) {}
      },
      _ => window.Swal?.fire?.('Error', 'Allow location access and try again.', 'error'),
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
  });
  document.getElementById('btnSyncMapFromFields')?.addEventListener('click', () => {
    try { syncMarkerFromFields(); } catch(_) {}
  });
  radEl?.addEventListener('change', () => { try { syncMarkerFromFields(); } catch(_) {} });

  // init map in a try/catch so form still works if Leaflet fails
  try {
    const startLat = parseFloat(latEl?.value || '-1.286389');
    const startLng = parseFloat(lngEl?.value || '36.817223');
    const startR   = Math.max(1, parseInt(radEl?.value || '150', 10));

    map = L.map(mapId, { scrollWheelZoom: true }).setView([startLat, startLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 20, attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    if (L.Control && L.Control.geocoder) {
      L.Control.geocoder({
        collapsed: false, defaultMarkGeocode: false, placeholder: 'Search building, address...'
      }).on('markgeocode', function(e) {
        const c = e.geocode.center;
        latEl.value = c.lat.toFixed(7);
        lngEl.value = c.lng.toFixed(7);
        syncMarkerFromFields();
        map.setView(c, 17);
      }).addTo(map);
    }

    marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);
    circle = L.circle([startLat, startLng], { radius: startR }).addTo(map);
    marker.on('drag', e => circle.setLatLng(e.target.getLatLng()));
    marker.on('dragend', syncFields);
    map.on('click', e => { marker.setLatLng(e.latlng); circle.setLatLng(e.latlng); syncFields(); });

    // Resize fixes
    const refresh = () => { try { map.invalidateSize(); } catch(_){} };
    window.addEventListener('load', refresh);
    setTimeout(refresh, 0);
    if ('ResizeObserver' in window) new ResizeObserver(refresh).observe(mapEl);
  } catch(e) {
    console.warn('Leaflet failed; form will still work:', e);
  }
})();
        </script>
    @endpush
@endif

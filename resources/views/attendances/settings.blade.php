<x-app-layout title="Attendance Settings">
    <div class="row g-20">
        <div class="col-12">
            <card class="mb-3 mt-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h5 class="mb-0">Attendance Settings</h5>
                        <a class="btn btn-outline-warning btn-sm" href="{{ route('business.holidays.index', $business->slug) }}">
                            <i class="bi bi-calendar2-event me-1"></i> Set Holidays
                        </a>
                    </div>
                    <form id="attendanceSettingsForm">
                        @csrf
                        <input type="hidden" name="business_slug" value="{{ $business->slug }}">

                        <div class="row g-3">
                            <div class="col-md-6 d-flex align-items-center justify-content-between">
                                <label class="form-label mb-0">Enforce Geofence</label>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="enforce_geofence"
                                           name="enforce_geofence" value="1" {{ ($business->enforce_geofence ?? 0) ? 'checked' : '' }}>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-center justify-content-between">
                                <label class="form-label mb-0">Enforce Registered Device (MAC)</label>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="enforce_mac"
                                           name="enforce_mac" value="1" {{ ($business->enforce_mac ?? 0) ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 border rounded-3 p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                <div>
                                    <div class="fw-semibold">Extra Geofences</div>
                                    <small class="text-muted">Search, paste, use current location, or click the map to add points.</small>
                                </div>
                                <div class="geofence-toolbar d-flex align-items-center gap-2">
                                    <label class="form-label mb-0">Default radius</label>
                                    <input type="number" class="form-control form-control-sm" id="defaultRadius" value="150" min="1" />
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="useMyLocationBtn">Use my location</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="clearAllFencesBtn">Clear all</button>
                                </div>
                            </div>

                            {{-- Search + Paste + Add row --}}
                            <div class="row g-2 mb-2">
                                <div class="col-md-5">
                                    <input type="text" id="geoSearch" class="form-control" placeholder="Search building/address (e.g. KICC, Nairobi)" autocomplete="off">
                                    <div id="geoSearchResults" class="list-group mt-1" style="max-height: 220px; overflow:auto; display:none;"></div>
                                </div>
                                <div class="col-md-5">
                                    <input type="text" id="geoPaste" class="form-control" placeholder="Paste coords or Google Maps link">
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button type="button" class="btn btn-outline-primary" id="addPointBtn">Add point</button>
                                </div>
                            </div>

                            <div id="extraGeofenceMap" class="mb-2" style="height: 320px; border-radius: .5rem;"></div>

                            <ul class="list-group geofence-list mb-2" id="geofenceList"></ul>

                            <input type="hidden" name="extra_geofences" id="extra_geofences"
                                   value='{{ is_array($business->extra_geofences ?? null) ? json_encode($business->extra_geofences) : ($business->extra_geofences ?? "[]") }}'>
                        </div>

                        <button type="button" class="btn btn-outline-primary w-100 mt-3" id="btnSaveAttendanceSettings">
                            Save Attendance Settings
                        </button>
                    </form>
                </div>
            </card>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet"
              href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
              integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
              crossorigin=""/>
        <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
        <style>
            .form-switch .form-check-input {
                width: 3.2rem; height: 1.6rem; cursor: pointer; background-color: #cbd5e1; border: 0; border-radius: 1rem;
                position: relative; transition: background-color .2s ease-in-out;
            }
            .form-switch .form-check-input:focus { box-shadow: 0 0 0 .15rem rgba(13,110,253,.15); }
            .form-switch .form-check-input:checked { background-color: #0d6efd; }
            .form-switch .form-check-input::before {
                content: ''; position: absolute; top: .2rem; left: .2rem; width: 1.2rem; height: 1.2rem; background: #fff; border-radius: 50%;
                transition: transform .2s ease-in-out; box-shadow: 0 1px 2px rgba(0,0,0,.25);
            }
            .form-switch .form-check-input:checked::before { transform: translateX(1.6rem); }

            .geofence-toolbar .form-control { max-width: 160px; }
            .geofence-list li { display:flex; align-items:center; justify-content:space-between; }
            .geofence-pill { display:inline-block; padding:.2rem .5rem; border-radius:999px; background:#f1f5f9; font-size:.875rem; }
        </style>
    @endpush

    @push('scripts')
        <script
            src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-XQoYMqMTK8LvdlxU/3mxGwJ4dDqLw4v0uQmPzLE0mXQ="
            crossorigin="">
        </script>
        <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

        {{-- Map + Search + Paste + Add logic --}}
        <script>
(function() {
  // Elements
  const mapEl = document.getElementById('extraGeofenceMap');
  const hidden = document.getElementById('extra_geofences');
  const searchInput = document.getElementById('geoSearch');
  const searchResults = document.getElementById('geoSearchResults');
  const pasteInput = document.getElementById('geoPaste');
  const addBtn = document.getElementById('addPointBtn');
  const defaultRadiusInput = document.getElementById('defaultRadius');

  // Stored state
  let geofences = [];
  try { geofences = JSON.parse(hidden.value || '[]') || []; } catch(_) { geofences = []; }

  // Map (optional)
  let map;
  const layers = []; // [{ marker, circle }]
  function addFenceToMap(lat, lng, radius) {
    if (!map) return;
    const m = L.marker([lat, lng], { draggable: true }).addTo(map);
    const c = L.circle([lat, lng], { radius }).addTo(map);
    m.on('drag', e => c.setLatLng(e.target.getLatLng()));
    m.on('dragend', e => {
      const p = e.target.getLatLng();
      const idx = layers.findIndex(x => x.marker === m);
      if (idx >= 0) {
        geofences[idx].lat = +p.lat.toFixed(7);
        geofences[idx].lng = +p.lng.toFixed(7);
        save();
        renderList();
      }
    });
    layers.push({ marker: m, circle: c });
  }
  function removeFenceFromMap(idx) {
    if (!layers[idx]) return;
    map.removeLayer(layers[idx].marker);
    map.removeLayer(layers[idx].circle);
    layers.splice(idx, 1);
  }
  function refreshMap() { try { map.invalidateSize(); } catch(_) {} }

  // Save + render list
  function save() { hidden.value = JSON.stringify(geofences); }
  function renderList() {
    const list = document.getElementById('geofenceList');
    list.innerHTML = '';
    if (!geofences.length) {
      list.innerHTML = '<li class="list-group-item text-muted">No extra geofences added.</li>';
      return;
    }
    geofences.forEach((g, i) => {
      const li = document.createElement('li');
      li.className = 'list-group-item d-flex align-items-center justify-content-between';
      li.innerHTML = `
        <span class="geofence-pill">${g.lat.toFixed(6)}, ${g.lng.toFixed(6)} — ${g.radius_m}m</span>
        <span class="d-flex align-items-center gap-2">
          <input class="form-control form-control-sm" type="number" min="1" value="${g.radius_m}" style="width:110px" data-idx="${i}" />
          <button type="button" class="btn btn-sm btn-outline-danger" data-remove="${i}">
            <i class="bi bi-trash3"></i>
          </button>
        </span>`;
      list.appendChild(li);
    });
    // wire up events
    list.querySelectorAll('input[type="number"]').forEach(input => {
      input.addEventListener('change', e => {
        const idx = +e.target.dataset.idx;
        const val = Math.max(1, parseInt(e.target.value || 150, 10));
        geofences[idx].radius_m = val;
        if (layers[idx]) layers[idx].circle.setRadius(val);
        save();
      });
    });
    list.querySelectorAll('button[data-remove]').forEach(btn => {
      btn.addEventListener('click', () => {
        const idx = +btn.dataset.remove;
        // remove from arrays
        geofences.splice(idx, 1);
        if (map) removeFenceFromMap(idx);
        save();
        renderList();
      });
    });
  }

  // Search via controller proxy
  let timer, aborter;
  async function geocode(term) {
    if (aborter) aborter.abort();
    aborter = new AbortController();
    const url = "{{ route('attendance.geocode') }}" + "?q=" + encodeURIComponent(term) + "&countrycodes=ke&limit=8";
    const res = await fetch(url, { signal: aborter.signal });
    if (!res.ok) throw new Error('geocode failed');
    return res.json();
  }

  function renderSearch(items) {
    searchResults.innerHTML = '';
    if (!items.length) { searchResults.style.display = 'none'; return; }
    items.forEach(it => {
      const a = document.createElement('a');
      a.href = 'javascript:void(0)';
      a.className = 'list-group-item list-group-item-action';
      a.innerHTML = `<div class="fw-semibold">${it.display_name}</div>
                     <small class="text-muted">${(+it.lat).toFixed(6)}, ${(+it.lon).toFixed(6)}</small>`;
      a.onclick = () => {
        addFence(+it.lat, +it.lon, +defaultRadiusInput.value || 150);
        searchInput.value = it.display_name;
        searchResults.style.display = 'none';
      };
      searchResults.appendChild(a);
    });
    searchResults.style.display = 'block';
  }
  searchInput?.addEventListener('input', () => {
    const term = searchInput.value.trim();
    clearTimeout(timer);
    if (!term) { searchResults.style.display = 'none'; return; }
    timer = setTimeout(async () => {
      try { renderSearch(await geocode(term)); } catch { searchResults.style.display = 'none'; }
    }, 250);
  });

  // Paste box parser
  function parseCoords(raw) {
    let m = raw.match(/(-?\d+(\.\d+)?)[,\s]+(-?\d+(\.\d+)?)/);
    if (m) return { lat: +m[1], lng: +m[3] };
    try {
      const u = new URL(raw);
      m = u.href.match(/@(-?\d+(\.\d+)?),\s*(-?\d+(\.\d+)?)/);
      if (m) return { lat: +m[1], lng: +m[3] };
      const q = u.searchParams.get('q');
      if (q) {
        const mm = q.match(/(-?\d+(\.\d+)?)[,\s]+(-?\d+(\.\d+)?)/);
        if (mm) return { lat: +mm[1], lng: +mm[3] };
      }
    } catch(_) {}
    return null;
  }

  // Add point (from search OR paste OR nothing -> error)
  function addFence(lat, lng, radius) {
    const r = Math.max(1, parseInt(radius || defaultRadiusInput.value || 150, 10));
    geofences.push({ lat: +(+lat).toFixed(7), lng: +(+lng).toFixed(7), radius_m: r });
    if (map) addFenceToMap(lat, lng, r);
    save();
    renderList();
    if (map && geofences.length === 1) map.setView([lat, lng], 16);
  }
  addBtn?.addEventListener('click', () => {
    const pasted = (pasteInput.value || '').trim();
    const parsed = pasted ? parseCoords(pasted) : null;
    if (parsed) {
      addFence(parsed.lat, parsed.lng);
      return;
    }
    // if not pasted, try to use current geolocation quickly
    if (!pasted && navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        pos => addFence(pos.coords.latitude, pos.coords.longitude),
        _ => window.Swal?.fire?.('Oops', 'Paste coords/URL or allow location to add a point.', 'info'),
        { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
      );
    } else {
      window.Swal?.fire?.('Oops', 'Paste coords/URL or use search/location.', 'info');
    }
  });

  // Use my location toolbar button
  document.getElementById('useMyLocationBtn')?.addEventListener('click', () => {
    if (!navigator.geolocation) return window.Swal?.fire?.('Error','Geolocation not supported.','error');
    navigator.geolocation.getCurrentPosition(
      pos => addFence(pos.coords.latitude, pos.coords.longitude),
      _ => window.Swal?.fire?.('Error','Allow location access and try again.','error'),
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
  });

  // Clear All
  document.getElementById('clearAllFencesBtn')?.addEventListener('click', () => {
    geofences = [];
    if (map) while (layers.length) { removeFenceFromMap(layers.length - 1); }
    save(); renderList();
  });

  // Init map safely (form still works without it)
  try {
    let center = { lat: -1.286389, lng: 36.817223 };
    if (geofences.length) center = { lat: geofences[0].lat, lng: geofences[0].lng };
    map = L.map('extraGeofenceMap', { scrollWheelZoom: true }).setView([center.lat, center.lng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 20, attribution: '&copy; OpenStreetMap' }).addTo(map);

    if (L.Control && L.Control.geocoder) {
      L.Control.geocoder({
        collapsed: false, defaultMarkGeocode: false, placeholder: 'Search building, address...'
      }).on('markgeocode', function(e) {
        const c = e.geocode.center;
        addFence(c.lat, c.lng);
        map.setView(c, 17);
      }).addTo(map);
    }
    if ('ResizeObserver' in window) new ResizeObserver(refreshMap).observe(mapEl);
  } catch (e) { console.warn('Map failed, list-only mode is active.', e); }

  // Seed existing
  geofences.forEach(g => { if (map) addFenceToMap(g.lat, g.lng, g.radius_m); });
  renderList();

  // Save handler
    document.getElementById('btnSaveAttendanceSettings')?.addEventListener('click', async () => {
        const form = document.getElementById('attendanceSettingsForm');
        const slug = "{{ $business->slug }}";
        const url = "{{ route('business.attendance.settings.update', ':slug') }}".replace(':slug', slug);
        const fd = new FormData(form);
        if (!document.getElementById('enforce_geofence').checked) fd.delete('enforce_geofence');
        if (!document.getElementById('enforce_mac').checked)      fd.delete('enforce_mac');

        try {
        const resp = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: fd
        });

        let json = {};
        try { json = await resp.json(); } catch(_) {}

        // Accept common "success" variants
        const okFlag =
            resp.ok &&
            (
            json.success === true ||
            json.ok === true ||
            json.status === true ||
            (typeof json.status === 'string' && json.status.toLowerCase() === 'success')
            );

        if (okFlag) {
            Swal.fire('Saved', 'Attendance settings saved.', 'success')
                .then(() => location.reload());
        } else {
            const msg = json.message || json.error || 'Failed to save.';
            Swal.fire('Error', msg, 'error');
            console.debug('Save response not OK:', json);
        }
        } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Network error. Try again.', 'error');
        }
    });
    })();

        </script>
    @endpush
</x-app-layout>

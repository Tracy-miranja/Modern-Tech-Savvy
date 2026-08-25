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
                            <div class="col-md-6">
                                <label class="form-label mb-0">Clock-in Method</label>
                                <select class="form-select" id="check_in_method" name="check_in_method">
                                    <option value="in_system" {{ ($business->check_in_method ?? 'in_system') === 'in_system' ? 'selected' : '' }}>In-system only (employee self clock-in/out)</option>
                                    <option value="device" {{ ($business->check_in_method ?? '') === 'device' ? 'selected' : '' }}>Biometric device only</option>
                                    <option value="both" {{ ($business->check_in_method ?? '') === 'both' ? 'selected' : '' }}>Both</option>
                                </select>
                                <small class="text-muted">Controls whether employees can self clock-in from the app, use a registered device below, or either.</small>
                            </div>
                            <div class="col-md-3 d-flex align-items-center justify-content-between">
                                <label class="form-label mb-0">Enforce Geofence</label>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="enforce_geofence"
                                           name="enforce_geofence" value="1" {{ ($business->enforce_geofence ?? 0) ? 'checked' : '' }}>
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-center justify-content-between">
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

            <card class="mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h5 class="mb-0">Expected Working Hours</h5>
                            <small class="text-muted">
                                Per-day target used to compute each month's expected hours on the Monthly report
                                (working days that month, minus holidays, times this figure) - not a fixed
                                monthly number, so it stays correct even when a month has extra public holidays.
                            </small>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newAttendancePolicyModal">
                            <i class="bi bi-plus-circle me-1"></i> Add Override
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>Scope</th>
                                    <th>Expected Hours/Day</th>
                                    <th style="width:100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="attendancePoliciesTableBody">
                                <tr><td colspan="3" class="text-center text-muted">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </card>

            <card class="mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0">Biometric Clock-in Devices</h5>
                            <small class="text-muted">
                                Register a physical terminal, then paste the webhook URL it gives you into that
                                device's own server configuration - the device pushes punches to us, we never
                                connect out to it.
                            </small>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newDeviceModal">
                            <i class="bi bi-plus-circle me-1"></i> Add Device
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Vendor</th>
                                    <th>Location</th>
                                    <th>Enrolled</th>
                                    <th>Last Seen</th>
                                    <th>Status</th>
                                    <th style="width:260px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="devicesTableBody">
                                <tr><td colspan="7" class="text-center text-muted">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </card>
        </div>
    </div>

    <div class="modal fade" id="newDeviceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deviceModalTitle">Register Device</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="deviceForm">
                        <input type="hidden" name="device_id" id="deviceFormId">
                        <div class="mb-3">
                            <label class="form-label">Device Name</label>
                            <input type="text" name="name" id="deviceFormName" class="form-control" placeholder="e.g. Main Gate Terminal" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vendor</label>
                            <select name="vendor" id="deviceFormVendor" class="form-select" required>
                                <option value="zkteco">ZKTeco</option>
                                <option value="hikvision">Hikvision</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location <small class="text-muted">(optional)</small></label>
                            <select name="location_id" id="deviceFormLocation" class="form-select">
                                <option value="">Not tied to a specific location</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-1" id="deviceFormSerialGroup">
                            <label class="form-label">Serial Number <small class="text-muted">(ZKTeco classic ADMS devices only)</small></label>
                            <input type="text" name="serial_number" id="deviceFormSerial" class="form-control" placeholder="Found on the device or its menu">
                            <small class="text-muted">Only needed if the device can't be given a custom server URL/path - it will identify itself by this serial instead.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitDeviceBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deviceUrlModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Device Webhook URL</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2">
                        Paste this into the device's "Server URL"/"Cloud Server" configuration. ZKTeco classic
                        ADMS devices ignore custom paths - point them at this domain and port instead, and make
                        sure the serial number is set on this device's record.
                    </p>
                    <div class="input-group">
                        <input type="text" class="form-control" id="deviceUrlValue" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="copyDeviceUrlBtn"><i class="bi bi-clipboard"></i></button>
                    </div>
                    <button type="button" class="btn btn-outline-warning btn-sm mt-3" id="regenerateDeviceUrlBtn">
                        <i class="bi bi-arrow-repeat me-1"></i> Rotate URL (if it leaked)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deviceEnrollmentsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Employee PIN Mapping - <span id="enrollmentsDeviceName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Only needed if an employee's PIN on this device doesn't match their employee code -
                        otherwise punches match automatically.
                    </p>
                    <form id="enrollmentForm" class="row g-2 mb-3">
                        <div class="col-4">
                            <input type="text" class="form-control" id="enrollmentPin" placeholder="Device PIN" required>
                        </div>
                        <div class="col-6">
                            <select class="form-select" id="enrollmentEmployee" required>
                                <option value="">Loading employees…</option>
                            </select>
                        </div>
                        <div class="col-2 d-grid">
                            <button type="submit" class="btn btn-primary">Map</button>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead><tr><th>PIN</th><th>Employee</th><th style="width:80px;"></th></tr></thead>
                            <tbody id="enrollmentsTableBody">
                                <tr><td colspan="3" class="text-center text-muted">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newAttendancePolicyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Expected Hours Override</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="attendancePolicyForm">
                        <div class="mb-3">
                            <label class="form-label">Scope</label>
                            <select name="scope" id="attendancePolicyScope" class="form-select">
                                <option value="business">Business default (applies to everyone)</option>
                                <option value="department">Department</option>
                                <option value="job_category">Job Category</option>
                                <option value="employee">Specific Employee</option>
                            </select>
                        </div>
                        <div class="mb-3 d-none" id="attendancePolicyDepartmentGroup">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">Select department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3 d-none" id="attendancePolicyJobCategoryGroup">
                            <label class="form-label">Job Category</label>
                            <select name="job_category_id" class="form-select">
                                <option value="">Select job category</option>
                                @foreach ($jobCategories as $jobCategory)
                                    <option value="{{ $jobCategory->id }}">{{ $jobCategory->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3 d-none" id="attendancePolicyEmployeeGroup">
                            <label class="form-label">Employee</label>
                            <select name="employee_id" class="form-select" id="attendancePolicyEmployeeSelect">
                                <option value="">Loading employees…</option>
                            </select>
                        </div>
                        <div class="mb-1">
                            <label class="form-label">Expected Hours per Day</label>
                            <input type="number" name="expected_hours_per_day" class="form-control" step="0.25" min="0" max="24" value="8" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitAttendancePolicyBtn">Save</button>
                </div>
            </div>
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
            .form-switch .form-check-input:focus { box-shadow: 0 0 0 .15rem rgba(241,139,5,.15); }
            .form-switch .form-check-input:checked { background-color: var(--clr-bg-primary, #f89616); }
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
      m = u.href.match(/@@(-?\d+(\.\d+)?),\s*(-?\d+(\.\d+)?)/);
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

    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const businessSlug = @json($business->slug);
        const fetchUrl = @json(route('business.attendances.policies.fetch', $business->slug));
        const storeUrl = @json(route('business.attendances.policies.store', $business->slug));
        const destroyUrlTemplate = @json(route('business.attendances.policies.destroy', ['business' => $business->slug, 'policy' => '__ID__']));
        const employeeOptionsUrl = @json(route('business.organogram.employee-options', $business->slug));

        async function postJson(url, data) {
            const resp = await fetch(url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(data),
            });
            const payload = await resp.json();
            if (!resp.ok) throw new Error(payload.message || 'Request failed.');
            return payload;
        }

        async function deleteRequest(url) {
            const resp = await fetch(url, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } });
            const payload = await resp.json();
            if (!resp.ok) throw new Error(payload.message || 'Request failed.');
            return payload;
        }

        async function loadPolicies() {
            const tbody = document.getElementById('attendancePoliciesTableBody');
            try {
                const resp = await fetch(fetchUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const policies = payload.data ?? [];

                if (!policies.length) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No overrides yet - everyone uses the default 8 hrs/day until you add one.</td></tr>';
                    return;
                }

                tbody.innerHTML = policies.map(p => `
                    <tr>
                        <td>${p.scope_label}</td>
                        <td>${p.expected_hours_per_day}</td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger delete-policy-btn" data-id="${p.id}">Delete</button></td>
                    </tr>
                `).join('');

                document.querySelectorAll('.delete-policy-btn').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        const { isConfirmed } = await Swal.fire({
                            title: 'Are you sure?',
                            text: 'Remove this override? Affected employees fall back to the next most specific rule.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Yes',
                        });
                        if (!isConfirmed) return;
                        try {
                            await deleteRequest(destroyUrlTemplate.replace('__ID__', this.dataset.id));
                            toastr.success('Override removed.');
                            loadPolicies();
                        } catch (e) { toastr.error(e.message); }
                    });
                });
            } catch (e) {
                console.error(e);
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Could not load policies.</td></tr>';
            }
        }

        document.getElementById('attendancePolicyScope').addEventListener('change', function () {
            document.getElementById('attendancePolicyDepartmentGroup').classList.toggle('d-none', this.value !== 'department');
            document.getElementById('attendancePolicyJobCategoryGroup').classList.toggle('d-none', this.value !== 'job_category');
            document.getElementById('attendancePolicyEmployeeGroup').classList.toggle('d-none', this.value !== 'employee');
        });

        document.getElementById('newAttendancePolicyModal').addEventListener('show.bs.modal', async function () {
            document.getElementById('attendancePolicyForm').reset();
            document.getElementById('attendancePolicyScope').dispatchEvent(new Event('change'));

            const select = document.getElementById('attendancePolicyEmployeeSelect');
            try {
                const resp = await fetch(employeeOptionsUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const employees = payload.data ?? [];
                select.innerHTML = '<option value="">Select employee</option>' + employees.map(e => `<option value="${e.id}">${e.name}</option>`).join('');
            } catch (e) {
                select.innerHTML = '<option value="">Could not load employees</option>';
            }
        });

        document.getElementById('submitAttendancePolicyBtn').addEventListener('click', async function () {
            const form = document.getElementById('attendancePolicyForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }

            const scope = document.getElementById('attendancePolicyScope').value;
            const data = Object.fromEntries(new FormData(form).entries());
            delete data.scope;
            if (scope !== 'department') delete data.department_id;
            if (scope !== 'job_category') delete data.job_category_id;
            if (scope !== 'employee') delete data.employee_id;

            try {
                await postJson(storeUrl, data);
                toastr.success('Override saved.');
                bootstrap.Modal.getInstance(document.getElementById('newAttendancePolicyModal')).hide();
                loadPolicies();
            } catch (e) {
                toastr.error(e.message || 'Could not save override.');
            }
        });

        loadPolicies();
    })();

    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const businessSlug = @json($business->slug);
        const fetchUrl = @json(route('business.attendances.devices.fetch', $business->slug));
        const storeUrl = @json(route('business.attendances.devices.store', $business->slug));
        const updateUrlTemplate = @json(route('business.attendances.devices.update', ['business' => $business->slug, 'device' => '__ID__']));
        const toggleUrlTemplate = @json(route('business.attendances.devices.toggle', ['business' => $business->slug, 'device' => '__ID__']));
        const regenUrlTemplate = @json(route('business.attendances.devices.regenerate-token', ['business' => $business->slug, 'device' => '__ID__']));
        const destroyUrlTemplate = @json(route('business.attendances.devices.destroy', ['business' => $business->slug, 'device' => '__ID__']));
        const enrollmentsFetchTemplate = @json(route('business.attendances.devices.enrollments.fetch', ['business' => $business->slug, 'device' => '__ID__']));
        const enrollmentsStoreTemplate = @json(route('business.attendances.devices.enrollments.store', ['business' => $business->slug, 'device' => '__ID__']));
        const enrollmentsDestroyTemplate = {!! json_encode(route('business.attendances.devices.enrollments.destroy', ['business' => $business->slug, 'device' => '__ID__', 'enrollment' => '__ENROLLMENT__'])) !!};
        const employeeOptionsUrl = @json(route('business.organogram.employee-options', $business->slug));

        async function postJson(url, data, method = 'POST') {
            const resp = await fetch(url, {
                method,
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(data),
            });
            const payload = await resp.json();
            if (!resp.ok) throw new Error(payload.message || 'Request failed.');
            return payload;
        }

        async function deleteRequest(url) {
            const resp = await fetch(url, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } });
            const payload = await resp.json();
            if (!resp.ok) throw new Error(payload.message || 'Request failed.');
            return payload;
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str ?? '';
            return div.innerHTML;
        }

        let currentEnrollmentsDeviceId = null;

        async function loadDevices() {
            const tbody = document.getElementById('devicesTableBody');
            try {
                const resp = await fetch(fetchUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const devices = payload.data ?? [];

                if (!devices.length) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No devices registered yet.</td></tr>';
                    return;
                }

                tbody.innerHTML = devices.map(d => `
                    <tr>
                        <td>${escapeHtml(d.name)}</td>
                        <td class="text-capitalize">${escapeHtml(d.vendor)}</td>
                        <td>${escapeHtml(d.location || '—')}</td>
                        <td>${d.enrollments_count}</td>
                        <td>${escapeHtml(d.last_seen_at || 'Never')}</td>
                        <td><span class="badge ${d.is_active ? 'bg-success' : 'bg-secondary'}">${d.is_active ? 'Active' : 'Inactive'}</span></td>
                        <td class="text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary device-url-btn" data-id="${d.id}" data-url="${escapeHtml(d.webhook_url)}" title="Webhook URL"><i class="bi bi-link-45deg"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary device-enroll-btn" data-id="${d.id}" data-name="${escapeHtml(d.name)}" title="PIN mapping"><i class="bi bi-people"></i></button>
                            <button type="button" class="btn btn-sm ${d.is_active ? 'btn-outline-warning' : 'btn-outline-success'} device-toggle-btn" data-id="${d.id}" title="${d.is_active ? 'Deactivate' : 'Activate'}"><i class="bi ${d.is_active ? 'bi-pause-circle' : 'bi-play-circle'}"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-danger device-delete-btn" data-id="${d.id}" title="Delete"><i class="bi bi-trash3"></i></button>
                        </td>
                    </tr>
                `).join('');

                tbody.querySelectorAll('.device-url-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        document.getElementById('deviceUrlValue').value = this.dataset.url;
                        document.getElementById('regenerateDeviceUrlBtn').dataset.id = this.dataset.id;
                        new bootstrap.Modal(document.getElementById('deviceUrlModal')).show();
                    });
                });

                tbody.querySelectorAll('.device-enroll-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        openEnrollmentsModal(this.dataset.id, this.dataset.name);
                    });
                });

                tbody.querySelectorAll('.device-toggle-btn').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        try {
                            await postJson(toggleUrlTemplate.replace('__ID__', this.dataset.id), {});
                            loadDevices();
                        } catch (e) { toastr.error(e.message); }
                    });
                });

                tbody.querySelectorAll('.device-delete-btn').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        const { isConfirmed } = await Swal.fire({
                            title: 'Remove this device?',
                            text: 'Its webhook URL will stop accepting punches immediately.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Yes, remove',
                        });
                        if (!isConfirmed) return;
                        try {
                            await deleteRequest(destroyUrlTemplate.replace('__ID__', this.dataset.id));
                            toastr.success('Device removed.');
                            loadDevices();
                        } catch (e) { toastr.error(e.message); }
                    });
                });
            } catch (e) {
                console.error(e);
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Could not load devices.</td></tr>';
            }
        }

        document.getElementById('newDeviceModal').addEventListener('show.bs.modal', function () {
            document.getElementById('deviceForm').reset();
            document.getElementById('deviceFormId').value = '';
            document.getElementById('deviceModalTitle').textContent = 'Register Device';
        });

        document.getElementById('submitDeviceBtn').addEventListener('click', async function () {
            const form = document.getElementById('deviceForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }

            const id = document.getElementById('deviceFormId').value;
            const data = {
                name: document.getElementById('deviceFormName').value,
                vendor: document.getElementById('deviceFormVendor').value,
                location_id: document.getElementById('deviceFormLocation').value || null,
                serial_number: document.getElementById('deviceFormSerial').value || null,
            };

            try {
                const url = id ? updateUrlTemplate.replace('__ID__', id) : storeUrl;
                const result = await postJson(url, data);
                bootstrap.Modal.getInstance(document.getElementById('newDeviceModal')).hide();
                loadDevices();
                if (!id && result.data?.webhook_url) {
                    document.getElementById('deviceUrlValue').value = result.data.webhook_url;
                    document.getElementById('regenerateDeviceUrlBtn').dataset.id = result.data.id;
                    new bootstrap.Modal(document.getElementById('deviceUrlModal')).show();
                } else {
                    toastr.success('Device saved.');
                }
            } catch (e) {
                toastr.error(e.message || 'Could not save device.');
            }
        });

        document.getElementById('copyDeviceUrlBtn').addEventListener('click', function () {
            const input = document.getElementById('deviceUrlValue');
            input.select();
            navigator.clipboard?.writeText(input.value);
            toastr.success('Copied.');
        });

        document.getElementById('regenerateDeviceUrlBtn').addEventListener('click', async function () {
            const { isConfirmed } = await Swal.fire({
                title: 'Rotate webhook URL?',
                text: 'The old URL will stop working - update it on the device right after.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, rotate',
            });
            if (!isConfirmed) return;
            try {
                const result = await postJson(regenUrlTemplate.replace('__ID__', this.dataset.id), {});
                document.getElementById('deviceUrlValue').value = result.data.webhook_url;
                toastr.success('URL rotated.');
                loadDevices();
            } catch (e) { toastr.error(e.message); }
        });

        async function openEnrollmentsModal(deviceId, deviceName) {
            currentEnrollmentsDeviceId = deviceId;
            document.getElementById('enrollmentsDeviceName').textContent = deviceName;
            document.getElementById('enrollmentForm').reset();

            const select = document.getElementById('enrollmentEmployee');
            select.innerHTML = '<option value="">Loading employees…</option>';
            try {
                const resp = await fetch(employeeOptionsUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const employees = payload.data ?? [];
                select.innerHTML = '<option value="">Select employee</option>' + employees.map(e => `<option value="${e.id}">${escapeHtml(e.name)}</option>`).join('');
            } catch (e) {
                select.innerHTML = '<option value="">Could not load employees</option>';
            }

            new bootstrap.Modal(document.getElementById('deviceEnrollmentsModal')).show();
            loadEnrollments();
        }

        async function loadEnrollments() {
            const tbody = document.getElementById('enrollmentsTableBody');
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Loading…</td></tr>';
            try {
                const resp = await fetch(enrollmentsFetchTemplate.replace('__ID__', currentEnrollmentsDeviceId), { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const enrollments = payload.data ?? [];

                if (!enrollments.length) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No PIN mappings yet - punches match by employee code automatically.</td></tr>';
                    return;
                }

                tbody.innerHTML = enrollments.map(e => `
                    <tr>
                        <td>${escapeHtml(e.device_pin)}</td>
                        <td>${escapeHtml(e.employee_name || '—')}</td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger enrollment-delete-btn" data-id="${e.id}"><i class="bi bi-trash3"></i></button></td>
                    </tr>
                `).join('');

                tbody.querySelectorAll('.enrollment-delete-btn').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        try {
                            await deleteRequest(enrollmentsDestroyTemplate.replace('__ID__', currentEnrollmentsDeviceId).replace('__ENROLLMENT__', this.dataset.id));
                            toastr.success('Mapping removed.');
                            loadEnrollments();
                        } catch (e) { toastr.error(e.message); }
                    });
                });
            } catch (e) {
                console.error(e);
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Could not load mappings.</td></tr>';
            }
        }

        document.getElementById('enrollmentForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const pin = document.getElementById('enrollmentPin').value.trim();
            const employeeId = document.getElementById('enrollmentEmployee').value;
            if (!pin || !employeeId) return;

            try {
                await postJson(enrollmentsStoreTemplate.replace('__ID__', currentEnrollmentsDeviceId), {
                    device_pin: pin,
                    employee_id: employeeId,
                });
                toastr.success('Mapped.');
                this.reset();
                loadEnrollments();
            } catch (e) { toastr.error(e.message || 'Could not save mapping.'); }
        });

        loadDevices();
    })();

        </script>
    @endpush
</x-app-layout>

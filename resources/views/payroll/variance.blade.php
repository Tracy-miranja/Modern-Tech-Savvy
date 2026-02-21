{{-- resources/views/payroll/variance.blade.php --}}
{{-- Route: GET /business/{business}/payroll/variance --}}

<x-app-layout title="Payroll Variance & AI Analysis">
<div class="container py-4">

    <div class="mb-4">
        <h1 class="h4 fw-bold mb-0">Payroll Variance & AI Analysis</h1>
        <p class="text-muted small">Compare two years or two months — no budget required</p>
    </div>

    {{-- Period selector card --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">

                {{-- Mode toggle --}}
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Compare Mode</label>
                    <select id="modeSelect" class="form-select form-select-sm">
                        <option value="year">Year vs Year</option>
                        <option value="month">Month vs Month</option>
                    </select>
                </div>

                {{-- Period 1 --}}
                <div class="col-md-auto" id="yearInputs1">
                    <label class="form-label fw-semibold small">Period 1</label>
                    <div class="d-flex gap-1">
                        <select id="year1" class="form-select form-select-sm" style="width:90px;">
                            @foreach($years as $y)
                                <option value="{{ $y }}" {{ $loop->index == 1 ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                        <select id="month1" class="form-select form-select-sm d-none" style="width:100px;">
                            @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $mn)
                                <option value="{{ $i+1 }}">{{ $mn }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-auto d-flex align-items-end pb-1">
                    <span class="text-muted fw-bold">vs</span>
                </div>

                {{-- Period 2 --}}
                <div class="col-md-auto">
                    <label class="form-label fw-semibold small">Period 2</label>
                    <div class="d-flex gap-1">
                        <select id="year2" class="form-select form-select-sm" style="width:90px;">
                            @foreach($years as $y)
                                <option value="{{ $y }}" {{ $loop->first ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                        <select id="month2" class="form-select form-select-sm d-none" style="width:100px;">
                            @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $mn)
                                <option value="{{ $i+1 }}" {{ $i+1 == 2 ? 'selected' : '' }}>{{ $mn }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="col-md-auto d-flex gap-2 align-items-end">
                    <button class="btn btn-primary btn-sm" id="loadBtn">
                        <i class="bi bi-bar-chart-line me-1"></i> Load Report
                    </button>
                    <a id="dlXlsx" href="#" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-file-earmark-excel me-1"></i> Excel
                    </a>
                    <a id="dlPdf" href="#" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                    </a>
                    <button class="btn btn-dark btn-sm" id="aiBtn" disabled>
                        <i class="bi bi-robot me-1"></i> AI Analysis
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary metrics cards --}}
    <div id="metricsRow" class="row g-3 mb-4" style="display:none!important;"></div>

    {{-- Charts --}}
    <div id="chartsRow" class="row g-3 mb-4" style="display:none!important;">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Gross Pay Comparison</h6>
                    <canvas id="mainChart" height="110"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Variance Breakdown</h6>
                    <canvas id="varChart" height="110"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- AI Output --}}
    <div id="aiCard" class="card shadow-sm mb-4 d-none">
        <div class="card-header bg-dark text-white d-flex align-items-center gap-2">
            <i class="bi bi-robot"></i>
            <span class="fw-semibold">Claude AI — Payroll Decision Report</span>
            <div id="aiSpinner" class="spinner-border spinner-border-sm ms-auto d-none" role="status"></div>
        </div>
        <div class="card-body" id="aiOutput"
             style="white-space:pre-wrap; font-size:0.88rem; line-height:1.75; min-height:120px;">
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const slug     = '{{ $business->slug }}';
const currency = '{{ $business->currency ?? "KES" }}';
let mainChart, varChart, cachedData = null;

// ── Toggle month selects based on mode ───────────────────────────────────────
document.getElementById('modeSelect').addEventListener('change', function() {
    const isMonth = this.value === 'month';
    ['month1','month2'].forEach(id => document.getElementById(id).classList.toggle('d-none', !isMonth));
    cachedData = null;
});

// ── Build URL params from current selections ─────────────────────────────────
function getParams() {
    const mode = document.getElementById('modeSelect').value;
    const p = { mode,
        year1: document.getElementById('year1').value,
        year2: document.getElementById('year2').value,
    };
    if (mode === 'month') {
        p.month1 = document.getElementById('month1').value;
        p.month2 = document.getElementById('month2').value;
    }
    return p;
}

function buildQuery(extra = {}) {
    return new URLSearchParams({...getParams(), ...extra}).toString();
}

// Update download links
function updateLinks() {
    document.getElementById('dlXlsx').href = `/business/${slug}/payroll/variance/download?${buildQuery({format:'xlsx'})}`;
    document.getElementById('dlPdf').href  = `/business/${slug}/payroll/variance/download?${buildQuery({format:'pdf'})}`;
}
updateLinks();
['modeSelect','year1','year2','month1','month2'].forEach(id =>
    document.getElementById(id).addEventListener('change', () => { cachedData=null; updateLinks(); })
);

// ── Load report ──────────────────────────────────────────────────────────────
document.getElementById('loadBtn').addEventListener('click', async function() {
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Loading…';

    try {
        const res  = await fetch(`/business/${slug}/payroll/variance/data?${buildQuery()}`);
        cachedData = await res.json();
        renderMetrics(cachedData);
        renderCharts(cachedData);
        document.getElementById('aiBtn').disabled = false;
    } catch(e) {
        alert('Failed to load data: ' + e.message);
    }

    this.disabled = false;
    this.innerHTML = '<i class="bi bi-bar-chart-line me-1"></i> Load Report';
});

// ── Render metric cards ──────────────────────────────────────────────────────
function renderMetrics(d) {
    const row = document.getElementById('metricsRow');
    row.innerHTML = '';
    row.style.display = 'flex';

    const p = d.params;
    const p1 = p.mode === 'year' ? p.year1 : `${monthName(p.month1)} ${p.year1}`;
    const p2 = p.mode === 'year' ? p.year2 : `${monthName(p.month2)} ${p.year2}`;

    d.data.summary.forEach(m => {
        const up  = m.variance > 0;
        const dn  = m.variance < 0;
        const cls = up ? 'text-danger' : dn ? 'text-success' : 'text-muted';
        const ico = up ? '▲' : dn ? '▼' : '—';
        const pct = Math.abs(m.variance_pct).toFixed(1);

        row.innerHTML += `
        <div class="col-md-2 col-sm-4 col-6">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body p-2">
                    <div class="small text-muted fw-semibold mb-1">${m.metric}</div>
                    <div class="fw-bold" style="font-size:.8rem">${fmt(m.period1)}</div>
                    <div style="font-size:.75rem;color:#888">→ ${fmt(m.period2)}</div>
                    <div class="${cls} fw-bold mt-1">${ico} ${pct}%</div>
                </div>
            </div>
        </div>`;
    });
}

// ── Render charts ────────────────────────────────────────────────────────────
function renderCharts(d) {
    document.getElementById('chartsRow').style.display = 'flex';

    const p  = d.params;
    const p1 = p.mode === 'year' ? p.year1 : `${monthName(p.month1)} ${p.year1}`;
    const p2 = p.mode === 'year' ? p.year2 : `${monthName(p.month2)} ${p.year2}`;

    // Main bar chart
    let labels, vals1, vals2;
    if (p.mode === 'year' && d.data.monthly.length) {
        const active = d.data.monthly.filter(r => r.period1 > 0 || r.period2 > 0);
        labels = active.map(r => r.month.substring(0,3));
        vals1  = active.map(r => r.period1);
        vals2  = active.map(r => r.period2);
    } else {
        labels = d.data.summary.map(r => r.metric);
        vals1  = d.data.summary.map(r => r.period1);
        vals2  = d.data.summary.map(r => r.period2);
    }

    if (mainChart) mainChart.destroy();
    mainChart = new Chart(document.getElementById('mainChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: String(p1), data: vals1, backgroundColor:'rgba(31,78,121,0.7)', borderColor:'#1F4E79', borderWidth:1 },
                { label: String(p2), data: vals2, backgroundColor:'rgba(189,215,238,0.85)', borderColor:'#2E86C1', borderWidth:1 },
            ]
        },
        options: { responsive:true,
                   plugins:{ legend:{ position:'top' } },
                   scales:{ y:{ ticks:{ callback: v => currency+' '+v.toLocaleString() } } } }
    });

    // Variance doughnut
    const variances = d.data.summary.map(r => Math.abs(r.variance));
    const labels2   = d.data.summary.map(r => r.metric);
    const colors    = ['#E74C3C','#2E86C1','#1E8449','#F39C12','#8E44AD','#1ABC9C'];

    if (varChart) varChart.destroy();
    varChart = new Chart(document.getElementById('varChart'), {
        type:'doughnut',
        data:{ labels: labels2, datasets:[{ data: variances, backgroundColor: colors, borderWidth:1 }] },
        options:{ responsive:true, plugins:{ legend:{ position:'bottom' } } }
    });
}

// ── AI Analysis ──────────────────────────────────────────────────────────────
document.getElementById('aiBtn').addEventListener('click', async function() {
    if (!cachedData) return;
    const d = cachedData;
    const p = d.params;
    const p1 = p.mode === 'year' ? p.year1 : `${monthName(p.month1)} ${p.year1}`;
    const p2 = p.mode === 'year' ? p.year2 : `${monthName(p.month2)} ${p.year2}`;

    document.getElementById('aiCard').classList.remove('d-none');
    document.getElementById('aiSpinner').classList.remove('d-none');
    document.getElementById('aiOutput').textContent = 'Analysing with Claude AI…';

    const summaryText = d.data.summary.map(m =>
        `  ${m.metric}: ${p1}=${fmt(m.period1)} | ${p2}=${fmt(m.period2)} | Variance=${fmt(m.variance)} (${m.variance_pct}%)`
    ).join('\n');

    const monthlyText = (d.data.monthly || [])
        .filter(m => m.period1 > 0 || m.period2 > 0)
        .map(m => `  ${m.month}: ${p1}=${fmt(m.period1)} | ${p2}=${fmt(m.period2)} | Variance=${fmt(m.variance)} (${m.var_pct}%) | Staff: ${m.count1}→${m.count2}`)
        .join('\n');

    const prompt = `You are a senior HR finance analyst. Analyse this payroll variance report for ${d.business}.

COMPARISON: ${p1} vs ${p2}
Currency: ${d.currency}

METRIC SUMMARY:
${summaryText}

${monthlyText ? 'MONTH-BY-MONTH GROSS PAY:\n' + monthlyText : ''}

Provide a structured decision report with these sections:

1. EXECUTIVE SUMMARY (2-3 sentences on the overall payroll trend)
2. KEY VARIANCES — which metrics changed most and why this matters
3. HEADCOUNT ANALYSIS — comment on staff count changes and their payroll impact
4. STATUTORY DEDUCTIONS — are PAYE, NSSF, SHIF, Housing Levy moving proportionally to gross pay? Any compliance concerns?
5. DECISIONS & RECOMMENDATIONS — at least 5 specific, numbered actions management should take now
6. RISK FLAGS — any figures that need immediate investigation

Be specific with numbers. Write for a finance director.`;

    try {
        const response = await fetch('https://api.anthropic.com/v1/messages', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                model: 'claude-sonnet-4-20250514',
                max_tokens: 1000,
                messages: [{ role: 'user', content: prompt }]
            })
        });

        const result = await response.json();
        const text   = result.content?.map(c => c.text || '').join('\n') || 'No response.';
        document.getElementById('aiOutput').textContent = text;
    } catch(e) {
        document.getElementById('aiOutput').textContent = '❌ Error: ' + e.message;
    }

    document.getElementById('aiSpinner').classList.add('d-none');
});

// ── Helpers ──────────────────────────────────────────────────────────────────
function fmt(v) {
    if (v === null || v === undefined) return '-';
    return currency + ' ' + parseFloat(v).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
}

function monthName(n) {
    return ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][parseInt(n)] || '';
}
</script>
@endpush
</x-app-layout>

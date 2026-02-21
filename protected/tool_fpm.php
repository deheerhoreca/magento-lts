<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;

require __DIR__."/../vendor/autoload.php";
require __DIR__."/_ip_check.php";

// avoid client cache
header("Cache-Control: no-cache, must-revalidate");
// Upload to private url or implement authorization...
if (isset($_GET["json"])) {
    header("Content-type: application/json");
    exit(json_encode( fpm_get_status() ));
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PHP-FPM Status Page</title>
    <meta name="ROBOTS" content="NOINDEX,NOFOLLOW,NOARCHIVE" />
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        html, body {
            width: 100vw;
            min-width: 100vw;
        }
        .table-responsive { margin-bottom: 2rem; }
        .fixed-width {
            width: 250px;
            word-break: break-all;
            white-space: normal;
        }
        .spinner-border { width: 2rem; height: 2rem; }
        .status-badge { font-size: 1rem; }
        .form-label, .btn, .status-badge, h1, h2, h3, h4, h5, h6, .fw-bold, .table, .table th, .table td {
            font-size: 1em !important;
        }
        .table th, .table td {
            font-size: 0.75em !important;
        }
    </style>
</head>
<body>
<div class="w-100 px-2 py-2" style="max-width:100vw;">
    <div class="d-flex align-items-center mb-4">
        <i class="fa-brands fa-php fa-2x me-2 text-primary"></i>
        <h1 class="mb-0">PHP-FPM Real-Time Status</h1>
    </div>
    <form class="row g-3 mb-3">
        <div class="col-md-6">
            <label for="url" class="form-label">Status URL</label>
            <input type="text" class="form-control" id="url" readonly>
        </div>
        <div class="col-md-2">
            <label for="rate" class="form-label">Refresh Rate (s)</label>
            <input type="number" class="form-control" id="rate" value="3" min="1">
        </div>
        <div class="col-md-4 d-flex align-items-end gap-2">
            <button type="button" class="btn btn-primary" id="refreshBtn"><i class="fa fa-rotate"></i> Manual Refresh</button>
            <button type="button" class="btn btn-secondary" id="playBtn"><i class="fa fa-play"></i> Play</button>
            <span id="status" class="ms-2 status-badge"></span>
        </div>
    </form>
    <div class="table-responsive">
        <h2>Pool Status</h2>
        <table class="table table-striped align-middle" id="short">
            <tbody></tbody>
        </table>
    </div>
    <div class="table-responsive">
        <h2>Active Processes Status</h2>
        <table class="table table-hover align-middle" id="active">
            <thead class="table-primary">
                <tr>
                    <th role="button" data-sort="0">PID <i class="fa fa-sort"></i></th>
                    <th role="button" data-sort="1">Start Time <i class="fa fa-sort"></i></th>
                    <th role="button" data-sort="2">Start Since <i class="fa fa-sort"></i></th>
                    <th role="button" data-sort="3">Requests Served <i class="fa fa-sort"></i></th>
                    <th role="button" data-sort="4">Request Duration <i class="fa fa-sort"></i></th>
                    <th role="button" data-sort="5">Request Method <i class="fa fa-sort"></i></th>
                    <th class="fixed-width" role="button" data-sort="6">Request URI <i class="fa fa-sort"></i></th>
                    <th class="fixed-width" role="button" data-sort="7">Query String <i class="fa fa-sort"></i></th>
                    <th role="button" data-sort="8">Request Length <i class="fa fa-sort"></i></th>
                    <th role="button" data-sort="9">User <i class="fa fa-sort"></i></th>
                    <th class="fixed-width" role="button" data-sort="10">Script <i class="fa fa-sort"></i></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <div class="table-responsive">
        <h2>Idle Processes Status</h2>
        <table class="table table-hover align-middle" id="idle">
            <thead class="table-secondary">
                <tr>
                    <th role="button" data-sort="0">PID <i class="fa fa-sort"></i></th>
                    <th role="button" data-sort="1">Start Time <i class="fa fa-sort"></i></th>
                    <th role="button" data-sort="2">Start Since <i class="fa fa-sort"></i></th>
                    <th role="button" data-sort="3">Requests Served <i class="fa fa-sort"></i></th>
                    <th role="button" data-sort="4">Request Duration <i class="fa fa-sort"></i></th>
                    <th role="button" data-sort="5">Request Method <i class="fa fa-sort"></i></th>
                    <th class="fixed-width" role="button" data-sort="6">Request URI <i class="fa fa-sort"></i></th>
                    <th class="fixed-width" role="button" data-sort="7">Query String <i class="fa fa-sort"></i></th>
                    <th role="button" data-sort="8">Request Length <i class="fa fa-sort"></i></th>
                    <th role="button" data-sort="9">User <i class="fa fa-sort"></i></th>
                    <th class="fixed-width" role="button" data-sort="10">Script <i class="fa fa-sort"></i></th>
                    <th role="button" data-sort="11">Last Request %CPU <i class="fa fa-sort"></i></th>
                    <th role="button" data-sort="12">Last Request Memory <i class="fa fa-sort"></i></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <footer class="mt-5 text-center text-muted">
        <small>&copy; 2025 Modernized by GitHub Copilot. Original by Jerome Loyet.</small>
    </footer>
</div>
<script>
    // Modern JS for status page
    const urlInput = document.getElementById('url');
    const rateInput = document.getElementById('rate');
    const statusSpan = document.getElementById('status');
    const playBtn = document.getElementById('playBtn');
    const refreshBtn = document.getElementById('refreshBtn');
    const shortTable = document.querySelector('#short tbody');
    const activeTable = document.querySelector('#active tbody');
    const idleTable = document.querySelector('#idle tbody');
    let play = false;
    let timer = null;
    let activeSort = { index: 0, reverse: false };
    let idleSort = { index: 0, reverse: false };

    urlInput.value = location.protocol + '//' + location.host + location.pathname + "?json";

    const setStatus = (msg, type = 'info') => {
        statusSpan.textContent = msg;
        statusSpan.className = `badge bg-${type} status-badge`;
    };

    const fetchStatus = async () => {
        setStatus('Loading...', 'secondary');
        try {
            const res = await fetch(urlInput.value);
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            setStatus('Loaded', 'success');
            renderStatus(data);
        } catch (e) {
            setStatus('Error: ' + e.message, 'danger');
        }
    };

    const renderStatus = (json) => {
        // Pool status
        shortTable.innerHTML = '';
        for (const key in json) {
            if (key === 'procs' || key === 'state') continue;
            let value = json[key];
            if (key === 'start-time') value = new Date(value * 1000).toLocaleString();
            if (key === 'start-since') value = time_s(value);
            shortTable.innerHTML += `<tr><td class="fw-bold">${key}</td><td>${value}</td></tr>`;
        }

        // Processes
        const procs = json.procs || [];
        renderProcessTable(procs, activeTable, false, activeSort);
        renderProcessTable(procs, idleTable, true, idleSort);
    };

    const renderProcessTable = (procs, table, idle, sort) => {
        let filtered = procs.filter(p => idle ? p.state === 'Idle' : p.state !== 'Idle');
        filtered = sortRows(filtered, sort.index, sort.reverse, idle);
        table.innerHTML = '';
        filtered.forEach(proc => {
            let row = '<tr>';
            row += `<td>${proc.pid}</td>`;
            row += `<td>${date(proc['start-time'] * 1000)}</td>`;
            row += `<td>${time_s(proc['start-since'])}</td>`;
            row += `<td>${proc.requests}</td>`;
            row += `<td>${time_u(proc['request-duration'])}</td>`;
            row += `<td>${proc['request-method']}</td>`;
            row += `<td class="fixed-width">${proc['request-uri']}</td>`;
            row += `<td class="fixed-width">${proc['query-string']}</td>`;
            row += `<td>${proc['request-length']}</td>`;
            row += `<td>${proc.user}</td>`;
            row += `<td class="fixed-width">${proc.script}</td>`;
            if (idle) {
                row += `<td>${cpu(proc['last-request-cpu'])}</td>`;
                row += `<td>${memory(proc['last-request-memory'])}</td>`;
            }
            row += '</tr>';
            table.innerHTML += row;
        });
    };

    // Sorting
    function updateSortIcons(tableId, sortObj) {
        const ths = document.querySelectorAll(`${tableId} thead th`);
        ths.forEach((th, i) => {
            const icon = th.querySelector('i');
            if (!icon) return;
            if (i === sortObj.index) {
                icon.className = sortObj.reverse ? 'fa fa-sort-up' : 'fa fa-sort-down';
            } else {
                icon.className = 'fa fa-sort';
            }
        });
    }

    document.querySelectorAll('#active thead th').forEach(th => {
        th.addEventListener('click', () => {
            const idx = parseInt(th.getAttribute('data-sort'));
            activeSort.reverse = activeSort.index === idx ? !activeSort.reverse : false;
            activeSort.index = idx;
            updateSortIcons('#active', activeSort);
            fetchStatus();
        });
    });
    document.querySelectorAll('#idle thead th').forEach(th => {
        th.addEventListener('click', () => {
            const idx = parseInt(th.getAttribute('data-sort'));
            idleSort.reverse = idleSort.index === idx ? !idleSort.reverse : false;
            idleSort.index = idx;
            updateSortIcons('#idle', idleSort);
            fetchStatus();
        });
    });

    // Set initial sort icons
    updateSortIcons('#active', activeSort);
    updateSortIcons('#idle', idleSort);

    // Manual refresh
    refreshBtn.addEventListener('click', () => {
        fetchStatus();
    });

    // Play/pause
    playBtn.addEventListener('click', () => {
        play = !play;
        playBtn.innerHTML = play ? '<i class="fa fa-pause"></i> Pause' : '<i class="fa fa-play"></i> Play';
        rateInput.disabled = play;
        if (play) {
            autoRefresh();
        } else {
            clearTimeout(timer);
        }
    });

    const autoRefresh = () => {
        fetchStatus();
        timer = setTimeout(autoRefresh, Math.max(1, parseInt(rateInput.value)) * 1000);
    };

    // Utility functions
    const date = d => {
        const t = new Date(d);
        return t.toLocaleString();
    };
    const cpu = c => c == 0 ? 0 : Math.round(c) + "%";
    const memory = mem => {
        if (mem == 0) return 0;
        if (mem < 1024) return mem + "B";
        if (mem < 1024 * 1024) return (mem/1024).toFixed(1) + "KB";
        if (mem < 1024*1024*1024) return (mem/1024/1024).toFixed(1) + "MB";
        return (mem/1024/1024/1024).toFixed(2) + "GB";
    };
    const time_s = t => {
        if (t < 60) return t + 's';
        let r = (t % 60) + 's';
        t = Math.floor(t / 60);
        if (t < 60) return t + 'm ' + r;
        r = (t % 60) + 'm ' + r;
        t = Math.floor(t/60);
        if (t < 24) return t + 'h ' + r;
        return Math.floor(t/24) + 'd ' + (t % 24) + 'h ' + t;
    };
    const time_u = t => {
        if (t < 1000) return t + '&micro;s';
        t = Math.floor(t / 1000);
        if (t < 1000) return t + 'ms';
        return time_s(Math.floor(t/1000)) + ' ' + (t%1000) + 'ms';
    };
    const sortRows = (rows, idx, reverse, idle) => {
        return rows.slice().sort((a, b) => {
            let va = getSortValue(a, idx, idle);
            let vb = getSortValue(b, idx, idle);
            if (typeof va === 'number' && typeof vb === 'number') {
                return reverse ? vb - va : va - vb;
            }
            va = String(va);
            vb = String(vb);
            return reverse ? vb.localeCompare(va) : va.localeCompare(vb);
        });
    };
    const getSortValue = (proc, idx, idle) => {
        const keys = [
            'pid', 'start-time', 'start-since', 'requests', 'request-duration',
            'request-method', 'request-uri', 'query-string', 'request-length', 'user', 'script',
            'last-request-cpu', 'last-request-memory'
        ];
        return proc[keys[idx]] ?? '';
    };

    // Initial load
    fetchStatus();
</script>
</body>
</html>

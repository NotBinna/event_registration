@extends('layouts.user_type.auth')

@section('content')
<main class="main-content position-relative max-height-vh-100 h-100 mt-1 border-radius-lg ">
  <div class="container py-4">
    <div class="row">
      <div class="col-lg-8 mx-auto" id="scan-content" style="display:none;">
        <div class="card">
          <div class="card-header pb-0">
            <h5 class="mb-0">Scan Tiket QR Code</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label for="cameraSelect" class="form-label">Pilih Kamera</label>
              <select id="cameraSelect" class="form-select"></select>
            </div>
            <div id="qr-reader" style="width:100%"></div>
            <div id="scan-result" class="mt-4"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

@push('dashboard')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const user = JSON.parse(localStorage.getItem('user'));
    if (!user || String(user.role_id) !== "4") {
        var modal = new bootstrap.Modal(document.getElementById('aksesDitolakModal'));
        modal.show();
        document.getElementById('btn-akses-ditolak-ok').onclick = function() {
            window.location.href = '/dashboard';
        };
        document.body.style.overflow = 'hidden';
        throw new Error('Akses ditolak');
    }
    document.getElementById('scan-content').style.display = '';
});
</script>
<!-- Tambahkan library html5-qrcode -->
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
  
let html5QrCode;
let cameras = [];
let selectedCameraId = null;

function showResult(message, isError = false) {
  document.getElementById('scan-result').innerHTML = `
    <div class="alert ${isError ? 'alert-danger' : 'alert-success'}" 
         style="font-size:1.1em; line-height:1.5;${isError ? 'color:#fff;font-weight:bold;' : ''}">
      ${message}
      ${isError ? '<br><button class="btn btn-light mt-2" onclick="restartScan()"><b>SCAN</b></button>' : ''}
    </div>
  `;
}

function renderTicketDetail(ticket) {
  return `
    <div class="card mt-3">
      <div class="card-body">
        <h6 class="mb-2">Nama Participant: <b>${ticket.participant_name}</b></h6>
        <div>Nama Event: <b>${ticket.event_name}</b></div>
        <div>Tanggal Event: <b>${ticket.event_date}</b></div>
        <div>Jam Event: <b>${ticket.event_time}</b></div>
      </div>
    </div>
  `;
}

function scanSuccess(decodedText, decodedResult) {
  // Cegah scan QR yang sama berulang-ulang
  if (window.lastScanned === decodedText) return;
  window.lastScanned = decodedText;

  // Proses hasil scan
  fetch('http://localhost:3000/api/scan-ticket', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: 'Bearer ' + localStorage.getItem('token')
    },
    body: JSON.stringify({ qr_code: decodedText })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      showResult('Tiket valid dan berhasil di-scan!' + renderTicketDetail(data.ticket));
    } else {
      showResult((data.error || 'Tiket tidak valid atau sudah pernah di-scan!'), true);
    }
    // Reset lastScanned setelah beberapa detik agar bisa scan QR yang sama lagi jika perlu
    setTimeout(() => { window.lastScanned = null; }, 2000);
  })
  .catch(() => showResult('Terjadi kesalahan saat verifikasi tiket.', true));
}

function startScanner(cameraId) {
  selectedCameraId = cameraId;
  document.getElementById('scan-result').innerHTML = '';
  document.getElementById('qr-reader').innerHTML = '';
  if (html5QrCode) {
    try { html5QrCode.clear(); } catch (e) {}
    try { html5QrCode.stop(); } catch (e) {}
    html5QrCode = null;
  }
  html5QrCode = new Html5Qrcode("qr-reader");
  html5QrCode.start(
    cameraId,
    { fps: 10, qrbox: 250 },
    scanSuccess,
    errorMessage => {}
  );
}

function restartScan() {
  document.getElementById('scan-result').innerHTML = '';
  document.getElementById('qr-reader').innerHTML = '';
  if (html5QrCode) {
    try { html5QrCode.clear(); } catch (e) {}
    try { html5QrCode.stop(); } catch (e) {}
    html5QrCode = null;
  }
  html5QrCode = new Html5Qrcode("qr-reader");
  html5QrCode.start(
    selectedCameraId,
    { fps: 10, qrbox: 250 },
    scanSuccess,
    errorMessage => {}
  );
}

function stopScanner() {
  if (html5QrCode && html5QrCode._isScanning) {
    html5QrCode.stop().catch(() => {});
  }
}

window.onload = function() {
  Html5Qrcode.getCameras().then(devices => {
    cameras = devices;
    const select = document.getElementById('cameraSelect');
    select.innerHTML = '';
    devices.forEach(device => {
      const option = document.createElement('option');
      option.value = device.id;
      option.text = device.label || `Camera ${select.length+1}`;
      select.appendChild(option);
    });
    if (devices.length > 0) {
      startScanner(devices[0].id);
      select.value = devices[0].id;
    }
    select.onchange = function() {
    if (html5QrCode && html5QrCode._isScanning) {
      html5QrCode.stop().then(() => {
        startScanner(this.value);
      }).catch(() => {
        startScanner(this.value);
      });
    } else {
      startScanner(this.value);
    }
  };
  });
};
</script>
@endpush
@endsection
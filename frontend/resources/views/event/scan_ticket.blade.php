@extends('layouts.user_type.auth')

@section('content')
<main class="main-content position-relative max-height-vh-100 h-100 mt-1 border-radius-lg ">
  <div class="container py-4">
    <div class="row">
      <div class="col-lg-8 mx-auto">
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

function restartScan() {
  document.getElementById('scan-result').innerHTML = '';
  startScanner(selectedCameraId);
}

function scanSuccess(decodedText, decodedResult) {
  // Matikan scanner sementara
  html5QrCode.stop().then(() => {
    // Kirim ke backend untuk verifikasi dan update scanned_at, scanned_by
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
        showResult('Tiket valid dan berhasil di-scan!' + renderTicketDetail(data.ticket) + '<br><button class="btn btn-primary mt-2" onclick="restartScan()">SCAN</button>');
      } else {
        showResult((data.error || 'Tiket tidak valid atau sudah pernah di-scan!'), true);
      }
    })
    .catch(() => showResult('Terjadi kesalahan saat verifikasi tiket.', true));
  });
}

function startScanner(cameraId) {
  selectedCameraId = cameraId;
  html5QrCode = new Html5Qrcode("qr-reader");
  html5QrCode.start(
    cameraId,
    { fps: 10, qrbox: 250 },
    scanSuccess,
    errorMessage => {}
  );
}

function stopScanner() {
  if (html5QrCode) html5QrCode.stop();
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
      stopScanner();
      startScanner(this.value);
    };
  });
};
</script>
@endpush
@endsection
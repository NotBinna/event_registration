@extends('layouts.user_type.auth')

@section('content')

<main class="main-content position-relative max-height-vh-100 h-100 mt-1 border-radius-lg ">
  <div class="container-fluid py-4">
    <div class="row">
      <div class="col-12">
        <div class="card mb-4">
          <div class="card-header pb-0">
            <h5 class="mb-0">My Events</h5>
          </div>
          <div class="card-body px-0 pt-0 pb-2">
            <div class="table-responsive p-0">
              <table class="table align-items-center mb-0">
                <thead>
                  <tr>
                    <th>Nama Event</th>
                    <th>Tanggal</th>
                    <th>Lokasi</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody id="events-table-body">
                  <!-- Akan diisi oleh JavaScript -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Modal Detail Event -->
    <div class="modal fade" id="eventDetailModal" tabindex="-1" aria-labelledby="eventDetailLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="eventDetailLabel">Detail Event</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" id="event-detail-body">
            <!-- Akan diisi JS -->
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

@push('dashboard')
<script>
function showEventDetail(event) {
  let statusHtml = '';
  if (!event.payment_id) {
    statusHtml = '<span class="badge badge-sm bg-gradient-warning">Waiting for Payment</span>';
  } else if (event.payment_status === 'pending') {
    statusHtml = '<span class="badge badge-sm bg-gradient-info">Pending Verification</span>';
  } else if (event.payment_status === 'verified') {
    statusHtml = '<span class="badge badge-sm bg-gradient-success">Payment Verified</span>';
  } else if (event.payment_status === 'rejected') {
    statusHtml = '<span class="badge badge-sm bg-gradient-danger">Payment Rejected</span>';
  } else {
    statusHtml = '<span class="badge badge-sm bg-gradient-secondary">Inactive</span>';
  }

  // QR code section (jika payment verified)
  let qrHtml = '';
  if (event.payment_status === 'verified' && event.tickets && event.tickets.length > 0) {
    qrHtml = '<hr><b>QR Code Tiket:</b><br>';
    event.tickets.forEach((ticket, idx) => {
      qrHtml += `
        <div class="mb-2">
          <div><b>Tiket #${idx + 1}</b></div>
          <img src="${ticket.qr_code}" alt="QR Code" style="width:120px;height:120px;">
          <div>
            <span class="text-secondary" style="font-size: 0.95em;">${ticket.participant_name}</span>
            ${
              ticket.scanned_at
                ? '<span class="badge bg-gradient-success ms-2">Sudah Scan</span>'
                : '<span class="badge bg-gradient-secondary ms-2">Belum Scan</span>'
            }
          </div>
        </div>
      `;
    });
  }

  document.getElementById('event-detail-body').innerHTML = `
    <img src="http://localhost:3000${event.poster || '/uploads/default-poster.jpg'}" class="img-fluid rounded mb-3" alt="poster">
    <h5>${event.name}</h5>
    <p>${event.description}</p>
    <ul class="list-unstyled mb-0">
      <li><b>Tanggal:</b> ${event.date}</li>
      <li><b>Lokasi:</b> ${event.location}</li>
      <li><b>Jumlah Tiket:</b> ${event.total_tickets}</li>
      <li><b>Status:</b> ${statusHtml}</li>
    </ul>
    ${qrHtml}
    ${
      !event.payment_id
        ? `<div class="mt-3">
            <button class="btn btn-success" onclick="window.location.href='{{ route('payment.page') }}?registration_id=${event.id}'">Bayar</button>
           </div>`
        : ''
    }
  `;
  var modal = new bootstrap.Modal(document.getElementById('eventDetailModal'));
  modal.show();
}

function showPaymentModal(registrationId) {
  // Ambil detail registrasi jika perlu, atau tampilkan info langsung
  const event = window.lastEvents.find(ev => ev.id === registrationId);
  document.getElementById('payment-modal-body').innerHTML = `
    <p>Silakan lakukan pembayaran untuk event <b>${event.name}</b>.</p>
    <p>Jumlah tiket: <b>${event.total_tickets}</b></p>
    <p>Total bayar: <b>Rp ...</b></p>
  `;
  document.getElementById('btn-bayar').onclick = function() {
    window.location.href = '/payment-page?registration_id=' + registrationId;
  };
  var modal = new bootstrap.Modal(document.getElementById('paymentModal'));
  modal.show();
}

function renderEvents(events) {
  window.lastEvents = events;
  let html = '';
  events.forEach(event => {
    let statusHtml = '';
    if (!event.payment_id) {
      statusHtml = '<span class="badge badge-sm bg-gradient-warning">Waiting for Payment</span>';
    } else if (event.payment_status === 'pending') {
      statusHtml = '<span class="badge badge-sm bg-gradient-info">Pending Verification</span>';
    } else if (event.payment_status === 'verified') {
      statusHtml = '<span class="badge badge-sm bg-gradient-success">Payment Verified</span>';
    } else if (event.payment_status === 'rejected') {
      statusHtml = '<span class="badge badge-sm bg-gradient-danger">Payment Rejected</span>';
    } else {
      statusHtml = '<span class="badge badge-sm bg-gradient-secondary">Inactive</span>';
    }

    html += `
      <tr class="event-row" style="cursor:pointer">
        <td>
          <div class="d-flex px-2 py-1">
            <div>
              <img src="http://localhost:3000${event.poster || '/uploads/default-poster.jpg'}" class="avatar avatar-sm me-3" alt="poster">
            </div>
            <div class="d-flex flex-column justify-content-center">
              <h6 class="mb-0 text-sm">${event.name}</h6>
              <p class="text-xs text-secondary mb-0">${event.description ? event.description.substring(0, 40) + '...' : ''}</p>
            </div>
          </div>
        </td>
        <td><span class="text-xs font-weight-bold">${event.date}</span></td>
        <td><span class="text-xs font-weight-bold">${event.location}</span></td>
        <td>${statusHtml}</td>
      </tr>
    `;
  });
  document.getElementById('events-table-body').innerHTML = html;

  // Tambahkan event click ke setiap baris
  document.querySelectorAll('.event-row').forEach((row, idx) => {
    row.onclick = function() {
      showEventDetail(events[idx]);
    };
  });
}

// Load events yang sudah dipesan user
function loadEvents() {
  const user = JSON.parse(localStorage.getItem('user'));
  fetch(`http://localhost:3000/api/my-events`, {
    headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
  })
  .then(res => res.json())
  .then(data => {
    if (!data || !data.events) return;
    renderEvents(data.events);
  });
}

window.onload = loadEvents;
</script>
@endpush

@endsection
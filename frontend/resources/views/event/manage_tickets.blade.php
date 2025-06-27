@extends('layouts.user_type.auth')

@section('content')
<main class="main-content position-relative max-height-vh-100 h-100 mt-1 border-radius-lg ">
  <div class="container-fluid py-4">
    <div class="row" id="not-for-you" style="display:none;">
      <div class="col-12">
        <div class="card mb-4">
          <div class="card-header pb-0">
            <h5 class="mb-0">Manajemen Tiket & Sertifikat</h5>
          </div>
          <div class="card-body px-0 pt-0 pb-2">
            <div class="table-responsive p-0">
              <table class="table align-items-center mb-0">
                <thead>
                  <tr>
                    <th>Nama Event</th>
                    <th>Nama Peserta</th>
                    <th>Status Scan</th>
                    <th>Sertifikat</th>
                    <th>Upload Sertifikat</th>
                  </tr>
                </thead>
                <tbody id="tickets-table-body">
                  <!-- Akan diisi oleh JavaScript -->
                </tbody>
              </table>
            </div>
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
    document.getElementById('not-for-you').style.display = '';
});
</script>
<script>
// Ganti URL sesuai endpoint backend Anda
function loadTickets() {
  fetch('http://localhost:3000/api/all-tickets', {
    headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
  })
  .then(res => res.json())
  .then(data => {
    if (!data || !data.tickets) return;
    renderTickets(data.tickets);
  });
}

function renderTickets(tickets) {
  let html = '';
  tickets.forEach(ticket => {
    html += `
      <tr>
        <td>${ticket.event_name}</td>
        <td>${ticket.participant_name}</td>
        <td>
          ${
            ticket.scanned_at
              ? '<span class="badge bg-gradient-success">Sudah Scan</span>'
              : '<span class="badge bg-gradient-secondary">Belum Scan</span>'
          }
        </td>
        <td>
          ${
            ticket.certificate_path
              ? `<a href="http://localhost:3000/${ticket.certificate_path}" target="_blank" class="btn btn-sm btn-info">Lihat</a>`
              : '<span class="text-secondary">Belum ada</span>'
          }
        </td>
        <td>
          <form onsubmit="return uploadCertificate(event, ${ticket.id})" enctype="multipart/form-data">
            <input type="file" name="certificate" accept="application/pdf,image/*" required>
            <button type="submit" class="btn btn-sm btn-primary mt-1">Upload</button>
          </form>
        </td>
      </tr>
    `;
  });
  document.getElementById('tickets-table-body').innerHTML = html;
}

function uploadCertificate(e, ticketId) {
  e.preventDefault();
  const form = e.target;
  const fileInput = form.querySelector('input[type="file"]');
  const file = fileInput.files[0];
  if (!file) return false;

  const formData = new FormData();
  formData.append('certificate', file);

  fetch(`http://localhost:3000/api/tickets/${ticketId}/certificate`, {
    method: 'POST',
    headers: { Authorization: 'Bearer ' + localStorage.getItem('token') },
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert('Sertifikat berhasil di-upload!');
      loadTickets();
    } else {
      alert(data.error || 'Gagal upload sertifikat');
    }
  });
  return false;
}

window.onload = loadTickets;
</script>
@endpush

@endsection
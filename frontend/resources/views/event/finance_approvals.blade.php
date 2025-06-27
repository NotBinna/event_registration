@extends('layouts.user_type.auth')

@section('content')
<main class="main-content position-relative max-height-vh-100 h-100 mt-1 border-radius-lg ">
  <div class="container-fluid py-4" id="not-for-you" style="display:none;">
    <div class="row">
      <div class="col-12">
        <div class="card mb-4">
          <div class="card-header pb-0">
            <h5 class="mb-0">Approve Pembayaran (Finance)</h5>
          </div>
          <div class="card-body px-0 pt-0 pb-2">
            <div class="table-responsive p-0">
              <table class="table align-items-center mb-0">
                <thead>
                  <tr>
                    <th>User ID</th>
                    <th>User Name</th>
                    <th>Tanggal Beli</th>
                    <th>Banyak Tiket</th>
                    <th>Status</th>
                    <th>Bukti Pembayaran</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody id="approvals-table-body">
                  <!-- Akan diisi oleh JavaScript -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Modal Bukti Pembayaran -->
    <div class="modal fade" id="proofModal" tabindex="-1" aria-labelledby="proofModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="proofModalLabel">Bukti Pembayaran</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center">
            <img id="proof-image" src="" alt="Bukti Pembayaran" class="img-fluid rounded">
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
    if (!user || String(user.role_id) !== "3") {
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
function showProofModal(imgUrl) {
  document.getElementById('proof-image').src = imgUrl;
  var modal = new bootstrap.Modal(document.getElementById('proofModal'));
  modal.show();
}
function renderApprovals(events) {
  let html = '';
  events.forEach(event => {
    let statusHtml = '';
    if (event.payment_status === 'pending') {
      statusHtml = '<span class="badge badge-sm bg-gradient-info">Pending Verification</span>';
    } else if (event.payment_status === 'verified') {
      statusHtml = '<span class="badge badge-sm bg-gradient-success">Payment Verified</span>';
    } else if (event.payment_status === 'rejected') {
      statusHtml = '<span class="badge badge-sm bg-gradient-danger">Payment Rejected</span>';
    } else {
      statusHtml = '<span class="badge badge-sm bg-gradient-secondary">Inactive</span>';
    }

    html += `
      <tr>
        <td>${event.users_id}</td>
        <td>${event.user_name}</td>
        <td>${new Date(event.registered_at).toLocaleString()}</td>
        <td>${event.total_tickets}</td>
        <td>${statusHtml}</td>
        <td>
          <button class="btn btn-info btn-sm" onclick="showProofModal('http://localhost:3000${event.proof_path}')">Lihat Bukti</button>
        </td>
        <td>
          <button class="btn btn-success btn-sm" onclick="approvePayment(${event.payment_id}, 'verified')">Approve</button>
          <button class="btn btn-danger btn-sm" onclick="approvePayment(${event.payment_id}, 'rejected')">Reject</button>
        </td>
      </tr>
    `;
  });
  document.getElementById('approvals-table-body').innerHTML = html;
}

function loadApprovals() {
  fetch('http://localhost:3000/api/finance/approvals', {
    headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
  })
  .then(res => res.json())
  .then(data => {
    if (!data || !data.events) return;
    renderApprovals(data.events);
  });
}

function approvePayment(paymentId, status) {
  if (!confirm('Yakin?')) return;
  fetch(`http://localhost:3000/api/finance/approve`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: 'Bearer ' + localStorage.getItem('token')
    },
    body: JSON.stringify({ payment_id: paymentId, status: status })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      loadApprovals();
    } else {
      alert(data.error || 'Gagal update status');
    }
  });
}

window.onload = loadApprovals;
</script>
@endpush
@endsection
@extends('layouts.user_type.auth')

@section('content')
<main class="main-content position-relative max-height-vh-100 h-100 mt-1 border-radius-lg">
  <div class="container-fluid py-4">
    <div class="row">
      <div class="col-md-6">
        <div id="event-detail-card"></div>
      </div>
      <div class="col-md-6">
        <div class="card mb-4">
          <div class="card-header pb-0">
            <h5 class="mb-0">Upload Bukti Pembayaran</h5>
          </div>
          <div class="card-body">
            <form id="payment-form" enctype="multipart/form-data">
              <div class="form-group mb-3">
                <label for="proof" class="form-control-label">Bukti Pembayaran (gambar)</label>
                <input type="file" class="form-control" id="proof" name="proof" accept="image/*" required>
              </div>
              <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn bg-gradient-success">
                  <i class="fas fa-upload me-2"></i>Upload & Submit
                </button>
              </div>
            </form>
            <div id="upload-status" class="mt-3"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

@push('dashboard')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  const registrationId = urlParams.get('registration_id');

  // Ambil detail event dari registration
  fetch(`http://localhost:3000/api/registration/${registrationId}`, {
    headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
  })
  .then(res => res.json())
  .then(data => {
    if (!data || !data.event) return;
    const event = data.event;
    document.getElementById('event-detail-card').innerHTML = `
      <div class="card">
        <div class="card-body">
          <div class="row">
            <div class="col-md-4 d-flex align-items-center justify-content-center">
              <img src="http://localhost:3000${event.poster || '/uploads/default-poster.jpg'}" class="img-fluid rounded" style="max-height:250px;" alt="poster">
            </div>
            <div class="col-md-8">
              <h3 class="mb-2"><b>${event.name}</b></h3>
              <p>${event.description}</p>
              <ul class="list-unstyled mb-0">
                <li><i class="fa fa-calendar text-primary me-2"></i> ${new Date(event.date).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</li>
                <li><i class="fa fa-clock text-primary me-2"></i> 10:00 WIB</li>
                <li><i class="fa fa-map-marker-alt text-primary me-2"></i> ${event.location}</li>
                <li><i class="fa fa-money-bill text-primary me-2"></i> Rp ${event.price ? event.price.toLocaleString('id-ID') : '-'}</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    `;
  });

  // Handle submit form pembayaran
  const paymentForm = document.getElementById('payment-form');
  if (paymentForm) {
    paymentForm.onsubmit = function(e) {
      e.preventDefault();
      const formData = new FormData();
      formData.append('registration_id', registrationId);
      formData.append('proof', document.getElementById('proof').files[0]);

      fetch('http://localhost:3000/api/payments', {
        method: 'POST',
        headers: { Authorization: 'Bearer ' + localStorage.getItem('token') },
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          document.getElementById('upload-status').innerHTML = '<span class="text-success">Pembayaran berhasil diupload! Menunggu verifikasi.</span>';
          setTimeout(() => window.location.href = '/my-events', 2000);
        } else {
          document.getElementById('upload-status').innerHTML = '<span class="text-danger">' + (data.error || 'Gagal upload pembayaran') + '</span>';
        }
      });
    };
  }
});
</script>
@endpush
@endsection
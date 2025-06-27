@extends('layouts.user_type.auth')

@section('content')
<div id="user-info" class="mb-4"></div>


<div class="row mt-4">
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header pb-0">
        <h6>Latest Events</h6>
      </div>
      <div class="card-body px-0 pt-0 pb-2">
        <div class="row px-3" id="dashboard-events">
          <!-- Events will be rendered here -->
        </div>
      </div>
    </div>
  </div>
@endsection

@push('dashboard')
<script>
  function loadDashboardEvents() {
  fetch('http://localhost:3000/api/events', {
    headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
  })
  .then(response => response.json())
  .then(data => {
    if (!data || !data.events) return;
    
    // Filter only active events
    const activeEvents = data.events.filter(event => event.is_active == 1);
    // Get 2 latest active events
    const latestEvents = activeEvents.slice(0, 2);
    let html = '';
    
    latestEvents.forEach(event => {
      html += `
      <div class="col-lg-6 mb-lg-0 mb-4">
        <div class="card" style="cursor: pointer; height: 300px;" onclick="window.location.href='/event/index'">
          <div class="card-body p-3">
            <div class="row h-100">
              <div class="col-lg-7">
                <div class="d-flex flex-column h-100">
                  <h5 class="font-weight-bolder">${event.name}</h5>
                  <p class="mb-5">${event.description ? event.description.substring(0, 100) + '...' : ''}</p>
                  <div class="mt-auto">
                    <div class="d-flex align-items-center">
                      <i class="fas fa-map-marker-alt text-primary me-2"></i>
                      <span class="text-sm">${event.location}</span>
                    </div>
                    <div class="d-flex align-items-center mt-2">
                      <i class="fas fa-user text-primary me-2"></i>
                      <span class="text-sm">${event.speaker}</span>
                    </div>
                    <a class="text-body text-sm font-weight-bold mb-0 icon-move-right mt-2">
                      View All Events
                      <i class="fas fa-arrow-right text-sm ms-1" aria-hidden="true"></i>
                    </a>
                  </div>
                </div>
              </div>
              <div class="col-lg-5 ms-auto text-center mt-lg-0 d-flex align-items-center">
                <div class="bg-gradient-primary border-radius-lg w-100" style="height: 250px;">
                  <img src="../assets/img/shapes/waves-white.svg" class="position-absolute h-100 w-50 top-0 d-lg-block d-none" alt="waves">
                  <div class="position-relative d-flex align-items-center justify-content-center h-100">
                    <img class="w-75 position-relative z-index-2" 
                         src="http://localhost:3000${event.poster || '/uploads/default-poster.jpg'}" 
                         alt="poster"
                         style="object-fit: contain; max-height: 200px;">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
    });
    
    document.getElementById('dashboard-events').innerHTML = html;
  });
}

window.onload = function() {
  const token = localStorage.getItem('token');
  if (!token) {
    window.location.href = '/login';
    return;
  }

  loadDashboardEvents();

  const user = JSON.parse(localStorage.getItem('user') || '{}');
  const userInfo = document.getElementById('user-info');
  if (userInfo && user.name) {
    userInfo.innerHTML = `<h4>Selamat datang, ${user.name}!</h4>`;
  }
}
</script>
@endpush
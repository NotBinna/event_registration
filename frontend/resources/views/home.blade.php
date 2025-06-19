@extends('layouts.user_type.guest')

@section('content')

<main class="main-content mt-0">
  <section>
    <div class="page-header min-vh-75">
      <div class="container">
        <div class="row">
          <div class="col-xl-4 col-lg-5 col-md-6 d-flex flex-column mx-auto">
            <div class="card card-plain mt-8">
              
              <div class="card-header pb-0 text-left bg-transparent">
                <h3 class="font-weight-bolder text-info text-gradient">Welcome back</h3>
                <p class="mb-0">Sign in to your account</p>
              </div>
              <div class="card-body">
                <form id="login-form">
                  @csrf
                  <label>Email</label>
                  <div class="mb-3">
                    <input type="email" class="form-control" name="email" id="email" placeholder="Email" required>
                  </div>
                  <label>Password</label>
                  <div class="mb-3">
                    <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                  </div>
                  <div class="text-center">
                    <button type="submit" class="btn bg-gradient-info w-100 mt-4 mb-0">Sign in</button>
                  </div>
                </form>
              </div>
              <div class="card-footer text-center pt-0 px-lg-2 px-1">
                <small class="text-muted">Forgot your password?
                  <a href="/login/forgot-password" class="text-info text-gradient font-weight-bold">Reset here</a>
                </small>
                <p class="mb-4 text-sm mx-auto">
                  Don't have an account?
                  <a href="/register" class="text-info text-gradient font-weight-bold">Sign up</a>
                </p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="oblique position-absolute top-0 h-100 d-md-block d-none me-n8">
              <div class="oblique-image bg-cover position-absolute fixed-top ms-auto h-100 z-index-0 ms-n6"
                style="background-image:url('../assets/img/curved-images/curved6.jpg')">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <section class="py-7">
            <div class="container">
              <div class="row">
                <div class="col-12">
                  <div class="card">
                    <div class="card-header pb-0">
                      <h6>Upcoming Events</h6>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                      <div class="row px-3" id="home-events">
                        <!-- Events will be rendered here -->
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>
  </section>
</main>

<script>
  function loadHomeEvents() {
  fetch('http://localhost:3000/api/public/events')
  .then(response => response.json())
  .then(data => {
    if (!data || !data.events) return;
    
    // Get 2 latest events (they're already active only from backend)
    const latestEvents = data.events.slice(0, 10);
    let html = '';
    
    latestEvents.forEach(event => {
      html += `
      <div class="col-lg-6 mb-lg-3 mb-4">
        <div class="card" style="cursor: pointer; height: 300px;" onclick="window.location.href='/login'">
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
                      Login to View Details
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
    
    document.getElementById('home-events').innerHTML = html;
  })
  .catch(error => console.error('Error loading events:', error));
}

// Call loadHomeEvents when page loads
window.onload = function() {
  loadHomeEvents();
};



// -------------------------------------------------------------------------------------
  document.getElementById('login-form').addEventListener('submit', async function (e) {
    e.preventDefault();

    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    try {
      const response = await fetch('http://localhost:3000/api/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password }),
      });

      const data = await response.json();

      if (response.ok && data.token) {
        // Simpan token ke localStorage
        localStorage.setItem('token', data.token);
        // Simpan user info jika ada
        if (data.user) {
          localStorage.setItem('user', JSON.stringify(data.user));
        }
        // Redirect ke dashboard
        window.location.href = '/dashboard';
      } else {
        alert(data.error || data.message || 'Login gagal, cek email dan password!');
      }
    } catch (error) {
      console.error('Login error:', error);
      alert('Terjadi kesalahan saat login');
    }
  });
</script>

@endsection

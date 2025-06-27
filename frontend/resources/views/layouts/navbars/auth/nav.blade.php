<nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur" navbar-scroll="true">
    <div class="container-fluid py-1 px-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
            <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
            <li class="breadcrumb-item text-sm text-dark active text-capitalize" aria-current="page">{{ str_replace('-', ' ', Request::path()) }}</li>
            </ol>
            <h6 class="font-weight-bolder mb-0 text-capitalize">{{ str_replace('-', ' ', Request::path()) }}</h6>
        </nav>
        <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4 d-flex justify-content-end" id="navbar">
            <ul class="navbar-nav  justify-content-end">
                <li class="nav-item dropdown d-flex align-items-center">
                    <a href="#" class="nav-link dropdown-toggle text-body font-weight-bold px-0" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa fa-user me-sm-1"></i>
                        <span id="user-name" class="d-sm-inline d-none"></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li>
                            <a class="dropdown-item" href="javascript:;" id="logout-btn">
                                <i class="fa fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<!-- End Navbar -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tampilkan nama user
    const user = JSON.parse(localStorage.getItem('user'));
    if (user && user.name) {
        document.getElementById('user-name').textContent = user.name;
    } else {
        document.getElementById('user-name').textContent = 'Profile';
    }

    // Logout
    document.getElementById('logout-btn').onclick = function() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '/';
    };
});
</script>
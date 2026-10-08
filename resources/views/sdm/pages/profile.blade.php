@php
    use Illuminate\Support\Facades\DB;

    $data_profile = DB::table('users')->where('username', auth()->user()->username)->first();
@endphp

<!--breadcrumb-->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Profile</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('sdm') }}"><i class="bx bx-home-alt"></i></a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Profile</li>
            </ol>
        </nav>
    </div>
</div>
<!--end breadcrumb-->
<div class="container">
    <div class="main-body">
        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-column align-items-center text-center">
                            <img src="{{ asset('assets/images/team.png') }}" alt="Admin" class="rounded-circle p-1 bg-warning" width="110">
                            <div class="mt-3">
                                <h4>{{ ucwords($data_profile->nama) }}</h4>
                                <p class="text-secondary mb-1"><b>{{ $data_profile->username }}</b></p>
                                <p class="text-muted font-size-sm">{{ strtoupper($data_profile->role) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <form action="" method="post" enctype="multipart/form-data">
                            @csrf
                            <!-- Password tersimpan (tidak ditampilkan) -->
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Status Password</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <input type="text" value="******** (Tersimpan dengan aman)" class="form-control" disabled>
                                </div>
                            </div>

                            <!-- Password Baru -->
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Password Baru</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <div class="input-group" id="show_hide_password1">
                                        <input type="password" name="password_baru" class="form-control border-end-0" id="password_baru" placeholder="Masukkan password baru" minlength="5" required>
                                        <a href="javascript:;" class="input-group-text bg-transparent toggle-password" data-target="password_baru">
                                            <i class='bx bx-hide'></i>
                                        </a>
                                    </div>
                                    <small class="text-muted">Minimal 5 karakter</small>
                                </div>
                            </div>

                            <!-- Konfirmasi Password Baru -->
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Konfirmasi Password</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <div class="input-group" id="show_hide_password2">
                                        <input type="password" name="konfirmasi_password" class="form-control border-end-0" id="konfirmasi_password" placeholder="Ketik ulang password baru" required>
                                        <a href="javascript:;" class="input-group-text bg-transparent toggle-password" data-target="konfirmasi_password">
                                            <i class='bx bx-hide'></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-3"></div>
                                <div class="col-sm-9 text-secondary">
                                    <input type="submit" name="submit" class="btn btn-primary px-4" value="Ubah Password" />
                                    <button type="reset" class="btn btn-secondary px-4">Reset</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Style untuk validasi password */
input:invalid {
    border-color: #dc3545;
}
input:valid {
    border-color: #28a745;
}
</style>

@section('scripts')
<script>
$(document).ready(function() {
    // Toggle password visibility
    $(".toggle-password").on('click', function(event) {
        event.preventDefault();
        var targetId = $(this).data('target');
        var input = $('#' + targetId);
        var icon = $(this).find('i');

        if (input.attr("type") == "text") {
            input.attr('type', 'password');
            icon.removeClass("bx-show").addClass("bx-hide");
        } else {
            input.attr('type', 'text');
            icon.removeClass("bx-hide").addClass("bx-show");
        }
    });

    // Auto-hide alert after 3 seconds
    window.setTimeout(function() {
        $(".alert").fadeTo(1000, 0).slideUp(1000, function() {
            $(this).remove();
        });
    }, 3000);
});
</script>
@endsection

@php
    $data = $data ?? null;
    $id = $data->id ?? 0;
@endphp

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Users</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('sdm') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item"><a href="{{ url('sdm/users') }}">Manajemen Users</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit User</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-xl-6 mx-auto">
        <div class="card border-top border-0 border-4 border-warning">
            <div class="card-body">
                <div class="border p-4 rounded">
                    <div class="card-title d-flex align-items-center gap-2">
                        <i class="bx bxs-edit font-22"></i>
                        <h5 class="mb-0">Edit User</h5>
                    </div>
                    <hr />
                    <form action="{{ url('sdm/users/edit/' . $id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">NID</label>
                            <input type="text" name="nid" class="form-control"
                                   value="{{ $data->username }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control"
                                   value="{{ $data->nama }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password Baru
                                <small class="text-muted">(kosongkan jika tidak diubah)</small>
                            </label>
                            <div class="input-group" id="show_hide_password">
                                <input type="password" name="password" class="form-control border-end-0" placeholder="Password baru">
                                <a href="javascript:;" class="input-group-text bg-transparent toggle-pass"
                                   data-target="#show_hide_password input"><i class="bx bx-hide"></i></a>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="unit"  {{ $data->role === 'unit'  ? 'selected' : '' }}>Unit</option>
                                <option value="sdm" {{ $data->role === 'sdm' ? 'selected' : '' }}>SDM</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" name="submit_edit" class="btn btn-warning px-4">
                                <i class="bx bx-save me-1"></i>Update
                            </button>
                            <a href="{{ url('sdm/users') }}" class="btn btn-secondary px-4">
                                <i class="bx bx-arrow-back me-1"></i>Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
document.querySelectorAll('.toggle-pass').forEach(function(btn) {
    btn.addEventListener('click', function () {
        var target = document.querySelector(this.getAttribute('data-target'));
        var icon   = this.querySelector('i');
        if (target.type === 'password') {
            target.type = 'text';
            icon.classList.replace('bx-hide', 'bx-show');
        } else {
            target.type = 'password';
            icon.classList.replace('bx-show', 'bx-hide');
        }
    });
});
</script>
@endsection

@php
    use Illuminate\Support\Facades\DB;

    $users = DB::table('users')->orderBy('created_at', 'desc')->get();
    $no = 1;
@endphp

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Users</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('sdm') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Manajemen Users</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">

    <!-- FORM TAMBAH -->
    <div class="col-xl-4">
        <div class="card border-top border-0 border-4 border-success">
            <div class="card-body">
                <div class="border p-4 rounded">
                    <div class="card-title d-flex align-items-center gap-2">
                        <i class="bx bxs-user-plus font-22"></i>
                        <h5 class="mb-0">Tambah User</h5>
                    </div>
                    <hr />
                    <form action="{{ url('sdm/users') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">NID</label>
                            <input type="text" name="nid" class="form-control" placeholder="Masukkan NID" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group" id="show_hide_password">
                                <input type="password" name="password" class="form-control border-end-0" placeholder="Password" required>
                                <a href="javascript:;" class="input-group-text bg-transparent toggle-pass" data-target="#show_hide_password input"><i class="bx bx-hide"></i></a>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="unit">Unit</option>
                                <option value="sdm">SDM</option>
                                <option value="manager">Manager</option>
                            </select>
                        </div>
                        <button type="submit" name="submit_add" class="btn btn-success px-5">
                            <i class="bx bx-user-plus me-1"></i>Daftarkan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- LIST DATA -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="bx bxs-group me-1"></i> Daftar Users</h5>
                    <span class="badge bg-primary">{{ $users->count() }} User</span>
                </div>
                <div class="ui-toolbar">
                    <div class="ui-search">
                        <i class="bx bx-search"></i>
                        <input type="text" class="form-control form-control-sm" placeholder="Cari NID / nama / role..."
                               data-ui-filter="#tabelUsers" aria-label="Cari user">
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" data-ui-filter-apply="#tabelUsers">
                        <i class="bx bx-search"></i> Cari
                    </button>
                </div>
                <div class="table-responsive ui-table-wrap">
                    <table class="table table-striped table-hover align-middle mb-0 ui-table" id="tabelUsers">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>NID</th>
                                <th>Nama</th>
                                <th>Role</th>
                                <th>Dibuat</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($users as $row)
                            <tr>
                                <td>{{ $no++ }}</td>
                                <td>{{ $row->username }}</td>
                                <td>{{ $row->nama }}</td>
                                <td>
                                    <span class="badge {{ $row->role === 'sdm' ? 'bg-danger' : ($row->role === 'manager' ? 'bg-warning text-dark' : 'bg-info') }}">
                                        {{ ucfirst($row->role) }}
                                    </span>
                                </td>
                                <td>{{ date('d-m-Y H:i', strtotime($row->created_at)) }}</td>
                                <td class="text-center">
                                    <div class="ui-actions">
                                    <a href="{{ url('sdm/users/edit/' . $row->id) }}"
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bx bx-edit-alt"></i>
                                    </a>
                                    <a href="{{ url('sdm/users/delete/' . $row->id) }}"
                                       class="btn btn-sm btn-danger" title="Hapus"
                                       data-ui-confirm="Yakin ingin menghapus user {{ $row->nama }}?"
                                       data-confirm-title="Hapus User?"
                                       data-confirm-ok="Hapus">
                                       <i class="bx bx-trash"></i>
                                    </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        @if ($users->count() == 0)
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data user.</td></tr>
                        @endif
                        </tbody>
                    </table>
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

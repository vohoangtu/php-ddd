@extends('layouts.admin')

@section('title', 'User Management')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Users</h5>
        <a href="/admin/users/create" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Add New User
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td>{{ $user->getId() }}</td>
                        <td>{{ $user->getName() }}</td>
                        <td>{{ $user->getEmail() }}</td>
                        <td>
                            <span class="badge bg-{{ $user->getRole() === 'admin' ? 'danger' : 'primary' }}">
                                {{ ucfirst($user->getRole()) }}
                            </span>
                        </td>
                        <td>{{ date('M d, Y', strtotime($user->getCreatedAt())) }}</td>
                        <td>
                            <div class="btn-group">
                                <a href="/admin/users/{{ $user->getId() }}/edit" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if($user->getId() !== auth()->getId())
                                <button type="button" 
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="confirmDelete({{ $user->getId() }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display: none;">
</form>

@endsection

@section('scripts')
<script>
function confirmDelete(userId) {
    if (confirm('Are you sure you want to delete this user?')) {
        const form = document.getElementById('deleteForm');
        form.action = `/admin/users/${userId}/delete`;
        form.submit();
    }
}
</script>
@endsection 
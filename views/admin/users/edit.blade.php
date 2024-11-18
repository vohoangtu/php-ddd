@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
<div class="card">
    <div class="card-body">
        <form action="/admin/users/{{ $user->getId() }}" method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" 
                       class="form-control" 
                       id="name" 
                       name="name" 
                       value="{{ $user->getName() }}" 
                       required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" 
                       class="form-control" 
                       id="email" 
                       name="email" 
                       value="{{ $user->getEmail() }}" 
                       required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" 
                       class="form-control" 
                       id="password" 
                       name="password" 
                       placeholder="Leave empty to keep current password">
                <div class="form-text">
                    Only fill this if you want to change the password
                </div>
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select class="form-select" 
                        id="role" 
                        name="role" 
                        {{ $user->getId() === auth()->getId() ? 'disabled' : '' }}>
                    <option value="user" {{ $user->getRole() === 'user' ? 'selected' : '' }}>
                        User
                    </option>
                    <option value="admin" {{ $user->getRole() === 'admin' ? 'selected' : '' }}>
                        Admin
                    </option>
                </select>
                @if($user->getId() === auth()->getId())
                    <div class="form-text">
                        You cannot change your own role
                    </div>
                @endif
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="/admin/users" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>
@endsection 
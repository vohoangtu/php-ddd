@extends('layouts.admin')

@section('title', 'Add New User')

@section('content')
<div class="card">
    <div class="card-body">
        <form action="/admin/users" method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" 
                       class="form-control" 
                       id="name" 
                       name="name" 
                       required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" 
                       class="form-control" 
                       id="email" 
                       name="email" 
                       required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" 
                       class="form-control" 
                       id="password" 
                       name="password" 
                       required>
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select class="form-select" id="role" name="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="/admin/users" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>
@endsection 
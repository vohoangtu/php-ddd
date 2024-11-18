@extends('layouts.admin')

@section('title', 'Add Category')

@section('content')
<div class="card">
    <div class="card-body">
        <form action="/admin/categories" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="name" class="form-label">Category Name</label>
                <input type="text" 
                       class="form-control" 
                       id="name" 
                       name="name" 
                       required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" 
                          id="description" 
                          name="description" 
                          rows="3"></textarea>
            </div>

            <div class="mb-3">
                <label for="parent_id" class="form-label">Parent Category</label>
                <select class="form-select" id="parent_id" name="parent_id">
                    <option value="">None</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->getId() }}">
                            {{ $category->getName() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="image" class="form-label">Category Image</label>
                <input type="file" 
                       class="form-control" 
                       id="image" 
                       name="image" 
                       accept="image/*">
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" 
                           type="checkbox" 
                           id="is_active" 
                           name="is_active" 
                           checked>
                    <label class="form-check-label" for="is_active">
                        Active
                    </label>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="/admin/categories" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Category</button>
            </div>
        </form>
    </div>
</div>
@endsection 
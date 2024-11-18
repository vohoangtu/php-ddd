<div class="category-tree" style="margin-left: {{ $level * 20 }}px">
    @foreach($categories as $item)
    <div class="category-item border-bottom py-2">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                @if($item['category']->getImage())
                    <img src="{{ $item['category']->getImage() }}" 
                         alt="{{ $item['category']->getName() }}" 
                         width="30" 
                         class="me-2">
                @endif
                <span class="{{ !$item['category']->isActive() ? 'text-muted' : '' }}">
                    {{ $item['category']->getName() }}
                </span>
            </div>
            <div class="btn-group">
                <a href="/admin/categories/{{ $item['category']->getId() }}/edit" 
                   class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil"></i>
                </a>
                <button type="button" 
                        class="btn btn-sm btn-outline-danger"
                        onclick="confirmDelete({{ $item['category']->getId() }})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </div>
    @if(!empty($item['children']))
        @include('admin.categories.partials.category-tree', [
            'categories' => $item['children'],
            'level' => $level + 1
        ])
    @endif
    @endforeach
</div> 
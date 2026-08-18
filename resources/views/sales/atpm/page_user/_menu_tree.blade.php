{{--
    Recursive menu tree partial.
    Variables expected:
        $nodes         - Collection of menu items at this level
        $menusByParent - All menus grouped by parent_id  (passed down every call)
        $userMenuIds   - Array of menu IDs the user already has (passed down every call)
        $depth         - Current depth, starts at 0 (used for styling)
--}}


@foreach ($nodes as $node)
    @php
        $hasChildren = isset($menusByParent[$node->id]);
        $isChecked   = in_array($node->id, $userMenuIds);

        // Styling berdasarkan depth
        $bgColors = ['#f8f9fa', '#eef0f2', '#e8ebee'];
        $bg       = $bgColors[min($depth, count($bgColors) - 1)];
        $fw       = $depth === 0 ? 'fw-bold' : ($depth === 1 ? 'fw-semibold' : '');
    @endphp

    <div class="menu-node mb-1" style="padding: 6px 10px; border-radius: 4px; background-color: {{ $bg }};">
        <div class="form-check">
            <input
                class="form-check-input menu-checkbox"
                type="checkbox"
                name="menu_ids[]"
                value="{{ $node->id }}"
                id="menu_{{ $node->id }}"
                data-id="{{ $node->id }}"
                data-parent="{{ $node->parent_id ?? '' }}"
                {{ $isChecked ? 'checked' : '' }}
            >
            <label class="form-check-label {{ $fw }}" for="menu_{{ $node->id }}">
                @if($node->icon) <i class="{{ $node->icon }}"></i> @endif
                {{ $node->title }}
            </label>
        </div>

        @if($hasChildren)
            <div style="margin-left: 24px; margin-top: 4px; padding-left: 12px; border-left: 2px solid #dee2e6;">
                @include('sales.atpm.page_user._menu_tree', [
                    'nodes'         => $menusByParent[$node->id],
                    'menusByParent' => $menusByParent,
                    'userMenuIds'   => $userMenuIds,
                    'depth'         => $depth + 1,
                ])
            </div>
        @endif
    </div>
@endforeach

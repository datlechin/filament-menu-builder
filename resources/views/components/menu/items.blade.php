@php
    use Illuminate\View\ComponentAttributeBag;

    $htmlAttributes = fn (array $attributes): ComponentAttributeBag => new ComponentAttributeBag(
        array_filter($attributes, fn (mixed $value): bool => $value !== null && $value !== false),
    );
@endphp

<ul>
    @foreach ($nodes as $node)
        <li {{ $htmlAttributes([
            'class' => $node->classes,
            'data-depth' => $node->depth,
            'data-active' => $node->isActive() ? '' : null,
            'data-active-trail' => $node->isActiveTrail() ? '' : null,
        ]) }}>
            @if (filled($node->url))
                <a {{ $htmlAttributes([
                    'href' => $node->url,
                    'target' => $node->target,
                    'rel' => $node->rel,
                    'aria-current' => $node->isActive() ? 'page' : null,
                ]) }}>{{ $node->title }}</a>
            @else
                <span>{{ $node->title }}</span>
            @endif

            @if ($node->hasChildren())
                @include('filament-menu-builder::components.menu.items', ['nodes' => $node->children])
            @endif
        </li>
    @endforeach
</ul>

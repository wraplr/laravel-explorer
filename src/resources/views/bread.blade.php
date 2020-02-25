@foreach ($breadcrumbDirs as $index => $directory)
    @if ($index == 0)
        <li class="breadcrumb-item">
            <a href="javascript:;" data-id="{{ $directory->id }}">
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAuUlEQVRIS2NkoDFgpLH5DPSzIKH9twMjA9N8BgYGBQp99eA/w7/EBZWsB0DmwH2Q2P73AQMDgzyFhsO0P5hfyayIbsF/kMD8SmaKgi2x/S+KOcg+GLUAHP6jQUQwFY+AICIYBkQqgGVYjIxGpH6CyrBZ8IGBgYGfoE7iFDycX8kMLjThPoCWpguoUOA9/M/wLwGjNCXGYehJkBg9JJWcNLEAZigu1xIq3gn6gOYWEBPO+NQQ9AGlFgAAv/R6GSeuz3UAAAAASUVORK5CYII="/>
            </a>
        </li>
    @elseif ($index < count($breadcrumbDirs) - 1)
        <li class="breadcrumb-item"><a href="javascript:;" data-id="{{ $directory->id }}">{{ $directory->name }}</a></li>
    @else
        <li class="breadcrumb-item">{{ $directory->name }}</li>
    @endif
@endforeach

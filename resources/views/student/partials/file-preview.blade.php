@php
    $kind = $file ? $file->kind() : 'none';
    $thumb = $file ? $file->thumbnailUrl() : null;
@endphp
<div class="relative h-[150px] w-full shrink-0 overflow-hidden rounded-2xl sm:w-[210px]">
    @if($kind === 'pdf')
        <svg viewBox="0 0 210 150" preserveAspectRatio="xMidYMid slice" role="img" aria-label="PDF file preview" class="h-full w-full">
            <rect width="210" height="150" fill="#FFF1F0"/>
            <rect x="64" y="16" width="82" height="118" rx="7" fill="#fff" stroke="#F3C4BF" stroke-width="1.5"/>
            <path d="M126 16l20 20h-14a6 6 0 0 1-6-6z" fill="#FBE0DD"/>
            <rect x="74" y="44" width="46" height="5" rx="2.5" fill="#E8D2D0"/>
            <rect x="74" y="56" width="62" height="5" rx="2.5" fill="#EFDEDC"/>
            <rect x="74" y="68" width="54" height="5" rx="2.5" fill="#EFDEDC"/>
            <rect x="74" y="92" width="44" height="22" rx="5" fill="#DC2626"/>
            <text x="96" y="108" text-anchor="middle" font-family="Plus Jakarta Sans,sans-serif" font-size="12" font-weight="800" fill="#fff">PDF</text>
        </svg>
    @elseif($kind === 'image')
        <svg viewBox="0 0 210 150" preserveAspectRatio="xMidYMid slice" role="img" aria-label="Image file preview" class="h-full w-full">
            <rect width="210" height="150" fill="#E3F2FF"/>
            <rect x="30" y="22" width="150" height="106" rx="10" fill="#fff"/>
            <rect x="38" y="30" width="134" height="90" rx="6" fill="#BFE3FF"/>
            <circle cx="68" cy="56" r="11" fill="#FDBA3B"/>
            <path d="M38 120V96l30-26 24 22 22-30 58 38v20z" fill="#34D399"/>
            <path d="M96 120l28-34 48 34z" fill="#059669"/>
            <rect x="130" y="34" width="38" height="18" rx="5" fill="#0F1B4D" opacity=".85"/>
            <text x="149" y="47" text-anchor="middle" font-family="Plus Jakarta Sans,sans-serif" font-size="10" font-weight="800" fill="#fff">{{ strtoupper($file->extension() ?: 'IMG') }}</text>
        </svg>
    @elseif($kind === 'none')
        <svg viewBox="0 0 210 150" preserveAspectRatio="xMidYMid slice" role="img" aria-label="No file attached yet" class="h-full w-full">
            <rect width="210" height="150" fill="#F5F7FF"/>
            <rect x="4" y="4" width="202" height="142" rx="14" fill="none" stroke="#C3CCEA" stroke-width="2" stroke-dasharray="6 6"/>
            <path d="M58 56a6 6 0 0 1 6-6h28l8 10h44a6 6 0 0 1 6 6v38a6 6 0 0 1-6 6H64a6 6 0 0 1-6-6z" fill="#C7D4FF"/>
            <path d="M58 70h96v28a6 6 0 0 1-6 6H64a6 6 0 0 1-6-6z" fill="#9DB4FF"/>
            <circle cx="145" cy="48" r="14" fill="#2F5BFF"/>
            <path d="M145 41v14M138 48h14" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
            <path d="M52 34l2.2 5.8L60 42l-5.8 2.2L52 50l-2.2-5.8L44 42l5.8-2.2z" fill="#FDBA3B"/>
            <circle cx="170" cy="92" r="3" fill="#9DB4FF"/>
            <circle cx="40" cy="96" r="2.5" fill="#C7D4FF"/>
            <text x="105" y="128" text-anchor="middle" font-family="Plus Jakarta Sans,sans-serif" font-size="12" font-weight="700" fill="#5B6478">No attachment yet</text>
        </svg>
    @else
        <svg viewBox="0 0 210 150" preserveAspectRatio="xMidYMid slice" role="img" aria-label="Document preview" class="h-full w-full">
            <rect width="210" height="150" fill="#EAF0FF"/>
            <rect x="64" y="16" width="82" height="118" rx="7" fill="#fff" stroke="#C3D2FB" stroke-width="1.5"/>
            <path d="M126 16l20 20h-14a6 6 0 0 1-6-6z" fill="#DCE6FF"/>
            <rect x="74" y="44" width="46" height="5" rx="2.5" fill="#CFDAF7"/>
            <rect x="74" y="56" width="62" height="5" rx="2.5" fill="#DCE5FA"/>
            <rect x="74" y="68" width="54" height="5" rx="2.5" fill="#DCE5FA"/>
            <rect x="74" y="80" width="60" height="5" rx="2.5" fill="#DCE5FA"/>
            <rect x="74" y="98" width="44" height="22" rx="5" fill="#2F5BFF"/>
            <text x="96" y="114" text-anchor="middle" font-family="Plus Jakarta Sans,sans-serif" font-size="12" font-weight="800" fill="#fff">{{ $kind === 'doc' ? 'DOC' : 'FILE' }}</text>
        </svg>
    @endif

    @if($thumb)
        <img src="{{ $thumb }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
    @endif
</div>
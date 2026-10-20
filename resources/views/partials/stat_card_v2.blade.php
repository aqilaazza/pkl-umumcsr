{{--
    ============================================================================
    Stat Cards V2 — kartu statistik modern (soft card, badge pastel).
    ----------------------------------------------------------------------------
    Cara pakai:
      @include('partials.stat_card_v2', [
        'value' => number_format($x, 0, ',', '.'),
        'label' => 'Disetujui',
        'icon' => 'bx bx-check-circle',
        'color' => 'success',
        'counterKey' => 'disetujui',   // opsional: integrasi AJAX counter
        'sub' => 'Total Alumni',       // opsional
        'colClass' => 'col-6 col-md-3' // opsional
      ])

    Props:
      $value       (wajib) angka/nilai utama (sudah diformat)
      $label       (wajib) label kategori
      $icon        (opsional) kelas BoxIcons
      $color       (opsional) primary|success|warning|danger|info|secondary|dark
      $counterKey  (opsional) bila diisi -> data-counter="..." pada elemen angka
      $sub         (opsional) sub-teks kecil
      $colClass    (opsional) grid wrapper
      $href        (opsional) bila diisi kartu menjadi tautan
    ============================================================================
--}}
@php
    $value      = $value ?? 0;
    $label      = $label ?? '';
    $color      = $color ?? 'primary';
    $icon       = $icon ?? 'bx bx-bar-chart';
    $counterKey = $counterKey ?? null;
    $sub        = $sub ?? null;
    $colClass   = $colClass ?? 'col-6 col-md-3';
    $href       = $href ?? null;
@endphp

<div class="{{ $colClass }}">
    <{{ $href ? 'a' : 'div' }} class="ui-stat-card" data-stat-color="{{ $color }}"@if($href) href="{{ $href }}"@endif>
        <div class="ui-stat-card__head">
            <span class="ui-stat-card__badge"><i class="{{ $icon }}"></i></span>
            <button type="button" class="ui-stat-card__menu" aria-label="Opsi"><i class="bx bx-dots-vertical-rounded"></i></button>
        </div>
        <div class="ui-stat-card__body">
            <div class="ui-stat-card__meta">
                <div class="ui-stat-card__value"@if($counterKey) data-counter="{{ $counterKey }}"@endif>{{ $value }}</div>
                <div class="ui-stat-card__label">{{ $label }}</div>
                @if(!is_null($sub))
                    <div class="ui-stat-card__sub" title="{{ $sub }}">{{ $sub }}</div>
                @endif
            </div>
        </div>
    </{{ $href ? 'a' : 'div' }}>
</div>

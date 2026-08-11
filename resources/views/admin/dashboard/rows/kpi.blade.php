{{-- แถว 1 — การ์ด KPI 4 ใบ · ตกบรรทัดเองด้วย auto-fit ไม่มี breakpoint ตายตัว --}}
<div class="dbo-kpis">
  @foreach ($data['kpis'] as $kpi)
    <div class="dbo-card dbo-kpi">
      <div class="dbo-kpi-head">
        <div class="dbo-kpi-text">
          <span class="dbo-kpi-label">{{ $kpi['label'] }}</span>
          <div class="dbo-kpi-value">
            <span class="dbo-num">{{ $num($kpi['value']) }}</span>
            <span class="dbo-kpi-unit">{{ $kpi['unit'] }}</span>
          </div>
        </div>
        <span class="dbo-kpi-icon">
          @include('admin.dashboard.icon', ['name' => $kpi['icon']])
        </span>
      </div>
      <div class="dbo-kpi-foot">
        <span class="dbo-delta" data-tone="{{ $kpi['delta_tone'] ?? 'flat' }}">{{ $kpi['delta'] }}</span>
        @if ($kpi['delta_note'] !== '')
          <span class="dbo-delta-note">{{ $kpi['delta_note'] }}</span>
        @endif
      </div>
    </div>
  @endforeach
</div>

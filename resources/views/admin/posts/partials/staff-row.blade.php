@php
  $row = $row ?? [];
  $photo = $row['photo'] ?? '';
  $photoUrl = $photo === '' ? '' : (str_starts_with($photo, 'http') ? $photo : asset(ltrim($photo, '/')));
@endphp
<div class="staff-row" data-staff-row>
  <input type="hidden" name="event_staff[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
  <input type="hidden" name="event_staff[{{ $i }}][photo]" value="{{ $photo }}" data-staff-photo>
  <div class="staff-row-grid">
    <div>
      <label>از کادر اصلی (اختیاری)</label>
      <select name="event_staff[{{ $i }}][crew_member_id]" data-staff-roster>
        <option value="">عضو جدید — دستی بنویس</option>
        @foreach($crewMembers as $member)
          <option value="{{ $member->id }}"
                  data-name="{{ $member->name }}"
                  data-photo="{{ $member->photo }}"
                  @selected((string)($row['crew_member_id'] ?? '') === (string)$member->id)>
            {{ $member->name }}@if($member->former) (سابق)@endif
          </option>
        @endforeach
      </select>
    </div>
    <div>
      <label>نام</label>
      <input type="text" name="event_staff[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" placeholder="نام و نام خانوادگی" data-staff-name>
    </div>
    <div>
      <label>سمت در این رویداد</label>
      <input type="text" name="event_staff[{{ $i }}][role]" value="{{ $row['role'] ?? '' }}" placeholder="مثلا: هماهنگی، استریم، پذیرایی">
    </div>
    <div>
      <label>عکس</label>
      <div style="display:flex;gap:.4rem;align-items:center">
        <span data-staff-prev @if($photoUrl==='') hidden @endif>
          @if($photoUrl!=='')
            <img src="{{ $photoUrl }}" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:8px">
          @endif
        </span>
        <label class="btn light" style="cursor:pointer;padding:.3rem .55rem;font-size:.8rem">
          انتخاب
          <input type="file" name="event_staff[{{ $i }}][file]" accept="image/*" hidden data-staff-file>
        </label>
      </div>
    </div>
    <div style="display:flex;align-items:flex-end">
      <button type="button" class="btn light" data-staff-remove style="padding:.3rem .55rem;font-size:.8rem">حذف</button>
    </div>
  </div>
</div>

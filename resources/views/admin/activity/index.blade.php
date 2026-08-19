@extends('layouts.admin')
@section('title','لاگ فعالیت')
@section('content')
<h1>لاگ فعالیت</h1>
<div class="card">
  <table>
    <tr><th>زمان</th><th>کاربر</th><th>عمل</th><th>توضیح</th></tr>
    @foreach($logs as $log)
      <tr>
        <td style="white-space:nowrap">{{ jdate($log->created_at, 'Y/m/d H:i') }}</td>
        <td>{{ $log->user?->name ?? '—' }}</td>
        <td dir="ltr"><code>{{ $log->action }}</code></td>
        <td>{{ $log->description }}</td>
      </tr>
    @endforeach
  </table>
  <div style="margin-top:1rem">{!! $logs->links() !!}</div>
</div>
@endsection

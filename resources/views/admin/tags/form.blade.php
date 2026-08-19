@extends('layouts.admin')
@section('title', $tag->exists ? 'ویرایش تگ' : 'تگ جدید')
@section('content')
<h1>{{ $tag->exists ? 'ویرایش تگ' : 'تگ جدید' }}</h1>
<form class="card" method="post" action="{{ $tag->exists ? route('admin.tags.update',$tag) : route('admin.tags.store') }}" enctype="multipart/form-data">
@csrf
@if($tag->exists) @method('PUT') @endif
<label>نام</label>
<input name="name" value="{{ old('name',$tag->name) }}" required>
<label>اسلاگ</label>
<input name="slug" value="{{ old('slug',$tag->slug) }}" dir="ltr" placeholder="auto">
<label>توضیح</label>
<textarea name="description" style="min-height:120px">{{ old('description',$tag->description) }}</textarea>

<label>تصویر کاور</label>
@if($tag->featuredImagePath())
  @php $cov = $tag->featuredImagePath(); $covUrl = str_starts_with($cov,'http') ? $cov : asset($cov); @endphp
  <div style="margin:.4rem 0 .6rem"><img src="{{ $covUrl }}" alt="" style="max-width:220px;border-radius:12px;border:1px solid var(--border)"></div>
@endif
<input type="file" name="image_file" accept="image/*">
<input name="featured_image" value="{{ old('featured_image',$tag->featured_image) }}" dir="ltr" placeholder="یا مسیر media/…">

<div class="row">
  <div><label>Meta title</label><input name="meta_title" value="{{ old('meta_title',$tag->meta_title) }}"></div>
  <div><label>Meta description</label><input name="meta_description" value="{{ old('meta_description',$tag->meta_description) }}"></div>
</div>

<div style="margin-top:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
  <button class="btn" type="submit">ذخیره</button>
  <a class="btn light" href="{{ route('admin.tags.index') }}">بازگشت</a>
</div>
</form>
@if($tag->exists)
<form method="post" action="{{ route('admin.tags.destroy',$tag) }}" onsubmit="return confirm('حذف شود؟')" style="margin-top:.8rem">
@csrf @method('DELETE')
<button class="btn red" type="submit">حذف</button>
</form>
@endif
@endsection

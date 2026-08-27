@extends('layouts.skeleton')

@section('title', trans('assistant.quick_record_title'))

@section('content')
  <main class="assistant-page ph3 ph5-ns pv5">
    <div class="mw8 center">
      <quick-record :enabled="{{ \Safe\json_encode($agentEnabled) }}"></quick-record>
    </div>
  </main>
@endsection

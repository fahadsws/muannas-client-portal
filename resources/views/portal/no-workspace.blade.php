@extends('portal.layout')
@section('title', 'Welcome')
@section('content')
<div class="empty"><div class="eic">{!! \App\Support\Ui::icon('photo') !!}</div><h3>Your workspace is not ready yet</h3><p>Your Muannas Atelier contact has not linked your login to a workspace. Please let them know.</p></div>
@endsection
